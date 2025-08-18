// Generic table pagination (10 rows/page) with Bootstrap-like footer
// Usage: add class `paginate-10` to any table you want paginated.
(function() {
  function paginateTable(table, rowsPerPage = 10) {
    if (!table || !table.tBodies || !table.tBodies[0]) return;
    const tbody = table.tBodies[0];
    const card = table.closest('.card');
    if (!card) return;

    // Ensure footer exists
    let footer = card.querySelector('.card-footer');
    if (!footer) {
      footer = document.createElement('div');
      footer.className = 'card-footer';
      card.appendChild(footer);
    }

    // Build footer content container
    footer.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.className = 'd-flex justify-content-between align-items-center';

    const info = document.createElement('small');
    info.className = 'text-muted';

    const nav = document.createElement('nav');
    const ul = document.createElement('ul');
    ul.className = 'pagination pagination-sm mb-0';

    const prevLi = document.createElement('li');
    prevLi.className = 'page-item';
    const prevA = document.createElement('a');
    prevA.className = 'page-link';
    prevA.href = '#';
    prevA.textContent = 'Previous';
    prevLi.appendChild(prevA);

    // Middle current page indicator (non-clickable)
    const pageInfoLi = document.createElement('li');
    pageInfoLi.className = 'page-item disabled';
    const pageInfoA = document.createElement('span');
    pageInfoA.className = 'page-link';
    pageInfoA.textContent = '1 / 1';
    pageInfoLi.appendChild(pageInfoA);

    const nextLi = document.createElement('li');
    nextLi.className = 'page-item';
    const nextA = document.createElement('a');
    nextA.className = 'page-link';
    nextA.href = '#';
    nextA.textContent = 'Next';
    nextLi.appendChild(nextA);

    ul.appendChild(prevLi);
    ul.appendChild(pageInfoLi);
    ul.appendChild(nextLi);
    nav.appendChild(ul);

    wrap.appendChild(info);
    wrap.appendChild(nav);
    footer.appendChild(wrap);

    let currentPage = 1;

    function render() {
      // Get all rows that aren't part of the header
      const allRows = Array.from(tbody.rows).filter(row => !row.classList.contains('d-none'));
      const totalRows = allRows.length;
      const totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));
      
      // Ensure current page is within valid range
      currentPage = Math.min(Math.max(1, currentPage), totalPages);

      // Calculate slice of rows to show
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      
      // Hide all rows first
      allRows.forEach(row => {
        row.style.display = 'none';
      });
      
      // Show only the rows for current page
      const visibleRows = allRows.slice(start, end);
      visibleRows.forEach(row => {
        row.style.display = '';
      });
      
      // Update pagination info
      const firstItem = start + 1;
      const lastItem = Math.min(start + rowsPerPage, totalRows);
      info.textContent = `Showing ${firstItem}-${lastItem} of ${totalRows} items`;

      // Prev/Next state
      if (currentPage === 1) prevLi.classList.add('disabled'); else prevLi.classList.remove('disabled');
      if (currentPage >= totalPages) nextLi.classList.add('disabled'); else nextLi.classList.remove('disabled');

      // Update middle current page indicator
      pageInfoA.textContent = `${currentPage} / ${totalPages}`;
    }

    prevA.addEventListener('click', function(e) {
      e.preventDefault();
      if (prevLi.classList.contains('disabled')) return;
      currentPage -= 1;
      render();
    });

    nextA.addEventListener('click', function(e) {
      e.preventDefault();
      if (nextLi.classList.contains('disabled')) return;
      currentPage += 1;
      render();
    });

    // Public API to re-render after filtering
    table._repaginate = function() { render(); };

    // Initial render
    render();

    // Observe for row changes
    const mo = new MutationObserver(() => {
      render();
    });
    mo.observe(tbody, { childList: true, subtree: false });
  }

  // Initialize pagination when DOM is ready
  function initPagination() {
    const candidates = [
      ...document.querySelectorAll('table.paginate-10'),
      ...document.querySelectorAll('#vehiclesTable, #maintenanceTable, #vehicleRequestsTable')
    ];
    
    const seen = new Set();
    candidates.forEach(table => {
      if (seen.has(table) || table._paginated) return;
      seen.add(table);
      table._paginated = true;
      paginateTable(table, 10);
    });
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPagination);
  } else {
    initPagination();
  }

  // Re-run when AJAX content loads
  document.addEventListener('ajaxComplete', initPagination);
  
  // Re-run when dynamic content changes
  const observer = new MutationObserver((mutations) => {
    if (mutations.some(m => m.type === 'childList')) {
      initPagination();
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });
})();
