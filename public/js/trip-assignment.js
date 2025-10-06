document.addEventListener('DOMContentLoaded', () => {
  const assignmentsTbody = document.querySelector('#assignmentsTable tbody');
  const completedTbody = document.querySelector('#completedAssignmentsTable tbody');
  const declinedTbody = document.querySelector('#declinedAssignmentsTable tbody');
  const refreshBtn = document.getElementById('refreshAssignments');

  if (refreshBtn) refreshBtn.addEventListener('click', () => loadAll());
  loadAll();

  async function loadAll() {
    setTablePlaceholders();
    const [pending, accepted, completed, declined] = await Promise.all([
      fetchAssignments('pending'),
      fetchAssignments('accepted'),
      fetchAssignments('completed'),
      fetchAssignments('declined')
    ]);

    renderCurrent([...pending, ...accepted]);
    renderCompleted([...completed]);
    renderDeclined([...declined]);
    updateStats({
      pending: pending.length,
      active: accepted.length,
      completed: completed.length
    });
  }

  async function fetchAssignments(status) {
    try {
      const url = (window.publicUrl ? window.publicUrl(`api/assignments.php?status=${encodeURIComponent(status)}`) : `api/assignments.php?status=${encodeURIComponent(status)}`);
      const res = await fetch(url, {
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
    if (declinedTbody) declinedTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>`;
  }

  function renderCurrent(items) {
    if (!assignmentsTbody) return;
    if (!items.length) {
      assignmentsTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">No assignments available.</td></tr>`;
      const badge0 = document.querySelector('.current-card .badge.bg-primary');
      if (badge0) badge0.textContent = '0';
      return;
    }
    assignmentsTbody.innerHTML = '';
    for (const a of items) {
      const tr = document.createElement('tr');
      const tripId = a.trip_id || a.id || '';
      const tripLabel = `Trip #${tripId}`;
      const vehicle = a.vehicle_label || a.plate_number || `#${a.vehicle_id ?? ''}`;
      const driver = a.driver_name || a.driver_full_name || '';
      const customer = a.customer_name || a.customer_full_name || '';
      const route = `${a.start_location || ''} → ${a.destination || ''}`;
      const sched = a.pickup_time || a.scheduled_at || a.created_at || '';
      const fare = a.fare_amount ? `₱${Number(a.fare_amount).toLocaleString()}` : '';
      const status = (a.status || '').toLowerCase();

      tr.innerHTML = `
        <td><div class="fw-semibold text-primary"><a href="#" class="trip-link">${escapeHtml(tripLabel)}</a></div><small class="text-muted">${escapeHtml(customer)}</small></td>
        <td><div>${escapeHtml(vehicle)}</div><small class="text-muted">${escapeHtml(driver)}</small></td>
        <td><div class="text-truncate" style="max-width:260px">${escapeHtml(route)}</div></td>
        <td><div>${escapeHtml(formatDateTime(sched))}</div><small class="text-success">${escapeHtml(fare)}</small></td>
        <td>${renderStatusControls(a)}</td>
      `;

      // Wire buttons if present
      tr.addEventListener('click', (e) => {
        const link = e.target.closest('.trip-link');
        if (link) { e.preventDefault(); if (tripId) goToLiveTracking(tripId); return; }
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        if (action === 'accept') acceptAssignment(a);
        if (action === 'decline') declineAssignment(a);
      });

      assignmentsTbody.appendChild(tr);
    }
    const badge = document.querySelector('.current-card .badge.bg-primary');
    if (badge) badge.textContent = String(items.length);
  }

  function goToLiveTracking(tripId) {
    const rel = `index.php?route=live-tracking&trip_id=${tripId}`;
    // Preferred: build via pageUrl (preserves BASE_URL + /public)
    const built = (window.pageUrl ? window.pageUrl(rel) : (window.BASE_URL ? (String(window.BASE_URL).replace(/\/+$/,'') + '/' + rel) : rel));
    const url = (/^https?:/i.test(built)) ? built : (window.location.origin + (built.startsWith('/') ? '' : '/') + built);
    try { console.debug('[trip-assignment] redirect', { rel, built, url, BASE_URL: window.BASE_URL }); } catch (_) {}
    // Try simple navigation
    try { window.location.assign(url); return; } catch (_) {}
    try { window.location.href = url; return; } catch (_) {}
    try { window.top.location.assign(url); return; } catch (_) {}
    try { window.top.location.href = url; return; } catch (_) {}
    // Anchor fallback
    try {
      const a = document.createElement('a');
      a.href = url; a.target = '_self'; a.rel = 'noopener';
      document.body.appendChild(a); a.click(); setTimeout(() => { try { a.remove(); } catch(_) {} }, 300);
      return;
    } catch (_) {}
    // Form submit fallback
    try {
      const form = document.createElement('form');
      form.method = 'GET';
      form.action = (window.pageUrl ? window.pageUrl('index.php') : 'index.php');
      const r = document.createElement('input'); r.type = 'hidden'; r.name = 'route'; r.value = 'live-tracking'; form.appendChild(r);
      const t = document.createElement('input'); t.type = 'hidden'; t.name = 'trip_id'; t.value = String(tripId); form.appendChild(t);
      document.body.appendChild(form);
      form.submit();
      return;
    } catch (_) {}
    // safety fallback
    setTimeout(() => { try { window.location.replace(url); } catch (_) {} }, 150);
  }

  function renderCompleted(items) {
    if (!completedTbody) return;
    if (!items.length) {
      completedTbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">No completed assignments available.</td></tr>`;
      const badge0 = document.querySelector('.completed-card .badge.bg-success');
      if (badge0) badge0.textContent = '0';
      return;
    }
    completedTbody.innerHTML = '';
    for (const a of items) {
      const tr = document.createElement('tr');
      const tripId = a.trip_id || a.id || '';
      const tripLabel = `Trip #${tripId}`;
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
    const badge = document.querySelector('.completed-card .badge.bg-success');
    if (badge) badge.textContent = String(items.length);
  }

  function renderDeclined(items) {
    if (!declinedTbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      declinedTbody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">No declined assignments.</td></tr>';
      const badge0 = document.querySelector('.declined-card .badge.bg-secondary');
      if (badge0) badge0.textContent = '0';
      return;
    }
    declinedTbody.innerHTML = '';
    const currentDriverId = Number(window.CURRENT_DRIVER_ID || 0);
    for (const a of items) {
      const tr = document.createElement('tr');
      const tripLabel = `Trip #${a.trip_id || a.id || ''}`;
      const vehicle = a.vehicle_label || a.plate_number || (a.vehicle_id ? `#${a.vehicle_id}` : '—');
      const driver = a.driver_name || a.driver_full_name || '';
      const route = `${a.start_location || ''} → ${a.destination || ''}`;
      const sched = a.pickup_time || a.scheduled_at || a.created_at || a.start_datetime || '';

      const statusLower = String(a.status || '').toLowerCase();
      const tripDriverId = (a.driver_id != null) ? Number(a.driver_id) : 0;
      const isReassigned = tripDriverId > 0 && currentDriverId > 0 && tripDriverId !== currentDriverId && (
        ['assigned','accepted','in_progress','en_route','arrived','ongoing','active','scheduled'].includes(statusLower)
      );

      const statusHtml =
        '<span class="badge bg-secondary">Declined</span>' +
        (isReassigned ? ' <span class="badge bg-warning text-dark ms-1">Reassigned</span>' : '');

      tr.innerHTML = `
        <td><div class="fw-semibold text-primary">${escapeHtml(tripLabel)}</div></td>
        <td><div>${escapeHtml(vehicle)}</div><small class="text-muted">${escapeHtml(driver)}</small></td>
        <td><div class="text-truncate" style="max-width:260px">${escapeHtml(route)}</div></td>
        <td>${escapeHtml(formatDateTime(sched))}</td>
        <td>${statusHtml}</td>
      `;
      declinedTbody.appendChild(tr);
    }
    const badge = document.querySelector('.declined-card .badge.bg-secondary');
    if (badge) badge.textContent = String(items.length);
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
      const apiUrl = (window.publicUrl ? window.publicUrl('api/assignments.php') : 'api/assignments.php');
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'accept', trip_id: tripId })
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data?.success !== false) {
        // Two-phase flow: only accepted. Redirect to Live Tracking.
        try { showAlert('success', 'Trip accepted', 'Redirecting to live tracking...'); } catch (_) {}
        const payload = data && data.data ? data.data : {};
        const openId = payload.trip_id || tripId;
        goToLiveTracking(openId);
        return;
      } else {
        // If server says you already have an ongoing trip, redirect to the current accepted one
        const msg = (data && data.message) ? String(data.message) : '';
        if (res.status === 409 || /ongoing trip/i.test(msg)) {
          const listUrl = (window.publicUrl ? window.publicUrl('api/assignments.php?status=accepted') : 'api/assignments.php?status=accepted');
          try {
            const r2 = await fetch(listUrl, { credentials: 'same-origin' });
            const d2 = await r2.json().catch(() => ({}));
            const rows = Array.isArray(d2?.data?.assignments) ? d2.data.assignments : [];
            const picked = rows.find(x => String(x.status||'').toLowerCase() === 'in_progress' || x.pickup_at);
            const target = picked || rows[0];
            if (target && (target.trip_id || target.id)) {
              goToLiveTracking(target.trip_id || target.id);
              return;
            }
          } catch (_) {}
        }
        throw new Error(msg || 'Failed to accept trip');
      }
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
      const apiUrl = (window.publicUrl ? window.publicUrl('api/assignments.php') : 'api/assignments.php');
      const res = await fetch(apiUrl, {
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
    const p = document.getElementById("pendingCount");
    const a = document.getElementById("activeCount");
    const c = document.getElementById("completedCount");
    if (p) p.innerText = String(stats.pending);
    if (a) a.innerText = String(stats.active);
    if (c) c.innerText = String(stats.completed);
  }
});