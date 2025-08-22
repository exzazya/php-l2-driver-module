<?php
$title = "Live Tracking";
session_start();
require 'db_connect.php'; // make sure this connects properly

if (!isset($_SESSION['driver_id'])) {
    header("Location: index.php"); // redirect if not logged in
    exit;
}

// Page-specific styles - Leaflet CSS
$styles = '<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />';

// Page-specific scripts - Leaflet JS and tracking scripts
$scripts = '
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Set initial display values
    function setInitialValues() {
        document.getElementById("lastUpdate").textContent = "Waiting for data...";
    }

    // Button event listeners
    document.getElementById("centerMapBtn").addEventListener("click", function() {
        this.innerHTML = "<i class=\"fas fa-spinner fa-spin me-1\"></i>Centering...";
        setTimeout(() => {
            this.innerHTML = "<i class=\"fas fa-crosshairs me-1\"></i>Center Map";
        }, 1000);
    });

    document.getElementById("refreshBtn").addEventListener("click", function() {
        this.innerHTML = "<i class=\"fas fa-spinner fa-spin me-1\"></i>Refreshing...";
        setTimeout(() => {
            this.innerHTML = "<i class=\"fas fa-sync-alt me-1\"></i>Refresh";
        }, 800);
    });

    // Initialize map centered on [0,0] until location is fetched
    const map = L.map("map").setView([0, 0], 15);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap"
    }).addTo(map);

    // Marker for driver/user
    const driverMarker = L.marker([0,0]).addTo(map)
        .bindPopup("<b>Driver: You</b>").openPopup();

    // Function to update marker and info box
    function updatePosition(lat, lng) {
        driverMarker.setLatLng([lat, lng]);
        map.setView([lat, lng], 15);
    }

    // Check for geolocation support
    if (navigator.geolocation) {
        // Watch position for real-time updates
        navigator.geolocation.watchPosition(position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const speed = position.coords.speed ? (position.coords.speed * 3.6).toFixed(1) : 0; // m/s -> km/h

            updatePosition(lat, lng);

            // Update stats
            document.getElementById("currentSpeed").textContent = speed + " km/h";
            document.getElementById("lastUpdate").textContent = "Just updated";
        }, error => {
            console.error("Geolocation error:", error);
            alert("Unable to fetch your location. Make sure location is enabled in your browser.");
        }, {
            enableHighAccuracy: true,
            maximumAge: 3000,
            timeout: 5000
        });
    } else {
        alert("Geolocation is not supported by your browser.");
    }

    // Center map button
    document.getElementById("centerMapBtn").addEventListener("click", function() {
        if (driverMarker.getLatLng()) {
            map.panTo(driverMarker.getLatLng(), { animate: true });
        }
    });

    // Initialize display
    setInitialValues();
});
</script>';

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
                        <div class="fw-bold text-muted">No active trip</div>
                        <small class="text-muted">--</small>
                    </div>

                    <div class="route-line"></div>

                    <div class="info-item mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-map-pin text-muted me-2"></i>
                            <small class="text-muted">TO</small>
                        </div>
                        <div class="fw-bold text-muted">No destination</div>
                        <small class="text-muted">--</small>
                    </div>
                </div>

                <hr>

                <div class="vehicle-info">
                    <h6 class="mb-2">
                        <i class="fas fa-car me-2 text-info"></i>Vehicle Info
                    </h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Model:</span>
                        <span class="fw-bold text-muted">--</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Plate:</span>
                        <span class="fw-bold text-muted">--</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Capacity:</span>
                        <span class="fw-bold text-muted">--</span>
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


</style>
<?php
$content = ob_get_clean();

// Include the layout
include __DIR__ . '/layouts/app.php';
?>