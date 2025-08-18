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

// ============================================
// FLEET AND VEHICLE MANAGEMENT (FVM) FUNCTIONS
// ============================================

function getAllVehicles($status = null) {
    $query = "SELECT * FROM vehicles";
    $params = [];
    
    if ($status) {
        $query .= " WHERE status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY created_at DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getVehicleById($id) {
    $stmt = executeQuery("SELECT * FROM vehicles WHERE id = ?", [$id]);
    return $stmt ? $stmt->fetch() : false;
}

function createVehicle($data) {
    $query = "INSERT INTO vehicles (make, model, year, plate_number, vin_number, vehicle_type, 
              passenger_capacity, color, fuel_type, status, current_mileage, insurance_expiry, 
              notes, date_acquired) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    return executeQuery($query, [
        $data['make'], $data['model'], $data['year'], $data['plate_number'],
        $data['vin_number'], $data['vehicle_type'], $data['passenger_capacity'],
        $data['color'], $data['fuel_type'], $data['status'], $data['current_mileage'],
        $data['insurance_expiry'], $data['notes'], $data['date_acquired']
    ]);
}

function updateVehicle($id, $data) {
    $query = "UPDATE vehicles SET make=?, model=?, year=?, plate_number=?, vin_number=?, 
              vehicle_type=?, passenger_capacity=?, color=?, fuel_type=?, status=?, 
              current_mileage=?, insurance_expiry=?, notes=?, updated_at=NOW() WHERE id=?";
    
    return executeQuery($query, [
        $data['make'], $data['model'], $data['year'], $data['plate_number'],
        $data['vin_number'], $data['vehicle_type'], $data['passenger_capacity'],
        $data['color'], $data['fuel_type'], $data['status'], $data['current_mileage'],
        $data['insurance_expiry'], $data['notes'], $id
    ]);
}

