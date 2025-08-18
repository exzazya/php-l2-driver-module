// Sample driver data (in a real app, this would come from an API)
const drivers = [
    {
        id: 1,
        name: 'John Smith',
        phone: '+1 (555) 123-4567',
        email: 'john.smith@example.com',
        license: 'DL12345678',
        vehicle: 'Toyota Camry (ABC-123)',
        status: 'active',
        joinDate: '2023-05-15',
        photo: 'img/profile-placeholder.png'
    },
    {
        id: 2,
        name: 'Maria Garcia',
        phone: '+1 (555) 234-5678',
        email: 'maria.garcia@example.com',
        license: 'DL23456789',
        vehicle: 'Honda Accord (XYZ-456)',
        status: 'on-leave',
        joinDate: '2023-02-20',
        photo: 'img/profile-placeholder.png'
    },
    {
        id: 3,
        name: 'David Kim',
        phone: '+1 (555) 345-6789',
        email: 'david.kim@example.com',
        license: 'DL34567890',
        vehicle: 'Ford Transit (DEF-789)',
        status: 'active',
        joinDate: '2023-07-10',
        photo: 'img/profile-placeholder.png'
    },
    {
        id: 4,
        name: 'Sarah Johnson',
        phone: '+1 (555) 456-7890',
        email: 'sarah.j@example.com',
        license: 'DL45678901',
        vehicle: 'Chevrolet Suburban (GHI-012)',
        status: 'inactive',
        joinDate: '2022-11-05',
        photo: 'img/profile-placeholder.png'
    }
];

// DOM Elements
const driversGrid = document.getElementById('driversGrid');
const driverSearch = document.getElementById('driverSearch');
const statusFilter = document.getElementById('statusFilter');
const sortBy = document.getElementById('sortBy');
const addDriverForm = document.getElementById('addDriverForm');
const driverDetailsModal = new bootstrap.Modal(document.getElementById('driverDetailsModal'));

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    renderDrivers(drivers);
    setupEventListeners();
});

// Set up event listeners
function setupEventListeners() {
    // Search functionality
    if (driverSearch) {
        driverSearch.addEventListener('input', filterAndSortDrivers);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', filterAndSortDrivers);
    }
    
    if (sortBy) {
        sortBy.addEventListener('change', filterAndSortDrivers);
    }
    
    // Form submission
    if (addDriverForm) {
        addDriverForm.addEventListener('submit', handleAddDriver);
    }
    
    // Edit button in details modal
    const editDriverBtn = document.getElementById('editDriverBtn');
    if (editDriverBtn) {
        editDriverBtn.addEventListener('click', () => {
            const driverId = parseInt(editDriverBtn.getAttribute('data-driver-id'));
            if (driverId) {
                editDriver(driverId);
            }
        });
    }
}

// Filter and sort drivers based on search, filter, and sort criteria
function filterAndSortDrivers() {
    const searchTerm = driverSearch ? driverSearch.value.toLowerCase() : '';
    const status = statusFilter ? statusFilter.value : '';
    const sortValue = sortBy ? sortBy.value : 'name_asc';
    
    let filteredDrivers = [...drivers];
    
    // Apply search filter
    if (searchTerm) {
        filteredDrivers = filteredDrivers.filter(driver => 
            (driver.name && driver.name.toLowerCase().includes(searchTerm)) ||
            (driver.phone && driver.phone.includes(searchTerm)) ||
            (driver.email && driver.email.toLowerCase().includes(searchTerm)) ||
            (driver.license && driver.license.toLowerCase().includes(searchTerm)) ||
            (driver.vehicle && driver.vehicle.toLowerCase().includes(searchTerm))
        );
    }
    
    // Apply status filter
    if (status) {
        filteredDrivers = filteredDrivers.filter(driver => driver.status === status);
    }
    
    // Apply sorting
    filteredDrivers.sort((a, b) => {
        switch (sortValue) {
            case 'name_asc':
                return (a.name || '').localeCompare(b.name || '');
            case 'name_desc':
                return (b.name || '').localeCompare(a.name || '');
            case 'latest':
                return new Date(b.joinDate) - new Date(a.joinDate);
            case 'oldest':
                return new Date(a.joinDate) - new Date(b.joinDate);
            default:
                return 0;
        }
    });
    
    renderDrivers(filteredDrivers);
}

