<?php
$title = "Trip Assignment";
session_start();

if (!isset($_SESSION['driver_id'])) {
    header("Location: index.php"); // redirect if not logged in
    exit;
}

// Page-specific styles
$styles = '<link rel="stylesheet" href="' . asset('css/trip-assignment.css') . '">';

// Page-specific scripts
$scripts = '<script src="' . asset('js/trip-assignment.js') . '"></script>';

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
                <h2 class="fw-bold mb-1">Trip Assignment</h2>
                <p class="text-muted mb-0">Manage your assigned trips and track progress</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" id="refreshAssignments">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm stats-card">
            <div class="card-body text-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary mb-2">
                    <i class="fas fa-clock"></i>
                </div>
                <h4 class="mb-1 text-primary" id="pendingCount">0</h4>
                <small class="text-muted">Pending Trips</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm stats-card">
            <div class="card-body text-center">
                <div class="stats-icon bg-info bg-opacity-10 text-info mb-2">
                    <i class="fas fa-route"></i>
                </div>
                <h4 class="mb-1 text-info" id="activeCount">0</h4>
                <small class="text-muted">Active Trips</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm stats-card">
            <div class="card-body text-center">
                <div class="stats-icon bg-success bg-opacity-10 text-success mb-2">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h4 class="mb-1 text-success" id="completedCount">0</h4>
                <small class="text-muted">Completed Today</small>
            </div>
        </div>
    </div>
</div>

<!-- Current Assignments Table -->
<div class="card main-card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="fas fa-tasks me-2 text-primary"></i>
            Current Assignments
            <span class="badge bg-primary ms-2">0</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-clean mb-0" id="assignmentsTable">
                <thead>
                    <tr>
                        <th>Trip Details</th>
                        <th>Vehicle & Driver</th>
                        <th>Route Information</th>
                        <th>Schedule & Fare</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No assignments available.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Completed Assignments Table -->
<div class="card main-card mt-4">
    <div class="card-header">
        <h5 class="card-title">
            <i class="fas fa-check-double me-2 text-success"></i>
            Completed Assignments
            <span class="badge bg-success ms-2">0</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-clean mb-0" id="completedAssignmentsTable">
                <thead>
                    <tr>
                        <th>Trip Details</th>
                        <th>Vehicle & Driver</th>
                        <th>Route Information</th>
                        <th>Schedule & Fare</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No completed assignments available.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the layout
include __DIR__ . '/layouts/app.php';
?>