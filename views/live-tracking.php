<?php
$title = 'Live Tracking';

// Page styles: Leaflet CSS and map layout
$styles = '<link rel="stylesheet" href="' . asset('vendor/leaflet/leaflet.css') . '">' . "\n" .
          '<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">' . "\n" .
          '<style>
            #map { width: 100%; height: 70vh; min-height: 380px; border-radius: 12px; }
            .tracking-meta { font-size: 0.95rem; }
            .sticky-actions { position: sticky; bottom: 0; background: #fff; padding: 0.75rem; z-index: 5; border-top: 1px solid #eee; }
            @media (max-width: 768px) { #map { height: 65vh; min-height: 300px; } }
          </style>';

// Precompute tripId from query if present; otherwise pick driver's current assignment
$tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
if ($tripId <= 0) {
  try {
    if (!function_exists('executeQuery')) {
      @require_once __DIR__ . '/../includes/database.php';
    }
    $driverId = isset($_SESSION['driver_id']) ? (int)$_SESSION['driver_id'] : 0;
    if ($driverId > 0 && function_exists('executeQuery')) {
      // Prefer in_progress, then accepted, newest first
      $stmt = executeQuery(
        "SELECT ma.trip_id
           FROM mobile_assignments ma
           JOIN trips t ON t.id = ma.trip_id
          WHERE ma.driver_id = ?
            AND (ma.is_declined = 0 OR ma.is_declined IS NULL)
            AND (t.status IN ('in_progress','accepted'))
            AND (t.status != 'completed' AND ma.completed_at IS NULL)
          ORDER BY (t.status = 'in_progress') DESC, ma.assigned_at DESC
          LIMIT 1",
        [$driverId]
      );
      $row = $stmt ? $stmt->fetch() : false;
      if ($row && isset($row['trip_id'])) {
        $tripId = (int)$row['trip_id'];
      }
    }
  } catch (Exception $e) {
    // ignore and keep tripId = 0
  }
}

// Page scripts: Leaflet and our live-tracking logic (with cache-busting)
$ver = date('Ymd-His');
$scripts = '<script>window.LIVE_TRACKING_CONFIG = ' . json_encode([ 'tripId' => $tripId ]) . ';</script>' . "\n" .
           '<script src="' . asset('vendor/leaflet/leaflet.js') . '"></script>' . "\n" .
           '<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>' . "\n" .
           '<script src="' . asset('js/live-tracking.js') . '?v=' . $ver . '"></script>';

$logoAsset = asset('img/jetlouge_logo.webp');

ob_start();
?>

  <div class="page-header-container mb-3">
    <div class="d-flex justify-content-between align-items-center page-header">
      <div class="d-flex align-items-center">
        <div class="dashboard-logo me-3">
          <img src="<?php echo asset('img/jetlouge_logo.webp'); ?>" alt="Jetlouge Icon" class="logo-img" style="max-height:44px; width:auto;"
               onerror="this.onerror=null; this.src='<?php echo asset('img/default-profile.jpg'); ?>';">
        </div>
        <div>
          <h4 class="fw-bold mb-0">Live Tracking</h4>
          <small class="text-muted">Real-time trip tracking</small>
        </div>
      </div>
      <div>
        <span id="pickedUpBadge" class="badge bg-success d-none"><i class="fa-solid fa-user-check me-1"></i>Picked Up</span>
      </div>
    </div>
  </div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2 align-items-center mb-2">
      <div class="col-12 col-md-6">
        <div class="text-truncate" title="Start">
          <small class="text-muted d-block"><i class="fa-solid fa-location-dot me-1 text-primary"></i>Start</small>
          <div class="fw-semibold" id="startLabel">—</div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="text-truncate" title="Destination">
          <small class="text-muted d-block"><i class="fa-solid fa-flag-checkered me-1 text-danger"></i>Destination</small>
          <div class="fw-semibold" id="destLabel">—</div>
        </div>
      </div>
    </div>

    <div class="tracking-meta row g-2 mb-2">
      <div class="col-12 col-md-4"><small class="text-muted">Status:</small> <span class="fw-semibold" id="trackingStatus">Not started</span></div>
      <div class="col-6 col-md-4"><small class="text-muted">Speed:</small> <span class="fw-semibold" id="trackingSpeed">—</span></div>
      <div class="col-6 col-md-4"><small class="text-muted">Last update:</small> <span class="fw-semibold" id="trackingLastUpdate">—</span></div>
      <div class="col-12 col-md-4"><small class="text-muted">Route:</small> <span id="routingMode" class="fw-semibold text-muted">Detecting...</span></div>
    </div>

    <div id="noActiveTrip" class="alert alert-info d-none mb-3" role="alert"></div>

    <div id="map" class="mb-3"></div>

    <div class="sticky-actions">
      <div class="row g-2">
        <div class="col-12 col-sm-6 d-grid">
          <button id="pickupTripBtn" class="btn btn-primary">
            <i class="fa-solid fa-user-check me-2"></i>Picked Up
          </button>
        </div>
        <div class="col-12 col-sm-6 d-grid">
          <button id="completeTripBtn" class="btn btn-success">
            <i class="fa-solid fa-check me-2"></i>Complete Trip
          </button>
        </div>
      </div>
    </div>
  </div>
  
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/app.php';
?>