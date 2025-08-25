<?php
// Authentication functions for Jetlouge Travels Driver Module (Driver-only login)
require_once 'database.php';
session_start();

// Provide getallheaders() for non-Apache environments (e.g., some Windows/IIS setups)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}

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
    
    if ($driver && passwordMatches($driver, $password, true)) {
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

// Check password against stored hash. Supports bcrypt/argon via password_verify and
// legacy SHA2 (sha256/sha512) hex strings from manual inserts. If $migrate is true
// and a legacy hash matches, it will be upgraded to password_hash() securely.
function passwordMatches($driver, $password, $migrate = false) {
    if (!isset($driver['password_hash'])) return false;
    $stored = (string)$driver['password_hash'];
    // Modern hashed password (bcrypt/argon prefixed with $)
    if (strlen($stored) > 0 && $stored[0] === '$') {
        return password_verify($password, $stored);
    }
    // Legacy hashes: handle hex and binary for SHA-256/SHA-512, and optionally SHA1/MD5
    $ok = false;
    $len = strlen($stored);
    $isHex = ctype_xdigit($stored);
    // SHA-256
    if ($isHex && $len === 64) { // sha256 hex
        $ok = hash_equals(strtolower($stored), hash('sha256', $password));
    } elseif (!$isHex && $len === 32) { // sha256 binary (32 bytes)
        $ok = hash_equals($stored, hash('sha256', $password, true));
    }
    // SHA-512
    elseif ($isHex && $len === 128) { // sha512 hex
        $ok = hash_equals(strtolower($stored), hash('sha512', $password));
    } elseif (!$isHex && $len === 64) { // sha512 binary (64 bytes)
        $ok = hash_equals($stored, hash('sha512', $password, true));
    }
    // SHA1 (less likely, but support just in case)
    elseif ($isHex && $len === 40) { // sha1 hex
        $ok = hash_equals(strtolower($stored), sha1($password));
    } elseif (!$isHex && $len === 20) { // sha1 binary
        $ok = hash_equals($stored, sha1($password, true));
    }
    // MD5 (not recommended, but handle if encountered)
    elseif ($isHex && $len === 32) { // md5 hex
        $ok = hash_equals(strtolower($stored), md5($password));
    } elseif (!$isHex && $len === 16) { // md5 binary
        $ok = hash_equals($stored, md5($password, true));
    }
    // If matched and migration requested, upgrade to password_hash
    if ($ok && $migrate) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            updateDriverPasswordHash($driver['id'], $newHash);
        } catch (Exception $e) {
            // Non-fatal: keep using legacy until next login
        }
    }
    return $ok;
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
