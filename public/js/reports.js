document.addEventListener('DOMContentLoaded', () => {
  console.log('Reports.js loaded');
  
  const completedTripsTable = document.getElementById('completedTripsTable');
  const submittedReportsTable = document.getElementById('submittedReportsTable');
  const reportModal = document.getElementById('reportModal');
  const reportForm = document.getElementById('reportForm');

  console.log('DOM elements found:', {
    completedTripsTable: !!completedTripsTable,
    submittedReportsTable: !!submittedReportsTable,
    reportModal: !!reportModal,
    reportForm: !!reportForm
  });

  // Initialize tables
  loadCompletedTrips();
  loadSubmittedReports();

  // Use setTimeout to ensure DOM is fully rendered
  setTimeout(() => {
    const submitReportBtn = document.getElementById('submitReportBtn');
    console.log('Submit button found after timeout:', submitReportBtn);
    
    if (submitReportBtn) {
      console.log('Adding click listener to submit button');
      
      // Remove any existing listeners
      submitReportBtn.replaceWith(submitReportBtn.cloneNode(true));
      const newBtn = document.getElementById('submitReportBtn');
      
      newBtn.addEventListener('click', (e) => {
        console.log('Submit button clicked');
        e.preventDefault();
        e.stopPropagation();
        submitReport();
      });
    } else {
      console.error('Submit button still not found after timeout!');
    }
  }, 1000);

  // Reset form when modal is closed
  if (reportModal) {
    reportModal.addEventListener('hidden.bs.modal', () => {
      resetReportForm();
    });
  }

  // Also add event delegation for dynamically created buttons
  document.addEventListener('click', (e) => {
    if (e.target && e.target.id === 'submitReportBtn') {
      console.log('Submit button clicked via delegation');
      e.preventDefault();
      submitReport();
    }
  });

  async function loadCompletedTrips() {
    try {
      const response = await fetch('api/reports.php?action=completed_trips', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to load completed trips');
      }

      renderCompletedTrips(data.data.trips || []);
    } catch (error) {
      console.error('Error loading completed trips:', error);
      showError('Failed to load completed trips');
    }
  }

  async function loadSubmittedReports() {
    try {
      const response = await fetch('api/reports.php', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to load submitted reports');
      }

      renderSubmittedReports(data.data.reports || []);
    } catch (error) {
      console.error('Error loading submitted reports:', error);
      showError('Failed to load submitted reports');
    }
  }

  function renderCompletedTrips(trips) {
    const tbody = completedTripsTable.querySelector('tbody');
    const countBadge = document.getElementById('completedTripsCount');
    
    if (!trips.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No completed trips available</td></tr>';
      countBadge.textContent = '0';
      return;
    }

    tbody.innerHTML = '';
    let availableTripsCount = 0;

    trips.forEach(trip => {
      const row = document.createElement('tr');
      const tripId = `#TRIP${String(trip.id).padStart(3, '0')}`;
      const destination = `${trip.start_location || ''} → ${trip.destination || ''}`;
      const completedDate = formatDate(trip.completed_at);
      const hasReport = trip.report_id !== null;

      if (!hasReport) {
        availableTripsCount++;
      }

      row.innerHTML = `
        <td>${escapeHtml(tripId)}</td>
        <td>${escapeHtml(destination)}</td>
        <td>${escapeHtml(completedDate)}</td>
        <td>
          ${hasReport 
            ? '<span class="badge bg-success">Report Submitted</span>'
            : `<button class="btn btn-primary btn-sm submit-report-btn" 
                data-trip-id="${trip.id}" 
                data-trip-display="${tripId}"
                data-bs-toggle="modal" 
                data-bs-target="#reportModal">
                Submit Report
              </button>`
          }
        </td>
      `;

      // Add event listener for submit report button
      const submitBtn = row.querySelector('.submit-report-btn');
      if (submitBtn) {
        submitBtn.addEventListener('click', (e) => {
          const tripId = e.target.getAttribute('data-trip-id');
          const tripDisplay = e.target.getAttribute('data-trip-display');
          openReportModal(tripId, tripDisplay);
        });
      }

      tbody.appendChild(row);
    });

    countBadge.textContent = availableTripsCount.toString();
  }

  function renderSubmittedReports(reports) {
    const tbody = submittedReportsTable.querySelector('tbody');
    const countBadge = document.getElementById('submittedReportsCount');
    
    if (!reports.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No submitted reports available</td></tr>';
      countBadge.textContent = '0';
      return;
    }

    tbody.innerHTML = '';
    reports.forEach(report => {
      const row = document.createElement('tr');
      const reportId = `#R${String(report.id).padStart(3, '0')}`;
      const tripId = `#TRIP${String(report.trip_id).padStart(3, '0')}`;
      const fuelUsed = report.fuel_used ? `${report.fuel_used} L` : 'N/A';
      const incidents = report.incidents || 'None';
      const remarks = report.remarks || 'N/A';
      const submittedDate = formatDate(report.submitted_at);

      row.innerHTML = `
        <td>${escapeHtml(reportId)}</td>
        <td>${escapeHtml(tripId)}</td>
        <td>${escapeHtml(fuelUsed)}</td>
        <td class="text-truncate" style="max-width: 150px;" title="${escapeHtml(incidents)}">${escapeHtml(incidents)}</td>
        <td class="text-truncate" style="max-width: 150px;" title="${escapeHtml(remarks)}">${escapeHtml(remarks)}</td>
        <td>${escapeHtml(submittedDate)}</td>
      `;

      tbody.appendChild(row);
    });

    countBadge.textContent = reports.length.toString();
  }

  function openReportModal(tripId, tripDisplay) {
    // Reset form first, then set the hidden trip ID value
    resetReportForm();
    const tripIdValueField = document.getElementById('tripIdValue');
    if (tripIdValueField) {
      tripIdValueField.value = tripId;
    }
  }

  function resetReportForm() {
    const form = document.getElementById('reportForm');
    if (form) {
      const tripField = document.getElementById('tripIdValue');
      const tripVal = tripField ? tripField.value : '';
      form.reset();
      if (tripField) tripField.value = tripVal;
    }
    // Don't reset the tripIdValue as it's set when opening modal
  }

  // Prefer existing global submitReport (inline version). If absent, define a local one.
  if (typeof window.submitReport !== 'function') {
    console.log('No inline submitReport found; defining default submitReport in reports.js');
    async function localSubmitReport() {
      console.log('submitReport function called (reports.js fallback)');
      
      const tripId = document.getElementById('tripIdValue')?.value;
      const fuelUsed = parseFloat(document.getElementById('fuelUsed')?.value) || 0;
      const tollFee = parseFloat(document.getElementById('tollFee')?.value) || 0;
      const fuelCost = parseFloat(document.getElementById('fuelCost')?.value) || 0;
      const parkingFee = parseFloat(document.getElementById('parkingFee')?.value) || 0;
      const incidents = document.getElementById('incidents')?.value?.trim() || '';
      const remarks = document.getElementById('remarks')?.value?.trim() || '';

      console.log('Form values:', { tripId, fuelUsed, tollFee, fuelCost, parkingFee, incidents, remarks });

      // Enhanced Validation
      if (!tripId) {
        showError('Trip ID is required');
        return;
      }

      // Check if at least one expense field has a value
      const hasExpenses = fuelUsed > 0 || tollFee > 0 || fuelCost > 0 || parkingFee > 0;
      const hasContent = incidents.length > 0 || remarks.length > 0;

      if (!hasExpenses && !hasContent) {
        showError('Please fill in at least one expense amount or add incidents/remarks');
        return;
      }

      // Validate fuel consistency
      if ((fuelUsed > 0 && fuelCost === 0) || (fuelUsed === 0 && fuelCost > 0)) {
        showError('If you enter fuel used, please also enter fuel cost, and vice versa');
        return;
      }

      // Disable submit button
      const localSubmitBtn = document.getElementById('submitReportBtn');
      if (localSubmitBtn) {
        localSubmitBtn.disabled = true;
        localSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';
      }

      try {
        // Create FormData for file uploads
        const formData = new FormData();
        formData.append('action', 'submit');
        formData.append('trip_id', tripId);
        formData.append('fuel_used', fuelUsed);
        formData.append('toll_fee', tollFee);
        formData.append('fuel_cost', fuelCost);
        formData.append('parking_fee', parkingFee);
        formData.append('incidents', incidents);
        formData.append('remarks', remarks);

        // Add file uploads
        const fileInputs = ['fuelReceipt', 'tollReceipt', 'parkingReceipt'];
        fileInputs.forEach(inputId => {
          const fileInput = document.getElementById(inputId);
          if (fileInput && fileInput.files.length > 0) {
            formData.append(inputId.replace(/([A-Z])/g, '_$1').toLowerCase(), fileInput.files[0]);
          }
        });

        const response = await fetch('api/reports.php', {
          method: 'POST',
          credentials: 'same-origin',
          body: formData
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Failed to submit report');
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(reportModal);
        if (modal) {
          modal.hide();
        }

        // Show success message
        const receiptsUploaded = data.data?.receipts_uploaded || 0;
        showSuccess(`Report submitted successfully! ${receiptsUploaded} receipt(s) uploaded.`);

        // Reload both tables
        loadCompletedTrips();
        loadSubmittedReports();

      } catch (error) {
        console.error('Error submitting report:', error);
        showError(error.message || 'Failed to submit report');
      } finally {
        // Re-enable submit button
        if (localSubmitBtn) {
          localSubmitBtn.disabled = false;
          localSubmitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Report';
        }
      }
    }
    // expose
    window.submitReport = localSubmitReport;
  } else {
    console.log('Inline submitReport detected; using it from reports.js listeners');
  }

  function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function showError(message) {
    console.error('Report Error:', message);
    alert('Error: ' + message);
  }

  function showSuccess(message) {
    console.log('Report Success:', message);
    alert('Success: ' + message);
  }

  // Debug function to check button functionality
  window.debugReportForm = function() {
    console.log('Submit button:', document.getElementById('submitReportBtn'));
    console.log('Trip ID value:', document.getElementById('tripIdValue')?.value);
    console.log('Form element:', document.getElementById('reportForm'));
  };

  // Make submitReport globally available immediately
  window.submitReport = submitReport;
  
  // Also expose other functions for debugging
  window.loadCompletedTrips = loadCompletedTrips;
  window.loadSubmittedReports = loadSubmittedReports;
  window.openReportModal = openReportModal;
});
