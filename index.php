<?php
// Simple PHP Router for Jetlouge Travels Fleet Management System
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
        'fvm.vehicles' => '/fvm/vehicles',
        'fvm.maintenance' => '/fvm/maintenance',
        'fvm.assignment-tracking' => '/fvm/assignment-tracking',
        'fvm.request-new-vehicle' => '/fvm/request-new-vehicle',
        'vrds.reservation' => '/vrds/reservation',
        'vrds.dispatch-scheduling' => '/vrds/dispatch-scheduling',
        'dtpm.driver-profiles' => '/dtpm/driver-profiles',
        'dtpm.trip-reports' => '/dtpm/trip-reports',
        'dtpm.performance' => '/dtpm/performance',
        'tcao.fuel-and-trip-cost' => '/tcao/fuel-and-trip-cost',
        'tcao.utilization' => '/tcao/utilization',
        'tcao.optimization' => '/tcao/optimization',
        'logout' => '/logout',
    ];
    
    $path = isset($routes[$name]) ? $routes[$name] : '/';
    // Use query-string routing to avoid dependency on mod_rewrite
    $qs = '/?route=' . ltrim($path, '/');
    return url($qs);
}

// Request helper function
function request() {
    return new class {
        public function routeIs($routeName) {
            global $route;
            $routes = [
                'dashboard' => '/dashboard',
                'fvm.vehicles' => '/fvm/vehicles',
                'fvm.maintenance' => '/fvm/maintenance',
                'fvm.assignment-tracking' => '/fvm/assignment-tracking',
                'fvm.request-new-vehicle' => '/fvm/request-new-vehicle',
                'vrds.reservation' => '/vrds/reservation',
                'vrds.dispatch-scheduling' => '/vrds/dispatch-scheduling',
                'dtpm.driver-profiles' => '/dtpm/driver-profiles',
                'dtpm.trip-reports' => '/dtpm/trip-reports',
                'dtpm.performance' => '/dtpm/performance',
                'tcao.fuel-and-trip-cost' => '/tcao/fuel-and-trip-cost',
                'tcao.utilization' => '/tcao/utilization',
                'tcao.optimization' => '/tcao/optimization',
            ];
            
            return isset($routes[$routeName]) && $routes[$routeName] === $route;
        }
        
        public function is($pattern) {
            global $route;
            return fnmatch($pattern, $route);
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
        // If already logged in, show dashboard directly (works even without mod_rewrite)
        require_once 'includes/auth.php';
        if (isLoggedIn()) {
            include 'views/dashboard.php';
        } else {
            include 'views/login.php';
        }
        break;
    case '/dashboard':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/dashboard.php';
        break;
    case '/fvm/vehicles':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/fvm/vehicles.php';
        break;
    case '/fvm/maintenance':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/fvm/maintenance.php';
        break;
    case '/fvm/assignment-tracking':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/fvm/assignment-tracking.php';
        break;
    case '/fvm/request-new-vehicle':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/fvm/request-new-vehicle.php';
        break;
    case '/vrds/reservation':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/vrds/reservation.php';
        break;
    case '/vrds/dispatch-scheduling':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/vrds/dispatch-scheduling.php';
        break;
    case '/dtpm/driver-profiles':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/dtpm/driver-profiles.php';
        break;
    case '/dtpm/trip-reports':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/dtpm/trip-reports.php';
        break;
    case '/dtpm/performance':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/dtpm/performance.php';
        break;
    case '/tcao/fuel-and-trip-cost':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/tcao/fuel-and-trip-cost.php';
        break;
    case '/tcao/utilization':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/tcao/utilization.php';
        break;
    case '/tcao/optimization':
        require_once 'includes/auth.php';
        requireLogin();
        include 'views/tcao/optimization.php';
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
