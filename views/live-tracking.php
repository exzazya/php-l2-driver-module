<?php
$title = "Live Tracking";

// Page-specific styles - Leaflet CSS
$styles = '<link rel="stylesheet" href="' . asset('vendor/leaflet/leaflet.css') . '">'
        . "\n<link rel=\"stylesheet\" href=\"https://unpkg.com/leaflet/dist/leaflet.css\" media=\"print\" onload=\"this.media='all'\">";

// Page-specific scripts - Leaflet JS and tracking scripts
$leafletTag = '<script src="' . asset('vendor/leaflet/leaflet.js') . '"></script>';
$cdnFallback = '<script>if(!window.L){var s=document.createElement("script");s.src="https://unpkg.com/leaflet/dist/leaflet.js";document.head.appendChild(s);}</script>';
$trackingJs = <<<'JS'
<script>
document.addEventListener("DOMContentLoaded", function() {
  const qs = new URLSearchParams(window.location.search);
  const tripIdParam = qs.get("trip_id");

  // UI refs
  const lastUpdateEl = document.getElementById("lastUpdate");
  const speedEl = document.getElementById("currentSpeed");
  const fromTitleEl = document.getElementById("fromTitle");
  const fromAddrEl = document.getElementById("fromAddress");
  const toTitleEl = document.getElementById("toTitle");
  const toAddrEl = document.getElementById("toAddress");
  const vehicleModelEl = document.getElementById("vehicleModel");
  const vehiclePlateEl = document.getElementById("vehiclePlate");
  const vehicleCapacityEl = document.getElementById("vehicleCapacity");

  // Map
  const map = L.map("map").setView([0, 0], 15);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19, attribution: "© OpenStreetMap" }).addTo(map);
  // Ensure proper sizing when embedded in cards/columns
  setTimeout(() => map.invalidateSize(), 0);
  window.addEventListener('resize', () => { try { map.invalidateSize(); } catch(_){} });
  
  // Custom icons for different marker types
  const driverIcon = L.divIcon({
    className: 'driver-marker',
    html: '<div class="marker-pin driver-pin"><i class="fas fa-car"></i></div>',
    iconSize: [30, 30],
    iconAnchor: [15, 15]
  });
  
  const startIcon = L.divIcon({
    className: 'start-marker',
    html: '<div class="marker-pin start-pin"><i class="fas fa-play"></i></div>',
    iconSize: [25, 25],
    iconAnchor: [12.5, 12.5]
  });
  
  const destIcon = L.divIcon({
    className: 'dest-marker',
    html: '<div class="marker-pin dest-pin"><i class="fas fa-flag-checkered"></i></div>',
    iconSize: [25, 25],
    iconAnchor: [12.5, 12.5]
  });
  
  const driverMarker = L.marker([0,0], {icon: driverIcon}).addTo(map).bindPopup("<b>Driver Location</b><br>Real-time GPS position");
  let startMarker = null, destMarker = null, routeLine = null;
  let trailLine = null; // breadcrumb of uploaded GPS points
  let trailCoords = [];
  let firstTrailRender = true;
  let geoHasFix = false;
  let firstGeoCenter = true;
  let firstViewDone = false;
  let pickedUp = false;

  // State
  let activeTrip = null;
  let pendingPoints = [];
  let lastUploadAt = 0;
  const uploadIntervalMs = 10000;

  init();

  async function init() {
    lastUpdateEl.textContent = "Waiting for data...";
    await loadActiveTrip();
    wireButtons();
    startGeolocation();
    startPollingTrail();
  }

  function onPickUp() {
    if (!activeTrip) return;
    pickedUp = true;
    // Hide start marker
    if (startMarker) { try { map.removeLayer(startMarker); } catch(_){} startMarker = null; }
    // Remove previous route and rebuild from driver to destination
    if (routeLine) { try { map.removeLayer(routeLine); } catch(_){} routeLine = null; }
    if (destMarker) {
      const cur = driverMarker.getLatLng();
      const d = destMarker.getLatLng();
      if (cur && d) {
        drawRoadRoute([cur.lat, cur.lng], [d.lat, d.lng]).catch(() => {
          routeLine = L.polyline([[cur.lat, cur.lng], [d.lat, d.lng]], { color: 'blue', weight: 3, opacity: 0.7 }).addTo(map);
        });
      }
    }
    // Optionally, update UI labels
    const fromTitle = document.getElementById('fromTitle');
    if (fromTitle) fromTitle.textContent = 'Picked Up';
    const fromAddr = document.getElementById('fromAddress');
    if (fromAddr) fromAddr.textContent = new Date().toLocaleString();
    updatePickupButtonState();
    updateCompleteButtonState();
  }

  function onCompleteTrip() {
    if (!activeTrip || !pickedUp) return;
    if (!confirm('Are you sure you want to complete this trip?')) return;
    
    const btn = document.getElementById('completeBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Completing...';
    btn.disabled = true;
    
    // Call API to complete the trip
    completeTrip(activeTrip.trip_id || activeTrip.id)
      .then(() => {
        // Trip completed successfully
        alert('Trip completed successfully!');
        // Redirect back to trip assignment page
        window.location.href = 'index.php?route=trip-assignment';
      })
      .catch(error => {
        console.error('Failed to complete trip:', error);
        alert('Failed to complete trip: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
      });
  }

  async function completeTrip(tripId) {
    const response = await fetch('api/assignments.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        action: 'complete',
        trip_id: tripId
      })
    });
    
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data?.success === false) {
      throw new Error(data?.message || 'Failed to complete trip');
    }
    return data;
  }

  // Draw road-snapped route using OSRM demo server (no API key). Fallback handled by caller.
  async function drawRoadRoute(start, dest) {
    try {
      const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${dest[1]},${dest[0]}?overview=full&geometries=geojson`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      if (!res.ok || !data || !data.routes || !data.routes[0]) throw new Error('No route');
      const coords = data.routes[0].geometry.coordinates; // [lng, lat]
      const latlngs = coords.map(c => [c[1], c[0]]);
      routeLine = L.polyline(latlngs, { color: 'blue', weight: 4, opacity: 0.8 }).addTo(map);
      if (!firstViewDone) {
        map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
        firstViewDone = true;
      }
    } catch (e) {
      console.warn('OSRM route failed', e);
      throw e;
    }
  }

  function wireButtons() {
    document.getElementById("centerMapBtn").addEventListener("click", function() {
      if (driverMarker.getLatLng()) map.panTo(driverMarker.getLatLng(), { animate: true });
    });
    document.getElementById("refreshBtn").addEventListener("click", function() {
      loadActiveTrip();
    });
    const pu = document.getElementById('pickupBtn');
    if (pu) pu.addEventListener('click', onPickUp);
    const cb = document.getElementById('completeBtn');
    if (cb) cb.addEventListener('click', onCompleteTrip);
    updatePickupButtonState();
    updateCompleteButtonState();
  }

  async function loadActiveTrip() {
    try {
      let trip = null;
      const accepted = await fetchAssignments('accepted');
      if (tripIdParam) {
        trip = accepted.find(t => String(t.trip_id || t.id) === String(tripIdParam)) || null;
      } else {
        trip = accepted[0] || null;
      }
      if (!trip) {
        fromTitleEl.textContent = 'No active trip';
        fromAddrEl.textContent = '--';
        toTitleEl.textContent = 'No destination';
        toAddrEl.textContent = '--';
        return;
      }
      activeTrip = trip;
      // If trip is already ongoing, treat as picked up
      const st = String(trip.status || '').toLowerCase();
      pickedUp = (st === 'in_progress' || st === 'en_route');
      updateTripInfo(trip);
      renderRoute(trip);
      updatePickupButtonState();
      updateCompleteButtonState();
    } catch (e) {
      console.error('Failed loading active trip', e);
    }
  }

  function updatePickupButtonState() {
    const pu = document.getElementById('pickupBtn');
    if (!pu) return;
    const hasTrip = !!activeTrip;
    if (!hasTrip || pickedUp) {
      pu.style.display = 'none';
    } else {
      pu.style.display = '';
      pu.disabled = false;
      pu.textContent = 'Pick Up';
    }
  }

  function updateCompleteButtonState() {
    const cb = document.getElementById('completeBtn');
    if (!cb) return;
    const hasTrip = !!activeTrip;
    if (!hasTrip || !pickedUp) {
      cb.style.display = 'none';
    } else {
      cb.style.display = '';
      cb.disabled = false;
    }
  }

  async function fetchAssignments(status) {
    const res = await fetch(`api/assignments.php?status=${encodeURIComponent(status)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!res.ok || data?.success === false) return [];
    return Array.isArray(data?.data?.assignments) ? data.data.assignments : [];
  }

  function updateTripInfo(t) {
    const from = t.start_location || 'Unknown';
    const to = t.destination || 'Unknown';
    fromTitleEl.textContent = from;
    fromAddrEl.textContent = t.pickup_time ? new Date(t.pickup_time).toLocaleString() : '--';
    toTitleEl.textContent = to;
    toAddrEl.textContent = t.dropoff_time ? new Date(t.dropoff_time).toLocaleString() : '--';

    if (vehicleModelEl) vehicleModelEl.textContent = t.vehicle_model || t.vehicle_name || '--';
    if (vehiclePlateEl) vehiclePlateEl.textContent = t.plate_number || '--';
    if (vehicleCapacityEl) vehicleCapacityEl.textContent = t.capacity || '--';
  }

  function renderRoute(t) {
    const slat = parseFloat(t.start_lat || t.startLatitude || t.start_latitude);
    const slng = parseFloat(t.start_lng || t.startLongitude || t.start_longitude);
    const dlat = parseFloat(t.destination_lat || t.dest_lat || t.destinationLatitude);
    const dlng = parseFloat(t.destination_lng || t.dest_lng || t.destinationLongitude);

    // Clear previous
    if (startMarker) { map.removeLayer(startMarker); startMarker = null; }
    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }

    const hasStart = Number.isFinite(slat) && Number.isFinite(slng);
    const hasDest = Number.isFinite(dlat) && Number.isFinite(dlng);

    if (hasStart && !pickedUp) {
      startMarker = L.marker([slat, slng], { 
        icon: startIcon, 
        title: 'Start Location' 
      }).addTo(map).bindPopup(`<b>Start Location</b><br>${activeTrip.start_location || 'Pickup Point'}`);
    }
    if (hasDest) {
      destMarker = L.marker([dlat, dlng], { 
        icon: destIcon, 
        title: 'Destination' 
      }).addTo(map).bindPopup(`<b>Destination</b><br>${activeTrip.destination || 'Drop-off Point'}`);
    }
    if (!pickedUp && hasStart && hasDest) {
      // Try road-snapped route via OSRM; fallback to straight line if it fails
      drawRoadRoute([slat, slng], [dlat, dlng])
        .catch(() => {
          routeLine = L.polyline([[slat, slng], [dlat, dlng]], { color: 'blue', weight: 3, opacity: 0.7 }).addTo(map);
          if (!firstViewDone) {
            map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
            firstViewDone = true;
          }
        });
    } else if (!pickedUp && hasStart && !firstViewDone) {
      // Center on start if only start is known
      map.setView([slat, slng], 16);
      firstViewDone = true;
    } else if (pickedUp && hasDest) {
      // If picked up, route from current driver location to destination
      const cur = driverMarker.getLatLng();
      if (cur && Number.isFinite(cur.lat) && Number.isFinite(cur.lng)) {
        drawRoadRoute([cur.lat, cur.lng], [dlat, dlng])
          .catch(() => {
            routeLine = L.polyline([[cur.lat, cur.lng], [dlat, dlng]], { color: 'blue', weight: 3, opacity: 0.7 }).addTo(map);
            if (!firstViewDone) {
              map.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
              firstViewDone = true;
            }
          });
      }
    }
  }

  function startGeolocation() {
    if (!navigator.geolocation) {
      alert('Geolocation is not supported by your browser.');
      return;
    }
    navigator.geolocation.watchPosition(onPosition, onGeoError, {
      enableHighAccuracy: true,
      maximumAge: 2000,
      timeout: 8000
    });
  }

  function onPosition(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const speedKmh = position.coords.speed ? (position.coords.speed * 3.6) : 0;
    const heading = position.coords.heading ?? null;
    const accuracy = position.coords.accuracy ?? null;
    const recorded_at = new Date().toISOString();

    driverMarker.setLatLng([lat, lng]);
    geoHasFix = true;
    // Only center on geolocation if we haven't shown a route-centered view yet
    if (firstGeoCenter && !firstViewDone) {
      map.setView([lat, lng], 17);
      firstGeoCenter = false;
      firstViewDone = true;
    }

    // If already picked up and we have a destination, refresh route from current position
    if (pickedUp && destMarker) {
      if (routeLine) { try { map.removeLayer(routeLine); } catch(_){} routeLine = null; }
      const d = destMarker.getLatLng();
      drawRoadRoute([lat, lng], [d.lat, d.lng]).catch(() => {
        routeLine = L.polyline([[lat, lng], [d.lat, d.lng]], { color: 'blue', weight: 3, opacity: 0.7 }).addTo(map);
      });
    }
    if (speedEl) speedEl.textContent = `${(speedKmh || 0).toFixed(1)} km/h`;
    if (lastUpdateEl) lastUpdateEl.textContent = 'Just updated';

    // Queue point
    if (activeTrip && (activeTrip.trip_id || activeTrip.id)) {
      // Use 'recorded_at' to match API contract
      pendingPoints.push({ lat, lng, speed: speedKmh, heading, accuracy, recorded_at });
      maybeUpload();
    }
  }

  function onGeoError(error) {
    console.error('Geolocation error:', error);
    alert('Unable to fetch your location. Make sure location is enabled in your browser.');
  }

  async function maybeUpload() {
    const now = Date.now();
    if (now - lastUploadAt < uploadIntervalMs) return;
    if (!pendingPoints.length) return;
    lastUploadAt = now;
    const points = pendingPoints.slice();
    pendingPoints = [];
    try {
      const tripId = activeTrip.trip_id || activeTrip.id;
      const res = await fetch('api/locations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ trip_id: tripId, points })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.success === false) {
        console.warn('Upload failed', data?.message);
      }
    } catch (e) {
      console.error('Upload error', e);
    }
  }

  // Polling for recent GPS points and render breadcrumb trail
  let trailTimer = null;
  let allLocationsMarkers = []; // Store all location markers for database view

  function startPollingTrail() {
    if (trailTimer) clearInterval(trailTimer);
    pollTrailOnce();
    trailTimer = setInterval(pollTrailOnce, 3000); // Poll every 3 seconds for more responsive updates
  }
  
  function toggleDatabaseLocations() {
    const showAll = document.getElementById('showAllLocations').checked;
    allLocationsMarkers.forEach(marker => {
      if (showAll) {
        map.addLayer(marker);
      } else {
        map.removeLayer(marker);
      }
    });
  }

  async function pollTrailOnce() {
    if (!activeTrip || !(activeTrip.trip_id || activeTrip.id)) return;
    const tripId = activeTrip.trip_id || activeTrip.id;
    try {
      const res = await fetch(`api/locations.php?trip_id=${encodeURIComponent(tripId)}&limit=500`, { credentials: 'same-origin' });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.success === false) return;
      const locs = Array.isArray(data?.data?.locations) ? data.data.locations : [];
      if (!locs.length) return;

      // Sort ascending by captured_at then id for a forward path
      locs.sort((a,b) => {
        const ta = new Date(a.captured_at).getTime() || 0;
        const tb = new Date(b.captured_at).getTime() || 0;
        if (ta !== tb) return ta - tb;
        return (a.id || 0) - (b.id || 0);
      });

      trailCoords = locs
        .map(r => [parseFloat(r.lat), parseFloat(r.lng)])
        .filter(p => Number.isFinite(p[0]) && Number.isFinite(p[1]));
      if (!trailCoords.length) return;

      if (!trailLine) {
        trailLine = L.polyline(trailCoords, { color: '#ff5722', weight: 4, opacity: 0.85 }).addTo(map);
      } else {
        trailLine.setLatLngs(trailCoords);
      }
      
      // Clear previous location markers
      allLocationsMarkers.forEach(marker => map.removeLayer(marker));
      allLocationsMarkers = [];
      
      // Create markers for database locations (hidden by default)
      locs.forEach((loc, index) => {
        const lat = parseFloat(loc.lat);
        const lng = parseFloat(loc.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        
        const locationIcon = L.divIcon({
          className: 'location-marker',
          html: `<div class="marker-pin location-pin"><span>${index + 1}</span></div>`,
          iconSize: [20, 20],
          iconAnchor: [10, 10]
        });
        
        const marker = L.marker([lat, lng], {icon: locationIcon})
          .bindPopup(`
            <b>Location ${index + 1}</b><br>
            Speed: ${loc.speed ? (parseFloat(loc.speed).toFixed(1) + ' km/h') : 'N/A'}<br>
            Time: ${new Date(loc.captured_at).toLocaleString()}<br>
            Accuracy: ${loc.accuracy ? (parseFloat(loc.accuracy).toFixed(1) + 'm') : 'N/A'}
          `);
        
        allLocationsMarkers.push(marker);
        
        // Only show if toggle is enabled
        if (document.getElementById('showAllLocations')?.checked) {
          map.addLayer(marker);
        }
      });

      const last = trailCoords[trailCoords.length - 1];
      if (last) {
        if (firstTrailRender && !firstViewDone) {
          map.fitBounds(trailLine.getBounds(), { padding: [30, 30] });
          firstViewDone = true;
        }
        const cur = driverMarker.getLatLng();
        if (!cur || (!cur.lat && !cur.lng)) {
          driverMarker.setLatLng(last);
        }
        if (lastUpdateEl) {
          const lastCap = locs[locs.length - 1]?.captured_at;
          if (lastCap) {
            const dt = new Date(lastCap);
            lastUpdateEl.textContent = `Updated ${isNaN(dt) ? 'recently' : timeAgo(dt)}`;
          } else {
            lastUpdateEl.textContent = 'Updated just now';
          }
        }

        // Update distance traveled (km)
        const distEl = document.getElementById('distanceTraveled');
        if (distEl) {
          const km = computePathDistanceKm(trailCoords);
          distEl.textContent = `${km.toFixed(2)} km`;
        }
      }
      firstTrailRender = false;
    } catch (e) {
      console.warn('Trail polling error', e);
    }
  }

  function computePathDistanceKm(coords) {
    let d = 0;
    for (let i = 1; i < coords.length; i++) {
      d += haversineKm(coords[i - 1], coords[i]);
    }
    return d;
  }

  function haversineKm(a, b) {
    const R = 6371; // km
    const toRad = x => x * Math.PI / 180;
    const dLat = toRad(b[0] - a[0]);
    const dLng = toRad(b[1] - a[1]);
    const lat1 = toRad(a[0]);
    const lat2 = toRad(b[0]);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  }

  function timeAgo(d) {
    const seconds = Math.floor((Date.now() - d.getTime()) / 1000);
    if (seconds < 5) return 'just now';
    const units = [
      ['day', 86400],
      ['hour', 3600],
      ['minute', 60],
      ['second', 1],
    ];
    for (const [name, s] of units) {
      if (seconds >= s) {
        const val = Math.floor(seconds / s);
        return `${val} ${name}${val !== 1 ? 's' : ''} ago`;
      }
    }
    return 'just now';
  }

});
</script>
JS;
$scripts = $leafletTag . "\n" . $cdnFallback . "\n" . $trackingJs;

