// Vehicles page scripts

document.addEventListener('DOMContentLoaded', function() {
  // Search
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const term = this.value.toLowerCase();
      document.querySelectorAll('#vehiclesTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  }

  // Filters
  const statusFilter = document.getElementById('statusFilter');
  const typeFilter = document.getElementById('typeFilter');
  function applyFilters() {
    const statusVal = (statusFilter && statusFilter.value) || '';
    const typeVal = (typeFilter && typeFilter.value) || '';
    document.querySelectorAll('#vehiclesTable tbody tr').forEach(row => {
      const statusCell = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
      const typeCell = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
      const statusMatch = !statusVal || statusCell.includes(statusVal);
      const typeMatch = !typeVal || typeCell.includes(typeVal);
      row.style.display = statusMatch && typeMatch ? '' : 'none';
    });
  }
  if (statusFilter) statusFilter.addEventListener('change', applyFilters);
  if (typeFilter) typeFilter.addEventListener('change', applyFilters);

  // Clear filters (exposed globally if needed)
  window.clearFilters = function() {
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (typeFilter) typeFilter.value = '';
    document.querySelectorAll('#vehiclesTable tbody tr').forEach(row => row.style.display = '');
  };

  // Photo previews (Add)
  const addPhotos = document.getElementById('vehiclePhotos');
  if (addPhotos) {
    addPhotos.addEventListener('change', function(e) {
      const files = e.target.files;
      const previewContainer = document.getElementById('photoPreview');
      if (!previewContainer) return;
      previewContainer.innerHTML = '';
      Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(ev) {
            const div = document.createElement('div');
            div.className = 'position-relative photo-preview-item';
            div.innerHTML = `
              <img src="${ev.target.result}" class="img-thumbnail" style="width: 100px; height: 80px; object-fit: cover;">
              <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle p-1" onclick="removePreviewPhoto(this)" style="width: 25px; height: 25px; font-size: 12px;">
                <i class="fas fa-times"></i>
              </button>
            `;
            previewContainer.appendChild(div);
          };
          reader.readAsDataURL(file);
        }
      });
    });
  }

  // Photo previews (Edit)
  const editPhotos = document.getElementById('editVehiclePhotos');
  if (editPhotos) {
    editPhotos.addEventListener('change', function(e) {
      const files = e.target.files;
      const previewContainer = document.getElementById('editPhotoPreview');
      if (!previewContainer) return;
      previewContainer.innerHTML = '';
      Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(ev) {
            const div = document.createElement('div');
            div.className = 'position-relative photo-preview-item';
            div.innerHTML = `
              <img src="${ev.target.result}" class="img-thumbnail" style="width: 100px; height: 80px; object-fit: cover;">
              <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle p-1" onclick="removePreviewPhoto(this)" style="width: 25px; height: 25px; font-size: 12px;">
                <i class="fas fa-times"></i>
              </button>
            `;
            previewContainer.appendChild(div);
          };
          reader.readAsDataURL(file);
        }
      });
    });
  }

  // Initialize first thumbnail active state
  const firstThumb = document.querySelector('.photo-thumbnail');
  if (firstThumb) {
    firstThumb.classList.add('border-primary');
    firstThumb.style.borderWidth = '3px';
  }

  // Modal lifecycle
  const addModalEl = document.getElementById('addVehicleModal');
  if (addModalEl) {
    addModalEl.addEventListener('hidden.bs.modal', function() {
      const form = document.getElementById('addVehicleForm');
      if (form) form.reset();
      const preview = document.getElementById('photoPreview');
      if (preview) preview.innerHTML = '';
    });
  }
  const editModalEl = document.getElementById('editVehicleModal');
  if (editModalEl) {
    editModalEl.addEventListener('show.bs.modal', function() {
      loadVehicleForEdit(1); // demo
    });
    editModalEl.addEventListener('hidden.bs.modal', function() {
      const preview = document.getElementById('editPhotoPreview');
      if (preview) preview.innerHTML = '';
    });
  }

  // Forms
  const addForm = document.getElementById('addVehicleForm');
  if (addForm) {
    addForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn?.innerHTML;
      if (submitBtn) {
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>';
        submitBtn.disabled = true;
      }
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('addVehicleModal'));
        if (modal) modal.hide();
        this.reset();
        const preview = document.getElementById('photoPreview');
        if (preview) preview.innerHTML = '';
        if (submitBtn) {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }
        showToast('Vehicle added successfully!', 'success');
      }, 2000);
    });
  }

  const editForm = document.getElementById('editVehicleForm');
  if (editForm) {
    editForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn?.innerHTML;
      if (submitBtn) {
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>';
        submitBtn.disabled = true;
      }
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('editVehicleModal'));
        if (modal) modal.hide();
        if (submitBtn) {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }
        showToast('Vehicle updated successfully!', 'success');
      }, 2000);
    });
  }
});

// Exposed helpers
togglePhotoSlide = function(index) { changeCarouselSlide(index); };

function changeCarouselSlide(slideIndex) {
  const carousel = new bootstrap.Carousel(document.getElementById('vehiclePhotosCarousel'));
  carousel.to(slideIndex);
  document.querySelectorAll('.photo-thumbnail').forEach((thumb, index) => {
    thumb.classList.toggle('border-primary', index === slideIndex);
    thumb.style.borderWidth = index === slideIndex ? '3px' : '1px';
  });
}

function removePreviewPhoto(button) {
  const item = button.closest('.photo-preview-item');
  if (item) item.remove();
}

function removePhoto(button) {
  if (confirm('Are you sure you want to remove this photo?')) {
    const item = button.closest('.photo-item');
    if (item) item.remove();
  }
}

function deleteVehicle(vehicleId) {
  if (!confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) return;
  const button = event.target.closest('button');
  if (!button) return;
  const originalContent = button.innerHTML;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  button.disabled = true;
  setTimeout(() => {
    const row = button.closest('tr');
    if (row) row.remove();
    showToast('Vehicle deleted successfully!', 'success');
  }, 1000);
}

function loadVehicleForEdit(vehicleId) {
  document.getElementById('editVehicleMake').value = 'Toyota';
  document.getElementById('editVehicleModel').value = 'Camry';
  document.getElementById('editVehicleYear').value = '2022';
  document.getElementById('editVehicleLicense').value = 'ABC-123';
  document.getElementById('editVehicleVin').value = '1HGBH41JXMN109186';
  document.getElementById('editVehicleType').value = 'sedan';
  document.getElementById('editVehicleCapacity').value = '4';
  document.getElementById('editVehicleColor').value = 'White';
  document.getElementById('editVehicleFuelType').value = 'gasoline';
  document.getElementById('editVehicleStatus').value = 'active';
  document.getElementById('editVehicleMileage').value = '25000';
  document.getElementById('editVehicleInsurance').value = '2024-12-31';
  document.getElementById('editVehicleNotes').value = 'Regular maintenance completed. Vehicle in excellent condition.';
}

function showToast(message, type = 'info') {
  document.querySelectorAll('.toast-notification').forEach(toast => toast.remove());
  const toast = document.createElement('div');
  toast.className = `toast-notification alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
  toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  toast.innerHTML = `
    <div class="d-flex align-items-center">
      <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
      <span>${message}</span>
      <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
    </div>
  `;
  document.body.appendChild(toast);
  setTimeout(() => { if (toast.parentElement) toast.remove(); }, 5000);
}

