<?php
// Driver Assignments API: list, accept, decline assignments
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
    switch ($method) {
        case 'GET':
            // List pending or all assignments for the driver
            $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'pending';
            $where = '';
            $params = [$driverId];
            if ($status === 'pending') {
                // Consider NULL flags as pending
                $where = "AND (ma.is_declined = 0 OR ma.is_declined IS NULL) AND (ma.is_accepted = 0 OR ma.is_accepted IS NULL) AND (t.status != 'completed' AND ma.completed_at IS NULL)";
            } elseif ($status === 'accepted') {
                $where = "AND ma.is_accepted = 1 AND (t.status != 'completed' AND ma.completed_at IS NULL)";
            } elseif ($status === 'declined') {
                $where = "AND ma.is_declined = 1 AND (t.status != 'completed' AND ma.completed_at IS NULL)";
            } elseif ($status === 'completed') {
                // Completed trips: either trip is marked completed or assignment has a completed timestamp
                $where = "AND (t.status = 'completed' OR ma.completed_at IS NOT NULL)";
            }

            $stmt = executeQuery(
                "SELECT ma.*, t.*
                 FROM mobile_assignments ma
                 JOIN trips t ON t.id = ma.trip_id
                 WHERE ma.driver_id = ? $where
                 ORDER BY ma.assigned_at DESC",
                $params
            );
            $rows = $stmt ? $stmt->fetchAll() : [];
            echo generateApiResponse(true, ['assignments' => $rows], 'OK');
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $action = isset($input['action']) ? strtolower(trim($input['action'])) : '';
            $tripId = isset($input['trip_id']) ? (int)$input['trip_id'] : 0;
            $assignmentId = isset($input['assignment_id']) ? (int)$input['assignment_id'] : 0;
            $declineReason = isset($input['decline_reason']) ? trim($input['decline_reason']) : null;

            if (!$action || ($tripId <= 0 && $assignmentId <= 0)) {
                http_response_code(400);
                echo generateApiResponse(false, null, 'action and trip_id or assignment_id required');
                exit();
            }

            // Resolve assignment
            if ($assignmentId > 0) {
                $maStmt = executeQuery('SELECT * FROM mobile_assignments WHERE id = ? AND driver_id = ?', [$assignmentId, $driverId]);
            } else {
                $maStmt = executeQuery('SELECT * FROM mobile_assignments WHERE trip_id = ? AND driver_id = ?', [$tripId, $driverId]);
            }
            $ma = $maStmt ? $maStmt->fetch() : null;
            if (!$ma) {
                http_response_code(404);
                echo generateApiResponse(false, null, 'Assignment not found');
                exit();
            }
            $tripId = (int)$ma['trip_id'];

            $pdo = getDBConnection();
            $pdo->beginTransaction();

            if ($action === 'accept') {
                // Prevent accepting a new trip if there's already an ongoing one
                $ongoingStmt = executeQuery(
                    "SELECT COUNT(*) AS cnt
                     FROM mobile_assignments ma
                     JOIN trips t ON t.id = ma.trip_id
                     WHERE ma.driver_id = ?
                       AND ma.is_accepted = 1
                       AND t.status IN ('accepted','in_progress','en_route')
                       AND t.id <> ?",
                    [$driverId, $tripId]
                );
                $ongoingCnt = $ongoingStmt ? (int)$ongoingStmt->fetchColumn() : 0;
                if ($ongoingCnt > 0) {
                    $pdo->rollBack();
                    http_response_code(409);
                    echo generateApiResponse(false, null, 'Cannot accept this assignment: you already have an ongoing trip. Complete or finish the current trip first.');
                    exit();
                }

                executeQuery('UPDATE mobile_assignments SET is_viewed = 1, is_accepted = 1, accepted_at = NOW(), is_declined = 0, decline_reason = NULL WHERE id = ?', [$ma['id']]);
                executeQuery("UPDATE trips SET status = 'accepted' WHERE id = ?", [$tripId]);
                // Optional: set driver/vehicle on_trip immediately
                executeQuery("UPDATE drivers SET status = 'on_trip' WHERE id = ?", [$driverId]);
                executeQuery("UPDATE vehicles v JOIN trips t ON v.id = t.vehicle_id SET v.status = 'on_trip' WHERE t.id = ?", [$tripId]);

                executeQuery('INSERT INTO audits (actor_type, actor_id, action, entity_type, entity_id, metadata_json) VALUES (?,?,?,?,?,?)', [
                    'driver', $driverId, 'accept_assignment', 'trip', $tripId, json_encode(['assignment_id' => $ma['id']])
                ]);

                $pdo->commit();
                echo generateApiResponse(true, ['trip_id' => $tripId, 'assignment_id' => $ma['id'], 'status' => 'accepted'], 'Assignment accepted');
                exit();
            } elseif ($action === 'decline') {
                executeQuery('UPDATE mobile_assignments SET is_viewed = 1, is_declined = 1, declined_at = NOW(), decline_reason = ? WHERE id = ?', [$declineReason, $ma['id']]);
                executeQuery("UPDATE trips SET status = 'declined' WHERE id = ?", [$tripId]);
                
                // Update reservation status to declined and clear driver/vehicle assignment
                executeQuery("UPDATE reservations SET status = 'declined', driver_id = NULL, vehicle_id = NULL WHERE id = (SELECT reservation_id FROM trips WHERE id = ?)", [$tripId]);

                executeQuery('INSERT INTO audits (actor_type, actor_id, action, entity_type, entity_id, metadata_json) VALUES (?,?,?,?,?,?)', [
                    'driver', $driverId, 'decline_assignment', 'trip', $tripId, json_encode(['assignment_id' => $ma['id'], 'reason' => (string)$declineReason])
                ]);

                $pdo->commit();
                echo generateApiResponse(true, ['trip_id' => $tripId, 'assignment_id' => $ma['id'], 'status' => 'declined'], 'Assignment declined');
                exit();
            } elseif ($action === 'complete') {
                // Complete the trip
                if ($ma['is_accepted'] != 1) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo generateApiResponse(false, null, 'Cannot complete trip: assignment was not accepted');
                    exit();
                }

                // Update trip status to completed
                executeQuery("UPDATE trips SET status = 'completed', completed_at = NOW() WHERE id = ?", [$tripId]);
                
                // Update assignment as completed
                executeQuery('UPDATE mobile_assignments SET completed_at = NOW() WHERE id = ?', [$ma['id']]);
                
                // Set driver and vehicle back to active status
                executeQuery("UPDATE drivers SET status = 'active' WHERE id = ?", [$driverId]);
                executeQuery("UPDATE vehicles v JOIN trips t ON v.id = t.vehicle_id SET v.status = 'active' WHERE t.id = ?", [$tripId]);

                // Log the completion
                executeQuery('INSERT INTO audits (actor_type, actor_id, action, entity_type, entity_id, metadata_json) VALUES (?,?,?,?,?,?)', [
                    'driver', $driverId, 'complete_trip', 'trip', $tripId, json_encode(['assignment_id' => $ma['id']])
                ]);

                $pdo->commit();
                echo generateApiResponse(true, ['trip_id' => $tripId, 'assignment_id' => $ma['id'], 'status' => 'completed'], 'Trip completed successfully');
                exit();
            } else {
                $pdo->rollBack();
                http_response_code(400);
                echo generateApiResponse(false, null, 'Unsupported action');
                exit();
            }

        default:
            http_response_code(405);
            echo generateApiResponse(false, null, 'Method not allowed');
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Driver assignments API error: ' . $e->getMessage());
    http_response_code(500);
    echo generateApiResponse(false, null, 'Internal server error');
}