// Start capturing content
ob_start();
?>
<div class="page-header-container mb-4">
    <div class="d-flex justify-content-between align-items-center page-header">
        <div class="d-flex align-items-center">
            <div class="dashboard-logo me-3">
                <img src="<?php echo asset('img/jetlouge_logo.png'); ?>" alt="Jetlouge Travels" class="logo-img">
            </div>
            <div>
                <h2 class="fw-bold mb-1">Live Tracking</h2>
                <p class="text-muted mb-0">Real-time driver location and route monitoring</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning btn-sm" id="pickupBtn">
                <i class="fas fa-user-check me-1"></i>Pick Up
            </button>
            <button class="btn btn-success btn-sm" id="completeBtn" style="display: none;">
                <i class="fas fa-flag-checkered me-1"></i>Complete Trip
            </button>
            <button class="btn btn-outline-primary btn-sm" id="centerMapBtn">
                <i class="fas fa-crosshairs me-1"></i>Center Map
            </button>
            <button class="btn btn-outline-success btn-sm" id="refreshBtn">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
    </div>
</div>

<!-- Driver Status Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <div class="status-indicator bg-success me-2"></div>
                    <h6 class="mb-0 text-success">Online</h6>
                </div>
                <small class="text-muted">Driver Status</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h5 class="mb-1 text-primary" id="currentSpeed">-- km/h</h5>
                <small class="text-muted">Current Speed</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h5 class="mb-1 text-info" id="distanceTraveled">-- km</h5>
                <small class="text-muted">Distance Traveled</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h5 class="mb-1 text-warning" id="eta">-- min</h5>
                <small class="text-muted">ETA to Destination</small>
            </div>
        </div>
    </div>
