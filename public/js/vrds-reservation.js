// VRDS Reservation page interactions
// - Bootstrap-like validation
// - Passengers increment/decrement
// - Notes character counter
// - Lightweight preview list (client-side only)

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reservationForm');
    const notes = document.getElementById('notes');
    const notesCount = document.getElementById('notesCount');
    const incBtn = document.getElementById('incrementPassengers');
    const decBtn = document.getElementById('decrementPassengers');
    const paxInput = document.getElementById('passengers');
    const table = document.getElementById('reservationsTable');
    const refreshBtn = document.getElementById('refreshList');
   
    const STORAGE_KEY = 'vrds_reservations_preview';

    function readPreview() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : [];
      } catch (e) {
        console.warn('Preview read failed', e);
        return [];
      }
    }

    function writePreview(list) {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, 20)));
      } catch (e) {
        console.warn('Preview write failed', e);
      }
    }

    function renderTable() {
      if (!table) return;
      const tbody = table.querySelector('tbody');
      const list = readPreview();
      tbody.innerHTML = '';
      if (!list.length) {
        const tr = document.createElement('tr');
        tr.className = 'text-muted';
        tr.innerHTML = '<td colspan="3">No reservations yet. Submit the form to add a preview here.</td>';
        tbody.appendChild(tr);
        return;
      }
      list.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <div class="small fw-semibold">${item.date} ${item.time}</div>
            <div class="text-muted small">${item.vehicle}</div>
          </td>
          <td>
            <div class="small">${item.pickup} → ${item.dropoff}</div>
            <div class="text-muted small">${item.purpose || ''}</div>
          </td>
          <td><span class="badge bg-secondary">${item.pax}</span></td>
        `;
        tbody.appendChild(tr);
      });
    }

    function updateNotesCount() {
      if (!notes || !notesCount) return;
      const max = notes.getAttribute('maxlength') ? parseInt(notes.getAttribute('maxlength'), 10) : 240;
      const val = notes.value.length;
      notesCount.textContent = `${val}/${max}`;
    }

    // Passengers controls
    function clampPax(v) {
      const n = isNaN(v) ? 1 : v;
      return Math.min(99, Math.max(1, n));
    }

    if (incBtn && paxInput) {
      incBtn.addEventListener('click', () => {
        paxInput.value = clampPax(Number(paxInput.value || 1) + 1);
      });
    }
    if (decBtn && paxInput) {
      decBtn.addEventListener('click', () => {
        paxInput.value = clampPax(Number(paxInput.value || 1) - 1);
      });
    }
    if (paxInput) {
      paxInput.addEventListener('input', () => {
        paxInput.value = clampPax(Number(paxInput.value || 1));
      });
    }

    if (notes) {
      notes.addEventListener('input', updateNotesCount);
      updateNotesCount();
    }

    // Form validation + preview add (client-side only)
    if (form) {
      form.addEventListener('submit', function (e) {
        // basic Bootstrap validation style
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

        if (!valid) {
          e.preventDefault();
          e.stopPropagation();
          return;
        }

        e.preventDefault(); // demo: prevent navigation, keep client-side only

        const payload = {
          name: document.getElementById('requesterName')?.value?.trim() || '',
          email: document.getElementById('requesterEmail')?.value?.trim() || '',
          pickup: document.getElementById('pickupLocation')?.value?.trim() || '',
          dropoff: document.getElementById('dropoffLocation')?.value?.trim() || '',
          date: document.getElementById('pickupDate')?.value || '',
          time: document.getElementById('pickupTime')?.value || '',
          pax: clampPax(Number(document.getElementById('passengers')?.value || 1)),
          vehicle: document.getElementById('vehicleType')?.value || '',
          purpose: document.getElementById('tripPurpose')?.value?.trim() || '',
          notes: notes?.value?.trim() || ''
        };

        // Save to preview store (top of list)
        const list = readPreview();
        list.unshift(payload);
        writePreview(list);
        renderTable();

        // Provide visual feedback
        const btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.classList.contains('loading')) {
          btn.click(); // trigger the global spinner feedback handler in layout
        }

        // Reset non-contact fields after adding preview
        form.reset();
        updateNotesCount();
        form.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
      });

      // Clear button resets validation styles
      form.addEventListener('reset', function () {
        setTimeout(() => {
          form.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
          updateNotesCount();
        }, 0);
      });
    }

    // Refresh/Clear actions
    if (refreshBtn) refreshBtn.addEventListener('click', renderTable);
    
    // Initial render
    renderTable();
  });
})();

