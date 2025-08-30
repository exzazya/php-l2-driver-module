<?php
$title = "Reports and Checklist";

if (!isset($_SESSION['driver_id'])) {
    header("Location: index.php"); // redirect if not logged in
    exit;
}

// Page-specific scripts
$scripts = '<script>
// Define submitReport function directly in page scope
async function submitReport() {
    console.log("submitReport function called directly");
    
    const tripId = document.getElementById("tripIdValue")?.value;
    const fuelUsed = parseFloat(document.getElementById("fuelUsed")?.value) || 0;
    const tollFee = parseFloat(document.getElementById("tollFee")?.value) || 0;
    const fuelCost = parseFloat(document.getElementById("fuelCost")?.value) || 0;
    const parkingFee = parseFloat(document.getElementById("parkingFee")?.value) || 0;
    const incidents = document.getElementById("incidents")?.value?.trim() || "";
    const remarks = document.getElementById("remarks")?.value?.trim() || "";

    console.log("Form values:", { tripId, fuelUsed, tollFee, fuelCost, parkingFee, incidents, remarks });
    
    // Debug form elements
    console.log("Form elements check:");
    console.log("fuelUsed element:", document.getElementById("fuelUsed"));
    console.log("tollFee element:", document.getElementById("tollFee"));
    console.log("fuelCost element:", document.getElementById("fuelCost"));
    console.log("parkingFee element:", document.getElementById("parkingFee"));
    console.log("incidents element:", document.getElementById("incidents"));
    console.log("remarks element:", document.getElementById("remarks"));

    // Enhanced Validation
    if (!tripId) {
        alert("Error: No trip selected. Please select a trip from the completed trips table.");
        return;
    }

    // Check if at least one expense field has a value
    const hasExpenses = fuelUsed > 0 || tollFee > 0 || fuelCost > 0 || parkingFee > 0;
    const hasContent = incidents.length > 0 || remarks.length > 0;

    if (!hasExpenses && !hasContent) {
        alert("Please fill in at least one expense amount or add incidents/remarks");
        return;
    }

    // Validate fuel consistency
    if ((fuelUsed > 0 && fuelCost === 0) || (fuelUsed === 0 && fuelCost > 0)) {
        alert("If you enter fuel used, please also enter fuel cost, and vice versa");
        return;
    }

    // Disable submit button
    const submitBtn = document.getElementById("submitReportBtn");
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = "<i class=\"fas fa-spinner fa-spin me-1\"></i>Submitting...";
    }

    try {
        const formData = new FormData();
        formData.append("action", "submit");
        formData.append("trip_id", tripId);
        formData.append("fuel_used", fuelUsed);
        formData.append("toll_fee", tollFee);
        formData.append("fuel_cost", fuelCost);
        formData.append("parking_fee", parkingFee);
        formData.append("incidents", incidents);
        formData.append("remarks", remarks);

        // Add receipt files if present
        const receiptTypes = ["fuelReceipt", "tollReceipt", "parkingReceipt"];
        receiptTypes.forEach(type => {
            const fileInput = document.getElementById(type);
            if (fileInput && fileInput.files[0]) {
                formData.append(type.replace("Receipt", "_receipt"), fileInput.files[0]);
            }
        });
        
        // Debug FormData contents
        console.log("FormData contents:");
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        const response = await fetch("api/reports.php", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });

        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || "Failed to submit report");
        }

        alert("Report submitted successfully!");
        
        // Close modal and reload data
        const modal = bootstrap.Modal.getInstance(document.getElementById("reportModal"));
        if (modal) modal.hide();
        
        // Reload tables if functions exist
        if (window.loadCompletedTrips) window.loadCompletedTrips();
        if (window.loadSubmittedReports) window.loadSubmittedReports();

    } catch (error) {
        console.error("Error submitting report:", error);
        alert("Error: " + (error.message || "Failed to submit report"));
    } finally {
        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = "<i class=\"fas fa-paper-plane me-1\"></i>Submit Report";
        }
    }
}

// Make function globally available
window.submitReport = submitReport;