</div>

<!-- Map and Trip Info Row -->
<div class="row">
    <div class="col-lg-8">
        <!-- Enhanced Map Card -->
        <div class="card main-card mb-4" id="mapCard">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">
                        <i class="fas fa-map-marked-alt me-2 text-primary"></i>
                        Live Location
                    </h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">
                            <i class="fas fa-circle pulse-animation me-1"></i>Live
                        </span>
                        <small class="text-muted" id="lastUpdate">Updated 2 seconds ago</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0" style="height: 500px; position: relative;">
                <div id="map" style="height: 100%; width: 100%;"></div>
                <div class="map-controls">
                    <label>
                        <input type="checkbox" id="showAllLocations" onchange="toggleDatabaseLocations()">
                        Show DB Locations
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Trip Details Card -->
        <div class="card main-card mb-4">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-route me-2 text-success"></i>
                    Trip Details
                </h5>
            </div>
            <div class="card-body">
                <div class="trip-info">
                    <div class="info-item mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-play-circle text-muted me-2"></i>
                            <small class="text-muted">FROM</small>
                        </div>
                        <div id="fromTitle" class="fw-bold text-muted">No active trip</div>
                        <small id="fromAddress" class="text-muted">--</small>
                    </div>

                    <div class="route-line"></div>

                    <div class="info-item mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-map-pin text-muted me-2"></i>
                            <small class="text-muted">TO</small>
                        </div>
                        <div id="toTitle" class="fw-bold text-muted">No destination</div>
                        <small id="toAddress" class="text-muted">--</small>
                    </div>
                </div>

                <hr>

                <div class="vehicle-info">
                    <h6 class="mb-2">
                        <i class="fas fa-car me-2 text-info"></i>Vehicle Info
                    </h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Model:</span>
                        <span id="vehicleModel" class="fw-bold text-muted">--</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Plate:</span>
                        <span id="vehiclePlate" class="fw-bold text-muted">--</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Capacity:</span>
                        <span id="vehicleCapacity" class="fw-bold text-muted">--</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="card main-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-history me-2 text-warning"></i>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-timeline" id="activityTimeline">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-2x mb-2"></i><br>
                        <div>No recent activity</div>
                        <small>Activity will appear here when trips are active</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}





