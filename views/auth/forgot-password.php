<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Driver Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo asset('css/login.css'); ?>">
</head>
<body>
  <div class="login-container">
    <div class="row g-0">
      <div class="col-lg-6 login-left">
        <div class="floating-shapes"><div class="shape"></div><div class="shape"></div><div class="shape"></div></div>
        <div class="logo-container">
          <div class="logo-box">
            <img src="<?php echo asset('img/jetlouge_logo.webp'); ?>" alt="Jetlouge Travels">
          </div>
          <h1 class="brand-text">Jetlouge Travels</h1>
          <p class="brand-subtitle">Driver Portal</p>
        </div>
      </div>
      <div class="col-lg-6 login-right">
        <h3 class="text-center mb-2" style="color: var(--jetlouge-primary); font-weight: 700;">Forgot Password</h3>
        <p class="text-muted text-center mb-4">Enter your email and we'll send a verification code.</p>

        <?php if (!empty($fpError)): ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($fpError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="email" name="email" placeholder="Enter your driver email" required>
            </div>
          </div>
          <button type="submit" class="btn btn-login w-100 mb-3"><i class="bi bi-envelope me-2"></i> Send Code</button>
          <div class="text-center">
            <a href="<?php echo route('login'); ?>" class="btn-forgot"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
