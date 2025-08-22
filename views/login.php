<?php
session_start();
require 'db_connect.php'; // make sure this connects to your logi_L2 DB

// If already logged in, redirect
if (isset($_SESSION['driver_id'])) {
    header("Location: dashboard.php");
    exit;
}

$loginError = '';

// If form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['username']); // your form calls it "username" but in DB it's email
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, email, password_hash, status FROM drivers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
if ($row['status'] === 'active') {
    // Store session in PHP
    $_SESSION['driver_id'] = $row['id'];
    $_SESSION['driver_name'] = $row['name'];
    $_SESSION['driver_email'] = $row['email'];

    // --- Insert into driver_sessions ---
    $session_id = session_id(); // PHP's session id
    $driver_id = $row['id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt2 = $conn->prepare("INSERT INTO driver_sessions (id, driver_id, ip_address, user_agent, last_activity) 
                             VALUES (?, ?, ?, ?, NOW())");
    $stmt2->bind_param("siss", $session_id, $driver_id, $ip_address, $user_agent);
    $stmt2->execute();

    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
} else {
                $loginError = "Your account is not active.";
            }
        } else {
            $loginError = "Invalid email or password.";
        }
    } else {
        $loginError = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jetlouge Travels - Driver Login</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo asset('css/login.css'); ?>"></head>
<body>
  <div class="login-container">
    <div class="row g-0">
      <!-- Left Side -->
      <div class="col-lg-6 login-left">
        <div class="floating-shapes">
          <div class="shape"></div>
          <div class="shape"></div>
          <div class="shape"></div>
        </div>

        <div class="logo-container">
          <div class="logo-box">
          <img src="<?php echo asset('img/jetlouge_logo.png'); ?>" alt="Jetlouge Travels">
          </div>
          <h1 class="brand-text">Jetlouge Travels</h1>
          <p class="brand-subtitle">Logistics Driver Portal</p>
        </div>
      </div>

      <!-- Right Side - Form -->
      <div class="col-lg-6 login-right">
        <h3 class="text-center mb-4" style="color: var(--jetlouge-primary); font-weight: 700;">
          Sign In to Your Account
        </h3>

        <form id="loginForm" method="POST" action="">
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Username or Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="text" class="form-control" id="email" name="username" placeholder="Enter driver username or email" required>
            </div>
              <small class="form-text text-muted">
                Only drivers can sign in. Use your admin username or email.              
              </small>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
              <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me" value="1">
              <label class="form-check-label" for="rememberMe">
                Remember me
              </label>
          </div>

          <button type="submit" class="btn btn-login mb-3 w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
          </button>

          <div class="text-center">
              <a href="#" class="btn-forgot">Forgot your password?</a>
          </div>
          </form>
          </div>

          <?php if (!empty($loginError)): ?>
            <div class="alert alert-danger text-center" role="alert">
              <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
      });
    });
  </script>
</body>
</html>