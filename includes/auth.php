<?php
// Authentication functions for Jetlouge Travels Fleet Management
require_once 'database.php';
session_start();

function isLoggedIn() {
    return (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) || 
           (isset($_SESSION['driver_id']) && !empty($_SESSION['driver_id']));
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
    // Get user (admin or driver) from database
    $user = getUserByCredentials($identifier);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['user_type'] === 'admin') {
            // Set admin session variables
            $_SESSION['user_type'] = 'admin';
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Update last login for admin
            updateLastLogin($user['id']);
        } else {
            // Set driver session variables
            $_SESSION['user_type'] = 'driver';
            $_SESSION['driver_id'] = $user['id'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['email']; // Use email as username for drivers
            $_SESSION['full_name'] = $user['name'];
            $_SESSION['role'] = 'driver';
            $_SESSION['email'] = $user['email'];
            $_SESSION['license_number'] = $user['license_number'];
            
            // Update last login for driver (you can add this function later)
            // updateDriverLastLogin($user['id']);
        }
        
        return true;
    }
    // Server-side diagnostics (do not expose sensitive info to users)
    if (!$user) {
        error_log('[AUTH] Login failed: user not found for identifier=' . (string)$identifier);
    } else {
        error_log('[AUTH] Login failed: password mismatch for user_type=' . $user['user_type'] . ' id=' . $user['id']);
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
