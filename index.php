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
