<?php
// layout.php

// Default placeholders
$title = $title ?? "Jetlouge Travels";
$content = $content ?? "";
$styles = $styles ?? "";
$scripts = $scripts ?? "";

// Simple function for "active" nav highlighting
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="img/jetlouge_logo.png">
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/dash-style-fixed.css">

  <!-- Page-specific styles -->
  <?= $styles ?>
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
    <div class="profile-section text-center">
      <img src="img/default-profile.jpg" alt="Driver Profile" class="profile-img mb-2">
      <h6 class="fw-semibold mb-1">John Doe</h6>
      <small class="text-muted">Jetlouge Driver</small>
    </div>

    <!-- Navigation Menu -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-dark <?= isActive('dashboard.php') ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="trip-assignment.php" class="nav-link text-dark <?= isActive('trip-assignment.php') ?>">
                <i class="bi bi-truck me-2"></i> Trip Assignment
            </a>
        </li>
        <li class="nav-item">
            <a href="live-tracking.php" class="nav-link text-dark <?= isActive('live-tracking.php') ?>">
                <i class="bi bi-geo-alt me-2"></i> Live Tracking
            </a>
        </li>
        <li class="nav-item">
            <a href="reports-and-checklist.php" class="nav-link text-dark <?= isActive('reports-and-checklist.php') ?>">
                <i class="bi bi-clipboard-check me-2"></i> Reports and Checklist
            </a>
        </li>
        <li class="nav-item mt-3">
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </li>
    </ul>
  </aside>

  <!-- Overlay for mobile -->
  <div id="overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index:1040; display: none;"></div>

  <!-- Main Content -->
  <main id="main-content">
      <?= $content ?>
  </main>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Sidebar toggle functionality -->
  <script>
    // same JS as before
  </script>

  <!-- Page-specific scripts -->
  <?= $scripts ?>
</body>
</html>