// Assignment Tracking page scripts
// Requires Leaflet loaded via CDN in the Blade template

// Mock data for assignments (UI-only)
const mockAssignments = [
  { id: 1, tripId: 'TRP-001', customerName: 'John Smith', customerPhone: '+1-555-0123', driverName: 'Mike Johnson', driverPhone: '+1-555-0456', vehicleInfo: 'Toyota Camry 2022', licensePlate: 'ABC-123', pickupAddress: '123 Main St, Downtown', pickupLat: 40.7128, pickupLng: -74.0060, destinationAddress: '456 Oak Ave, Uptown', destinationLat: 40.7589, destinationLng: -73.9851, scheduledTime: '2024-01-15 14:30:00', status: 'in_progress', currentLat: 40.7300, currentLng: -74.0000, estimatedArrival: '2024-01-15 15:15:00', fare: 45.50 },
  { id: 2, tripId: 'TRP-002', customerName: 'Sarah Davis', customerPhone: '+1-555-0789', driverName: 'Alex Rodriguez', driverPhone: '+1-555-0321', vehicleInfo: 'Honda Accord 2023', licensePlate: 'XYZ-789', pickupAddress: '789 Pine St, Midtown', pickupLat: 40.7505, pickupLng: -73.9934, destinationAddress: '321 Elm St, Brooklyn', destinationLat: 40.6782, destinationLng: -73.9442, scheduledTime: '2024-01-15 15:00:00', status: 'assigned', currentLat: 40.7505, currentLng: -73.9934, estimatedArrival: '2024-01-15 15:45:00', fare: 38.75 },
  { id: 3, tripId: 'TRP-003', customerName: 'Robert Wilson', customerPhone: '+1-555-0654', driverName: 'Lisa Chen', driverPhone: '+1-555-0987', vehicleInfo: 'Ford Transit 2023', licensePlate: 'DEF-456', pickupAddress: '555 Broadway, Manhattan', pickupLat: 40.7614, pickupLng: -73.9776, destinationAddress: 'JFK Airport Terminal 4', destinationLat: 40.6413, destinationLng: -73.7781, scheduledTime: '2024-01-15 16:30:00', status: 'pending', currentLat: null, currentLng: null, estimatedArrival: '2024-01-15 17:30:00', fare: 65.00 },
  { id: 4, tripId: 'TRP-004', customerName: 'Emily Brown', customerPhone: '+1-555-0147', driverName: 'David Kim', driverPhone: '+1-555-0258', vehicleInfo: 'Mercedes E-Class 2022', licensePlate: 'LUX-001', pickupAddress: '100 Wall St, Financial District', pickupLat: 40.7074, pickupLng: -74.0113, destinationAddress: '200 Central Park West', destinationLat: 40.7829, destinationLng: -73.9654, scheduledTime: '2024-01-15 13:45:00', status: 'completed', currentLat: 40.7829, currentLng:  -73.9654, estimatedArrival: '2024-01-15 14:30:00', fare: 52.25 }
];

let map;
let markers = [];
let isMapVisible = false;

// Table sorting states for both tables
let sortStates = {
  assignmentsTable: { column: null, direction: 'asc' },
  completedAssignmentsTable: { column: null, direction: 'asc' }
};

document.addEventListener('DOMContentLoaded', function() {
  loadAssignments();
  loadCompletedAssignments();
  updateStats();
  initializeEventListeners();
});

function initializeEventListeners() {
  const toggleMapBtn = document.getElementById('toggleMapBtn');
  if (toggleMapBtn) toggleMapBtn.addEventListener('click', toggleMap);

  const refreshBtn = document.getElementById('refreshBtn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function() {
      loadAssignments();
      loadCompletedAssignments();
      updateStats();
      if (isMapVisible) updateMapMarkers();
    });
  }

  const statusFilter = document.getElementById('statusFilter');
  if (statusFilter) statusFilter.addEventListener('change', function() { filterAssignments(this.value); });

  // Enable header click sorting for both tables
  ['assignmentsTable', 'completedAssignmentsTable'].forEach(tableId => {
    const table = document.getElementById(tableId);
    if (!table) return;
    const headers = table.querySelectorAll('thead th');
    headers.forEach((th, index) => {
      th.style.cursor = 'pointer';
      th.addEventListener('click', () => { setSort(tableId, index); });
    });
  });
}

