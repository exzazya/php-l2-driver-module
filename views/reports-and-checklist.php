<?php
$title = "Reports and Checklist";
session_start();
require 'db_connect.php'; // make sure this connects properly

if (!isset($_SESSION['driver_id'])) {
    header("Location: index.php"); // redirect if not logged in
    exit;
}

// Page-specific scripts
$scripts = '
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll(".open-report");
    const tripField = document.getElementById("tripIdField");

    buttons.forEach(btn => {
      btn.addEventListener("click", () => {
        const tripId = btn.getAttribute("data-trip");
        tripField.value = tripId;
      });
    });
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
        <h2 class="fw-bold mb-1">Reports and Checklist</h2>
        <p class="text-muted mb-0">Submit your daily report here</p>
      </div>
    </div>
  </div>
</div>

<!-- Completed Trips Table -->
<div class="card main-card mb-4">
  <div class="card-header">
    <h5 class="card-title">
      <i class="fas fa-check-double me-2 text-success"></i>
      Completed Trips
      <span class="badge bg-success ms-2">2</span>
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-clean mb-0">
        <thead>
          <tr>
            <th>Trip ID</th>
            <th>Destination</th>
            <th>Date Completed</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#TRIP001</td>
            <td>Manila → Baguio</td>
            <td>2025-08-16</td>
            <td>
              <button 
                class="btn btn-primary btn-sm open-report" 
                data-bs-toggle="modal" 
                data-bs-target="#reportModal"
                data-trip="#TRIP001">
                Submit Report
              </button>
            </td>
          </tr>
          <tr>
            <td>#TRIP002</td>
            <td>Cebu → Davao</td>
            <td>2025-08-15</td>
            <td>
              <button 
                class="btn btn-primary btn-sm open-report" 
                data-bs-toggle="modal" 
                data-bs-target="#reportModal"
                data-trip="#TRIP002">
                Submit Report
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Submitted Reports Table -->
<div class="card main-card">
  <div class="card-header">
    <h5 class="card-title">
      <i class="fas fa-file-alt me-2 text-primary"></i>
      Submitted Reports
      <span class="badge bg-primary ms-2">2</span>
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-clean mb-0">
        <thead>
          <tr>
            <th>Report ID</th>
            <th>Trip ID</th>
            <th>Fuel Used</th>
            <th>Incidents</th>
            <th>Remarks</th>
            <th>Date Submitted</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>#R001</td>
            <td>#TRIP001</td>
            <td>40 L</td>
            <td>None</td>
            <td>Smooth trip</td>
            <td>2025-08-16</td>
          </tr>
          <tr>
            <td>#R002</td>
            <td>#TRIP002</td>
            <td>35 L</td>
            <td>Tire puncture</td>
            <td>Replaced spare tire</td>
            <td>2025-08-15</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="reportModalLabel">Driver Report Form</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <!-- Trip ID (auto-fill) -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Trip ID</label>
            <input type="text" id="tripIdField" class="form-control" disabled>
          </div>

          <!-- Fuel Used -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Fuel Used (Liters)</label>
            <input type="number" class="form-control" placeholder="Enter fuel used">
          </div>

          <!-- Incidents -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Incidents</label>
            <textarea class="form-control" rows="2" placeholder="Describe any incidents..."></textarea>
          </div>

          <!-- Remarks -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Remarks</label>
            <textarea class="form-control" rows="2" placeholder="Additional notes or remarks..."></textarea>
          </div>

          <!-- Date -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Date of Report</label>
            <input type="date" class="form-control">
          </div>

          <!-- File Upload -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Upload Photo (optional)</label>
            <input type="file" class="form-control">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success">Submit Report</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();

// Include the layout
include __DIR__ . '/layouts/app.php';
?>
