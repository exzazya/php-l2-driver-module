// Maintenance page scripts (UI only)

document.addEventListener('DOMContentLoaded', () => {
  const maintenanceSearch = document.getElementById('searchMaintenance');
  if (maintenanceSearch) {
    maintenanceSearch.addEventListener('input', function() {
      // Delegate to unified filter handler
      applyFilters();
    });
  }

  // Add form stub
  const addForm = document.getElementById('addMaintenanceForm');
  if (addForm) {
    addForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const modal = bootstrap.Modal.getInstance(document.getElementById('addMaintenanceModal'));
      if (modal) modal.hide();
      alert('Maintenance added (demo). Connect to backend to persist.');
    });
  }
});

// Filters
const statusFilter = document.getElementById('statusFilter');
const typeFilter = document.getElementById('typeFilter');
function applyFilters() {
  const searchInput = document.getElementById('searchMaintenance');
  const term = (searchInput && searchInput.value.trim().toLowerCase()) || '';
  const statusVal = (statusFilter && statusFilter.value.trim().toLowerCase()) || '';
  const typeVal = (typeFilter && typeFilter.value.trim().toLowerCase()) || '';

  document.querySelectorAll('#maintenanceTable tbody tr').forEach(row => {
    // Column indices based on Blade: 1 Vehicle, 2 Type, 3 Date, 4 Odometer, 5 Cost, 6 Status
    const rowText = row.textContent.toLowerCase();
    const typeCell = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
    const statusCell = row.querySelector('td:nth-child(6)')?.textContent.toLowerCase() || '';

    const searchMatch = !term || rowText.includes(term);
    const typeMatch = !typeVal || typeCell.includes(typeVal.replace(/_/g, ' '));
    const statusMatch = !statusVal || statusCell.includes(statusVal);

    row.style.display = (searchMatch && typeMatch && statusMatch) ? '' : 'none';
  });
}
if (statusFilter) statusFilter.addEventListener('change', applyFilters);
if (typeFilter) typeFilter.addEventListener('change', applyFilters);

// Clear filters (exposed globally to match Blade onclick="clearMaintenanceFilters()")
window.clearMaintenanceFilters = function() {
  const searchInput = document.getElementById('searchMaintenance');
  if (searchInput) searchInput.value = '';
  if (statusFilter) statusFilter.value = '';
  if (typeFilter) typeFilter.value = '';
  applyFilters();
};

