<?php
// Fleet Management API Endpoints
require_once '../includes/auth.php';
require_once '../includes/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

// Authenticate API requests for all endpoints
$auth = authenticateApiRequest();
if (!$auth) {
    http_response_code(401);
    echo generateApiResponse(false, null, 'Authentication required');
    exit();
}

// Route API requests
switch ($method) {
    case 'GET':
        handleGetRequest($endpoint);
        break;
    case 'POST':
        handlePostRequest($endpoint);
        break;
    case 'PUT':
        handlePutRequest($endpoint);
        break;
    case 'DELETE':
        handleDeleteRequest($endpoint);
        break;
    default:
        http_response_code(405);
        echo generateApiResponse(false, null, 'Method not allowed');
        break;
}

function handleGetRequest($endpoint) {
    switch ($endpoint) {
        case 'vehicles':
            $status = $_GET['status'] ?? null;
            $vehicles = getAllVehicles($status);
            echo generateApiResponse(true, $vehicles, 'Vehicles retrieved successfully');
            break;
            
        case 'drivers':
            $status = $_GET['status'] ?? null;
            $drivers = getAllDrivers($status);
            echo generateApiResponse(true, $drivers, 'Drivers retrieved successfully');
            break;
            
        case 'reservations':
            $status = $_GET['status'] ?? null;
            $reservations = getAllReservations($status);
            echo generateApiResponse(true, $reservations, 'Reservations retrieved successfully');
            break;
            
        case 'trips':
            $status = $_GET['status'] ?? null;
            $trips = getAllTrips($status);
            echo generateApiResponse(true, $trips, 'Trips retrieved successfully');
            break;
            
        case 'vehicle-requests':
            $status = $_GET['status'] ?? null;
            $requests = getVehicleRequests($status);
            echo generateApiResponse(true, $requests, 'Vehicle requests retrieved successfully');
            break;
            
        case 'maintenance':
            $vehicleId = $_GET['vehicle_id'] ?? null;
            $maintenance = getMaintenanceRecords($vehicleId);
            echo generateApiResponse(true, $maintenance, 'Maintenance records retrieved successfully');
            break;
            
        case 'driver-performance':
            $driverId = $_GET['driver_id'] ?? null;
            $performance = getDriverPerformance($driverId);
            echo generateApiResponse(true, $performance, 'Driver performance retrieved successfully');
            break;
            
        case 'trip-reports':
            $driverId = $_GET['driver_id'] ?? null;
            $reports = getTripReports($driverId);
            echo generateApiResponse(true, $reports, 'Trip reports retrieved successfully');
            break;
            
        case 'trip-expenses':
            $tripId = $_GET['trip_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $expenses = getTripExpenses($tripId, $dateFrom, $dateTo);
            echo generateApiResponse(true, $expenses, 'Trip expenses retrieved successfully');
            break;
            
        case 'fuel-records':
            $vehicleId = $_GET['vehicle_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $fuelRecords = getFuelRecords($vehicleId, $dateFrom, $dateTo);
            echo generateApiResponse(true, $fuelRecords, 'Fuel records retrieved successfully');
            break;
            
        case 'vehicle-utilization':
            $vehicleId = $_GET['vehicle_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $utilization = getVehicleUtilization($vehicleId, $dateFrom, $dateTo);
            echo generateApiResponse(true, $utilization, 'Vehicle utilization retrieved successfully');
            break;
            
        case 'available-drivers':
            $date = $_GET['date'] ?? null;
            $drivers = getAvailableDrivers($date);
            echo generateApiResponse(true, $drivers, 'Available drivers retrieved successfully');
            break;
            
        case 'available-vehicles':
            $vehicleType = $_GET['vehicle_type'] ?? null;
            $vehicles = getAvailableVehicles($vehicleType);
            echo generateApiResponse(true, $vehicles, 'Available vehicles retrieved successfully');
            break;
            
        case 'unassigned-reservations':
            $reservations = getUnassignedReservations();
            echo generateApiResponse(true, $reservations, 'Unassigned reservations retrieved successfully');
            break;
            
        default:
            http_response_code(404);
            echo generateApiResponse(false, null, 'Endpoint not found');
            break;
    }
}

function handlePostRequest($endpoint) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo generateApiResponse(false, null, 'Invalid JSON input');
        return;
    }
    
    switch ($endpoint) {
        case 'vehicles':
            if (createVehicle($input)) {
                echo generateApiResponse(true, null, 'Vehicle created successfully');
            } else {
                http_response_code(500);
                echo generateApiResponse(false, null, 'Failed to create vehicle');
            }
            break;
            
        case 'drivers':
            if (createDriver($input)) {
                echo generateApiResponse(true, null, 'Driver created successfully');
            } else {
                http_response_code(500);
                echo generateApiResponse(false, null, 'Failed to create driver');
            }
            break;
            
        case 'reservations':
            if (createReservation($input)) {
                echo generateApiResponse(true, null, 'Reservation created successfully');
            } else {
                http_response_code(500);
                echo generateApiResponse(false, null, 'Failed to create reservation');
            }
            break;
            
        case 'assign-vehicle-driver':
            $reservationId = $input['reservation_id'] ?? null;
            $vehicleId = $input['vehicle_id'] ?? null;
            $driverId = $input['driver_id'] ?? null;
            
            if (!$reservationId || !$vehicleId || !$driverId) {
                http_response_code(400);
                echo generateApiResponse(false, null, 'Missing required fields: reservation_id, vehicle_id, driver_id');
                return;
            }
            
            if (assignVehicleAndDriver($reservationId, $vehicleId, $driverId)) {
                echo generateApiResponse(true, null, 'Vehicle and driver assigned successfully');
            } else {
                http_response_code(500);
                echo generateApiResponse(false, null, 'Failed to assign vehicle and driver');
            }
            break;
            
        case 'auto-assign':
            $result = performAutoAssignment();
            if ($result['success']) {
                echo generateApiResponse(true, $result['data'], $result['message']);
            } else {
                http_response_code(500);
                echo generateApiResponse(false, null, $result['message']);
            }
            break;
            
        default:
            http_response_code(404);
            echo generateApiResponse(false, null, 'Endpoint not found');
            break;
    }
}

function handlePutRequest($endpoint) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo generateApiResponse(false, null, 'Invalid JSON input');
        return;
    }
    
    switch ($endpoint) {
        case 'vehicles':
            $id = $input['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo generateApiResponse(false, null, 'Vehicle ID required');
                return;
            }
            
            if (updateVehicle($id, $input)) {
                echo generateApiResponse(true, null, 'Vehicle updated successfully');
            } else {
                http_response_code(500);
                echo generateApiResponse(false, null, 'Failed to update vehicle');
            }
            break;
            
        default:
            http_response_code(404);
            echo generateApiResponse(false, null, 'Endpoint not found');
            break;
    }
}

