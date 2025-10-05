<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/webp" href="<?php echo asset('img/jetlouge_logo.webp'); ?>">
  <title>Jetlouge Travels - <?php echo $title ?? 'Driver Dashboard'; ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <!-- Font Awesome 5 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

  <link rel="stylesheet" href="<?php echo asset('css/dash-style-fixed.css'); ?>">

  <script>
    window.BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    window.publicUrl = function(path) {
      if (!path) return '';
      var p = String(path).replace(/^\/+/, '');
      // Ensure API calls work even if BASE_URL contains /public
      var base = String(window.BASE_URL || '').replace(/\/public\/?$/i, '');
      return base + '/' + p;
    };
    window.assetUrl = function(path) {
      if (!path) return '';
      var p = String(path).replace(/^\/+/, '');
      return window.BASE_URL + '/public/' + p;
    };
  </script>

  <!-- Page-specific styles -->
  <?php echo $styles ?? ''; ?>
  
</head>

<body style="background-color: #f8f9fa !important;">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background-color: var(--jetlouge-primary);">
    <div class="container-fluid">
      <button class="sidebar-toggle desktop-toggle me-3" id="desktop-toggle" title="Toggle Sidebar">
        <i class="bi bi-list fs-5"></i>
      </button>
      <a class="navbar-brand fw-bold">
        <i class="bi bi-airplane me-2"></i>Jetlouge Travels
      </a>
      <div class="d-flex align-items-center">
        <button class="sidebar-toggle mobile-toggle" id="menu-btn" title="Open Menu">
          <i class="bi bi-list fs-5"></i>
        </button>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <aside id="sidebar" class="bg-white border-end p-3 shadow-sm">
      <!-- Profile Section -->
      <?php
      // Default values
      $driver_id = $_SESSION['driver_id'] ?? null;
      $driverName = $_SESSION['full_name'] ?? 'Driver';
      $driverEmail = $_SESSION['email'] ?? '';
      $driverPhone = $_SESSION['phone'] ?? '';
      $driverAddress = $_SESSION['address'] ?? '';
      $driverEmergencyContact = $_SESSION['emergency_contact'] ?? '';
      $driverEmergencyPhone = $_SESSION['emergency_phone'] ?? '';
      $driverImg = 'img/default-profile.jpg'; // default image (under public)

      // Prefer session-cached path set after upload
      if (!empty($_SESSION['profile_image'])) {
          $driverImg = (string)$_SESSION['profile_image'];
      } elseif ($driver_id && function_exists('executeQuery')) {
          // Fallback: fetch from DB using PDO helper
          $stmt = executeQuery("SELECT profile_image FROM drivers WHERE id = ?", [$driver_id]);
          if ($stmt) {
              $row = $stmt->fetch();
              if ($row && !empty($row['profile_image'])) {
                  $driverImg = (string)$row['profile_image'];
              }
          }
      }
      ?>
    <div class="profile-section text-center position-relative">
      <button
        type="button"
        id="driverProfileInfoButton"
        class="btn btn-outline-primary btn-sm position-absolute top-0 end-0 mt-1 me-1 d-flex align-items-center justify-content-center"
        style="width: 32px; height: 32px;"
        title="View profile details"
        aria-label="View profile details"
        data-name="<?php echo htmlspecialchars($driverName); ?>"
        data-email="<?php echo htmlspecialchars($driverEmail); ?>"
        data-phone="<?php echo htmlspecialchars($driverPhone); ?>"
        data-address="<?php echo htmlspecialchars($driverAddress); ?>"
        data-emergency-contact="<?php echo htmlspecialchars($driverEmergencyContact); ?>"
        data-emergency-phone="<?php echo htmlspecialchars($driverEmergencyPhone); ?>"
      >
        <i class="bi bi-person-vcard"></i>
      </button>
      <?php if (!empty($_SESSION['flash_error']) || !empty($_SESSION['flash_success'])): ?>
        <div class="mb-2">
          <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger py-1 px-2 small mb-1"><?php echo htmlspecialchars($_SESSION['flash_error']); ?></div>
          <?php endif; ?>
          <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success py-1 px-2 small mb-1"><?php echo htmlspecialchars($_SESSION['flash_success']); ?></div>
          <?php endif; ?>
        </div>
        <?php unset($_SESSION['flash_error'], $_SESSION['flash_success']); ?>
      <?php endif; ?>
      <?php
        // Resolve profile image URL: uploaded files live under /uploads, defaults under /public
        $imgPath = (string)$driverImg;
        if (strpos($imgPath, 'uploads/') === 0) {
          $resolvedImgUrl = BASE_URL . '/' . $imgPath;
          // Add cache-busting query using filemtime if file exists
          $fsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imgPath);
          if (is_file($fsPath)) {
            $resolvedImgUrl .= '?v=' . filemtime($fsPath);
          }
        } else {
          $resolvedImgUrl = asset($imgPath);
        }
      ?>
      <div class="position-relative d-inline-block mb-2">
        <img src="<?php echo htmlspecialchars($resolvedImgUrl); ?>" alt="Driver Profile" class="profile-img">
        <form id="drvAvatarForm" action="<?php echo route('profile-upload'); ?>" method="post" enctype="multipart/form-data">
          <input id="drvAvatarInput" class="d-none" type="file" name="avatar" accept="image/*" />
        </form>
        <button type="button" class="btn btn-light btn-sm rounded-circle position-absolute" style="right:-6px; bottom:-6px; box-shadow: 0 0 6px rgba(0,0,0,.2);" title="Change photo" onclick="document.getElementById('drvAvatarInput').click();">
          <i class="bi bi-pencil"></i>
        </button>
      </div>
      <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($driverName); ?></h6>
      <small class="text-muted">Jetlouge Travels Driver</small>
      <br>
      <small class="text-muted" id="driverEmailText"><?php echo htmlspecialchars($driverEmail); ?></small>
      <script>
        (function(){
          const input = document.getElementById('drvAvatarInput');
          const form = document.getElementById('drvAvatarForm');
          if (input && form) {
            input.addEventListener('change', function(){
              if (input.files && input.files.length > 0) {
                form.submit();
              }
            });
          }
        })();
      </script>
    </div>

    <!-- Navigation Menu -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="<?php echo route('dashboard'); ?>" class="nav-link text-dark <?php echo request()->routeIs('dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo route('trip-assignment'); ?>" class="nav-link text-dark <?php echo request()->routeIs('trip-assignment') ? 'active' : ''; ?>">
                <i class="bi bi-truck me-2"></i> Trip Assignment
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo route('live-tracking'); ?>" class="nav-link text-dark <?php echo request()->routeIs('live-tracking') ? 'active' : ''; ?>">
                <i class="bi bi-geo-alt me-2"></i> Live Tracking
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo route('reports-and-checklist'); ?>" class="nav-link text-dark <?php echo request()->routeIs('reports-and-checklist') ? 'active' : ''; ?>">
                <i class="bi bi-clipboard-check me-2"></i> Reports and Checklist
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo route('account-security'); ?>" class="nav-link text-dark <?php echo request()->routeIs('account-security') ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock me-2"></i> Account Security
            </a>
        </li>
        <li class="nav-item mt-3">
            <a href="<?php echo route('logout'); ?>" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
  </aside>

  <div class="modal fade" id="driverProfileInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="driverProfileDetailsForm" method="post" action="<?php echo route('profile-update'); ?>">
          <div class="modal-header">
            <h5 class="modal-title">Profile Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" data-field="full_name" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" data-field="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input type="text" class="form-control" name="phone" maxlength="20">
            </div>
            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Emergency Contact Name</label>
              <input type="text" class="form-control" name="emergency_contact" maxlength="100">
            </div>
            <div class="mb-3">
              <label class="form-label">Emergency Contact Number</label>
              <input type="text" class="form-control" name="emergency_phone" maxlength="20">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Overlay for mobile -->
  <div id="overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1040; display: none;"></div>

  <!-- Main Content -->
  <main id="main-content">
      <?php echo $content ?? ''; ?>
  </main>

  <?php
    // Global footer with company contact and legal links
    $footerPath = __DIR__ . '/footer.php';
    if (file_exists($footerPath)) { include $footerPath; }
  ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Sidebar toggle functionality -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const menuBtn = document.getElementById('menu-btn');
      const desktopToggle = document.getElementById('desktop-toggle');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('overlay');
      const mainContent = document.getElementById('main-content');
      const showNotification = (type, message) => {
        const msg = message || '';
        if (typeof window.pushNotification === 'function') {
          window.pushNotification(type, msg, 3500);
        } else if (typeof window.showToast === 'function') {
          window.showToast(msg, type, 3500);
        } else {
          window.alert(msg);
        }
      };

      // Mobile sidebar toggle
      if (menuBtn && sidebar && overlay) {
        menuBtn.addEventListener('click', (e) => {
          e.preventDefault();
          sidebar.classList.toggle('active');
          overlay.classList.toggle('show');
          document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });
      }

      // Desktop sidebar toggle
      if (desktopToggle && sidebar && mainContent) {
        desktopToggle.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const isCollapsed = sidebar.classList.contains('collapsed');
          sidebar.classList.toggle('collapsed');
          mainContent.classList.toggle('expanded');
          localStorage.setItem('sidebarCollapsed', !isCollapsed);
          setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 300);
        });
      }

      // Restore sidebar collapsed state
      const savedState = localStorage.getItem('sidebarCollapsed');
      if (savedState === 'true' && sidebar && mainContent) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
      }

      // Close mobile sidebar when clicking overlay
      if (overlay) {
        overlay.addEventListener('click', () => {
          sidebar.classList.remove('active');
          overlay.classList.remove('show');
          document.body.style.overflow = '';
        });
      }

      // Add loading animation to buttons
      document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
          if (!this.classList.contains('loading')) {
            this.classList.add('loading');
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Loading...';
            setTimeout(() => {
              this.innerHTML = originalText;
              this.classList.remove('loading');
            }, 1500);
          }
        });
      });

      // Reset mobile sidebar on window resize
      window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
          sidebar.classList.remove('active');
          overlay.classList.remove('show');
          document.body.style.overflow = '';
        }
      });

      const profileBtn = document.getElementById('driverProfileInfoButton');
      const profileModalEl = document.getElementById('driverProfileInfoModal');
      if (profileBtn && profileModalEl && window.bootstrap) {
        const modal = new window.bootstrap.Modal(profileModalEl);
        const form = profileModalEl.querySelector('#driverProfileDetailsForm');
        const fullNameInput = profileModalEl.querySelector('[data-field="full_name"]');
        const emailInput = form ? form.querySelector('input[name="email"]') : null;
        const phoneInput = form ? form.querySelector('input[name="phone"]') : null;
        const addressInput = form ? form.querySelector('textarea[name="address"]') : null;
        const emergencyNameInput = form ? form.querySelector('input[name="emergency_contact"]') : null;
        const emergencyPhoneInput = form ? form.querySelector('input[name="emergency_phone"]') : null;
        const decode = (value) => {
          const textarea = document.createElement('textarea');
          textarea.innerHTML = value || '';
          return textarea.value;
        };
        profileBtn.addEventListener('click', () => {
          const data = profileBtn.dataset;
          if (fullNameInput) fullNameInput.value = decode(data.name);
          if (emailInput) emailInput.value = decode(data.email);
          if (phoneInput) phoneInput.value = decode(data.phone);
          if (addressInput) addressInput.value = decode(data.address);
          if (emergencyNameInput) emergencyNameInput.value = decode(data.emergencyContact);
          if (emergencyPhoneInput) emergencyPhoneInput.value = decode(data.emergencyPhone);
          modal.show();
        });

        if (form) {
          form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);
            if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.dataset.orig = submitBtn.dataset.orig || submitBtn.innerHTML;
              submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>';
            }
            try {
              const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              });
              let payload = null;
              try {
                payload = await response.json();
              } catch (err) {
                payload = { success: false, message: 'Unexpected server response.' };
              }
              if (!response.ok || !payload || payload.success !== true) {
                const message = (payload && payload.message) ? payload.message : 'Unable to update profile.';
                showNotification('danger', message);
                return;
              }
              const updatedEmail = payload.email || formData.get('email') || '';
              profileBtn.dataset.email = updatedEmail;
              const driverEmailText = document.getElementById('driverEmailText');
              if (driverEmailText) driverEmailText.textContent = updatedEmail;
              profileBtn.dataset.phone = payload.phone || formData.get('phone') || '';
              profileBtn.dataset.address = payload.address || formData.get('address') || '';
              profileBtn.dataset.emergencyContact = payload.emergency_contact || formData.get('emergency_contact') || '';
              profileBtn.dataset.emergencyPhone = payload.emergency_phone || formData.get('emergency_phone') || '';
              showNotification('success', payload.message || 'Profile updated successfully.');
              modal.hide();
            } catch (error) {
              showNotification('danger', 'Network error. Please try again.');
            } finally {
              if (submitBtn) {
                submitBtn.disabled = false;
                if (submitBtn.dataset.orig) {
                  submitBtn.innerHTML = submitBtn.dataset.orig;
                }
              }
            }
          });
        }
      }
    });
  </script>

  <?php echo $scripts ?? ''; ?>
</body>
</html>
