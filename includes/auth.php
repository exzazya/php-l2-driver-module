<?php
// Authentication functions for Jetlouge Travels Driver Module (Driver-only login)
require_once 'database.php';
require_once 'mailer.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        // Stage email-based 2FA only when enabled per driver settings
        $twofaEnabled = (int)($driver['twofa_enabled'] ?? 0) === 1;
        $twofaMethod  = (string)($driver['twofa_method'] ?? 'email');
        if ($twofaEnabled && $twofaMethod === 'email') {
            try { maybeSendDriverEmailOtp($driver, true); } catch (Exception $e) {}
            $_SESSION['2fa_pending_driver'] = [
                'driver_id' => (int)$driver['id'],
                'email_mask' => maskEmail((string)$driver['email'])
            ];
            $_SESSION['2fa_method'] = 'email';
            return true; // caller should redirect to /auth/2fa when this flag is present
        }
        // No 2FA configured -> establish session directly
        establishDriverSession($driver);
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

// Establish driver session and update last_login
if (!function_exists('establishDriverSession')) {
    function establishDriverSession($driver) {
        if (!$driver) return false;
        $_SESSION['user_type'] = 'driver';
        $_SESSION['driver_id'] = $driver['id'];
        $_SESSION['user_id'] = $driver['id'];
        $_SESSION['username'] = $driver['email'];
        $_SESSION['full_name'] = $driver['name'] ?? '';
        $_SESSION['role'] = 'driver';
        $_SESSION['email'] = $driver['email'];
        $_SESSION['license_number'] = $driver['license_number'] ?? '';
        try { updateDriverLastLogin($driver['id']); } catch (Exception $e) {}
        return true;
    }
}

// Email OTP helpers for drivers
if (!function_exists('generateEmailOtpCodeDriver')) {
    function generateEmailOtpCodeDriver($digits = 6) {
        $min = (int)pow(10, $digits - 1);
        $max = (int)pow(10, $digits) - 1;
        return (string)random_int($min, $max);
    }
}

if (!function_exists('maskEmail')) {
    function maskEmail($email) {
        $email = (string)$email;
        if ($email === '' || strpos($email, '@') === false) return 'your email';
        [$name, $domain] = explode('@', $email, 2);
        $nameMasked = strlen($name) <= 2 ? substr($name, 0, 1) . '*' : substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2));
        $domainParts = explode('.', $domain);
        $domainParts[0] = substr($domainParts[0], 0, 1) . str_repeat('*', max(1, strlen($domainParts[0]) - 1));
        return $nameMasked . '@' . implode('.', $domainParts);
    }
}

if (!function_exists('maybeSendDriverEmailOtp')) {
    function maybeSendDriverEmailOtp($driver, $force = false) {
        $driverId = (int)$driver['id'];
        $email = trim((string)($driver['email'] ?? ''));
        if ($email === '') return false;
        $rec = getDriverEmailOtpRecord($driverId);
        $now = time();
        $canResend = true;
        if ($rec && !$force) {
            $last = strtotime((string)$rec['sent_at']);
            if ($last && ($now - $last) < 60) { $canResend = false; }
        }
        if (!$rec || $force || $canResend) {
            $code = generateEmailOtpCodeDriver(6);
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $expiresAt = date('Y-m-d H:i:s', $now + 10 * 60);
            upsertDriverEmailOtpCode($driverId, $hash, $expiresAt);
            $html = '<p>Your Jetlouge Travels driver portal security code is:</p>' .
                    '<p style="font-size:24px;font-weight:700;letter-spacing:3px;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</p>' .
                    '<p>This code will expire in 10 minutes. If you did not request this, you can ignore this email.</p>';
            $text = "Your Jetlouge Travels driver portal security code is: {$code}\nThis code will expire in 10 minutes.";
            sendSystemEmail($email, 'Your Verification Code', $html, $text);
            return true;
        }
        return false;
    }
}

if (!function_exists('verifyDriverEmailOtp')) {
    function verifyDriverEmailOtp($driverId, $code) {
        $rec = getDriverEmailOtpRecord((int)$driverId);
        if (!$rec) return false;
        if (strtotime((string)$rec['expires_at']) <= time()) return false;
        $ok = password_verify((string)$code, (string)$rec['code_hash']);
        if (!$ok) { incrementDriverEmailOtpAttempts((int)$driverId); return false; }
        deleteDriverEmailOtpRecord((int)$driverId);
        return true;
    }
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
