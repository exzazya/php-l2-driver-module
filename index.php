<?php
// Simple PHP Router for Jetlouge Travels Driver Module
session_start();

// Determine base path (when deployed in a subdirectory) and current route
$request_uri = $_SERVER['REQUEST_URI'];
$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$path = parse_url($request_uri, PHP_URL_PATH);

// Strip base path prefix from request path
if ($script_dir && strpos($path, $script_dir) === 0) {
    $path = substr($path, strlen($script_dir));
}
$path = '/' . ltrim($path, '/');
$path = rtrim($path, '/');

// Current route (support query-string routing when mod_rewrite is unavailable)
if (isset($_GET['route']) && $_GET['route'] !== '') {
    $route = '/' . ltrim($_GET['route'], '/');
} else {
    $route = $path ?: '/';
}

// Define base URL for assets
define('BASE_URL', '//' . $_SERVER['HTTP_HOST'] . $script_dir);

// Asset helper function
function asset($path) {
    return BASE_URL . '/public/' . ltrim($path, '/');
}

// URL helper for internal redirects/links
function url($path) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $script = ($base ?: '') . '/index.php';
    // For query-string based routing, always target index.php explicitly
    if (strpos($path, '/?') === 0) {
        return $script . substr($path, 1);
    }
    return ($base ?: '') . $path;
}

// Route helper function
function route($name, $params = []) {
    $routes = [
        'login' => '/',
        'dashboard' => '/dashboard',
        'live-tracking' => '/live-tracking',
        'trip-assignment' => '/trip-assignment',
        'reports-and-checklist' => '/reports-and-checklist',
        'profile-upload' => '/profile-upload',
        'logout' => '/logout',
        'twofa' => '/auth/2fa',
        'account-security' => '/account/security',
    ];
    
    $path = $routes[$name] ?? '/';
    // For the root path, return index.php with query-string routing to ensure compatibility
    if ($path === '/') {
        return url('/?route=/');
    }
    // Use query-string routing to avoid dependency on mod_rewrite for non-root paths
    $qs = '/?route=' . ltrim($path, '/');
    return url($qs);
}

// Request helper function
function request() {
    global $route;
    return new class($route) {
        private $route;
        
        public function __construct($route) {
            $this->route = $route;
        }
        
        public function routeIs($routeName) {
            $routes = [
                'dashboard' => '/dashboard',
                'live-tracking' => '/live-tracking',
                'trip-assignment' => '/trip-assignment',
                'reports-and-checklist' => '/reports-and-checklist',
                'account-security' => '/account/security',
            ];
            
            return isset($routes[$routeName]) && $routes[$routeName] === $this->route;
        }
        
        public function is($pattern) {
            return fnmatch($pattern, $this->route);
        }
    };
}

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $route === '/') {
    require_once 'includes/auth.php';
    require_once 'includes/database.php';
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    
    // Ensure DB is reachable before attempting login
    if (!getDBConnection()) {
        $loginError = 'Database connection error. Please try again later.';
        $oldUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    } elseif (login($username, $password)) {
        // If 2FA is staged, send to the verification route; otherwise go to dashboard
        if (!empty($_SESSION['2fa_pending_driver'])) {
            header('Location: ' . route('twofa'));
            exit();
        } else {
            header('Location: ' . route('dashboard'));
            exit();
        }
    } else {
        $loginError = 'Invalid username or password';
        $oldUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    }
}