// Helper function to get asset URL
function asset(path) {
    const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/public/' + path.replace(/^\//, '');
}

// Render drivers to the grid
function renderDrivers(driversToRender) {
    if (!driversGrid) return;
    
    if (!driversToRender || driversToRender.length === 0) {
        driversGrid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-user-slash fa-3x mb-3 text-muted"></i>
                <h4>No drivers found</h4>
                <p class="text-muted">Try adjusting your search or filter criteria</p>
            </div>
        `;
        return;
    }
    
    driversGrid.innerHTML = driversToRender.map(driver => `
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card driver-card h-100 position-relative">
                <div class="card-header bg-primary text-white text-center py-4">
                    <!-- Empty space for the photo -->
                </div>
                <div class="card-body text-center pt-5">
                    <img src="${driver.photo ? asset(driver.photo) : asset('img/profile-placeholder.png')}" 
                         alt="${driver.name || 'Driver'}" 
                         class="driver-photo"
                         onerror="this.onerror=null; this.src='${asset('img/profile-placeholder.png')}'">
                    
                    <div class="driver-status status-${driver.status || 'inactive'}" 
                         title="${formatStatus(driver.status || 'inactive')}"></div>
                    
                    <div class="driver-actions">
                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                onclick="viewDriverDetails(${driver.id})"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                onclick="editDriver(${driver.id})"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteDriver(${driver.id})"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    
                    <h5 class="card-title mb-1">${driver.name || 'Unnamed Driver'}</h5>
                    <p class="text-muted mb-2">${driver.license || 'No license'}</p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        ${driver.phone ? `
                        <a href="tel:${driver.phone}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-phone-alt me-1"></i> Call
                        </a>` : ''}
                        
                        ${driver.email ? `
                        <a href="mailto:${driver.email}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-envelope me-1"></i> Email
                        </a>` : ''}
                    </div>
                    
                    <div class="text-start small">
                        ${driver.vehicle ? `
                        <div class="d-flex mb-1">
                            <span class="text-muted flex-shrink-0" style="width: 80px;">Vehicle:</span>
                            <span class="text-truncate">${driver.vehicle}</span>
                        </div>` : ''}
                        
                        ${driver.joinDate ? `
                        <div class="d-flex">
                            <span class="text-muted flex-shrink-0" style="width: 80px;">Joined:</span>
                            <span>${formatDate(driver.joinDate)}</span>
                        </div>` : ''}
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-${getStatusBadgeClass(driver.status || 'inactive')}">
                            ${formatStatus(driver.status || 'inactive')}
                        </span>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewDriverDetails(${driver.id})">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

// View driver details in modal
function viewDriverDetails(driverId) {
    const driver = drivers.find(d => d.id === driverId);
    if (!driver) return;
    
    const modalContent = document.getElementById('driverDetailsContent');
    const editBtn = document.getElementById('editDriverBtn');
    
    if (!modalContent) return;
    
    if (editBtn) {
        editBtn.setAttribute('data-driver-id', driver.id);
    }
    
    modalContent.innerHTML = `
        <div class="text-center mb-4">
            <img src="${driver.photo ? asset(driver.photo) : asset('img/profile-placeholder.png')}" 
                 alt="${driver.name || 'Driver'}" 
                 class="img-thumbnail rounded-circle mb-3" 
                 style="width: 120px; height: 120px; object-fit: cover;"
                 onerror="this.onerror=null; this.src='${asset('img/profile-placeholder.png')}'">
            <h4>${driver.name || 'Unnamed Driver'}</h4>
            ${driver.license ? `<p class="text-muted">${driver.license}</p>` : ''}
            <span class="badge bg-${getStatusBadgeClass(driver.status || 'inactive')} mb-3">
                ${formatStatus(driver.status || 'inactive')}
            </span>
        </div>
        
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Contact Information</h6>
                    </div>
                    <div class="card-body">
                        ${driver.phone ? `<p class="mb-2"><i class="fas fa-phone-alt me-2 text-muted"></i> ${driver.phone}</p>` : ''}
                        ${driver.email ? `<p class="mb-2"><i class="fas fa-envelope me-2 text-muted"></i> ${driver.email}</p>` : ''}
                        ${driver.joinDate ? `<p class="mb-0"><i class="fas fa-calendar-alt me-2 text-muted"></i> Joined ${formatDate(driver.joinDate, true)}</p>` : ''}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Vehicle Information</h6>
                    </div>
                    <div class="card-body">
                        ${driver.vehicle ? `<p class="mb-2"><i class="fas fa-car me-2 text-muted"></i> ${driver.vehicle}</p>` : ''}
                        ${driver.license ? `<p class="mb-0"><i class="fas fa-id-card me-2 text-muted"></i> ${driver.license}</p>` : ''}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h6>Recent Activity</h6>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Activity history would be displayed here in a real application.
            </div>
        </div>
    `;
    
    driverDetailsModal.show();
}

// Handle add driver form submission
function handleAddDriver(e) {
    e.preventDefault();
    
    // In a real app, this would collect form data and send to your backend
    const formData = new FormData(e.target);
    const newDriver = {
        id: Math.max(0, ...drivers.map(d => d.id)) + 1, // Generate new ID
        name: formData.get('name') || 'New Driver',
        phone: formData.get('phone') || '',
        email: formData.get('email') || '',
        license: formData.get('license') || '',
        vehicle: formData.get('vehicle') || '',
        status: 'active',
        joinDate: new Date().toISOString().split('T')[0],
        photo: 'img/profile-placeholder.png'
    };
    
    // Add to our array (in a real app, this would be an API call)
    drivers.unshift(newDriver);
    
    // Update the UI
    filterAndSortDrivers();
    
    // Show success message
    showAlert('Driver added successfully!', 'success');
    
    // Close the modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addDriverModal'));
    if (modal) {
        modal.hide();
    }
    
    // Reset the form
    e.target.reset();
}

// Edit driver
function editDriver(driverId) {
    const driver = drivers.find(d => d.id === driverId);
    if (!driver) return;
    
    // In a real app, this would open an edit form or switch to edit mode
    // For now, we'll just show an alert with the driver's name
    showAlert(`Editing driver: ${driver.name}`, 'info');
    
    // You could open a modal with a form pre-filled with the driver's data
    // For example:
    // openEditModal(driver);
}

// Delete driver
function deleteDriver(driverId, confirmDelete = false) {
    const driver = drivers.find(d => d.id === driverId);
    if (!driver) return;
    
    if (!confirmDelete) {
        if (confirm(`Are you sure you want to delete ${driver.name || 'this driver'}?`)) {
            deleteDriver(driverId, true);
        }
        return;
    }
    
    // In a real app, this would be an API call
    const index = drivers.findIndex(d => d.id === driverId);
    if (index > -1) {
        drivers.splice(index, 1);
        filterAndSortDrivers();
        showAlert('Driver deleted successfully!', 'success');
    }
}

// Helper function to format status
function formatStatus(status) {
    if (!status) return 'Inactive';
    return status
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// Helper function to format date
function formatDate(dateString, includeTime = false) {
    if (!dateString) return 'N/A';
    
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        ...(includeTime ? { 
            hour: '2-digit', 
            minute: '2-digit' 
        } : {})
    };
    
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Get Bootstrap badge class based on status
function getStatusBadgeClass(status) {
    if (!status) return 'secondary';
    
    const statusClasses = {
        'active': 'success',
        'inactive': 'danger',
        'on-leave': 'warning'
    };
    
    return statusClasses[status] || 'secondary';
}

// Show alert message
function showAlert(message, type = 'info') {
    // In a real app, you might use a toast notification library
    alert(`${type.toUpperCase()}: ${message}`);
}

// Make functions available globally for HTML onclick attributes
window.viewDriverDetails = viewDriverDetails;
window.editDriver = editDriver;
window.deleteDriver = deleteDriver;