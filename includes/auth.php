<?php
// Authentication functions for Jetlouge Travels Driver Module (Driver-only login)
require_once 'database.php';
session_start();

function isLoggedIn() {
    // Driver-only session
    return (isset($_SESSION['driver_id']) && !empty($_SESSION['driver_id']));
}

function requireLogin() {
    if (!isLoggedIn()) {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $login = ($base === '' ? '/' : $base . '/');
        header('Location: ' . $login);
        exit();
    }
}

function login($identifier, $password) {
    // Driver-only authentication by email
    $driver = getDriverByEmail($identifier);
    
    if ($driver && password_verify($password, $driver['password_hash'])) {
        // Set driver session variables
        $_SESSION['user_type'] = 'driver';
        $_SESSION['driver_id'] = $driver['id'];
        $_SESSION['user_id'] = $driver['id'];
        $_SESSION['username'] = $driver['email']; // Use email as username for drivers
        $_SESSION['full_name'] = $driver['name'] ?? '';
        $_SESSION['role'] = 'driver';
        $_SESSION['email'] = $driver['email'];
        $_SESSION['license_number'] = $driver['license_number'] ?? '';
        
        return true;
    }
    // Server-side diagnostics (do not expose sensitive info to users)
    if (!$driver) {
        error_log('[AUTH] Login failed (driver-only): driver not found for email=' . (string)$identifier);
    } else {
        error_log('[AUTH] Login failed (driver-only): password mismatch for driver id=' . $driver['id']);
    }
    
    return false;
}

function logout() {
    session_destroy();
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $login = ($base === '' ? '/' : $base . '/');
    header('Location: ' . $login);
    exit();
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? 'Unknown',
            'full_name' => $_SESSION['full_name'] ?? 'Unknown',
            'role' => $_SESSION['role'] ?? 'user',
            'email' => $_SESSION['email'] ?? '',
            'user_type' => $_SESSION['user_type'] ?? 'admin'
        ];
    }
    return null;
}

// API Authentication functions
function authenticateApiRequest() {
    $headers = getallheaders();
    $token = null;
    
    // Check Authorization header
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            $token = substr($auth, 7);
        }
    }
    
    // Check token parameter
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    if ($token) {
        return validateApiToken($token);
    }
    
    return false;
}

function generateApiResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    return json_encode($response, JSON_PRETTY_PRINT);
}
?>
