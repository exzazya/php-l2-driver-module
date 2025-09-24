<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
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
          <img src="<?php echo asset('img/jetlouge_logo.webp'); ?>" alt="Jetlouge Travels">
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

        <?php if (isset($loginError)): ?>
          <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-3">
            <label for="email" class="form-label fw-semibold">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="text" class="form-control" id="email" name="username" placeholder="Enter your email" value="<?php echo isset($oldUsername) ? $oldUsername : ''; ?>" required>
            </div>
              <small class="form-text text-muted">
                Only drivers can sign in.
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

          <div class="mb-3 form-check text-start">
            <?php $autoCheckPolicies = (!empty($_COOKIE['policies_accepted']) || (!empty($auto_check_policies) && $auto_check_policies) || (isset($_GET['accepted']) && $_GET['accepted'] === '1')); ?>
            <input type="checkbox" class="form-check-input" id="accept_policies" name="accept_policies" value="1" <?php echo $autoCheckPolicies ? 'checked' : ''; ?>>
            <label class="form-check-label" for="accept_policies">
              I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#policiesModal">Terms &amp; Conditions and Privacy Policy</a>.
            </label>
          </div>

          <button type="submit" class="btn btn-login mb-3 w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
          </button>

          <div class="text-center">
              <a href="<?php echo route('forgot-password'); ?>" class="btn-forgot">Forgot your password?</a>
          </div>
          </form>
          </div>
      </div>
    </div>
  </div>

  <!-- Simple Policies Modal -->
  <div class="modal fade" id="policiesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title">Terms &amp; Privacy Policy</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="max-height: 65vh;">
          <div class="mb-4">
            <h5 class="fw-bold text-primary mb-3">Terms &amp; Conditions</h5>
            <p class="small text-muted"><strong>Effective Date:</strong> 2024</p>
            <p class="small">Welcome to Logistics2 by Jetlouge Travels. By creating an account or using our services, you agree to the following terms:</p>
            
            <h6 class="fw-semibold mt-3">1. User Roles</h6>
            <ul class="small">
              <li><strong>Driver:</strong> Uses the driver portal and consents to GPS tracking</li>
              <li><strong>Fleet Manager:</strong> Oversees vehicles, drivers, and dispatching</li>
              <li><strong>Admin:</strong> Manages the overall system and controls access</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">2. Account Use</h6>
            <ul class="small">
              <li>Users must provide accurate information (email, phone number, and other details)</li>
              <li>Each account is personal and must not be shared with others</li>
              <li>Any attempt to bypass security, manipulate GPS data, or misuse the system may result in suspension or termination</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">3. GPS Tracking</h6>
            <ul class="small">
              <li>Drivers agree that their GPS location will be tracked while logged into the driver portal</li>
              <li>Location data is collected only for operational purposes such as dispatching, monitoring, and safety</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">4. Responsibilities</h6>
            <ul class="small">
              <li>We strive to keep the system available and secure but cannot guarantee uninterrupted service</li>
              <li>Users are responsible for maintaining the confidentiality of their login credentials</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">5. Termination</h6>
            <ul class="small">
              <li>We reserve the right to suspend or terminate accounts that violate these Terms</li>
              <li>Access may also be revoked for security, compliance, or misuse</li>
            </ul>
          </div>
          
          <hr class="my-4">
          
          <div>
            <h5 class="fw-bold text-primary mb-3">Privacy Policy</h5>
            <p class="small text-muted"><strong>Effective Date:</strong> 2024</p>
            <p class="small">We respect your privacy and are committed to protecting your personal data. This Privacy Policy explains what information we collect and how it is used.</p>
            
            <h6 class="fw-semibold mt-3">1. Data We Collect</h6>
            <ul class="small">
              <li><strong>Personal Information:</strong> Name, email, phone number, account details</li>
              <li><strong>Location Data:</strong> Real-time GPS tracking for drivers when logged into the driver portal</li>
              <li><strong>System Usage Data:</strong> Login times, activity logs, and performance data</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">2. How We Use Your Data</h6>
            <ul class="small">
              <li>To manage fleet operations and dispatch assignments</li>
              <li>To monitor driver performance and ensure safety</li>
              <li>To communicate with users (e.g., notifications, updates)</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">3. Data Sharing</h6>
            <ul class="small">
              <li>Data is shared only with authorized personnel (Admins and Fleet Managers)</li>
              <li>We do not sell or rent your personal data to third parties</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">4. Data Retention</h6>
            <ul class="small">
              <li>Data is stored as long as you have an active account or as required by law</li>
              <li>You may request deletion of your account and related data by contacting us</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">5. Security</h6>
            <ul class="small">
              <li>We implement reasonable security measures (such as encryption and access control) to protect your information</li>
              <li>However, no system is completely secure, and we cannot guarantee absolute protection</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">6. User Rights</h6>
            <ul class="small">
              <li>You may request access to the personal data we store about you</li>
              <li>You may request correction or deletion of your information, subject to operational and legal requirements</li>
            </ul>
            
            <h6 class="fw-semibold mt-3">7. Contact Us</h6>
            <p class="small">For questions about this Privacy Policy or your data, please contact support.</p>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnAgreeModal">I Agree</button>
        </div>
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
      // Submit spinner and disable button (match L2 behavior)
      const loginForm = document.getElementById('loginForm');
      if (loginForm) {
        loginForm.addEventListener('submit', function() {
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>';
            submitBtn.disabled = true;
          }
        });
      }
      // Simple modal agree handler
      const btnAgreeModal = document.getElementById('btnAgreeModal');
      if (btnAgreeModal) {
        btnAgreeModal.addEventListener('click', function() {
          const cb = document.getElementById('accept_policies');
          if (cb) { 
            cb.checked = true;
            // Set cookie for persistence
            document.cookie = 'policies_accepted=1;path=/;max-age=31536000;SameSite=Lax';
          }
          const modal = bootstrap.Modal.getInstance(document.getElementById('policiesModal'));
          if (modal) modal.hide();
        });
      }
    });
  </script>
  </body>
  </html>