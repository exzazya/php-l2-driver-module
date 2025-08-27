<?php
// Driver Locations API: upload and fetch GPS telemetry
require_once '../includes/auth.php';
require_once '../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo generateApiResponse(false, null, 'Login required');
    exit();
}

$driverId = (int)($_SESSION['driver_id'] ?? 0);
if ($driverId <= 0) {
    http_response_code(401);
    echo generateApiResponse(false, null, 'Invalid session');
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $tripId = isset($input['trip_id']) ? (int)$input['trip_id'] : 0;
        $points = isset($input['points']) && is_array($input['points']) ? $input['points'] : [];

        if ($tripId <= 0 || empty($points)) {
            http_response_code(400);
            echo generateApiResponse(false, null, 'trip_id and points[] are required');
            exit();
        }

        // Ensure assignment belongs to this driver
        $check = executeQuery('SELECT 1 FROM mobile_assignments WHERE trip_id = ? AND driver_id = ? AND is_declined = 0', [$tripId, $driverId]);
        if (!$check || !$check->fetchColumn()) {
            http_response_code(403);
            echo generateApiResponse(false, null, 'Not assigned to this trip');
            exit();
        }

        $pdo = getDBConnection();
        $pdo->beginTransaction();

        $inserted = 0;
        foreach ($points as $p) {
            $lat = isset($p['lat']) ? (float)$p['lat'] : null;
            $lng = isset($p['lng']) ? (float)$p['lng'] : null;
            $speed = isset($p['speed']) ? (float)$p['speed'] : null;
            $heading = isset($p['heading']) ? (float)$p['heading'] : null;
            $accuracy = isset($p['accuracy']) ? (float)$p['accuracy'] : null;
            $recordedAt = isset($p['recorded_at']) ? $p['recorded_at'] : null; // ISO string
            if ($lat === null || $lng === null) continue;

            executeQuery(
                'INSERT INTO driver_locations (driver_id, trip_id, lat, lng, speed, heading, accuracy, captured_at) VALUES (?,?,?,?,?,?,?,?)',
                [
                    $driverId, $tripId, $lat, $lng, $speed, $heading, $accuracy,
                    $recordedAt ? date('Y-m-d H:i:s', strtotime($recordedAt)) : date('Y-m-d H:i:s')
                ]
            );
            $inserted++;
        }

        // If first upload for trip, transition trip to in_progress
        executeQuery("UPDATE trips SET status = 'in_progress' WHERE id = ? AND status IN ('accepted','assigned','scheduled','en_route')", [$tripId]);

        executeQuery('INSERT INTO audits (actor_type, actor_id, action, entity_type, entity_id, metadata_json) VALUES (?,?,?,?,?,?)', [
            'driver', $driverId, 'upload_locations', 'trip', $tripId, json_encode(['points' => $inserted])
        ]);

        $pdo->commit();
        echo generateApiResponse(true, ['inserted' => $inserted], 'Locations uploaded');
        exit();
    } elseif ($method === 'GET') {
        $tripId = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
        $limit = isset($_GET['limit']) ? max(1, min(1000, (int)$_GET['limit'])) : 200;
        if ($tripId <= 0) {
            http_response_code(400);
            echo generateApiResponse(false, null, 'trip_id required');
            exit();
        }
        // Ensure assignment belongs to this driver
        $check = executeQuery('SELECT 1 FROM mobile_assignments WHERE trip_id = ? AND driver_id = ? AND is_declined = 0', [$tripId, $driverId]);
        if (!$check || !$check->fetchColumn()) {
            http_response_code(403);
            echo generateApiResponse(false, null, 'Not assigned to this trip');
            exit();
        }
        $stmt = executeQuery(
            'SELECT id, lat, lng, speed, heading, accuracy, captured_at, uploaded_at
             FROM driver_locations WHERE driver_id = ? AND trip_id = ?
             ORDER BY captured_at DESC, id DESC LIMIT ' . (int)$limit,
            [$driverId, $tripId]
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        echo generateApiResponse(true, ['locations' => $rows], 'OK');
        exit();
    }

    http_response_code(405);
    echo generateApiResponse(false, null, 'Method not allowed');
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Driver locations API error: ' . $e->getMessage());
    http_response_code(500);
    echo generateApiResponse(false, null, 'Internal server error');
}
