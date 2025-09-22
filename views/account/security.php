<?php
$title = "Account Security";

ob_start();
?>
<div class="page-header-container mb-4">
  <div class="d-flex align-items-center">
    <div class="dashboard-logo me-3">
      <img src="<?php echo asset('img/jetlouge_logo.webp'); ?>" class="logo-img" alt>
    </div>
    <div>
      <h2 class="fw-bold mb-1">Account Security</h2>
      <p class="text-muted mb-0">Two-Factor Authentication and Password</p>
    </div>
  </div>
</div>
<div class="container py-3">
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header">Two-Factor Authentication (2FA)</div>
        <div class="card-body">
          <?php if (empty($twofaEnabled)): ?>
            <p class="text-muted">Protect your account with an extra verification step. We will send a 6-digit code to your email.</p>
            <form method="post" class="mb-2">
              <button type="submit" name="action" value="start_2fa_email" class="btn btn-primary">Send Code to Email</button>
            </form>
            <?php if (!empty($_SESSION['2fa_email_setup'])): ?>
              <hr>
              <form method="post">
                <div class="mb-2">
                  <label class="form-label">Verification Code (sent to your email)</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
                    <button type="submit" name="action" value="resend_2fa_email" class="btn btn-outline-secondary" formnovalidate>Send Code</button>
                  </div>
                </div>
                <button type="submit" name="action" value="confirm_2fa_email" class="btn btn-success">Confirm & Enable 2FA</button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-success">2FA is enabled (Email).</div>
            <form method="post" class="mb-2">
              <label class="form-label">Verification Code (sent to your email)</label>
              <div class="input-group">
                <input type="text" class="form-control" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
                <button type="submit" name="action" value="resend_2fa_email" class="btn btn-outline-secondary" formnovalidate>Send Code</button>
              </div>
              <button type="submit" name="action" value="disable_2fa_email" class="btn btn-outline-danger mt-2">Disable 2FA</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header">Change Password</div>
        <div class="card-body">
          <form method="post" id="passwordForm">
            <?php 
            // Get password form data from session or POST
            $formData = $_SESSION['password_form_data'] ?? [];
            $currentPassword = $_POST['current_password'] ?? $formData['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? $formData['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? $formData['confirm_password'] ?? '';
            // Clear session data after using it
            unset($_SESSION['password_form_data']);
            ?>
            <div class="mb-2">
              <label class="form-label">Current Password</label>
              <input type="password" class="form-control" name="current_password" value="<?php echo htmlspecialchars($currentPassword, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="row g-2">
              <div class="col-12 col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" value="<?php echo htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8'); ?>" minlength="8" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" value="<?php echo htmlspecialchars($confirmPassword, ENT_QUOTES, 'UTF-8'); ?>" minlength="8" required>
              </div>
            </div>
            <?php if (!empty($twofaEnabled)): ?>
              <label class="form-label mt-2">Authentication Code</label>
              <div class="input-group mb-2">
                <input type="text" class="form-control" name="otp" inputmode="numeric" pattern="[0-9]*" maxlength="6" required>
                <button type="button" class="btn btn-outline-secondary" onclick="sendPasswordChangeCode()">Send Code</button>
              </div>
            <?php endif; ?>
            <button type="submit" name="action" value="change_password" class="btn btn-primary mt-2">Update Password</button>
          </form>
          
          <?php if (!empty($twofaEnabled)): ?>
          <form method="post" id="sendCodeForm" style="display: none;">
            <input type="hidden" name="action" value="resend_2fa_email">
            <input type="hidden" name="current_password" value="">
            <input type="hidden" name="new_password" value="">
            <input type="hidden" name="confirm_password" value="">
          </form>
          
          <script>
          function sendPasswordChangeCode() {
            const form = document.getElementById('passwordForm');
            const sendForm = document.getElementById('sendCodeForm');
            
            // Copy password values to hidden form
            sendForm.querySelector('input[name="current_password"]').value = form.querySelector('input[name="current_password"]').value;
            sendForm.querySelector('input[name="new_password"]').value = form.querySelector('input[name="new_password"]').value;
            sendForm.querySelector('input[name="confirm_password"]').value = form.querySelector('input[name="confirm_password"]').value;
            
            // Submit the send code form
            sendForm.submit();
          }
          </script>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/app.php'; ?>
