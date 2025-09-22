<?php
// Driver 2FA verification page (pre-login)
// Variables available from route: $twofaError, $twofaMasked, $twofaMethod
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="<?php echo asset('img/jetlouge_logo.webp'); ?>">
  <title>Two-Factor Authentication - Driver Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .twofa-container { max-width: 420px; margin: 6rem auto; }
    .brand { text-align:center; margin-bottom: 1rem; }
    .brand img { width: 64px; height: 64px; }
    .card { border-radius: .75rem; }
  </style>
</head>
<body>
  <div class="twofa-container">
    <div class="brand">
      <img src="<?php echo asset('img/jetlouge_logo.webp'); ?>" alt="Jetlouge Travels">
      <h4 class="mt-2 mb-0 fw-bold" style="color:#0d6efd;">Two-Factor Authentication</h4>
      <div class="text-muted">We've sent a 6-digit code to <?php echo htmlspecialchars($twofaMasked ?? 'your email', ENT_QUOTES, 'UTF-8'); ?>.</div>
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <?php if (!empty($twofaError)): ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($twofaError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-3">
            <label for="otp" class="form-label fw-semibold">Authentication Code</label>
            <div class="input-group">
              <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control" id="otp" name="otp" placeholder="123456" required autofocus>
              <button type="submit" name="action" value="resend" class="btn btn-outline-secondary" formnovalidate>
                <i class="bi bi-envelope me-1"></i>Send Code
              </button>
            </div>
            <div class="form-text">Enter the 6-digit code sent to your email.</div>
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-shield-lock me-2"></i>Verify & Continue
            </button>
            <a class="btn btn-outline-secondary" href="<?php echo route('login'); ?>">
              <i class="bi bi-arrow-left me-2"></i>Back to Login
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
