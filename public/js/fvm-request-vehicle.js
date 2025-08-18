// Vehicle Request Page Scripts

(function() {
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('requestVehicleForm');
    if (!form) return;

    // Initialize table pagination if the table exists
    const requestsTable = document.getElementById('vehicleRequestsTable');
    if (requestsTable) {
      // This will be handled by table-pagination.js
      console.log('Table pagination will be initialized by table-pagination.js');
    }

    // Form validation and submission
    function invalidate(el, msg) {
      if (!el) return false;
      el.classList.add('is-invalid');
      const fb = el.parentElement?.querySelector('.invalid-feedback');
      if (fb) fb.textContent = msg || 'This field is required.';
      return false;
    }

    function clearInvalid(el) {
      if (!el) return;
      el.classList.remove('is-invalid');
    }

    function validateForm() {
      let isValid = true;
      const req = (id) => document.getElementById(id);

      const requester = req('rvRequester');
      const department = req('rvDepartment');
      const vehicleType = req('rvVehicleType');
      const budget = req('rvBudget');
      const justification = req('rvJustification');
      const specifications = req('rvSpecifications');

      // Clear previous invalid states
      [requester, department, vehicleType, budget, justification, specifications].forEach(clearInvalid);

      // Validate required fields
      if (!requester || !requester.value.trim()) isValid = invalidate(requester, 'Requester name is required.');
      if (!department || !department.value.trim()) isValid = invalidate(department, 'Department is required.');
      if (!vehicleType || !vehicleType.value) isValid = invalidate(vehicleType, 'Please select a vehicle type.');
      if (!budget || !budget.value.trim()) {
        isValid = invalidate(budget, 'Budget is required.');
      } else if (parseFloat(budget.value) <= 0) {
        isValid = invalidate(budget, 'Budget must be greater than 0.');
      }
      if (!justification || !justification.value.trim()) isValid = invalidate(justification, 'Business justification is required.');
      if (!specifications || !specifications.value.trim()) isValid = invalidate(specifications, 'Preferred specifications are required.');

      return isValid;
    }

    // Handle form submission
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      if (!validateForm()) return;

      try {
        // Get form data
        const formData = {
          requester: document.getElementById('rvRequester').value.trim(),
          department: document.getElementById('rvDepartment').value.trim(),
          vehicleType: document.getElementById('rvVehicleType').value,
          budget: parseFloat(document.getElementById('rvBudget').value),
          justification: document.getElementById('rvJustification').value.trim(),
          specifications: document.getElementById('rvSpecifications').value.trim(),
          status: 'Pending',
          requestDate: new Date().toISOString()
        };

        // In a real application, you would send this to your backend
        console.log('Submitting vehicle request:', formData);
        
        // Example: Using fetch to send data to the server
        /*
        const response = await fetch('/api/vehicle-requests', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(formData)
        });

        if (!response.ok) throw new Error('Failed to submit request');
        */

        // For demo purposes, we'll just add to the table directly
        addRequestToTable(formData);
        
        // Show success message
        showAlert('Vehicle request submitted successfully!', 'success');
        
        // Reset form
        form.reset();
        
      } catch (error) {
        console.error('Error submitting request:', error);
        showAlert('Failed to submit request. Please try again.', 'danger');
      }
    });

    // Add a new request to the table
    function addRequestToTable(request) {
      const tbody = document.querySelector('#vehicleRequestsTable tbody');
      if (!tbody) return;

      const row = document.createElement('tr');
      
      // Format the price with currency
      const formattedPrice = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
      }).format(request.budget);

      // Create status badge
      const statusClass = {
        'Pending': 'bg-warning',
        'Approved': 'bg-success',
        'Rejected': 'bg-danger',
        'In Review': 'bg-info'
      }[request.status] || 'bg-secondary';

      row.innerHTML = `
        <td>${request.requester}</td>
        <td>${request.department}</td>
        <td>${request.vehicleType.charAt(0).toUpperCase() + request.vehicleType.slice(1)}</td>
        <td>${formattedPrice}</td>
        <td><span class="badge ${statusClass}">${request.status}</span></td>
        <td class="text-nowrap">
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-primary view-request" data-id="${request.id || Date.now()}" title="View">
              <i class="fas fa-eye"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary edit-request" data-id="${request.id || Date.now()}" title="Edit">
              <i class="fas fa-edit"></i>
            </button>
          </div>
        </td>
      `;

      // Add the new row at the beginning of the table
      tbody.insertBefore(row, tbody.firstChild);

      // Add event listeners for the new buttons
      addRequestRowEventListeners(row);

      // Reinitialize table pagination if it exists
      if (window.initializeTablePagination) {
        window.initializeTablePagination();
      }
    }

    // Add event listeners for request row buttons
    function addRequestRowEventListeners(row) {
      // View button click handler
      const viewBtn = row.querySelector('.view-request');
      if (viewBtn) {
        viewBtn.addEventListener('click', function() {
          const requestId = this.getAttribute('data-id');
          viewRequestDetails(requestId);
        });
      }

      // Edit button click handler
      const editBtn = row.querySelector('.edit-request');
      if (editBtn) {
        editBtn.addEventListener('click', function() {
          const requestId = this.getAttribute('data-id');
          editRequest(requestId);
        });
      }
    }

    // View request details in a modal
    function viewRequestDetails(requestId) {
      // In a real app, you would fetch the request details from the server
      console.log('Viewing request:', requestId);
      
      // For demo, we'll create a sample request object
      const request = {
        id: requestId,
        requester: 'Sample Requester',
        department: 'Sample Department',
        vehicleType: 'sedan',
        budget: 1500000,
        justification: 'Sample justification text',
        specifications: 'Sample specifications text',
        status: 'Pending',
        requestDate: new Date().toISOString().split('T')[0]
      };
      
      // Create and show the view modal
      showRequestModal('view', request);
    }

    // Edit request in a modal
    function editRequest(requestId) {
      // In a real app, you would fetch the request details from the server
      console.log('Editing request:', requestId);
      
      // For demo, we'll create a sample request object
      const request = {
        id: requestId,
        requester: 'Sample Requester',
        department: 'Sample Department',
        vehicleType: 'sedan',
        budget: 1500000,
        justification: 'Sample justification text',
        specifications: 'Sample specifications text',
        status: 'Pending',
        requestDate: new Date().toISOString().split('T')[0]
      };
      
      // Create and show the edit modal
      showRequestModal('edit', request);
    }
    
    // Show request modal (view or edit)
    function showRequestModal(mode, request) {
      // Create modal if it doesn't exist
      let modal = document.getElementById('requestModal');
      
      if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'requestModal';
        modal.tabIndex = '-1';
        modal.setAttribute('aria-labelledby', 'requestModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        
        modal.innerHTML = `
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="requestModalBody">
                <!-- Content will be loaded here -->
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary d-none" id="saveChangesBtn">Save changes</button>
              </div>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
      }
      
      // Set modal title and content based on mode
      const modalTitle = modal.querySelector('.modal-title');
      const modalBody = modal.querySelector('.modal-body');
      const saveBtn = modal.querySelector('#saveChangesBtn');
      
      if (mode === 'view') {
        modalTitle.textContent = 'View Vehicle Request';
        saveBtn.classList.add('d-none');
        
        // Format the price
        const formattedPrice = new Intl.NumberFormat('en-PH', {
          style: 'currency',
          currency: 'PHP',
          minimumFractionDigits: 2
        }).format(request.budget);
        
        // Create view content
        modalBody.innerHTML = `
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Requester</label>
              <p>${request.requester || 'N/A'}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Department</label>
              <p>${request.department || 'N/A'}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Vehicle Type</label>
              <p>${(request.vehicleType || '').charAt(0).toUpperCase() + (request.vehicleType || '').slice(1) || 'N/A'}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Estimated Price</label>
              <p>${formattedPrice}</p>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Business Justification</label>
              <p>${request.justification || 'N/A'}</p>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Preferred Specifications</label>
              <p>${request.specifications || 'N/A'}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Status</label>
              <p><span class="badge ${request.status === 'Pending' ? 'bg-warning' : request.status === 'Approved' ? 'bg-success' : 'bg-danger'}">${request.status || 'N/A'}</span></p>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Request Date</label>
              <p>${request.requestDate || 'N/A'}</p>
            </div>
          </div>
        `;
      } else if (mode === 'edit') {
        modalTitle.textContent = 'Edit Vehicle Request';
        saveBtn.classList.remove('d-none');
        
        // Create edit form
        modalBody.innerHTML = `
          <form id="editRequestForm">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="editRequester" class="form-label">Requester</label>
                <input type="text" class="form-control" id="editRequester" value="${request.requester || ''}" required>
              </div>
              <div class="col-md-6">
                <label for="editDepartment" class="form-label">Department</label>
                <input type="text" class="form-control" id="editDepartment" value="${request.department || ''}" required>
              </div>
              <div class="col-md-6">
                <label for="editVehicleType" class="form-label">Vehicle Type</label>
                <select class="form-select" id="editVehicleType" required>
                  <option value="" ${!request.vehicleType ? 'selected' : ''}>Select type</option>
                  <option value="sedan" ${request.vehicleType === 'sedan' ? 'selected' : ''}>Sedan</option>
                  <option value="suv" ${request.vehicleType === 'suv' ? 'selected' : ''}>SUV</option>
                  <option value="van" ${request.vehicleType === 'van' ? 'selected' : ''}>Van</option>
                  <option value="truck" ${request.vehicleType === 'truck' ? 'selected' : ''}>Truck</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="editBudget" class="form-label">Estimated Price</label>
                <input type="number" class="form-control" id="editBudget" value="${request.budget || ''}" required>
              </div>
              <div class="col-12">
                <label for="editJustification" class="form-label">Business Justification</label>
                <textarea class="form-control" id="editJustification" rows="3" required>${request.justification || ''}</textarea>
              </div>
              <div class="col-12">
                <label for="editSpecifications" class="form-label">Preferred Specifications</label>
                <textarea class="form-control" id="editSpecifications" rows="3" required>${request.specifications || ''}</textarea>
              </div>
              <div class="col-md-6">
                <label for="editStatus" class="form-label">Status</label>
                <select class="form-select" id="editStatus" ${request.status === 'Approved' ? 'disabled' : ''}>
                  <option value="Pending" ${request.status === 'Pending' ? 'selected' : ''}>Pending</option>
                  <option value="Approved" ${request.status === 'Approved' ? 'selected' : ''}>Approved</option>
                  <option value="Rejected" ${request.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                </select>
              </div>
            </div>
          </form>
        `;
        
        // Add save button handler
        saveBtn.onclick = function() {
          // Here you would typically save the changes to the server
          const updatedRequest = {
            id: requestId,
            requester: document.getElementById('editRequester').value,
            department: document.getElementById('editDepartment').value,
            vehicleType: document.getElementById('editVehicleType').value,
            budget: parseFloat(document.getElementById('editBudget').value),
            justification: document.getElementById('editJustification').value,
            specifications: document.getElementById('editSpecifications').value,
            status: document.getElementById('editStatus').value
          };
          
          console.log('Saving changes:', updatedRequest);
          
          // In a real app, you would make an API call to update the request
          // For now, we'll just show a success message and close the modal
          showAlert('Request updated successfully!', 'success');
          
          // Close the modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
          modal.hide();
        };
      }
      
      // Initialize and show the modal
      const modalInstance = new bootstrap.Modal(modal);
      modalInstance.show();
    }

    // Show alert message
    function showAlert(message, type = 'info') {
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
      alertDiv.role = 'alert';
      alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      
      // Insert alert before the form
      const container = document.querySelector('.container');
      if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
          const bsAlert = new bootstrap.Alert(alertDiv);
          bsAlert.close();
        }, 5000);
      }
    }

    // Clear validation on input
    form.addEventListener('input', function(e) {
      const target = e.target;
      if (target && target.classList.contains('is-invalid')) {
        clearInvalid(target);
      }
    });

    // Expose form reset function
    window.resetVehicleRequestForm = function() {
      form.reset();
      form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    };
  });
})();
