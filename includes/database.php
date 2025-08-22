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
    $stmt = executeQuery("SELECT * FROM drivers WHERE email = ? AND status = 'active' AND password_hash IS NOT NULL", [$email]);
    return $stmt ? $stmt->fetch() : false;
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

