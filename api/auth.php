<?php
// API Authentication Endpoints for Jetlouge Travels Fleet Management
require_once '../includes/auth.php';
require_once '../includes/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Route API requests
switch ($method) {
    case 'POST':
        if (end($pathParts) === 'login') {
            handleLogin();
        } elseif (end($pathParts) === 'logout') {
            handleLogout();
        } else {
            http_response_code(404);
            echo generateApiResponse(false, null, 'Endpoint not found');
        }
        break;
        
    case 'GET':
        if (end($pathParts) === 'profile') {
            handleGetProfile();
        } else {
            http_response_code(404);
            echo generateApiResponse(false, null, 'Endpoint not found');
        }
        break;
        
    default:
        http_response_code(405);
        echo generateApiResponse(false, null, 'Method not allowed');
        break;
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo generateApiResponse(false, null, 'Username and password required');
        return;
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo generateApiResponse(false, null, 'Username and password cannot be empty');
        return;
    }
    
    // Attempt login
    if (login($username, $password)) {
        $user = getCurrentUser();
        $token = createApiToken($user['id']);
        
        if ($token) {
            echo generateApiResponse(true, [
                'user' => $user,
                'token' => $token,
                'expires_in' => 86400 // 24 hours in seconds
            ], 'Login successful');
        } else {
            http_response_code(500);
            echo generateApiResponse(false, null, 'Failed to generate API token');
        }
    } else {
        http_response_code(401);
        echo generateApiResponse(false, null, 'Invalid username or password');
    }
}

function handleLogout() {
    $auth = authenticateApiRequest();
    
    if (!$auth) {
        http_response_code(401);
        echo generateApiResponse(false, null, 'Authentication required');
        return;
    }
    
    // Invalidate the token
    $token = null;
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
        }
    }
    
    if ($token) {
        executeQuery("DELETE FROM api_tokens WHERE token = ?", [$token]);
    }
    
    echo generateApiResponse(true, null, 'Logout successful');
}

function handleGetProfile() {
    $auth = authenticateApiRequest();
    
    if (!$auth) {
        http_response_code(401);
        echo generateApiResponse(false, null, 'Authentication required');
        return;
    }
    
    $admin = getAdminById($auth['admin_id']);
    
    if ($admin) {
        // Remove sensitive data
        unset($admin['password_hash']);
        
        echo generateApiResponse(true, $admin, 'Profile retrieved successfully');
    } else {
        http_response_code(404);
        echo generateApiResponse(false, null, 'User not found');
    }
}

// Removed test endpoint for production
?>
