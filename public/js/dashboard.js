(function(){
  if (window.__dashboardInitDone) return;
  window.__dashboardInitDone = true;
  const $ = (id) => document.getElementById(id);
  const setText = (id, value) => {
    const el = $(id);
    if (el) el.textContent = String(value);
  };

  async function fetchJson(url) {
    try {
      const res = await fetch(url, { credentials: 'same-origin' });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.warn('Dashboard fetch error for', url, err);
      return null;
    }
  }

  async function loadAssignments() {
    const urls = [
      window.publicUrl('api/assignments.php?status=pending'),
      window.publicUrl('api/assignments.php?status=accepted'),
      window.publicUrl('api/assignments.php?status=completed'),
    ];
    const [pendingResp, acceptedResp, completedResp] = await Promise.all(urls.map(fetchJson));

    const pending = (pendingResp && pendingResp.success && Array.isArray(pendingResp.data?.assignments)) ? pendingResp.data.assignments.length : 0;
    const active = (acceptedResp && acceptedResp.success && Array.isArray(acceptedResp.data?.assignments)) ? acceptedResp.data.assignments.length : 0;
    const completed = (completedResp && completedResp.success && Array.isArray(completedResp.data?.assignments)) ? completedResp.data.assignments.length : 0;

    setText('pendingAssignmentsCount', pending);
    setText('activeAssignmentsCount', active);
    setText('completedAssignmentsCount', completed);
    setText('totalTrips', (pending + active + completed));
  }

  function parseSqlDate(str) {
    if (!str) return null;
    const iso = String(str).replace(' ', 'T');
    const d = new Date(iso);
    return isNaN(d) ? null : d;
  }

  async function loadReports() {
    const [listResp, completedTripsResp] = await Promise.all([
      fetchJson(window.publicUrl('api/reports.php?action=list')),
      fetchJson(window.publicUrl('api/reports.php?action=completed_trips')),
    ]);

    const submitted = (listResp && listResp.success && Array.isArray(listResp.data?.reports)) ? listResp.data.reports.length : 0;
    const pendingReports = (completedTripsResp && completedTripsResp.success && Array.isArray(completedTripsResp.data?.trips)) ? completedTripsResp.data.trips.length : 0;

    setText('submittedReportsCount', submitted);
    setText('pendingReportsCount', pendingReports);

    // This month submitted reports
    let thisMonth = 0;
    if (listResp && listResp.success && Array.isArray(listResp.data?.reports)) {
      const now = new Date();
      const m = now.getMonth();
      const y = now.getFullYear();
      thisMonth = listResp.data.reports.filter(r => {
        const d = parseSqlDate(r.submitted_at);
        return d && d.getMonth() === m && d.getFullYear() === y;
      }).length;
    }
    setText('thisMonthReportsCount', thisMonth);
  }

  function wireButtons() {
    const wireNav = (id) => {
      const el = $(id);
      if (el && el.dataset.href) {
        el.addEventListener('click', () => {
          window.location.href = el.dataset.href;
        });
      }
    };

    wireNav('qaRequestTrip');
    wireNav('qaSubmitReport');
    wireNav('qaStartTracking');

    const quickReport = $('quickReport');
    if (quickReport) {
      quickReport.addEventListener('click', () => {
        const url = $('qaSubmitReport')?.dataset.href;
        if (url) window.location.href = url;
      });
    }

    const refreshBtn = $('refreshDashboard');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', () => {
        loadAll();
      });
    }

    const emergencyBtn = $('qaEmergency');
    if (emergencyBtn) {
      emergencyBtn.addEventListener('click', () => {
        const name = emergencyBtn.dataset.name || 'Emergency Contact';
        const phone = emergencyBtn.dataset.phone || '';
        if (phone) {
          const go = confirm(`${name}: ${phone}\n\nCall now?`);
          if (go) {
            window.location.href = `tel:${phone}`;
          }
        } else {
          alert('Please contact your dispatcher or supervisor.');
        }
      });
    }
  }

  async function loadAll() {
    await Promise.all([loadAssignments(), loadReports()]);

    // Placeholders for metrics without backend yet
    const avgRatingEl = $('avgRating');
    if (avgRatingEl && (avgRatingEl.textContent || '').trim() === '0.0') {
      avgRatingEl.textContent = '—';
    }
    const totalDistanceEl = $('totalDistance');
    if (totalDistanceEl && (/^0\s*km$/i).test((totalDistanceEl.textContent || '').trim())) {
      totalDistanceEl.textContent = '—';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    wireButtons();
    loadAll();
  });
})();