function getVehicleRequests($status = null) {
    $query = "SELECT vr.*, u.name as requester_name FROM vehicle_requests vr 
              JOIN users u ON vr.requester_id = u.id";
    $params = [];
    
    if ($status) {
        $query .= " WHERE vr.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY vr.created_at DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getMaintenanceRecords($vehicleId = null) {
    $query = "SELECT mr.*, v.make, v.model, v.plate_number FROM maintenance_records mr 
              JOIN vehicles v ON mr.vehicle_id = v.id";
    $params = [];
    
    if ($vehicleId) {
        $query .= " WHERE mr.vehicle_id = ?";
        $params[] = $vehicleId;
    }
    
    $query .= " ORDER BY mr.date_performed DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// ============================================
// DRIVER AND TRIP PERFORMANCE MONITORING (DTPM) FUNCTIONS
// ============================================

function getAllDrivers($status = null) {
    $query = "SELECT * FROM drivers";
    $params = [];
    
    if ($status) {
        $query .= " WHERE status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY name ASC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getDriverById($id) {
    $stmt = executeQuery("SELECT * FROM drivers WHERE id = ?", [$id]);
    return $stmt ? $stmt->fetch() : false;
}

function createDriver($data) {
    $query = "INSERT INTO drivers (name, email, license_number, license_expiry, phone, 
              address, hire_date, status, emergency_contact, emergency_phone) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    return executeQuery($query, [
        $data['name'], $data['email'], $data['license_number'], $data['license_expiry'],
        $data['phone'], $data['address'], $data['hire_date'], $data['status'],
        $data['emergency_contact'], $data['emergency_phone']
    ]);
}

function getDriverPerformance($driverId = null) {
    $query = "SELECT dp.*, d.name as driver_name, t.start_location, t.destination 
              FROM driver_performance dp 
              JOIN drivers d ON dp.driver_id = d.id 
              LEFT JOIN trips t ON dp.trip_id = t.id";
    $params = [];
    
    if ($driverId) {
        $query .= " WHERE dp.driver_id = ?";
        $params[] = $driverId;
    }
    
    $query .= " ORDER BY dp.created_at DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getTripReports($driverId = null) {
    $query = "SELECT tr.*, t.start_location, t.destination, d.name as driver_name, 
              v.make, v.model, v.plate_number 
              FROM trip_reports tr 
              JOIN trips t ON tr.trip_id = t.id 
              JOIN drivers d ON t.driver_id = d.id 
              JOIN vehicles v ON t.vehicle_id = v.id";
    $params = [];
    
    if ($driverId) {
        $query .= " WHERE t.driver_id = ?";
        $params[] = $driverId;
    }
    
    $query .= " ORDER BY tr.submitted_at DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// ============================================
// VEHICLE RESERVATION AND DISPATCH SYSTEM (VRDS) FUNCTIONS
// ============================================

function getAllReservations($status = null) {
    $query = "SELECT r.*, u.name as requester_name, v.make, v.model, v.plate_number, 
              d.name as driver_name FROM reservations r 
              JOIN users u ON r.requester_id = u.id 
              LEFT JOIN vehicles v ON r.vehicle_id = v.id 
              LEFT JOIN drivers d ON r.driver_id = d.id";
    $params = [];
    
    if ($status) {
        $query .= " WHERE r.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY r.reservation_date DESC, r.priority DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function createReservation($data) {
    $query = "INSERT INTO reservations (requester_id, trip_purpose, start_location, 
              destination, reservation_date, departure_time, return_time, passenger_count, 
              priority, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    return executeQuery($query, [
        $data['requester_id'], $data['trip_purpose'], $data['start_location'],
        $data['destination'], $data['reservation_date'], $data['departure_time'],
        $data['return_time'], $data['passenger_count'], $data['priority'], $data['notes']
    ]);
}

function assignVehicleAndDriver($reservationId, $vehicleId, $driverId) {
    $query = "UPDATE reservations SET vehicle_id = ?, driver_id = ?, status = 'approved', 
              approval_date = NOW() WHERE id = ?";
    
    return executeQuery($query, [$vehicleId, $driverId, $reservationId]);
}

function getAllTrips($status = null) {
    $query = "SELECT t.*, r.trip_purpose, d.name as driver_name, v.make, v.model, v.plate_number 
              FROM trips t 
              LEFT JOIN reservations r ON t.reservation_id = r.id 
              JOIN drivers d ON t.driver_id = d.id 
              JOIN vehicles v ON t.vehicle_id = v.id";
    $params = [];
    
    if ($status) {
        $query .= " WHERE t.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY t.start_datetime DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// ============================================
// TRANSPORT COST ANALYSIS AND OPTIMIZATION (TCAO) FUNCTIONS
// ============================================

function getTripExpenses($tripId = null, $dateFrom = null, $dateTo = null) {
    $query = "SELECT te.*, t.start_location, t.destination, v.make, v.model, v.plate_number 
              FROM trip_expenses te 
              JOIN trips t ON te.trip_id = t.id 
              JOIN vehicles v ON t.vehicle_id = v.id WHERE 1=1";
    $params = [];
    
    if ($tripId) {
        $query .= " AND te.trip_id = ?";
        $params[] = $tripId;
    }
    
    if ($dateFrom) {
        $query .= " AND te.expense_date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $query .= " AND te.expense_date <= ?";
        $params[] = $dateTo;
    }
    
    $query .= " ORDER BY te.expense_date DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getFuelRecords($vehicleId = null, $dateFrom = null, $dateTo = null) {
    $query = "SELECT fr.*, v.make, v.model, v.plate_number, d.name as filled_by_name 
              FROM fuel_records fr 
              JOIN vehicles v ON fr.vehicle_id = v.id 
              LEFT JOIN drivers d ON fr.filled_by = d.id WHERE 1=1";
    $params = [];
    
    if ($vehicleId) {
        $query .= " AND fr.vehicle_id = ?";
        $params[] = $vehicleId;
    }
    
    if ($dateFrom) {
        $query .= " AND fr.fill_date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $query .= " AND fr.fill_date <= ?";
        $params[] = $dateTo;
    }
    
    $query .= " ORDER BY fr.fill_date DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getVehicleUtilization($vehicleId = null, $dateFrom = null, $dateTo = null) {
    $query = "SELECT vu.*, v.make, v.model, v.plate_number 
              FROM vehicle_utilization vu 
              JOIN vehicles v ON vu.vehicle_id = v.id WHERE 1=1";
    $params = [];
    
    if ($vehicleId) {
        $query .= " AND vu.vehicle_id = ?";
        $params[] = $vehicleId;
    }
    
    if ($dateFrom) {
        $query .= " AND vu.date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $query .= " AND vu.date <= ?";
        $params[] = $dateTo;
    }
    
    $query .= " ORDER BY vu.date DESC";
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// ============================================
// AUTO-ASSIGNMENT FUNCTIONS
// ============================================

function getAvailableDrivers($date = null) {
    $query = "SELECT d.* FROM drivers d 
              WHERE d.status = 'active'";
    $params = [];
    
    if ($date) {
        $query .= " AND d.id IN (
                    SELECT ds.driver_id FROM driver_schedule ds 
                    WHERE ds.shift_date = ? AND ds.status = 'available'
                  )";
        $params[] = $date;
    }
    
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getAvailableVehicles($vehicleType = null) {
    $query = "SELECT * FROM vehicles WHERE status = 'active'";
    $params = [];
    
    if ($vehicleType) {
        $query .= " AND vehicle_type = ?";
        $params[] = $vehicleType;
    }
    
    $stmt = executeQuery($query, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getUnassignedReservations() {
    $stmt = executeQuery("SELECT * FROM reservations 
                         WHERE status = 'approved' AND (driver_id IS NULL OR vehicle_id IS NULL) 
                         ORDER BY priority DESC, reservation_date ASC");
    return $stmt ? $stmt->fetchAll() : [];
}
?>
