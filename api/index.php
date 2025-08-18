<?php
// API Router for Jetlouge Travels Fleet Management
require_once '../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path = rtrim($path, '/');

// Route API requests
switch ($path) {
    case '/auth/login':
    case '/auth/logout':
    case '/auth/profile':
        require_once 'auth.php';
        break;
        
    case '/':
    case '':
        // API Documentation
        echo generateApiResponse(true, [
            'name' => 'Jetlouge Travels Fleet Management API',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /api/auth/login' => 'Admin login (username, password)',
                'POST /api/auth/logout' => 'Admin logout (requires token)',
                'GET /api/auth/profile' => 'Get admin profile (requires token)',
                // test endpoint removed for production
            ],
            'authentication' => 'Bearer token in Authorization header'
        ], 'API is running');
        break;
        
    default:
        http_response_code(404);
        echo generateApiResponse(false, null, 'API endpoint not found');
        break;
}
?>