// Simple routing
switch ($route) {
    case '/':
        require_once 'includes/auth.php';
        if (isLoggedIn()) {
            header('Location: ' . route('dashboard'));
            exit();
        }
        include 'views/login.php';
        break;
    case '/account/security':
        require_once 'includes/auth.php';
        require_once 'includes/database.php';
        requireLogin();
        $driverId = $_SESSION['driver_id'] ?? 0;
        $dbDriver = $driverId ? getDriverById((int)$driverId) : false;
        if (!$dbDriver) { $_SESSION['flash_error'] = 'Driver account not found or inactive.'; header('Location: ' . route('dashboard')); exit; }

        $twofaEnabled = (int)($dbDriver['twofa_enabled'] ?? 0) === 1;
        $twofaMethod  = (string)($dbDriver['twofa_method'] ?? 'email');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'start_2fa_email') {
                try { maybeSendDriverEmailOtp($dbDriver, true); } catch (Exception $e) {}
                $_SESSION['2fa_email_setup'] = true;
                $_SESSION['flash_info'] = 'We sent a verification code to your email. Enter it to enable 2FA.';
                header('Location: ' . route('account-security'));
                exit;
            } elseif ($action === 'resend_2fa_email') {
                try { maybeSendDriverEmailOtp($dbDriver, false); } catch (Exception $e) {}
                // Preserve password form data for password change flow
                if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
                    $_SESSION['password_form_data'] = [
                        'current_password' => $_POST['current_password'] ?? '',
                        'new_password' => $_POST['new_password'] ?? '',
                        'confirm_password' => $_POST['confirm_password'] ?? ''
                    ];
                }
                $_SESSION['flash_info'] = 'We resent the verification code to your email.';
                header('Location: ' . route('account-security'));
                exit;
            } elseif ($action === 'confirm_2fa_email') {
                $otp = isset($_POST['otp']) ? preg_replace('/\D+/', '', $_POST['otp']) : '';
                if ($otp !== '' && verifyDriverEmailOtp((int)$driverId, $otp)) {
                    setDriverTwoFactor((int)$driverId, 1, null);
                    setDriverTwoFactorMethod((int)$driverId, 'email');
                    unset($_SESSION['2fa_email_setup']);
                    $_SESSION['flash_success'] = 'Two-factor authentication (Email) enabled.';
                } else {
                    $_SESSION['flash_error'] = 'Invalid verification code.';
                }
                header('Location: ' . route('account-security'));
                exit;
            } elseif ($action === 'disable_2fa_email') {
                $otp = isset($_POST['otp']) ? preg_replace('/\D+/', '', $_POST['otp']) : '';
                if ($otp !== '' && verifyDriverEmailOtp((int)$driverId, $otp)) {
                    setDriverTwoFactor((int)$driverId, 0, null);
                    $_SESSION['flash_success'] = 'Two-factor authentication disabled.';
                } else {
                    $_SESSION['flash_error'] = 'Invalid code. 2FA not disabled.';
                }
                header('Location: ' . route('account-security'));
                exit;
            } elseif ($action === 'change_password') {
                $current = (string)($_POST['current_password'] ?? '');
                $new = (string)($_POST['new_password'] ?? '');
                $confirm = (string)($_POST['confirm_password'] ?? '');
                $otp = isset($_POST['otp']) ? preg_replace('/\D+/', '', $_POST['otp']) : '';
                if ($new === '' || strlen($new) < 8) { $_SESSION['flash_error'] = 'New password must be at least 8 characters.'; header('Location: ' . route('account-security')); exit; }
                if ($new !== $confirm) { $_SESSION['flash_error'] = 'New password confirmation does not match.'; header('Location: ' . route('account-security')); exit; }
                if (!passwordMatches($dbDriver, $current, false)) { $_SESSION['flash_error'] = 'Current password is incorrect.'; header('Location: ' . route('account-security')); exit; }
                if ($twofaEnabled) {
                    if ($otp === '' || !verifyDriverEmailOtp((int)$driverId, $otp)) {
                        $_SESSION['flash_error'] = 'Invalid or missing authentication code.'; header('Location: ' . route('account-security')); exit;
                    }
                }
                $hash = password_hash($new, PASSWORD_DEFAULT);
                updateDriverPasswordHash((int)$driverId, $hash);
                $_SESSION['flash_success'] = 'Password updated successfully.';
                header('Location: ' . route('account-security'));
                exit;
            }
        }
        include 'views/account/security.php';
        break;
    case '/auth/2fa':
        require_once 'includes/auth.php';
        // Require a staged driver pending record
        if (empty($_SESSION['2fa_pending_driver']) || empty($_SESSION['2fa_pending_driver']['driver_id'])) {
            header('Location: ' . route('login'));
            exit();
        }
        $pending = $_SESSION['2fa_pending_driver'];
        $twofaMasked = $pending['email_mask'] ?? '';
        $twofaMethod = 'email';
        $twofaError = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'resend') {
                $drv = getDriverById((int)$pending['driver_id']);
                if ($drv) { try { maybeSendDriverEmailOtp($drv, false); } catch (Exception $e) {} }
            } else {
                $otp = isset($_POST['otp']) ? preg_replace('/\D+/', '', $_POST['otp']) : '';
                if ($otp !== '' && verifyDriverEmailOtp((int)$pending['driver_id'], $otp)) {
                    $drv = getDriverById((int)$pending['driver_id']);
                    if ($drv) { establishDriverSession($drv); }
                    unset($_SESSION['2fa_pending_driver'], $_SESSION['2fa_method']);
                    header('Location: ' . route('dashboard'));
                    exit();
                } else {
                    $twofaError = 'Invalid verification code.';
                }
            }
        }
        include 'views/auth/2fa.php';
        break;
        
    case '/dashboard':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/dashboard.php';
        break;
        
    case '/live-tracking':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/live-tracking.php';
        break;

    case '/trip-assignment':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/trip-assignment.php';
        break;

    case '/reports-and-checklist':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/reports-and-checklist.php';
        break;
    
    case '/profile-upload':
        require_once 'includes/auth.php';
        require_once 'includes/database.php';
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            break;
        }
        $driverId = $_SESSION['driver_id'] ?? 0;
        if (!$driverId) {
            http_response_code(401);
            echo 'Unauthorized';
            break;
        }
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'No image uploaded or upload error.';
            header('Location: ' . route('dashboard'));
            exit();
        }
        $file = $_FILES['avatar'];
        // Basic validation
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = 'Image too large. Max 5MB.';
            header('Location: ' . route('dashboard'));
            exit();
        }
        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = @finfo_file($finfo, $file['tmp_name']);
                if ($detected) { $mime = $detected; }
                @finfo_close($finfo);
            }
        }
        if (!$mime && !empty($file['type'])) {
            $mime = $file['type'];
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $ext = null;
        if ($mime && isset($allowed[$mime])) {
            $ext = $allowed[$mime];
        }
        if (!$ext) {
            // fallback to original filename extension
            $pi = pathinfo($file['name']);
            $origExt = isset($pi['extension']) ? strtolower($pi['extension']) : '';
            if ($origExt === 'jpeg') { $origExt = 'jpg'; }
            if (in_array($origExt, ['jpg','png','gif','webp'])) {
                $ext = $origExt;
            }
        }
        if (!$ext) {
            $_SESSION['flash_error'] = 'Invalid or unsupported image type.';
            header('Location: ' . route('dashboard'));
            exit();
        }
        $safeBase = 'driver_' . intval($driverId) . '_' . date('Ymd_His');
        $fileName = $safeBase . '.' . $ext;
        $targetDir = __DIR__ . '/uploads/profile_image';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $targetPath = $targetDir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['flash_error'] = 'Failed to save uploaded file.';
            header('Location: ' . route('dashboard'));
            exit();
        }
        // Compute public path relative to app root
        $publicPath = 'uploads/profile_image/' . $fileName;
        // Update database
        $ok = executeQuery("UPDATE drivers SET profile_image = ?, updated_at = NOW() WHERE id = ?", [$publicPath, $driverId]);
        if ($ok) {
            // Update session if we store the path there
            $_SESSION['profile_image'] = $publicPath;
            $_SESSION['flash_success'] = 'Profile photo updated.';
        } else {
            $_SESSION['flash_error'] = 'Failed to update profile image in database.';
        }
        // Redirect back to dashboard (or referer if within app)
        $ref = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : route('dashboard');
        header('Location: ' . $ref);
        exit();
        
    case '/logout':
        require_once 'includes/auth.php';
        logout();
        break;
        
    default:
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        break;
}
?>
