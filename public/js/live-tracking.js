(function(){
  if (window.__liveTrackingInit) return;
  window.__liveTrackingInit = true;

  const CONFIG = window.LIVE_TRACKING_CONFIG || {};
  const tripIdParam = Number(CONFIG.tripId || 0) || 0;

  const els = {
    map: null,
    statusText: null,
    speedText: null,
    lastUpdateText: null,
    startLabel: null,
    destLabel: null,
    completeBtn: null,
    pickupBtn: null,
    pickupBadge: null,
    noTrip: null,
  };

  // --- Trip coordinate helpers ---
  function firstDefined(obj, keys) {
    for (const k of keys) {
      if (obj && obj[k] != null && obj[k] !== '') return obj[k];
    }
    return null;
  }
  function parseCoord(v) {
    const n = parseFloat(v);
    return (isFinite(n) ? n : null);
  }
  function getTripStartLatLng(trip) {
    const lat = parseCoord(firstDefined(trip, ['start_lat','pickup_lat','pick_up_lat','startLatitude','pickupLatitude','start_latitude']));
    const lng = parseCoord(firstDefined(trip, ['start_lng','pickup_lng','pick_up_lng','startLongitude','pickupLongitude','start_longitude']));
    return (lat != null && lng != null) ? [lat, lng] : null;
  }
  function getTripDestLatLng(trip) {
    const lat = parseCoord(firstDefined(trip, ['destination_lat','dest_lat','end_lat','destinationLatitude','destLatitude','destination_latitude']));
    const lng = parseCoord(firstDefined(trip, ['destination_lng','dest_lng','end_lng','destinationLongitude','destLongitude','destination_longitude']));
    return (lat != null && lng != null) ? [lat, lng] : null;
  }

  const state = {
    tripId: tripIdParam,
    trip: null, // from assignments GET (includes trips.*)
    isPickedUp: false,
    assignmentAccepted: false,
    driverPos: null, // [lat,lng]
    map: null,
    routeGroup: null, // layer group to hold all route polylines for easy clearing
    layers: {
      driver: null,
      start: null,
      dest: null,
      driverToStart: null,   // fallback straight lines
      startToDest: null,     // fallback straight lines
      driverToDest: null,    // fallback straight lines
      routedDriverToStart: null,
      routedStartToDest: null,
      routedDriverToDest: null,
    },
    uploadBuffer: [],
    lastUploadAt: 0,
    uploading: false,
    watchId: null,
    fittedOnce: false,
    routingService: null,
    lastRouteAt: 0,
    lastRouteDriverLatLng: null,
    pickupProcessing: false,
    assignPollId: null,
  };

  const fmt = {
    kmh: (ms) => {
      if (ms == null || isNaN(ms)) return '—';
      const kmh = Math.max(0, (ms * 3.6));
      return `${kmh.toFixed(0)} km/h`;
    },
    time: (d) => d ? new Date(d).toLocaleTimeString() : '—',
    coordOk: (v) => typeof v === 'number' && isFinite(v),
  };

  function qs(id){ return document.getElementById(id); }

  function initDomRefs(){
    els.statusText = qs('trackingStatus');
    els.speedText = qs('trackingSpeed');
    els.lastUpdateText = qs('trackingLastUpdate');
    els.startLabel = qs('startLabel');
    els.destLabel = qs('destLabel');
    els.completeBtn = qs('completeTripBtn');
    els.pickupBtn = qs('pickupTripBtn');
    els.pickupBadge = qs('pickedUpBadge');
    els.noTrip = qs('noActiveTrip');
  }

  function setStatus(txt){ if (els.statusText) els.statusText.textContent = txt; }
  function setSpeed(ms){ if (els.speedText) els.speedText.textContent = fmt.kmh(ms); }
  function setLastUpdate(d){ if (els.lastUpdateText) els.lastUpdateText.textContent = fmt.time(d); }

  function assetUrl(path){ return (window.assetUrl ? window.assetUrl(path) : path); }
  function publicUrl(path){ return (window.publicUrl ? window.publicUrl(path) : path); }

  // Local storage helpers for pickup persistence across reloads
  function lsKeyPicked(id){ return 'lt_pick_' + String(id || ''); }
  function isLocalPicked(id){ try { return localStorage.getItem(lsKeyPicked(id)) === '1'; } catch(_) { return false; } }
  function markLocalPicked(id){ try { localStorage.setItem(lsKeyPicked(id), '1'); } catch(_) {} }
  function clearLocalPicked(id){ try { localStorage.removeItem(lsKeyPicked(id)); } catch(_) {} }
  function setLastPickedTripId(id){ try { localStorage.setItem('lt_last_picked_trip_id', String(id)); } catch(_) {} }
  function getLastPickedTripId(){ try { const v = localStorage.getItem('lt_last_picked_trip_id'); const n = parseInt(v,10); return isNaN(n)?0:n; } catch(_) { return 0; } }
  function clearLastPickedTripId(){ try { localStorage.removeItem('lt_last_picked_trip_id'); } catch(_) {} }

  async function fetchJson(url, opts){
    try {
      const res = await fetch(url, Object.assign({ credentials: 'same-origin' }, opts));
      const data = await res.json().catch(() => null);
      return { ok: res.ok, data };
    } catch (e) {
      return { ok: false, data: null };
    }
  }

  async function fetchAssignments(status){
    const q = status ? ('?status=' + encodeURIComponent(status)) : '';
    const { ok, data } = await fetchJson(publicUrl('api/assignments.php' + q));
    if (!ok || !data || data.success === false) return [];
    const items = Array.isArray(data?.data?.assignments) ? data.data.assignments : [];
    return items;
  }

  async function fetchAssignmentByTripId(tripId){
    const { ok, data } = await fetchJson(publicUrl('api/assignments.php?trip_id=' + encodeURIComponent(tripId)));
    if (!ok || !data || data.success === false) return null;
    const items = Array.isArray(data?.data?.assignments) ? data.data.assignments : [];
    return items.length > 0 ? items[0] : null;
  }

  async function loadTrip() {
    // If a specific tripId is provided, try to load it directly first
    let tr = null;
    if (state.tripId) {
      tr = await fetchAssignmentByTripId(state.tripId);
    }

    // Try accepted first (includes in_progress), then pending (fallback when not found by id)
    let accepted = await fetchAssignments('accepted');
    let pending = [];
    if (!accepted || accepted.length === 0) {
      pending = await fetchAssignments('pending');
    }

    let all = (accepted || []).concat(pending || []);
    if (!Array.isArray(all)) all = [];

    // If not found by direct fetch, prefer explicit tripId among loaded arrays
    if (!tr && state.tripId) {
      tr = all.find(a => Number(a.trip_id || a.id) === Number(state.tripId)) || null;
    }
    // Otherwise, if we remember a last picked trip locally, prefer it
    if (!tr) {
      const lastId = getLastPickedTripId();
      if (lastId > 0) {
        tr = all.find(a => Number(a.trip_id || a.id) === lastId) || tr;
      }
    }
    // Otherwise prefer an in-progress/ongoing (picked) accepted assignment
    const isPicked = (row) => {
      const s = String(row?.status || '').toLowerCase();
      return s === 'in_progress' || s === 'ongoing' || !!row?.pickup_at;
    };
    if (!tr) tr = (accepted.find(isPicked) || null);
    // Fallback to first accepted, then first pending
    if (!tr) tr = accepted[0] || pending[0] || null;

    if (!tr) {
      showNoTrip('No active trip. Accept an assignment to start tracking.');
      console.debug('[LiveTracking] No assignments found. Accepted:', accepted.length, 'Pending:', pending.length);
      return;
    }

    state.tripId = Number(tr.trip_id || tr.id); // normalize
    state.trip = tr;
    const statusStr = String(tr.status || tr.trip_status || '').toLowerCase();
    const picked = (
      statusStr === 'in_progress' ||
      statusStr === 'ongoing' ||
      statusStr === 'picked_up' ||
      !!tr.pickup_at || !!tr.picked_up_at || !!tr.pickupTime
    ) || isLocalPicked(state.tripId);
    state.isPickedUp = picked;
    const acceptedFlag = (Number(tr.is_accepted || 0) === 1) || statusStr === 'accepted' || picked;
    state.assignmentAccepted = !!acceptedFlag;

    // Fill labels
    if (els.startLabel) els.startLabel.textContent = String(tr.start_location || tr.pickup_address || 'Start');
    if (els.destLabel) els.destLabel.textContent = String(tr.destination || tr.destination_location || tr.destination_address || 'Destination');
    if (els.pickupBadge) els.pickupBadge.classList.toggle('d-none', !state.isPickedUp);
    if (els.pickupBtn) {
      els.pickupBtn.classList.toggle('d-none', state.isPickedUp);
      // Disable pickup until assignment is accepted
      els.pickupBtn.disabled = !acceptedFlag;
    }
    if (!acceptedFlag) {
      showNoTrip('Pending assignment detected. Accept it to start tracking.');
    } else {
      if (els.noTrip) els.noTrip.classList.add('d-none');
      const mapEl = document.getElementById('map');
      if (mapEl) mapEl.classList.remove('d-none');
    }
  }

  function startAssignmentPoll(){
    if (!state.tripId || state.assignPollId) return;
    state.assignPollId = setInterval(async () => {
      try {
        const item = await fetchAssignmentByTripId(state.tripId);
        if (!item) return;
        const prevPicked = !!state.isPickedUp;
        const statusStr = String(item.status || item.trip_status || '').toLowerCase();
        const picked = (
          statusStr === 'in_progress' || statusStr === 'ongoing' || statusStr === 'picked_up' ||
          !!item.pickup_at || !!item.picked_up_at || !!item.pickupTime || isLocalPicked(state.tripId)
        );
        state.isPickedUp = picked;
        if (els.pickupBadge) els.pickupBadge.classList.toggle('d-none', !picked);
        if (els.pickupBtn) els.pickupBtn.classList.toggle('d-none', picked);
        if (picked && state.layers.start) { try { state.map.removeLayer(state.layers.start); } catch (e) {} state.layers.start = null; }
        if (picked !== prevPicked && state.map) {
          purgeRouteVisuals();
          try { await updateRoutedPaths(); } catch(_) { renderRoute(); }
        }
      } catch (_) {}
    }, 10000);
  }

  function initMap(){
    const mapEl = document.getElementById('map');
    if (mapEl) mapEl.classList.remove('d-none');
    const map = L.map('map', { zoomControl: true });
    state.map = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Group for all route lines to ensure we can clear them in one call
    state.routeGroup = L.layerGroup().addTo(map);

    // If we already have trip coords, add markers
    placeTripMarkers();
  }

  function placeTripMarkers(){
    if (!state.map || !state.trip) return;
    const startLL = getTripStartLatLng(state.trip);
    const destLL = getTripDestLatLng(state.trip);

    if (!state.isPickedUp && startLL && fmt.coordOk(startLL[0]) && fmt.coordOk(startLL[1])) {
      if (!state.layers.start) {
        state.layers.start = L.marker(startLL, { title: 'Start' }).addTo(state.map);
        state.layers.start.bindPopup('Start');
      } else {
        state.layers.start.setLatLng(startLL);
      }
    } else {
      if (state.layers.start) {
        try { state.map.removeLayer(state.layers.start); } catch (e) {}
        state.layers.start = null;
      }
    }
    if (destLL && fmt.coordOk(destLL[0]) && fmt.coordOk(destLL[1])) {
      if (!state.layers.dest) {
        state.layers.dest = L.marker(destLL, { title: 'Destination' }).addTo(state.map);
        state.layers.dest.bindPopup('Destination');
      } else {
        state.layers.dest.setLatLng(destLL);
      }
    }

    // Fit to start/dest first time
    if (!state.fittedOnce) {
      const bounds = [];
      if (state.layers.start) bounds.push(state.layers.start.getLatLng());
      if (state.layers.dest) bounds.push(state.layers.dest.getLatLng());
      if (bounds.length) {
        try { state.map.fitBounds(bounds, { padding: [30, 30] }); } catch (e) {}
      }
    }
  }

  // --- Routing helpers (Leaflet Routing Machine) ---
  function hasRouting(){ return !!(window.L && L.Routing && L.Routing.osrmv1); }

  function metersBetween(a, b){
    if (!a || !b) return Infinity;
    const R = 6371000;
    const toRad = (x) => x * Math.PI / 180;
    const dLat = toRad(b.lat - a.lat);
    const dLng = toRad(b.lng - a.lng);
    const lat1 = toRad(a.lat), lat2 = toRad(b.lat);
    const sinDLat = Math.sin(dLat/2), sinDLng = Math.sin(dLng/2);
    const c = 2 * Math.asin(Math.sqrt(sinDLat*sinDLat + Math.cos(lat1)*Math.cos(lat2)*sinDLng*sinDLng));
    return R * c;
  }

  function clearRoutingLines(){
    ['routedDriverToStart','routedStartToDest','routedDriverToDest'].forEach(key => {
      if (state.layers[key]) {
        try { state.map.removeControl(state.layers[key]); } catch (e) {}
        try { state.map.removeLayer(state.layers[key]); } catch (e) {}
        state.layers[key] = null;
      }
    });
    if (state.routeGroup && typeof state.routeGroup.clearLayers === 'function') {
      try { state.routeGroup.clearLayers(); } catch(_) {}
    }
  }

  function purgeRouteVisuals(){
    // Clear tracked route layers and any stray polylines on the map
    clearRoutingLines();
    clearPolyline('driverToStart');
    clearPolyline('startToDest');
    clearPolyline('driverToDest');
    if (!state.map || !window.L) return;
    state.map.eachLayer((layer) => {
      try {
        if (layer instanceof L.Polyline) {
          // keep driver's circle marker (CircleMarker is not a Polyline), and markers are not polylines
          state.map.removeLayer(layer);
        }
      } catch(_) {}
    });
  }

  function fadeOutLayer(key, done){
    const layer = state.layers[key];
    if (!layer) { if (done) done(); return; }
    let opacity = 1.0;
    const step = 0.2;
    const timer = setInterval(() => {
      try {
        opacity = Math.max(0, opacity - step);
        if (typeof layer.setStyle === 'function') {
          layer.setStyle({ opacity, fillOpacity: opacity });
        }
        if (opacity <= 0) {
          clearInterval(timer);
          try { state.map.removeLayer(layer); } catch (e) {}
          state.layers[key] = null;
          if (done) done();
        }
      } catch (e) {
        clearInterval(timer);
        try { state.map.removeLayer(layer); } catch (e2) {}
        state.layers[key] = null;
        if (done) done();
      }
    }, 80);
  }

  function drawRoutedLine(route, styles){
    // Use L.Routing.line to create a styled polyline from a computed route
    const line = L.Routing.line(route, {
      addWaypoints: false,
      extendToWaypoints: false,
      routeWhileDragging: false,
      styles: styles || [{ color: '#1e90ff', weight: 5 }]
    });
    if (state.routeGroup) {
      line.addTo(state.routeGroup);
    } else {
      line.addTo(state.map);
    }
    return line;
  }

  function requestRoute(waypoints){
    const hosts = [
      'https://router.project-osrm.org/route/v1',
      'https://routing.openstreetmap.de/routed-car/route/v1'
    ];
    const wps = waypoints.map(ll => L.Routing.waypoint(L.latLng(ll.lat, ll.lng)));
    return new Promise(async (resolve, reject) => {
      let lastErr = null;
      for (let i = 0; i < hosts.length; i++) {
        try {
          // create a fresh router per host to avoid caching failures
          const router = L.Routing.osrmv1({ serviceUrl: hosts[i] });
          await new Promise((res, rej) => {
            router.route(wps, (err, routes) => {
              if (err || !routes || !routes.length) return rej(err || new Error('no route'));
              // success
              resolve(routes[0]);
              res();
            });
          });
          return; // resolved
        } catch (e) {
          lastErr = e;
          continue;
        }
      }
      reject(lastErr || new Error('routing failed for all hosts'));
    });
  }

  async function updateRoutedPaths(){
    if (!hasRouting() || !state.map) return false;
    const now = Date.now();
    const driver = state.driverPos ? { lat: state.driverPos[0], lng: state.driverPos[1] } : null;
    const start = (state.layers.start ? state.layers.start.getLatLng() : null) || (function(){ const s=getTripStartLatLng(state.trip); return s?L.latLng(s[0],s[1]):null; })();
    const dest = (state.layers.dest ? state.layers.dest.getLatLng() : null) || (function(){ const d=getTripDestLatLng(state.trip); return d?L.latLng(d[0],d[1]):null; })();
    if (!driver || !dest) return false;

    // throttle: update if 10s passed or moved > 30m
    const last = state.lastRouteDriverLatLng;
    const moved = last ? metersBetween(last, driver) : Infinity;
    if ((now - state.lastRouteAt) < 10000 && moved < 30) return true;

    clearRoutingLines();
    try {
      if (!state.isPickedUp && start) {
        const r1 = await requestRoute([ driver, { lat: start.lat, lng: start.lng } ]);
        state.layers.routedDriverToStart = drawRoutedLine(r1, [{ color: '#1e90ff', weight: 5 }]);
      } else {
        const r3 = await requestRoute([ driver, { lat: dest.lat, lng: dest.lng } ]);
        state.layers.routedDriverToDest = drawRoutedLine(r3, [{ color: '#2ecc71', weight: 6 }]);
      }
      state.lastRouteAt = now;
      state.lastRouteDriverLatLng = driver;
      const rm = document.getElementById('routingMode');
      if (rm) { rm.textContent = 'Roads'; rm.classList.remove('text-danger'); rm.classList.add('text-success'); }
      return true;
    } catch (e) {
      // fall back to straight polylines if routing fails
      const rm = document.getElementById('routingMode');
      if (rm) { rm.textContent = 'Straight (fallback)'; rm.classList.remove('text-success'); rm.classList.add('text-danger'); }
      return false;
    }
  }

  function updateDriverMarker(lat, lng, extra){
    const latlng = [lat, lng];
    if (!state.layers.driver) {
      state.layers.driver = L.circleMarker(latlng, {
        radius: 7,
        color: '#1971c2',
        fillColor: '#228be6',
        fillOpacity: 0.9,
        weight: 2,
      }).addTo(state.map);
      state.layers.driver.bindPopup('You');
    } else {
      state.layers.driver.setLatLng(latlng);
    }

    // First-fit include driver also
    if (!state.fittedOnce) {
      const bounds = [];
      if (state.layers.start) bounds.push(state.layers.start.getLatLng());
      if (state.layers.dest) bounds.push(state.layers.dest.getLatLng());
      bounds.push(L.latLng(lat, lng));
      try { state.map.fitBounds(bounds, { padding: [30, 30] }); } catch (e) {}
      state.fittedOnce = true;
    }
  }

  function clearPolyline(key){
    if (state.layers[key]) {
      try { state.map.removeLayer(state.layers[key]); } catch (e) {}
      state.layers[key] = null;
    }
  }

  function renderRoute(){
    if (!state.map) return;
    const driver = state.driverPos ? L.latLng(state.driverPos[0], state.driverPos[1]) : null;
    const start = (state.layers.start ? state.layers.start.getLatLng() : null) || (function(){ const s=getTripStartLatLng(state.trip); return s?L.latLng(s[0],s[1]):null; })();
    const dest = (state.layers.dest ? state.layers.dest.getLatLng() : null) || (function(){ const d=getTripDestLatLng(state.trip); return d?L.latLng(d[0],d[1]):null; })();

    // Try routing first
    if (hasRouting()) {
      updateRoutedPaths().then((usedRouting) => {
        if (usedRouting) return;
        // fallback straight lines if routing failed
        drawStraight();
      }).catch(drawStraight);
      return;
    }
    // no routing available -> straight
    drawStraight();

    function drawStraight(){
      // Remove previous straight polylines
      clearPolyline('driverToStart');
      clearPolyline('startToDest');
      clearPolyline('driverToDest');
      if (!driver || !dest) return;
      if (!state.isPickedUp && start) {
        const pl = L.polyline([driver, start], { color: '#1e90ff', weight: 5 });
        (state.routeGroup || state.map).addLayer(pl);
        state.layers.driverToStart = pl;
        if (state.layers.start) { state.layers.start.addTo(state.map); }
      } else {
        const pl = L.polyline([driver, dest], { color: '#2ecc71', weight: 6 });
        (state.routeGroup || state.map).addLayer(pl);
        state.layers.driverToDest = pl;
        if (state.layers.start) { state.layers.start.addTo(state.map); }
      }
    }
  }

  function pushUploadPoint(lat, lng, speed, heading, accuracy){
    state.uploadBuffer.push({ lat, lng, speed, heading, accuracy, recorded_at: new Date().toISOString() });
    const now = Date.now();
    if (state.uploadBuffer.length >= 3 || (now - state.lastUploadAt) > 5000) {
      uploadLocations().catch(()=>{});
    }
  }

  async function uploadLocations(){
    if (state.uploading || !state.tripId || state.uploadBuffer.length === 0) return;
    const payload = state.uploadBuffer.splice(0, state.uploadBuffer.length);
    state.uploading = true;
    try {
      const { ok } = await fetchJson(publicUrl('api/locations.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ trip_id: state.tripId, points: payload })
      });
      state.lastUploadAt = Date.now();
      if (!ok) {
        // Put back if failed
        state.uploadBuffer.unshift.apply(state.uploadBuffer, payload);
      }
    } finally {
      state.uploading = false;
    }
  }

  function onPosition(pos){
    const c = pos.coords || {};
    const lat = c.latitude, lng = c.longitude;
    if (!fmt.coordOk(lat) || !fmt.coordOk(lng)) return;
    state.driverPos = [lat, lng];
    updateDriverMarker(lat, lng, c);
    renderRoute();
    setSpeed(c.speed);
    setLastUpdate(Date.now());
    setStatus('Tracking');
    pushUploadPoint(lat, lng, c.speed, c.heading, c.accuracy);
  }

  function onGeoError(err){
    setStatus('Location error: ' + (err && err.message ? err.message : 'Unknown'));
  }

  function startWatch(){
    if (!('geolocation' in navigator)) {
      setStatus('Geolocation not supported on this device');
      return;
    }
    state.watchId = navigator.geolocation.watchPosition(onPosition, onGeoError, {
      enableHighAccuracy: true,
      timeout: 15000,
      maximumAge: 5000,
    });
    setStatus('Waiting for GPS...');
  }

  function stopWatch(){
    if (state.watchId && navigator.geolocation && navigator.geolocation.clearWatch) {
      try { navigator.geolocation.clearWatch(state.watchId); } catch (e) {}
      state.watchId = null;
    }
  }

  function showNoTrip(msg){
    if (els.noTrip) {
      els.noTrip.classList.remove('d-none');
      els.noTrip.textContent = msg || 'No active trip';
    }
    const container = document.getElementById('map');
    if (container) container.classList.add('d-none');
    if (els.completeBtn) els.completeBtn.disabled = true;
  }

  async function completeTrip(){
    if (!state.tripId || !els.completeBtn) return;
    const go = confirm('Mark trip as completed?');
    if (!go) return;
    const btn = els.completeBtn;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Completing...';
    try {
      const { ok, data } = await fetchJson(publicUrl('api/assignments.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ action: 'complete', trip_id: state.tripId })
      });
      if (!ok || !data || data.success === false) {
        alert((data && data.message) ? data.message : 'Failed to complete trip');
        return;
      }
      clearLocalPicked(state.tripId);
      const lastId = getLastPickedTripId();
      if (lastId && Number(lastId) === Number(state.tripId)) clearLastPickedTripId();
      alert('Trip completed successfully.');
      window.location.href = publicUrl('index.php?route=trip-assignment');
    } catch (e) {
      alert('Network error while completing trip.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = orig;
    }
  }

  function bindUi(){
    if (els.completeBtn) els.completeBtn.addEventListener('click', completeTrip);
    if (els.pickupBtn) els.pickupBtn.addEventListener('click', pickupTrip);
  }

  function setupUnloadFlush(){
    function flush(){
      if (!state.tripId || state.uploadBuffer.length === 0) return;
      const payload = { trip_id: state.tripId, points: state.uploadBuffer.slice() };
      state.uploadBuffer.length = 0;
      const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
      if (navigator.sendBeacon) {
        try { navigator.sendBeacon(publicUrl('api/locations.php'), blob); } catch (e) {}
      } else {
        fetch(publicUrl('api/locations.php'), { method: 'POST', body: JSON.stringify(payload), headers: { 'Content-Type': 'application/json' }, keepalive: true });
      }
    }
    window.addEventListener('beforeunload', flush);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') flush(); });
  }

  async function main(){
    initDomRefs();
    await loadTrip();
    if (!state.trip) return; // no trip
    // Do not show map or start tracking until assignment is accepted
    bindUi();
    if (!state.assignmentAccepted) {
      const mapContainer = document.getElementById('map');
      if (mapContainer) mapContainer.classList.add('d-none');
      const pendingMsg = document.getElementById('pending-msg');
      if (pendingMsg) pendingMsg.classList.remove('d-none');
      return;
    }
    initMap();
    setupUnloadFlush();
    startWatch();
    startAssignmentPoll();
  }

  async function pickupTrip(){
    if (state.pickupProcessing || !state.tripId) return;
    state.pickupProcessing = true;
    const btn = els.pickupBtn;
    const orig = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Picking up...'; }
    try {
      const { ok, data } = await fetchJson(publicUrl('api/assignments.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ action: 'pickup', trip_id: state.tripId })
      });
      if (!ok || !data || data.success === false) {
        alert((data && data.message) ? data.message : 'Failed to mark pickup');
        return;
      }
      state.isPickedUp = true;
      markLocalPicked(state.tripId);
      setLastPickedTripId(state.tripId);
      if (state.trip) { state.trip.status = 'in_progress'; state.trip.pickup_at = new Date().toISOString(); }
      if (els.pickupBadge) els.pickupBadge.classList.remove('d-none');
      if (els.pickupBtn) els.pickupBtn.classList.add('d-none');
      setStatus('Picked up');
      // Remove start marker entirely after pickup
      if (state.layers.start) { try { state.map.removeLayer(state.layers.start); } catch (e) {} state.layers.start = null; }
      // Force immediate reroute to destination
      state.lastRouteAt = 0;
      state.lastRouteDriverLatLng = null;
      purgeRouteVisuals();
      // Draw new route to destination right away
      try { await updateRoutedPaths(); } catch(_) { renderRoute(); }
      // Attempt a graceful fade if any pre-pickup line still exists
      fadeOutLayer('routedDriverToStart');
      fadeOutLayer('driverToStart');
    } catch (e) {
      alert('Network error while marking pickup.');
    } finally {
      state.pickupProcessing = false;
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
    }
  }

  document.addEventListener('DOMContentLoaded', main);
})();