.route-line {
    height: 30px;
    width: 2px;
    background: linear-gradient(to bottom, #28a745, #dc3545);
    margin: 0 auto;
    position: relative;
}

.route-line::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: #ffc107;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.activity-timeline {
    max-height: 200px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.activity-time {
    font-size: 0.75rem;
    color: #6c757d;
    min-width: 60px;
    margin-right: 12px;
    font-weight: 500;
}

.activity-desc {
    font-size: 0.85rem;
    line-height: 1.4;
}

.info-item {
    text-align: center;
}

.vehicle-info {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    font-size: 0.9rem;
}

/* Custom marker styles */
.marker-pin {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    border: 2px solid white;
}

.driver-pin {
    background: linear-gradient(135deg, #007bff, #0056b3);
    animation: pulse-driver 2s infinite;
}

.start-pin {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.dest-pin {
    background: linear-gradient(135deg, #dc3545, #bd2130);
}

.location-pin {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #000;
    font-size: 10px;
    width: 18px;
    height: 18px;
}

@keyframes pulse-driver {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.map-controls {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1000;
    background: white;
    padding: 8px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.map-controls label {
    font-size: 12px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 5px;
}


</style>
<?php
$content = ob_get_clean();

// Include the layout
include __DIR__ . '/layouts/app.php';
?>