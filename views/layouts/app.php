<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="<?php echo asset('img/jetlouge_logo.png'); ?>">
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
      $driverImg = 'img/default-profile.jpg'; // default image

      if ($driver_id && $conn) {
          // Fetch the driver's profile image from database
          $stmt = $conn->prepare("SELECT profile_image FROM drivers WHERE id = ?");
          $stmt->bind_param("i", $driver_id);
          $stmt->execute();
          $stmt->store_result();
          
          if ($stmt->num_rows > 0) {
              $stmt->bind_result($profileImage);
              $stmt->fetch();
              if (!empty($profileImage)) {
                  // Use the uploaded image
                  $driverImg = htmlspecialchars($profileImage);
              }
          }
          $stmt->close();
      }
      ?>
    <div class="profile-section text-center">
      <img src="<?php echo htmlspecialchars($driverImg); ?>" alt="Driver Profile" class="profile-img mb-2">
      <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($driverName); ?></h6>
      <small class="text-muted">Jetlouge Travels Driver</small>
      <br>
      <small class="text-muted"><?php echo htmlspecialchars($driverEmail); ?></small>
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
        <li class="nav-item mt-3">
            <a href="<?php echo route('logout'); ?>" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
  </aside>

  <!-- Overlay for mobile -->
  <div id="overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1040; display: none;"></div>

  <!-- Main Content -->
  <main id="main-content">
      <?php echo $content ?? ''; ?>
  </main>

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
    });
  </script>

  <?php echo $scripts ?? ''; ?>
</body>
</html>