function loadAssignments() {
  const tbody = document.getElementById('assignmentsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  // Only non-completed in current table
  let data = mockAssignments.filter(a => a.status !== 'completed');
  // Sort data before rendering
  const st = sortStates.assignmentsTable;
  if (st.column !== null) {
    const cmp = getComparator(st.column);
    data.sort(cmp);
    if (st.direction === 'desc') data.reverse();
  }
  data.forEach(assignment => tbody.appendChild(createAssignmentRow(assignment)));
}

function loadCompletedAssignments() {
  const tbody = document.getElementById('completedAssignmentsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  // Only completed in completed table
  let data = mockAssignments.filter(a => a.status === 'completed');
  const st = sortStates.completedAssignmentsTable;
  if (st.column !== null) {
    const cmp = getComparator(st.column);
    data.sort(cmp);
    if (st.direction === 'desc') data.reverse();
  }
  data.forEach(assignment => tbody.appendChild(createAssignmentRow(assignment)));
}

function createAssignmentRow(assignment) {
  const row = document.createElement('tr');
  const statusClass = getStatusClass(assignment.status);
  const statusText = assignment.status.replace('_', ' ').toUpperCase();
  row.innerHTML = `
    <td><div class="trip-info"><h6 class="mb-0 text-primary fw-bold">${assignment.tripId}</h6></div></td>
    <td><div class="customer-info"><h6 class="mb-0">${assignment.customerName}</h6><small class="text-muted">${assignment.customerPhone}</small></div></td>
    <td><div class="driver-info"><h6 class="mb-0">${assignment.driverName}</h6><small class="text-muted">${assignment.driverPhone}</small></div></td>
    <td><div class="vehicle-info"><h6 class="mb-0">${assignment.vehicleInfo}</h6><span class="badge bg-light text-dark">${assignment.licensePlate}</span></div></td>
    <td><div class="location-full"><strong>Pickup:</strong><br>${assignment.pickupAddress}<br><strong class="mt-2 d-block">Destination:</strong><br>${assignment.destinationAddress}</div></td>
    <td><div class="time-details"><strong>Scheduled:</strong><br>${formatDateTime(assignment.scheduledTime)}<br><strong class="mt-2 d-block">ETA:</strong><br>${formatDateTime(assignment.estimatedArrival)}<br><small class="text-success"><strong>Fare: $${assignment.fare}</strong></small></div></td>
    <td><span class="badge status-badge ${statusClass}">${statusText}</span></td>
    <td>
      <div class="btn-group" role="group">
        ${assignment.currentLat && assignment.currentLng ? `<button type="button" class="btn btn-sm btn-outline-success" onclick="trackVehicle(${assignment.id})" title="Track Vehicle"><i class="fas fa-map-marker-alt"></i></button>` : ''}
        <button type="button" class="btn btn-sm btn-outline-info" onclick="callCustomer('${assignment.customerPhone}')" title="Call Customer"><i class="fas fa-phone"></i></button>
      </div>
    </td>`;
  return row;
}

// Set sort column/direction and rerender
function setSort(tableId, index) {
  const state = sortStates[tableId];
  if (!state) return;
  if (state.column === index) {
    state.direction = state.direction === 'asc' ? 'desc' : 'asc';
  } else {
    state.column = index;
    state.direction = 'asc';
  }
  if (tableId === 'assignmentsTable') {
    loadAssignments();
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) filterAssignments(statusFilter.value);
  } else if (tableId === 'completedAssignmentsTable') {
    loadCompletedAssignments();
  }
}

// Get comparator for column index
function getComparator(index) {
  const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
  return (a, b) => {
    let va, vb;
    switch (index) {
      case 0: va = a.tripId; vb = b.tripId; break;
      case 1: va = a.customerName; vb = b.customerName; break;
      case 2: va = a.driverName; vb = b.driverName; break;
      case 3: va = a.vehicleInfo; vb = b.vehicleInfo; break;
      case 4: va = a.pickupAddress; vb = b.pickupAddress; break;
      case 5: // Schedule & Fare -> sort by scheduledTime
        return new Date(a.scheduledTime) - new Date(b.scheduledTime);
      case 6: va = a.status; vb = b.status; break;
      default: va = a.tripId; vb = b.tripId; break;
    }
    return collator.compare(String(va ?? ''), String(vb ?? ''));
  };
}

function getStatusClass(status) {
  const statusClasses = { pending: 'bg-warning', assigned: 'bg-info', in_progress: 'bg-primary', completed: 'bg-success', cancelled: 'bg-danger' };
  return statusClasses[status] || 'bg-secondary';
}

function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function updateStats() {
  const stats = {
    active: mockAssignments.filter(a => ['assigned', 'in_progress'].includes(a.status)).length,
    inProgress: mockAssignments.filter(a => a.status === 'in_progress').length,
    pending: mockAssignments.filter(a => a.status === 'pending').length,
    completed: mockAssignments.filter(a => a.status === 'completed').length
  };
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('activeCount', stats.active); set('inProgressCount', stats.inProgress); set('pendingCount', stats.pending); set('completedCount', stats.completed);
}

function filterAssignments(status) {
  const rows = document.querySelectorAll('#assignmentsTableBody tr');
  rows.forEach(row => {
    if (!status) { row.style.display = ''; return; }
    const badge = row.querySelector('.status-badge');
    const rowStatus = (badge?.textContent || '').toLowerCase().replace(' ', '_');
    row.style.display = rowStatus === status ? '' : 'none';
  });
}

function toggleMap() {
  const mapContainer = document.getElementById('mapContainer');
  const toggleBtn = document.getElementById('toggleMapBtn');
  if (!mapContainer || !toggleBtn) return;
  if (isMapVisible) {
    mapContainer.style.display = 'none';
    toggleBtn.innerHTML = '<i class="fas fa-map me-2"></i>Show Map';
    isMapVisible = false;
  } else {
    mapContainer.style.display = 'block';
    toggleBtn.innerHTML = '<i class="fas fa-map me-2"></i>Hide Map';
    isMapVisible = true;
    if (!map) initializeMap(); else updateMapMarkers();
  }
}

function initializeMap() {
  map = L.map('map').setView([40.7128, -74.0060], 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
  updateMapMarkers();
}

function updateMapMarkers() {
  if (!map) return;
  markers.forEach(marker => map.removeLayer(marker));
  markers = [];
  mockAssignments.forEach(assignment => {
    if (assignment.pickupLat && assignment.pickupLng) {
      const pickupMarker = L.marker([assignment.pickupLat, assignment.pickupLng], { icon: L.divIcon({ className: 'custom-marker pickup-marker', html: '<i class="fas fa-map-marker-alt text-success"></i>', iconSize: [20,20] }) }).addTo(map);
      pickupMarker.bindPopup(`<strong>Pickup: ${assignment.tripId}</strong><br>Customer: ${assignment.customerName}<br>Address: ${assignment.pickupAddress}<br>Time: ${formatDateTime(assignment.scheduledTime)}`);
      markers.push(pickupMarker);
    }
    if (assignment.destinationLat && assignment.destinationLng) {
      const destMarker = L.marker([assignment.destinationLat, assignment.destinationLng], { icon: L.divIcon({ className: 'custom-marker destination-marker', html: '<i class="fas fa-flag text-danger"></i>', iconSize: [20,20] }) }).addTo(map);
      destMarker.bindPopup(`<strong>Destination: ${assignment.tripId}</strong><br>Customer: ${assignment.customerName}<br>Address: ${assignment.destinationAddress}`);
      markers.push(destMarker);
    }
    if (assignment.currentLat && assignment.currentLng && assignment.status === 'in_progress') {
      const vehicleMarker = L.marker([assignment.currentLat, assignment.currentLng], { icon: L.divIcon({ className: 'custom-marker vehicle-marker pulse', html: '<i class="fas fa-car text-primary"></i>', iconSize: [25,25] }) }).addTo(map);
      vehicleMarker.bindPopup(`<strong>Vehicle: ${assignment.vehicleInfo}</strong><br>Driver: ${assignment.driverName}<br>Trip: ${assignment.tripId}<br>Status: ${assignment.status.replace('_', ' ').toUpperCase()}`);
      markers.push(vehicleMarker);
    }
  });
}

function trackVehicle(assignmentId) {
  const assignment = mockAssignments.find(a => a.id === assignmentId);
  if (!assignment || !assignment.currentLat || !assignment.currentLng) { alert('Vehicle location not available'); return; }
  if (!isMapVisible) toggleMap();
  setTimeout(() => {
    if (!map) return;
    map.setView([assignment.currentLat, assignment.currentLng], 15);
    markers.forEach(marker => {
      const p = marker.getLatLng();
      if (Math.abs(p.lat - assignment.currentLat) < 0.001 && Math.abs(p.lng - assignment.currentLng) < 0.001) marker.openPopup();
    });
  }, 500);
}

function callCustomer(phoneNumber) {
  if (confirm(`Call customer at ${phoneNumber}?`)) window.open(`tel:${phoneNumber}`);
}

// Simulated periodic updates
setInterval(() => {
  mockAssignments.forEach(a => {
    if (a.status === 'in_progress' && a.currentLat && a.currentLng) {
      a.currentLat += (Math.random() - 0.5) * 0.001;
      a.currentLng += (Math.random() - 0.5) * 0.001;
    }
  });
  if (isMapVisible && map) updateMapMarkers();
}, 10000);

setInterval(() => { updateStats(); }, 30000);

