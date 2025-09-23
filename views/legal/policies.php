<?php /* Driver Combined Policies: Terms & Conditions + Privacy Policy */
// Agree & Return handler: set cookie, redirect back with accepted=1
if (isset($_GET['agree']) && $_GET['agree'] === '1') {
  $ret = isset($_GET['return']) ? (string)$_GET['return'] : '';
  $cookieParams = [
    'expires' => time() + 31536000,
    'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
  ];
  setcookie('policies_accepted', '1', $cookieParams);
  if ($ret !== '') {
    $sep = (strpos($ret, '?') === false) ? '?' : '&';
    header('Location: ' . $ret . $sep . 'accepted=1');
    exit;
  }
}
$backTo = isset($_GET['return']) && $_GET['return'] !== '' ? (string)$_GET['return'] : route('login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Policies - Jetlouge Travels</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo asset('css/login.css'); ?>">
  <style>
    .legal-card{max-width:960px;margin:40px auto;background:#fff;border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,.1)}
    .legal-card .card-body{padding:2rem 2rem}
    body{background:linear-gradient(135deg,var(--jetlouge-primary) 0%,var(--jetlouge-secondary) 100%)}
    .section-divider{border-top:2px solid #e5e7eb;margin:2rem 0}
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="legal-card">
      <div class="card-body">
        <h1 class="h3 mb-3" style="color:var(--jetlouge-primary);font-weight:700;">Policies</h1>
        <p class="text-muted">This page contains our Terms &amp; Conditions and Privacy Policy.</p>

        <hr class="section-divider" />
        <section id="terms">
          <h2 class="h4 mb-3">Terms &amp; Conditions</h2>
          <p class="text-muted mb-3"><strong>Effective Date:</strong> [2026]</p>
          <p>Welcome to Logistics2 by Jetlouge Travels. By creating an account or using our services, you agree to the following terms:</p>
          <h3 class="h5 mt-4">1. User Roles</h3>
          <ul>
            <li><strong>Driver:</strong> Uses the driver portal and consents to GPS tracking.</li>
            <li><strong>Fleet Manager:</strong> Oversees vehicles, drivers, and dispatching.</li>
            <li><strong>Admin:</strong> Manages the overall system and controls access.</li>
          </ul>
          <h3 class="h5 mt-4">2. Account Use</h3>
          <ul>
            <li>Users must provide accurate information (email, phone number, and other details).</li>
            <li>Each account is personal and must not be shared with others.</li>
            <li>Any attempt to bypass security, manipulate GPS data, or misuse the system may result in suspension or termination.</li>
          </ul>
          <h3 class="h5 mt-4">3. GPS Tracking</h3>
          <ul>
            <li>Drivers agree that their GPS location will be tracked while logged into the driver portal.</li>
            <li>Location data is collected only for operational purposes such as dispatching, monitoring, and safety.</li>
          </ul>
          <h3 class="h5 mt-4">4. Responsibilities</h3>
          <ul>
            <li>We strive to keep the system available and secure but cannot guarantee uninterrupted service.</li>
            <li>Users are responsible for maintaining the confidentiality of their login credentials.</li>
          </ul>
          <h3 class="h5 mt-4">5. Termination</h3>
          <ul>
            <li>We reserve the right to suspend or terminate accounts that violate these Terms.</li>
            <li>Access may also be revoked for security, compliance, or misuse.</li>
          </ul>
          <h3 class="h5 mt-4">6. Governing Law</h3>
          <p>These Terms shall be governed by the laws of [Your Country].</p>
        </section>

        <hr class="section-divider" />
        <section id="privacy">
          <h2 class="h4 mb-3">Privacy Policy</h2>
          <p class="text-muted mb-3"><strong>Effective Date:</strong> 2026</p>
          <p>We respect your privacy and are committed to protecting your personal data. This Privacy Policy explains what information we collect and how it is used.</p>
          <h3 class="h5 mt-4">1. Data We Collect</h3>
          <ul>
            <li><strong>Personal Information:</strong> Name, email, phone number, account details.</li>
            <li><strong>Location Data:</strong> Real-time GPS tracking for drivers when logged into the driver portal.</li>
            <li><strong>System Usage Data:</strong> Login times, activity logs, and performance data.</li>
          </ul>
          <h3 class="h5 mt-4">2. How We Use Your Data</h3>
          <ul>
            <li>To manage fleet operations and dispatch assignments.</li>
            <li>To monitor driver performance and ensure safety.</li>
            <li>To communicate with users (e.g., notifications, updates).</li>
          </ul>
          <h3 class="h5 mt-4">3. Data Sharing</h3>
          <ul>
            <li>Data is shared only with authorized personnel (Admins and Fleet Managers).</li>
            <li>We do not sell or rent your personal data to third parties.</li>
          </ul>
          <h3 class="h5 mt-4">4. Data Retention</h3>
          <ul>
            <li>Data is stored as long as you have an active account or as required by law.</li>
            <li>You may request deletion of your account and related data by contacting us.</li>
          </ul>
          <h3 class="h5 mt-4">5. Security</h3>
          <ul>
            <li>We implement reasonable security measures (such as encryption and access control) to protect your information.</li>
            <li>However, no system is completely secure, and we cannot guarantee absolute protection.</li>
          </ul>
          <h3 class="h5 mt-4">6. User Rights</h3>
          <ul>
            <li>You may request access to the personal data we store about you.</li>
            <li>You may request correction or deletion of your information, subject to operational and legal requirements.</li>
          </ul>
          <h3 class="h5 mt-4">7. Contact Us</h3>
          <p>For questions about this Privacy Policy or your data, please contact:<br>
             Email: [Insert Contact Email]<br>
             Phone: [Insert Contact Number]</p>
        </section>

        <div class="mt-4 d-flex gap-2">
          <a class="btn btn-primary" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') . (strpos($_SERVER['REQUEST_URI'], '?') === false ? '?' : '&'); ?>agree=1&return=<?php echo urlencode($backTo); ?>">I Agree &amp; Return</a>
          <a class="btn btn-outline-primary" href="<?php echo $backTo; ?>">Back to Sign In</a>
        </div>
      </div>
    </div>
  </div>
  </body>
</html>
