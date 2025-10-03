-- Jetlouge Travels Fleet Management System Database Schema
-- Complete database setup for pure PHP deployment

-- Create database (run this first if database doesn't exist)
-- CREATE DATABASE logi_L2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE logi_L2;

-- ============================================
-- AUTHENTICATION & USER MANAGEMENT
-- ============================================

-- Admin users table (for system administration)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) NULL,
    role ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- (Removed legacy users table; system now uses admins for requester/approver roles)

-- API tokens table for authentication
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Sessions table for web login tracking
CREATE TABLE IF NOT EXISTS admin_sessions (
    id VARCHAR(128) PRIMARY KEY,
    admin_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- ============================================
-- FLEET AND VEHICLE MANAGEMENT (FVM)
-- ============================================

-- Vehicles table - comprehensive vehicle information
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Basic Vehicle Information
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    vin_number VARCHAR(17) UNIQUE,
    
    -- Vehicle Classification
    vehicle_type ENUM('sedan', 'suv', 'van', 'bus', 'luxury') NOT NULL,
    passenger_capacity INT NOT NULL,
    color VARCHAR(30),
    fuel_type ENUM('gasoline', 'diesel', 'hybrid', 'electric') NOT NULL,
    
    -- Status and Operational Data
    status ENUM('active', 'maintenance', 'inactive', 'on_trip', 'on trip') NOT NULL DEFAULT 'active',
    current_mileage INT DEFAULT 0,
    total_kilometers INT DEFAULT 0,
    
    -- Administrative Information
    insurance_expiry DATE,
    notes TEXT,
    date_acquired DATE,
    photo_path VARCHAR(255),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicle Requests table - for requesting new vehicles
CREATE TABLE IF NOT EXISTS vehicle_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    description TEXT,
    justification TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date DATE NOT NULL,
    reviewed_by INT NULL,
    review_date DATE NULL,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES admins(id),
    FOREIGN KEY (reviewed_by) REFERENCES admins(id)
);

-- Maintenance Records table
CREATE TABLE IF NOT EXISTS maintenance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_type VARCHAR(50) NOT NULL,
    description TEXT,
    date_performed DATE NOT NULL,
    cost DECIMAL(10, 2) DEFAULT 0.00,
    next_due_date DATE,
    performed_by VARCHAR(100),
    shop_name VARCHAR(100) NULL,
    receipt_image VARCHAR(255) NULL,
    odometer_reading INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Maintenance Queue table (pending maintenance awaiting completion/receipt)
CREATE TABLE IF NOT EXISTS maintenance_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_type VARCHAR(50) NOT NULL,
    description TEXT,
    requested_by INT NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    odometer_reading INT NULL,
    priority ENUM('low','normal','high') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES admins(id)
);

-- Vehicle Utilization table - for tracking usage patterns
CREATE TABLE IF NOT EXISTS vehicle_utilization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    date DATE NOT NULL,
    total_trips INT DEFAULT 0,
    total_distance INT DEFAULT 0,
    total_hours DECIMAL(5,2) DEFAULT 0.00,
    fuel_consumed DECIMAL(8,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vehicle_date (vehicle_id, date)
);

-- ============================================
-- DRIVER AND TRIP PERFORMANCE MONITORING (DTPM)
-- ============================================

-- Drivers table
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    license_expiry DATE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    hire_date DATE,
    status ENUM('active', 'inactive', 'on_trip') DEFAULT 'active',
    profile_image VARCHAR(255),
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Driver Schedule table
CREATE TABLE IF NOT EXISTS driver_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    shift_date DATE NOT NULL,
    shift_start TIME NOT NULL,
    shift_end TIME NOT NULL,
    status ENUM('available', 'on_leave', 'off_duty') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_driver_date (driver_id, shift_date)
);

-- Driver Performance table
CREATE TABLE IF NOT EXISTS driver_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    driver_id INT NOT NULL,
    rating DECIMAL(2,1) CHECK (rating >= 1.0 AND rating <= 5.0),
    feedback TEXT,
    violations TEXT,
    reviewer_id INT,
    review_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (reviewer_id) REFERENCES admins(id)
);

-- ============================================
-- VEHICLE RESERVATION AND DISPATCH SYSTEM (VRDS)
-- ============================================

-- Reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    vehicle_id INT NULL,
    driver_id INT NULL,
    trip_purpose TEXT NOT NULL,
    start_location TEXT NOT NULL,
    destination TEXT NOT NULL,
    reservation_date DATE NOT NULL,
    departure_time TIME,
    return_time TIME,
    passenger_count INT DEFAULT 1,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    status ENUM('pending', 'approved', 'declined', 'completed', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    approval_date DATETIME NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES admins(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (approved_by) REFERENCES admins(id)
);

-- Trips table - actual trip execution
CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    start_location TEXT NOT NULL,
    destination TEXT NOT NULL,
    start_lat DECIMAL(10, 6),
    start_lng DECIMAL(10, 6),
    destination_lat DECIMAL(10, 6),
    destination_lng DECIMAL(10, 6),
    start_datetime DATETIME,
    end_datetime DATETIME,
    status ENUM('requested', 'assigned', 'accepted', 'en_route', 'arrived', 'completed', 'declined', 'cancelled', 'scheduled', 'in_progress') DEFAULT 'scheduled',
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
);

-- Trip Reports table
CREATE TABLE IF NOT EXISTS trip_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    mileage_start INT NOT NULL,
    mileage_end INT NOT NULL,
    fuel_used DECIMAL(10, 2) DEFAULT 0.00,
    incidents TEXT,
    remarks TEXT,
    submitted_by INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES drivers(id)
);

-- Mobile Assignments table - for driver mobile app
CREATE TABLE IF NOT EXISTS mobile_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    trip_id INT NOT NULL,
    is_viewed BOOLEAN DEFAULT FALSE,
    is_accepted BOOLEAN DEFAULT FALSE,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    viewed_at DATETIME NULL,
    accepted_at DATETIME NULL,
    -- Decline support
    is_declined BOOLEAN DEFAULT FALSE,
    declined_at DATETIME NULL,
    decline_reason VARCHAR(255) NULL,
    -- Completion support
    completed_at DATETIME NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- ============================================
-- TELEMETRY & AUDITING (for live tracking and compliance)
-- ============================================

-- Driver GPS locations captured on device and uploaded in near real-time
CREATE TABLE IF NOT EXISTS driver_locations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    trip_id INT NULL,
    lat DECIMAL(9,6) NOT NULL,
    lng DECIMAL(9,6) NOT NULL,
    speed DECIMAL(6,2) NULL,
    heading DECIMAL(6,2) NULL,
    accuracy DECIMAL(6,2) NULL,
    captured_at DATETIME NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    source ENUM('web','android','ios','other') DEFAULT 'web',
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    INDEX idx_driver_trip_time (driver_id, trip_id, captured_at),
    INDEX idx_lat_lng (lat, lng)
);

-- Audit log for key actions (assignment, accept/decline, status transitions)
CREATE TABLE IF NOT EXISTS audits (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('admin','driver','system') NOT NULL,
    actor_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type ENUM('trip','vehicle','driver','assignment','reservation','system') NOT NULL,
    entity_id INT NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor (actor_type, actor_id),
    INDEX idx_entity (entity_type, entity_id)
);

-- ============================================
-- TRANSPORT COST ANALYSIS AND OPTIMIZATION (TCAO)
-- ============================================

-- Trip Expenses table
CREATE TABLE IF NOT EXISTS trip_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    fuel_cost DECIMAL(10,2) DEFAULT 0.00,
    toll_fees DECIMAL(10,2) DEFAULT 0.00,
    parking_fees DECIMAL(10,2) DEFAULT 0.00,
    maintenance_cost DECIMAL(10,2) DEFAULT 0.00,
    other_expenses DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) GENERATED ALWAYS AS (fuel_cost + toll_fees + parking_fees + maintenance_cost + other_expenses) STORED,
    expense_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- Fuel Records table - detailed fuel tracking
CREATE TABLE IF NOT EXISTS fuel_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    trip_id INT NULL,
    fuel_type ENUM('gasoline', 'diesel', 'hybrid', 'electric') NOT NULL,
    liters_filled DECIMAL(8,2) NOT NULL,
    cost_per_liter DECIMAL(6,2) NOT NULL,
    total_cost DECIMAL(10,2) GENERATED ALWAYS AS (liters_filled * cost_per_liter) STORED,
    odometer_reading INT,
    fuel_station VARCHAR(100),
    receipt_number VARCHAR(50),
    filled_by INT,
    fill_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (filled_by) REFERENCES drivers(id)
);