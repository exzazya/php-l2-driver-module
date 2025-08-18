// VRDS Dispatch Scheduling interactions
// - Loads reservation queue from localStorage (created by reservation page)
// - Select a reservation to prefill assignment form
// - Validate and save assigned dispatches to localStorage
// - Renders both queue and assigned tables with generic table-pagination.js

(function () {
  'use strict';

  const PREVIEW_KEY = 'vrds_reservations_preview';
  const ASSIGNED_KEY = 'vrds_assigned_dispatches';

  document.addEventListener('DOMContentLoaded', function () {
    const queueTable = document.getElementById('reservationQueueTable');
    const assignedTable = document.getElementById('assignedTable');

    const reloadBtn = document.getElementById('reloadReservations');
    const clearAssignedBtn = document.getElementById('clearAssigned');

    const selectedReservationInput = document.getElementById('selectedReservation');
    const form = document.getElementById('dispatchForm');
    const driverSelect = document.getElementById('driverSelect');
    const vehicleSelect = document.getElementById('vehicleSelect');
    const dispatchDate = document.getElementById('dispatchDate');
    const dispatchTime = document.getElementById('dispatchTime');
    const dNotes = document.getElementById('dispatchNotes');
    const dNotesCount = document.getElementById('dNotesCount');

    let selectedIdx = -1;

    // Build a signature to match reservations with assigned entries
    function makeSignature(obj) {
      if (!obj) return '';
      const date = obj.date || '';
      const time = obj.time || '';
      const pickup = obj.pickup || '';
      const dropoff = obj.dropoff || '';
      const pax = String(obj.pax || '');
      const vehicle = obj.vehicle || '';
      return [date, time, pickup, dropoff, pax, vehicle].join('|').toLowerCase();
    }

    function readJSON(key, fallback = []) {
      try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
      } catch (e) {
        console.warn('Failed to read storage for', key, e);
        return fallback;
      }
    }
    function writeJSON(key, value) {
      try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { console.warn('Failed to write storage for', key, e); }
    }

    function updateDNotesCount() {
      if (!dNotes || !dNotesCount) return;
      const max = dNotes.getAttribute('maxlength') ? parseInt(dNotes.getAttribute('maxlength'), 10) : 240;
      dNotesCount.textContent = `${dNotes.value.length}/${max}`;
    }

    function renderQueue() {
      if (!queueTable || !queueTable.tBodies[0]) return;
      const tbody = queueTable.tBodies[0];
      const data = readJSON(PREVIEW_KEY);
      const assigned = readJSON(ASSIGNED_KEY);
      const assignedSet = new Set(assigned.map(a => makeSignature(a)));
      tbody.innerHTML = '';
      if (!data.length) {
        const tr = document.createElement('tr');
        tr.className = 'text-muted';
        tr.innerHTML = '<td colspan="4">No reservations in queue.</td>';
        tbody.appendChild(tr);
      } else {
        data.forEach((r, idx) => {
          const isAssigned = assignedSet.has(makeSignature(r));
          const tr = document.createElement('tr');
          tr.tabIndex = 0;
          tr.style.cursor = 'pointer';
          tr.dataset.index = String(idx);
          tr.innerHTML = `
            <td>
              <div class=\"small fw-semibold\">${r.date || ''} ${r.time || ''} ${isAssigned ? '<span class=\\"badge bg-success ms-2\\">Assigned</span>' : ''}</div>
              <div class="text-muted small">${r.name || ''}</div>
            </td>
            <td>
              <div class="small">${r.pickup || ''} → ${r.dropoff || ''}</div>
              <div class="text-muted small">${r.purpose || ''}</div>
            </td>
            <td><span class="badge bg-secondary">${r.pax || 1}</span></td>
            <td><span class="badge bg-light text-dark">${r.vehicle || ''}</span></td>
          `;
          tr.addEventListener('click', () => selectReservation(idx));
          tr.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); selectReservation(idx); } });
          tbody.appendChild(tr);
        });
      }
      // trigger repaginate if available
      if (queueTable._repaginate) queueTable._repaginate();
    }

    function selectReservation(index) {
      const list = readJSON(PREVIEW_KEY);
      const r = list[index];
      selectedIdx = index;
      // highlight row
      queueTable.querySelectorAll('tbody tr').forEach((tr, i) => {
        tr.classList.toggle('table-active', i === index);
      });
      if (r) {
        selectedReservationInput.value = `${r.date || ''} ${r.time || ''} — ${r.pickup || ''} → ${r.dropoff || ''} (${r.pax || 1} pax, ${r.vehicle || ''})`;
        // prefill dispatch date/time if empty
        if (!dispatchDate.value && r.date) dispatchDate.value = r.date;
        if (!dispatchTime.value && r.time) dispatchTime.value = r.time;
      }
    }

    function renderAssigned() {
      if (!assignedTable || !assignedTable.tBodies[0]) return;
      const tbody = assignedTable.tBodies[0];
      const data = readJSON(ASSIGNED_KEY);
      tbody.innerHTML = '';
      if (!data.length) {
        const tr = document.createElement('tr');
        tr.className = 'text-muted';
        tr.innerHTML = '<td colspan="4">No assigned dispatches.</td>';
        tbody.appendChild(tr);
      } else {
        data.forEach(item => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${item.driverName || item.driverId || ''}</td>
            <td>${item.vehicleName || item.vehicleId || ''}</td>
            <td><div class="small fw-semibold">${item.date || ''} ${item.time || ''}</div><div class="text-muted small">${item.vehicle || ''}</div></td>
            <td><div class="small">${item.pickup || ''} → ${item.dropoff || ''}</div></td>
          `;
          tbody.appendChild(tr);
        });
      }
      if (assignedTable._repaginate) assignedTable._repaginate();
    }

    // Buttons
    if (reloadBtn) reloadBtn.addEventListener('click', renderQueue);
   
    if (clearAssignedBtn) clearAssignedBtn.addEventListener('click', function () {
      writeJSON(ASSIGNED_KEY, []);
      renderAssigned();
    });

    // Notes counter
    if (dNotes) {
      dNotes.addEventListener('input', updateDNotesCount);
      updateDNotesCount();
    }

    // Form submission
    if (form) {
      form.addEventListener('submit', function (e) {
        // validation
        const requiredControls = form.querySelectorAll('[required]');
        let valid = true;
        requiredControls.forEach(ctrl => {
          if (!ctrl.value) {
            ctrl.classList.add('is-invalid');
            valid = false;
          } else {
            ctrl.classList.remove('is-invalid');
            ctrl.classList.add('is-valid');
          }
        });
        if (!selectedReservationInput.value) {
          valid = false;
          selectedReservationInput.classList.add('is-invalid');
          setTimeout(() => selectedReservationInput.classList.remove('is-invalid'), 1500);
        }
        if (!valid) { e.preventDefault(); e.stopPropagation(); return; }

        e.preventDefault(); // client-side demo

        const reservations = readJSON(PREVIEW_KEY);
        const selected = selectedIdx >= 0 ? reservations[selectedIdx] : null;
        const driverText = driverSelect.options[driverSelect.selectedIndex]?.text || '';
        const vehicleText = vehicleSelect.options[vehicleSelect.selectedIndex]?.text || '';

        const assigned = readJSON(ASSIGNED_KEY);
        const entry = {
          driverId: driverSelect.value,
          driverName: driverText,
          vehicleId: vehicleSelect.value,
          vehicleName: vehicleText,
          date: dispatchDate.value || (selected?.date || ''),
          time: dispatchTime.value || (selected?.time || ''),
          pickup: selected?.pickup || '',
          dropoff: selected?.dropoff || '',
          pax: selected?.pax || 1,
          vehicle: selected?.vehicle || '',
          notes: (dNotes?.value || '')
        };
        assigned.unshift(entry);
        writeJSON(ASSIGNED_KEY, assigned);
        renderAssigned();

        // Keep reservation in queue; just clear current selection and refresh to show the 'Assigned' badge
        if (selectedIdx >= 0) {
          selectedIdx = -1;
          selectedReservationInput.value = '';
        }
        renderQueue();

        // feedback spinner via global button handler
        const btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.classList.contains('loading')) {
          btn.click();
        }

        // reset minimal fields
        form.reset();
        updateDNotesCount();
        form.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
      });

      form.addEventListener('reset', function () {
        setTimeout(() => {
          form.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
          updateDNotesCount();
        }, 0);
      });
    }

    // Initial renders
    renderQueue();
    renderAssigned();
  });
})();
