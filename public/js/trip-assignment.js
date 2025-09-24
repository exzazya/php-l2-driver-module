document.addEventListener('DOMContentLoaded', () => {
  const assignmentsTbody = document.querySelector('#assignmentsTable tbody');
  const completedTbody = document.querySelector('#completedAssignmentsTable tbody');
  const refreshBtn = document.getElementById('refreshAssignments');

  if (refreshBtn) refreshBtn.addEventListener('click', () => loadAll());
  loadAll();

  async function loadAll() {
    setTablePlaceholders();
    const [pending, accepted, completed] = await Promise.all([
      fetchAssignments('pending'),
      fetchAssignments('accepted'),
      fetchAssignments('completed')
    ]);

    renderCurrent([...pending, ...accepted]);
    renderCompleted([...completed]);
    updateStats({
      pending: pending.length,
      active: accepted.length,
      completed: completed.length
    });
  }

  async function fetchAssignments(status) {
    try {
      const res = await fetch(`api/assignments.php?status=${encodeURIComponent(status)}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (!res.ok || data?.success === false) return [];
      const rows = data?.data?.assignments || [];
      return Array.isArray(rows) ? rows : [];
    } catch (e) {
      console.error('Failed to load assignments', e);
      return [];
    }
  }

  function setTablePlaceholders() {
    if (assignmentsTbody) assignmentsTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>`;
    if (completedTbody) completedTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>`;
  }

  function renderCurrent(items) {
    if (!assignmentsTbody) return;
    if (!items.length) {
      assignmentsTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">No assignments available.</td></tr>`;
      document.querySelector('.card.main-card .badge.bg-primary').textContent = '0';
      return;
    }
    assignmentsTbody.innerHTML = '';
    for (const a of items) {
      const tr = document.createElement('tr');
      const tripLabel = `Trip #${a.trip_id || a.id || ''}`;
      const vehicle = a.vehicle_label || a.plate_number || `#${a.vehicle_id ?? ''}`;
      const driver = a.driver_name || a.driver_full_name || '';
      const customer = a.customer_name || a.customer_full_name || '';
      const route = `${a.start_location || ''} → ${a.destination || ''}`;
      const sched = a.pickup_time || a.scheduled_at || a.created_at || '';
      const fare = a.fare_amount ? `₱${Number(a.fare_amount).toLocaleString()}` : '';
      const status = (a.status || '').toLowerCase();

      tr.innerHTML = `
        <td><div class="fw-semibold text-primary">${escapeHtml(tripLabel)}</div><small class="text-muted">${escapeHtml(customer)}</small></td>
        <td><div>${escapeHtml(vehicle)}</div><small class="text-muted">${escapeHtml(driver)}</small></td>
        <td><div class="text-truncate" style="max-width:260px">${escapeHtml(route)}</div></td>
        <td><div>${escapeHtml(formatDateTime(sched))}</div><small class="text-success">${escapeHtml(fare)}</small></td>
        <td>${renderStatusControls(a)}</td>
      `;

      // Wire buttons if present
      tr.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        if (action === 'accept') acceptAssignment(a);
        if (action === 'decline') declineAssignment(a);
      });

      assignmentsTbody.appendChild(tr);
    }
    document.querySelector('.card.main-card .badge.bg-primary').textContent = String(items.length);
  }

  function renderCompleted(items) {
    if (!completedTbody) return;
    if (!items.length) {
      completedTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">No completed assignments available.</td></tr>`;
      document.querySelector('.card.main-card.mt-4 .badge.bg-success').textContent = '0';
      return;
    }
    completedTbody.innerHTML = '';
    for (const a of items) {
      const tr = document.createElement('tr');
      const tripLabel = `Trip #${a.trip_id || a.id || ''}`;
      const vehicle = a.vehicle_label || a.plate_number || `#${a.vehicle_id ?? ''}`;
      const driver = a.driver_name || a.driver_full_name || '';
      const route = `${a.start_location || ''} → ${a.destination || ''}`;
      const sched = a.pickup_time || a.scheduled_at || a.created_at || '';
      tr.innerHTML = `
        <td><div class="fw-semibold text-primary">${escapeHtml(tripLabel)}</div></td>
        <td><div>${escapeHtml(vehicle)}</div><small class="text-muted">${escapeHtml(driver)}</small></td>
        <td><div class="text-truncate" style="max-width:260px">${escapeHtml(route)}</div></td>
        <td>${escapeHtml(formatDateTime(sched))}</td>
        <td><span class="badge bg-success">Completed</span></td>
      `;
      completedTbody.appendChild(tr);
    }
    document.querySelector('.card.main-card.mt-4 .badge.bg-success').textContent = String(items.length);
  }

  function renderStatusControls(a) {
    const status = (a.status || '').toLowerCase();
    if (status === 'accepted' || a.is_accepted == 1) {
      return `<span class="badge bg-info">Active</span>`;
    }
    if (a.is_declined == 1 || status === 'declined') {
      return `<span class="badge bg-secondary">Declined</span>`;
    }
    // Pending -> show Accept/Decline
    return `
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-success" data-action="accept"><i class="fas fa-check me-1"></i>Accept</button>
        <button type="button" class="btn btn-outline-danger" data-action="decline"><i class="fas fa-times me-1"></i>Decline</button>
      </div>
    `;
  }

  async function acceptAssignment(a) {
    const tripId = a.trip_id || a.id;
    if (!tripId) return;
    const btns = document.querySelectorAll('button[data-action]');
    btns.forEach(b => b.disabled = true);
    try {
      const res = await fetch('api/assignments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'accept', trip_id: tripId })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.success === false) throw new Error(data?.message || 'Failed to accept');
      // Redirect to live tracking with trip_id
      window.location.href = `index.php?route=live-tracking&trip_id=${encodeURIComponent(tripId)}`;
    } catch (e) {
      alert(`Accept failed: ${e.message}`);
      await loadAll();
    } finally {
      btns.forEach(b => b.disabled = false);
    }
  }

  async function declineAssignment(a) {
    const tripId = a.trip_id || a.id;
    if (!tripId) return;
    const reason = prompt('Reason for declining? (optional)') || null;
    const btns = document.querySelectorAll('button[data-action]');
    btns.forEach(b => b.disabled = true);
    try {
      const res = await fetch('api/assignments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'decline', trip_id: tripId, decline_reason: reason })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data?.success === false) throw new Error(data?.message || 'Failed to decline');
      await loadAll();
    } catch (e) {
      alert(`Decline failed: ${e.message}`);
      await loadAll();
    } finally {
      btns.forEach(b => b.disabled = false);
    }
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function formatDateTime(v) {
    if (!v) return '';
    const d = new Date(v);
    if (isNaN(d)) return String(v);
    return d.toLocaleString();
  }

  function updateStats(stats) {
    document.getElementById("pendingCount").innerText = stats.pending;
    document.getElementById("activeCount").innerText = stats.active;
    document.getElementById("completedCount").innerText = stats.completed;
  }
});