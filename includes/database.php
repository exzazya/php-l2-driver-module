<?php
// Database connection for Jetlouge Travels Fleet Management
// MySQL PDO connection with proper error handling

// Database configuration - Updated for logistics2.jetlougetravels-ph.com hosting
define('DB_HOST', 'localhost');    // Your database host IP
define('DB_NAME', 'logi_L2');           // Your database name
define('DB_USER', 'logi_logs2jetl');   // Your database username
define('DB_PASS', 'hahaha25');          // Your database password

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

// Helper function for safe queries
function executeQuery($query, $params = []) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return false;
    }
}

// Test database connection
function testDBConnection() {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    return false;
}

// Admin-specific database functions
function getAdminByUsername($username) {
    $stmt = executeQuery("SELECT * FROM admins WHERE username = ? AND status = 'active'", [$username]);
    return $stmt ? $stmt->fetch() : false;
}

function getDriverByEmail($email) {
    $stmt = executeQuery(
        "SELECT * FROM drivers WHERE email = ? AND status IN ('active','on_trip') AND password_hash IS NOT NULL",
        [$email]
    );
    return $stmt ? $stmt->fetch() : false;
}

function getDriverById($id) {
    $stmt = executeQuery("SELECT * FROM drivers WHERE id = ?", [(int)$id]);
    return $stmt ? $stmt->fetch() : false;
}

function updateDriverLastLogin($driverId) {
    return executeQuery("UPDATE drivers SET last_login = NOW() WHERE id = ?", [(int)$driverId]);
}

function updateDriverContactDetails($driverId, $email, $phone, $address, $emergencyContact, $emergencyPhone) {
    return executeQuery(
        "UPDATE drivers SET email = ?, phone = ?, address = ?, emergency_contact = ?, emergency_phone = ?, updated_at = NOW() WHERE id = ?",
        [
            $email !== '' ? $email : null,
            $phone !== '' ? $phone : null,
            $address !== '' ? $address : null,
            $emergencyContact !== '' ? $emergencyContact : null,
            $emergencyPhone !== '' ? $emergencyPhone : null,
            (int)$driverId
        ]
    );
}

function getUserByCredentials($identifier) {
    // Try admin first
    $admin = getAdminByUsername($identifier);
    if ($admin) {
        $admin['user_type'] = 'admin';
        return $admin;
    }
    
    // Try driver by email
    $driver = getDriverByEmail($identifier);
    if ($driver) {
        $driver['user_type'] = 'driver';
        return $driver;
    }
    
    return false;
}

function getAdminById($id) {
    $stmt = executeQuery("SELECT * FROM admins WHERE id = ? AND status = 'active'", [$id]);
    return $stmt ? $stmt->fetch() : false;
}

function updateLastLogin($adminId) {
    return executeQuery("UPDATE admins SET last_login = NOW() WHERE id = ?", [$adminId]);
}

function createApiToken($adminId) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = executeQuery("INSERT INTO api_tokens (admin_id, token, expires_at) VALUES (?, ?, ?)", 
                        [$adminId, $token, $expires]);
    
    return $stmt ? $token : false;
}

function validateApiToken($token) {
    $stmt = executeQuery("SELECT at.*, a.* FROM api_tokens at 
                         JOIN admins a ON at.admin_id = a.id 
                         WHERE at.token = ? AND at.expires_at > NOW() AND a.status = 'active'", 
                         [$token]);
    
    return $stmt ? $stmt->fetch() : false;
}

// ==============================
// Driver password management
// ==============================

function updateDriverPasswordHash($driverId, $newHash) {
    return executeQuery("UPDATE drivers SET password_hash = ? WHERE id = ?", [$newHash, $driverId]);
}

function setDriverPasswordByEmail($email, $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    return executeQuery("UPDATE drivers SET password_hash = ? WHERE email = ?", [$hash, $email]);
}

function setDriverPasswordById($driverId, $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    return updateDriverPasswordHash($driverId, $hash);
}

// ==============================
// Driver Email 2FA (OTP) helpers
// ==============================

function upsertDriverEmailOtpCode($driverId, $codeHash, $expiresAt) {
    return executeQuery(
        "INSERT INTO twofactor_email_codes_driver (driver_id, code_hash, expires_at, attempts, sent_at)
         VALUES (?, ?, ?, 0, NOW())
         ON DUPLICATE KEY UPDATE code_hash = VALUES(code_hash), expires_at = VALUES(expires_at), attempts = 0, sent_at = NOW()",
        [(int)$driverId, $codeHash, $expiresAt]
    );
}

function getDriverEmailOtpRecord($driverId) {
    $stmt = executeQuery(
        "SELECT * FROM twofactor_email_codes_driver WHERE driver_id = ?",
        [(int)$driverId]
    );
    return $stmt ? $stmt->fetch() : false;
}

function incrementDriverEmailOtpAttempts($driverId) {
    return executeQuery(
        "UPDATE twofactor_email_codes_driver SET attempts = attempts + 1 WHERE driver_id = ?",
        [(int)$driverId]
    );
}

function deleteDriverEmailOtpRecord($driverId) {
    return executeQuery(
        "DELETE FROM twofactor_email_codes_driver WHERE driver_id = ?",
        [(int)$driverId]
    );
}

// ==============================
// Driver Two-Factor flags
// ==============================

function setDriverTwoFactor($driverId, $enabled, $secret = null) {
    return executeQuery(
        "UPDATE drivers SET twofa_enabled = ?, twofa_secret = ? WHERE id = ?",
        [(int)$enabled, $secret, (int)$driverId]
    );
}

function setDriverTwoFactorMethod($driverId, $method) {
    $method = in_array($method, ['email','totp'], true) ? $method : 'email';
    return executeQuery(
        "UPDATE drivers SET twofa_method = ? WHERE id = ?",
        [$method, (int)$driverId]
    );
}

// ============================================
// POLICY ACCEPTANCE HELPERS
// ============================================
/**
 * Record acceptance of Terms & Conditions and Privacy Policy for a subject (admin, fleet_manager, driver)
 */
function recordPolicyAcceptance($subjectType, $subjectId, $ipAddress = null, $userAgent = null, $acceptedTerms = 1, $acceptedPrivacy = 1) {
    $subjectType = in_array($subjectType, ['admin','fleet_manager','driver'], true) ? $subjectType : 'driver';
    return executeQuery(
        "INSERT INTO policy_acceptance (subject_type, subject_id, accepted_terms, accepted_privacy, accepted_at, ip_address, user_agent)
         VALUES (?, ?, ?, ?, NOW(), ?, ?)
         ON DUPLICATE KEY UPDATE accepted_terms = VALUES(accepted_terms), accepted_privacy = VALUES(accepted_privacy), accepted_at = VALUES(accepted_at), ip_address = VALUES(ip_address), user_agent = VALUES(user_agent)",
        [$subjectType, (int)$subjectId, (int)$acceptedTerms, (int)$acceptedPrivacy, $ipAddress, $userAgent]
    );
}

/** Check if policies were already accepted for this subject */
function hasAcceptedPolicies($subjectType, $subjectId) {
    $stmt = executeQuery("SELECT 1 FROM policy_acceptance WHERE subject_type = ? AND subject_id = ? LIMIT 1", [
        in_array($subjectType, ['admin','fleet_manager','driver'], true) ? $subjectType : 'driver',
        (int)$subjectId,
    ]);
    return $stmt && ($stmt->fetchColumn() ? true : false);
}

