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
  <link rel="stylesheet" href="<?php echo asset('css/login.css'); ?>">
  <style>
    body { 
      background: linear-gradient(135deg, var(--jetlouge-primary) 0%, var(--jetlouge-secondary) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .twofa-container { 
      max-width: 420px; 
      width: 100%;
      padding: 15px;
    }
    .twofa-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      padding: 30px 25px;
      text-align: center;
      border: none;
    }
    .logo-container {
      margin-bottom: 20px;
    }
    .logo-container img {
      width: 65px;
      height: 65px;
      border-radius: 50%;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    .twofa-title {
      color: var(--jetlouge-primary);
      font-size: 1.5rem;
      font-weight: 700;
      margin: 15px 0 8px 0;
    }
    .twofa-subtitle {
      color: #6b7280;
      font-size: 0.9rem;
      margin-bottom: 25px;
      line-height: 1.4;
    }
    .form-label {
      color: var(--jetlouge-primary);
      font-weight: 600;
      font-size: 0.95rem;
      margin-bottom: 8px;
      text-align: left;
    }
    .form-control {
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      padding: 12px 18px;
      font-size: 1rem;
      text-align: center;
      letter-spacing: 0.1em;
      transition: all 0.3s ease;
      background: #f9fafb;
    }
    .form-control:focus {
      border-color: var(--jetlouge-primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.15);
      background: white;
    }
    .form-text {
      color: #6b7280;
      font-size: 0.85rem;
      margin-top: 8px;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--jetlouge-primary) 0%, var(--jetlouge-secondary) 100%);
      border: none;
      border-radius: 10px;
      padding: 12px 25px;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3);
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(30, 58, 138, 0.45);
    }
    .btn-outline-secondary {
      border: 2px solid #e5e7eb;
      color: var(--jetlouge-primary);
      border-radius: 10px;
      padding: 10px 20px;
      font-weight: 500;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      background: transparent;
    }
    .btn-outline-secondary:hover {
      background: var(--jetlouge-light);
      border-color: var(--jetlouge-primary);
      color: var(--jetlouge-primary);
    }
    .alert-danger {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #dc2626;
      border-radius: 12px;
      padding: 15px;
      margin-bottom: 25px;
    }
    .d-grid {
      gap: 12px !important;
    }
    .input-group .btn {
      border-radius: 0 10px 10px 0;
      padding: 12px 15px;
      font-size: 0.85rem;
    }
    .input-group .form-control {
      border-radius: 10px 0 0 10px;
      text-align: left;
    }
  </style>
</head>
<body>
  <div class="twofa-container">
    <div class="twofa-card">
      <div class="logo-container">
        <img src="<?php echo asset('img/jetlouge_logo.webp'); ?>" alt="Jetlouge Travels">
      </div>
      
      <h1 class="twofa-title">Two-Factor Authentication</h1>
      <p class="twofa-subtitle">
        We've sent a 6-digit code to <?php echo htmlspecialchars($twofaMasked ?? 'your email', ENT_QUOTES, 'UTF-8'); ?>.
      </p>

      <?php if (!empty($twofaError)): ?>
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($twofaError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mb-3">
          <label for="otp" class="form-label">Authentication Code</label>
          <div class="input-group">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control" id="otp" name="otp" placeholder="123456" required autofocus>
            <button type="submit" name="action" value="resend" class="btn btn-outline-secondary" formnovalidate>
              <i class="bi bi-envelope me-1"></i>Send Code
            </button>
          </div>
          <div class="form-text">Enter the 6-digit code sent to your email.</div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-shield-check me-2"></i>Verify & Continue
          </button>
          <a class="btn btn-outline-secondary" href="<?php echo route('login'); ?>">
            <i class="bi bi-arrow-left me-2"></i>Back to Login
          </a>
        </div>
      </form>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
