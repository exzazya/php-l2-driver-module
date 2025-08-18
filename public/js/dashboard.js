// Dashboard page scripts
// Requires Chart.js loaded via CDN in the Blade template

document.addEventListener('DOMContentLoaded', function() {
  initializeDashboard();

  const refreshBtn = document.getElementById('refreshDashboard');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function() {
      refreshDashboardData();
    });
  }

  document.querySelectorAll('[data-period]').forEach(button => {
    button.addEventListener('click', function() {
      document.querySelectorAll('[data-period]').forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
      updateChartData(this.dataset.period);
    });
  });
});

function initializeDashboard() {
  const ctx = document.getElementById('fleetChart').getContext('2d');
  window.fleetChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['6 AM', '9 AM', '12 PM', '3 PM', '6 PM', '9 PM'],
      datasets: [{
        label: 'Active Vehicles',
        data: [12, 19, 24, 22, 18, 15],
        borderColor: 'rgb(30, 58, 138)',
        backgroundColor: 'rgba(30, 58, 138, 0.1)',
        tension: 0.4,
        fill: true
      }, {
        label: 'Completed Trips',
        data: [8, 15, 18, 20, 16, 12],
        borderColor: 'rgb(34, 197, 94)',
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
        x: { grid: { color: 'rgba(0,0,0,0.05)' } }
      }
    }
  });

  updateLastUpdatedTime();
  setInterval(updateLastUpdatedTime, 30000);
}

function refreshDashboardData() {
  const button = document.getElementById('refreshDashboard');
  if (!button) return;
  const originalText = button.innerHTML;

  button.innerHTML = '<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>';
  button.disabled = true;

  setTimeout(() => {
    updateMetrics();
    updateLastUpdatedTime();

    button.innerHTML = originalText;
    button.disabled = false;

    showNotification('Dashboard refreshed successfully!', 'success');
  }, 1500);
}

function updateMetrics() {
  const metrics = {
    totalVehicles: Math.floor(Math.random() * 5) + 26,
    activeAssignments: Math.floor(Math.random() * 8) + 10,
    maintenanceDue: Math.floor(Math.random() * 4) + 4,
    totalDrivers: Math.floor(Math.random() * 10) + 28
  };

  Object.keys(metrics).forEach(key => {
    const element = document.getElementById(key);
    if (element) element.textContent = metrics[key];
  });
}

function updateChartData(period) {
  const data = {
    today: {
      labels: ['6 AM', '9 AM', '12 PM', '3 PM', '6 PM', '9 PM'],
      active: [12, 19, 24, 22, 18, 15],
      completed: [8, 15, 18, 20, 16, 12]
    },
    week: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      active: [20, 22, 25, 23, 24, 18, 16],
      completed: [45, 52, 48, 61, 55, 42, 38]
    },
    month: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      active: [22, 24, 23, 25],
      completed: [180, 195, 210, 205]
    }
  };

  window.fleetChart.data.labels = data[period].labels;
  window.fleetChart.data.datasets[0].data = data[period].active;
  window.fleetChart.data.datasets[1].data = data[period].completed;
  window.fleetChart.update();
}

function updateLastUpdatedTime() {
  const now = new Date();
  const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  const el = document.getElementById('lastUpdated');
  if (el) el.textContent = timeString;
}

function showNotification(message, type) {
  const notification = document.createElement('div');
  notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  notification.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  document.body.appendChild(notification);
  setTimeout(() => notification.remove(), 3000);
}

