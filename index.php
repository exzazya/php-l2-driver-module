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
    ];
    
    $path = $routes[$name] ?? '/';
    // Use query-string routing to avoid dependency on mod_rewrite
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
        // Send explicitly to dashboard via query-string route to avoid DirectoryIndex dependency
        header('Location: ' . route('dashboard'));
        exit();
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