function handleDeleteRequest($endpoint) {
    // Implement delete operations as needed
    http_response_code(404);
    echo generateApiResponse(false, null, 'Delete endpoint not implemented');
}

function performAutoAssignment() {
    $unassigned = getUnassignedReservations();
    $assigned = [];
    $errors = [];
    
    foreach ($unassigned as $reservation) {
        // Get available drivers for the reservation date
        $availableDrivers = getAvailableDrivers($reservation['reservation_date']);
        
        // Get available vehicles (optionally filter by type if needed)
        $availableVehicles = getAvailableVehicles();
        
        if (empty($availableDrivers)) {
            $errors[] = "No available drivers for reservation ID {$reservation['id']}";
            continue;
        }
        
        if (empty($availableVehicles)) {
            $errors[] = "No available vehicles for reservation ID {$reservation['id']}";
            continue;
        }
        
        // Simple assignment logic: pick first available driver and vehicle
        // You can enhance this with more sophisticated matching logic
        $selectedDriver = $availableDrivers[0];
        $selectedVehicle = null;
        
        // Find suitable vehicle based on passenger capacity
        foreach ($availableVehicles as $vehicle) {
            if ($vehicle['passenger_capacity'] >= $reservation['passenger_count']) {
                $selectedVehicle = $vehicle;
                break;
            }
        }
        
        if (!$selectedVehicle) {
            $selectedVehicle = $availableVehicles[0]; // Fallback to first available
        }
        
        // Assign the vehicle and driver
        if (assignVehicleAndDriver($reservation['id'], $selectedVehicle['id'], $selectedDriver['id'])) {
            $assigned[] = [
                'reservation_id' => $reservation['id'],
                'vehicle' => $selectedVehicle['make'] . ' ' . $selectedVehicle['model'] . ' (' . $selectedVehicle['plate_number'] . ')',
                'driver' => $selectedDriver['name']
            ];
            
            // Update driver status to on_trip
            executeQuery("UPDATE drivers SET status = 'on_trip' WHERE id = ?", [$selectedDriver['id']]);
        } else {
            $errors[] = "Failed to assign reservation ID {$reservation['id']}";
        }
    }
    
    return [
        'success' => !empty($assigned) || empty($unassigned),
        'data' => [
            'assigned' => $assigned,
            'errors' => $errors,
            'total_processed' => count($unassigned),
            'total_assigned' => count($assigned)
        ],
        'message' => count($assigned) > 0 ? 
            count($assigned) . ' reservations assigned successfully' : 
            'No reservations to assign or all assignments failed'
    ];
}
?>