document.addEventListener("DOMContentLoaded", function() {
    console.log("Page script loaded, submitReport available:", typeof submitReport);
    
    // Attach to submit button immediately
    setTimeout(() => {
        const btn = document.getElementById("submitReportBtn");
        if (btn) {
            btn.onclick = function(e) {
                e.preventDefault();
                console.log("Submit button clicked");
                
                // Debug trip ID
                const tripIdField = document.getElementById("tripIdValue");
                console.log("Trip ID field:", tripIdField);
                console.log("Trip ID value:", tripIdField?.value);
                
                submitReport();
            };
            console.log("Submit button handler attached");
        }
    }, 500);
    
    // Also attach listener to modal show event to ensure trip ID is set
    setTimeout(() => {
        const modal = document.getElementById("reportModal");
        if (modal) {
            modal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget; // Button that triggered the modal
                if (button && button.hasAttribute("data-trip-id")) {
                    const tripId = button.getAttribute("data-trip-id");
                    const tripDisplay = button.getAttribute("data-trip-display");
                    console.log("Modal opening for trip:", tripId, tripDisplay);
                    
                    // Set the trip ID in the hidden field
                    const tripIdField = document.getElementById("tripIdValue");
                    if (tripIdField) {
                        tripIdField.value = tripId;
                        console.log("Trip ID set to:", tripId);
                    }
                    
                    // Update modal title
                    const modalTitle = document.getElementById("reportModalLabel");
                    if (modalTitle) {
                        modalTitle.textContent = `Trip Report - ${tripDisplay}`;
                    }
                }
            });
        }
    }, 500);
});
</script>
<script src="' . asset('js/reports.js') . '"></script>';

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
      <span class="badge bg-success ms-2" id="completedTripsCount">0</span>
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-clean mb-0" id="completedTripsTable">
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
            <td colspan="4" class="text-center text-muted">Loading completed trips...</td>
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
      <span class="badge bg-primary ms-2" id="submittedReportsCount">0</span>
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-clean mb-0" id="submittedReportsTable">
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
            <td colspan="6" class="text-center text-muted">Loading submitted reports...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-sm-down modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="reportModalLabel">Trip Report</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="reportForm" enctype="multipart/form-data">
          <input type="hidden" id="tripIdValue">
          
          <!-- Fuel Used Section -->
          <div class="card mb-3">
            <div class="card-header py-2">
              <h6 class="mb-0"><i class="fas fa-gas-pump me-2"></i>Fuel Information</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Fuel Used (Liters)</label>
                  <input type="number" id="fuelUsed" class="form-control" placeholder="0.0" step="0.1" min="0">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Fuel Cost (₱)</label>
                  <input type="number" id="fuelCost" class="form-control" placeholder="0.00" step="0.01" min="0">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Fuel Receipt</label>
                <input type="file" id="fuelReceipt" class="form-control" accept="image/*" capture="environment">
                <div class="form-text">Upload fuel receipt image</div>
              </div>
            </div>
          </div>

          <!-- Toll Fee Section -->
          <div class="card mb-3">
            <div class="card-header py-2">
              <h6 class="mb-0"><i class="fas fa-road me-2"></i>Toll Expenses</h6>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Toll Fee (₱)</label>
                <input type="number" id="tollFee" class="form-control" placeholder="0.00" step="0.01" min="0">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Toll Receipt</label>
                <input type="file" id="tollReceipt" class="form-control" accept="image/*" capture="environment">
                <div class="form-text">Upload toll receipt image</div>
              </div>
            </div>
          </div>

          <!-- Parking Fee Section -->
          <div class="card mb-3">
            <div class="card-header py-2">
              <h6 class="mb-0"><i class="fas fa-parking me-2"></i>Parking Expenses</h6>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Parking Fee (₱)</label>
                <input type="number" id="parkingFee" class="form-control" placeholder="0.00" step="0.01" min="0">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Parking Receipt</label>
                <input type="file" id="parkingReceipt" class="form-control" accept="image/*" capture="environment">
                <div class="form-text">Upload parking receipt image</div>
              </div>
            </div>
          </div>


          <!-- Incidents and Remarks Section -->
          <div class="card mb-3">
            <div class="card-header py-2">
              <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Additional Information</h6>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Incidents</label>
                <textarea id="incidents" class="form-control" rows="3" placeholder="Describe any incidents during the trip (leave blank if none)"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Remarks</label>
                <textarea id="remarks" class="form-control" rows="3" placeholder="Additional notes or remarks about the trip"></textarea>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="submitReportBtn">
          <i class="fas fa-paper-plane me-1"></i>Submit Report
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();

// Include the layout
include __DIR__ . '/layouts/app.php';
?>
