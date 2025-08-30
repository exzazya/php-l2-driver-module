<?php
// Trip Reports API: submit and manage trip reports
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
            // Get trip reports for the driver
            $action = $_GET['action'] ?? 'list';
            
            if ($action === 'completed_trips') {
                // Fetch completed trips that don't have reports yet
                $stmt = executeQuery(
                    "SELECT t.*, v.plate_number, v.make, v.model,
                            tr.id as report_id, tr.submitted_at as report_submitted
                     FROM trips t
                     JOIN vehicles v ON v.id = t.vehicle_id
                     LEFT JOIN trip_reports tr ON tr.trip_id = t.id
                     WHERE t.driver_id = ? 
                       AND t.status = 'completed' 
                       AND t.completed_at IS NOT NULL
                     ORDER BY t.completed_at DESC",
                    [$driverId]
                );
                $trips = $stmt ? $stmt->fetchAll() : [];
                echo generateApiResponse(true, ['trips' => $trips], 'OK');
            } else {
                // Get submitted reports
                $stmt = executeQuery(
                    "SELECT tr.*, t.start_location, t.destination, t.completed_at,
                            v.plate_number, v.make, v.model
                     FROM trip_reports tr
                     JOIN trips t ON t.id = tr.trip_id
                     JOIN vehicles v ON v.id = t.vehicle_id
                     WHERE tr.submitted_by = ?
                     ORDER BY tr.submitted_at DESC",
                    [$driverId]
                );
                $reports = $stmt ? $stmt->fetchAll() : [];
                echo generateApiResponse(true, ['reports' => $reports], 'OK');
            }
            break;

        case 'POST':
            // Handle multipart form data for file uploads
            $action = $_POST['action'] ?? '';
            
            if ($action === 'submit') {
                // Log received POST data for debugging
                error_log('Reports API - Received POST data: ' . print_r($_POST, true));
                error_log('Reports API - Received FILES data: ' . print_r($_FILES, true));
                
                $tripId = (int)($_POST['trip_id'] ?? 0);
                $fuelUsed = (float)($_POST['fuel_used'] ?? 0);
                $tollFee = (float)($_POST['toll_fee'] ?? 0);
                $fuelCost = (float)($_POST['fuel_cost'] ?? 0);
                $parkingFee = (float)($_POST['parking_fee'] ?? 0);
                $incidents = trim($_POST['incidents'] ?? '');
                $remarks = trim($_POST['remarks'] ?? '');
                
                // Log parsed values
                error_log('Reports API - Parsed values: ' . json_encode([
                    'tripId' => $tripId,
                    'fuelUsed' => $fuelUsed,
                    'tollFee' => $tollFee,
                    'fuelCost' => $fuelCost,
                    'parkingFee' => $parkingFee,
                    'incidents' => $incidents,
                    'remarks' => $remarks,
                    'driverId' => $driverId
                ]));

                if ($tripId <= 0) {
                    http_response_code(400);
                    echo generateApiResponse(false, null, 'Trip ID is required');
                    exit();
                }

                // Verify trip belongs to driver and is completed
                $tripStmt = executeQuery(
                    "SELECT * FROM trips WHERE id = ? AND driver_id = ? AND status = 'completed'",
                    [$tripId, $driverId]
                );
                $trip = $tripStmt ? $tripStmt->fetch() : null;
                if (!$trip) {
                    http_response_code(404);
                    echo generateApiResponse(false, null, 'Trip not found or not completed');
                    exit();
                }

                // Check if report already exists
                $existingStmt = executeQuery(
                    "SELECT id FROM trip_reports WHERE trip_id = ?",
                    [$tripId]
                );
                if ($existingStmt && $existingStmt->fetch()) {
                    http_response_code(409);
                    echo generateApiResponse(false, null, 'Report already submitted for this trip');
                    exit();
                }

                $pdo = getDBConnection();
                $pdo->beginTransaction();

                // Helper to get table columns
                if (!function_exists('getTableColumns')) {
                    function getTableColumns(PDO $pdo, string $table): array {
                        try {
                            $stmt = $pdo->query("DESCRIBE `{$table}`");
                            $cols = [];
                            if ($stmt) {
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    if (isset($row['Field'])) { $cols[] = $row['Field']; }
                                }
                            }
                            return $cols;
                        } catch (Exception $e) {
                            error_log("Reports API - DESCRIBE failed for {$table}: " . $e->getMessage());
                            return [];
                        }
                    }
                }

                try {
                    // Handle file uploads (robust)
                    $uploadDir = rtrim(__DIR__ . '/../uploads/receipts/', '/\\') . DIRECTORY_SEPARATOR;
                    $publicPathPrefix = 'uploads/receipts/';
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            error_log('Reports API - Failed to create upload directory: ' . $uploadDir);
                        }
                    }

                    $receiptPaths = [];
                    // Accept both snake_case and camelCase keys just in case
                    $receiptKeyMap = [
                        'fuel_receipt' => ['fuel_receipt', 'fuelReceipt'],
                        'toll_receipt' => ['toll_receipt', 'tollReceipt'],
                        'parking_receipt' => ['parking_receipt', 'parkingReceipt'],
                    ];

                    // Helper for upload error messages
                    $uploadErrorMsg = function($code) {
                        $map = [
                            UPLOAD_ERR_OK => 'OK',
                            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds upload_max_filesize',
                            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds MAX_FILE_SIZE',
                            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                        ];
                        return $map[$code] ?? ('Unknown error code ' . $code);
                    };

                    foreach ($receiptKeyMap as $normalizedKey => $candidates) {
                        $file = null;
                        $pickedKey = null;
                        foreach ($candidates as $key) {
                            if (isset($_FILES[$key])) {
                                $file = $_FILES[$key];
                                $pickedKey = $key;
                                break;
                            }
                        }
                        if (!$file) {
                            error_log("Reports API - No file found for {$normalizedKey} (checked: " . implode(',', $candidates) . ")");
                            continue;
                        }

                        if (!isset($file['error'])) {
                            error_log("Reports API - File array missing 'error' for {$pickedKey}");
                            continue;
                        }

                        if ($file['error'] !== UPLOAD_ERR_OK) {
                            error_log("Reports API - Upload error for {$pickedKey}: " . $uploadErrorMsg($file['error']));
                            continue;
                        }

                        // Determine MIME type and accept any image/*
                        $mime = null;
                        if (function_exists('finfo_open')) {
                            $f = @finfo_open(FILEINFO_MIME_TYPE);
                            if ($f) {
                                $mime = @finfo_file($f, $file['tmp_name']);
                                @finfo_close($f);
                            }
                        }
                        if (!$mime && function_exists('mime_content_type')) {
                            $mime = @mime_content_type($file['tmp_name']);
                        }
                        if (!$mime && !empty($file['type'])) {
                            $mime = $file['type'];
                        }

                        if (!$mime || strpos($mime, 'image/') !== 0) {
                            error_log("Reports API - Rejected non-image file for {$pickedKey}, MIME: " . var_export($mime, true));
                            continue;
                        }

                        $extMap = [
                            'image/jpeg' => 'jpg',
                            'image/pjpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'image/webp' => 'webp',
                            'image/bmp' => 'bmp',
                            'image/x-ms-bmp' => 'bmp',
                            'image/tiff' => 'tiff',
                            'image/x-icon' => 'ico',
                            'image/vnd.microsoft.icon' => 'ico',
                            'image/svg+xml' => 'svg',
                            'image/heif' => 'heif',
                            'image/heic' => 'heic',
                            'image/avif' => 'avif',
                        ];

                        $fileExtension = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!$fileExtension) { $fileExtension = 'img'; }
                        error_log("Reports API - {$pickedKey} detected MIME {$mime}, using extension .{$fileExtension}");

                        $fileName = 'trip_' . $tripId . '_' . $normalizedKey . '_' . time() . '.' . $fileExtension;
                        $filePath = $uploadDir . $fileName;

                        // Try to move the uploaded file
                        $moved = @move_uploaded_file($file['tmp_name'], $filePath);
                        if (!$moved) {
                            error_log("Reports API - move_uploaded_file failed for {$pickedKey} to {$filePath}; attempting copy()");
                            // Fallback attempt
                            $moved = @copy($file['tmp_name'], $filePath);
                        }

                        if ($moved) {
                            $receiptPaths[$normalizedKey] = $publicPathPrefix . $fileName;
                            error_log("Reports API - Stored {$pickedKey} as {$receiptPaths[$normalizedKey]}");
                        } else {
                            error_log("Reports API - Failed to save file for {$pickedKey} to {$filePath}");
                        }
                    }

                    error_log('Reports API - Final receiptPaths: ' . json_encode($receiptPaths));

                    // Log data before insertion
                    error_log('Reports API - About to insert trip_expenses with data: ' . json_encode([
                        'trip_id' => $tripId,
                        'fuel_cost' => $fuelCost,
                        'toll_fees' => $tollFee,
                        'parking_fees' => $parkingFee,
                        'fuel_receipt' => $receiptPaths['fuel_receipt'] ?? null,
                        'toll_receipt' => $receiptPaths['toll_receipt'] ?? null,
                        'parking_receipt' => $receiptPaths['parking_receipt'] ?? null
                    ]));

                    // Decide where to store expense values based on available columns
                    $tripReportsCols = getTableColumns($pdo, 'trip_reports');
                    $tripExpensesCols = getTableColumns($pdo, 'trip_expenses');

                    $canStoreInReports = in_array('fuel_cost', $tripReportsCols) || in_array('toll_fee', $tripReportsCols) || in_array('parking_fee', $tripReportsCols)
                        || in_array('fuel_receipt', $tripReportsCols) || in_array('toll_receipt', $tripReportsCols) || in_array('parking_receipt', $tripReportsCols);

                    $expenseId = null;

                    if ($canStoreInReports) {
                        error_log('Reports API - Storing expenses in trip_reports based on available columns');
                    } else {
                        // Prepare dynamic columns for trip_expenses
                        $colFuelCost = in_array('fuel_cost', $tripExpensesCols) ? 'fuel_cost' : null;
                        $colToll = in_array('toll_fees', $tripExpensesCols) ? 'toll_fees' : (in_array('toll_fee', $tripExpensesCols) ? 'toll_fee' : null);
                        $colParking = in_array('parking_fees', $tripExpensesCols) ? 'parking_fees' : (in_array('parking_fee', $tripExpensesCols) ? 'parking_fee' : null);
                        $colFuelR = in_array('fuel_receipt', $tripExpensesCols) ? 'fuel_receipt' : null;
                        $colTollR = in_array('toll_receipt', $tripExpensesCols) ? 'toll_receipt' : null;
                        $colParkR = in_array('parking_receipt', $tripExpensesCols) ? 'parking_receipt' : null;

                        $cols = ['trip_id'];
                        $vals = ['?'];
                        $params = [$tripId];

                        if ($colFuelCost) { $cols[] = $colFuelCost; $vals[] = '?'; $params[] = $fuelCost; }
                        if ($colToll) { $cols[] = $colToll; $vals[] = '?'; $params[] = $tollFee; }
                        if ($colParking) { $cols[] = $colParking; $vals[] = '?'; $params[] = $parkingFee; }

                        // Add expense_date if present
                        if (in_array('expense_date', $tripExpensesCols)) { $cols[] = 'expense_date'; $vals[] = 'CURDATE()'; }

                        if ($colFuelR) { $cols[] = $colFuelR; $vals[] = '?'; $params[] = $receiptPaths['fuel_receipt'] ?? null; }
                        if ($colTollR) { $cols[] = $colTollR; $vals[] = '?'; $params[] = $receiptPaths['toll_receipt'] ?? null; }
                        if ($colParkR) { $cols[] = $colParkR; $vals[] = '?'; $params[] = $receiptPaths['parking_receipt'] ?? null; }

                        $sql = 'INSERT INTO trip_expenses (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
                        error_log('Reports API - trip_expenses INSERT SQL: ' . $sql . ' params: ' . json_encode($params));

                        $expenseStmt = executeQuery($sql, $params);
                        if (!$expenseStmt) {
                            error_log('Reports API - Failed to insert trip_expenses');
                            throw new Exception('Failed to insert trip expenses');
                        }
                        $expenseId = $pdo->lastInsertId();
                        error_log('Reports API - trip_expenses inserted with ID: ' . $expenseId);
                    }

                    // Log data before trip_reports insertion
                    error_log('Reports API - About to insert trip_reports with data: ' . json_encode([
                        'trip_id' => $tripId,
                        'fuel_used' => $fuelUsed,
                        'incidents' => $incidents,
                        'remarks' => $remarks,
                        'submitted_by' => $driverId
                    ]));

                    // Insert trip report with dynamic optional expense fields if available
                    $reportCols = ['trip_id', 'fuel_used', 'incidents', 'remarks', 'submitted_by', 'submitted_at'];
                    $reportVals = ['?', '?', '?', '?', '?', 'NOW()'];
                    $reportParams = [$tripId, $fuelUsed, $incidents, $remarks, $driverId];

                    if ($canStoreInReports) {
                        if (in_array('fuel_cost', $tripReportsCols)) { $reportCols[] = 'fuel_cost'; $reportVals[] = '?'; $reportParams[] = $fuelCost; }
                        if (in_array('toll_fee', $tripReportsCols)) { $reportCols[] = 'toll_fee'; $reportVals[] = '?'; $reportParams[] = $tollFee; }
                        if (in_array('parking_fee', $tripReportsCols)) { $reportCols[] = 'parking_fee'; $reportVals[] = '?'; $reportParams[] = $parkingFee; }
                        if (in_array('fuel_receipt', $tripReportsCols)) { $reportCols[] = 'fuel_receipt'; $reportVals[] = '?'; $reportParams[] = $receiptPaths['fuel_receipt'] ?? null; }
                        if (in_array('toll_receipt', $tripReportsCols)) { $reportCols[] = 'toll_receipt'; $reportVals[] = '?'; $reportParams[] = $receiptPaths['toll_receipt'] ?? null; }
                        if (in_array('parking_receipt', $tripReportsCols)) { $reportCols[] = 'parking_receipt'; $reportVals[] = '?'; $reportParams[] = $receiptPaths['parking_receipt'] ?? null; }
                        error_log('Reports API - Storing expense fields in trip_reports');
                    }

                    $reportSql = 'INSERT INTO trip_reports (' . implode(', ', $reportCols) . ') VALUES (' . implode(', ', $reportVals) . ')';
                    error_log('Reports API - trip_reports INSERT SQL: ' . $reportSql . ' params: ' . json_encode($reportParams));

                    $reportStmt = executeQuery($reportSql, $reportParams);
                    
                    if (!$reportStmt) {
                        error_log('Reports API - Failed to insert trip_reports');
                        throw new Exception('Failed to insert trip report');
                    }
                    
                    $reportId = $pdo->lastInsertId();
                    error_log('Reports API - trip_reports inserted with ID: ' . $reportId);

                    // Log the action
                    executeQuery('INSERT INTO audits (actor_type, actor_id, action, entity_type, entity_id, metadata_json) VALUES (?,?,?,?,?,?)', [
                        'driver', $driverId, 'submit_report', 'trip', $tripId, json_encode(['report_id' => $reportId, 'expense_id' => $expenseId, 'receipts' => $receiptPaths])
                    ]);

                    $pdo->commit();
                    echo generateApiResponse(true, [
                        'report_id' => $reportId,
                        'expense_id' => $expenseId,
                        'receipts_uploaded' => count($receiptPaths)
                    ], 'Report submitted successfully');

                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                http_response_code(400);
                echo generateApiResponse(false, null, 'Invalid action');
            }
            break;

        default:
            http_response_code(405);
            echo generateApiResponse(false, null, 'Method not allowed');
            break;
    }
} catch (Exception $e) {
    error_log('Trip reports API error: ' . $e->getMessage());
    http_response_code(500);
    echo generateApiResponse(false, null, 'Internal server error');
}
?>
