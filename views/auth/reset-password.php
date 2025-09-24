<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Driver Reset Password</title>
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
        <h3 class="text-center mb-2" style="color: var(--jetlouge-primary); font-weight: 700;">Reset Password</h3>
        <p class="text-muted text-center mb-3">Enter the code sent to <strong><?php echo htmlspecialchars($maskedEmail ?? 'your email', ENT_QUOTES, 'UTF-8'); ?></strong> and set a new password.</p>

        <?php if (!empty($rpError)): ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($rpError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-3">
            <label for="otp" class="form-label fw-semibold">Verification Code</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
              <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control" id="otp" name="otp" placeholder="6-digit code" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label fw-semibold">New Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="new_password" name="new_password" placeholder="At least 8 characters" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-login"><i class="bi bi-key me-2"></i> Reset Password</button>
            <button type="submit" name="action" value="resend" class="btn btn-outline-secondary" formnovalidate><i class="bi bi-envelope me-2"></i> Resend Code</button>
          </div>

          <div class="text-center mt-3">
            <a class="btn-forgot" href="<?php echo route('login'); ?>"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
