<?php
// api_fixed.php - Fixed version with full tracking data in bulk responses

// CORS headers must be first
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set time limits - longer for bulk operations
if (isset($_POST['tracking']) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    // Bulk request - allow more time
    set_time_limit(600); // 10 minutes for bulk (up to 250 items with parallel processing)
    ini_set('memory_limit', '512M');
} else {
    // Single request - shorter timeout
    set_time_limit(30);
    ini_set('memory_limit', '128M');
}

// Load autoloader and class OUTSIDE try block
if (!file_exists('vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'status' => 0,
        'message' => 'Composer autoload not found',
        'error' => 'Please run: composer install',
        'http_code' => 500
    ], JSON_PRETTY_PRINT);
    exit();
}

require 'vendor/autoload.php';

// Load configuration
$db_config = require_once 'config.php';

// USE statement must be at top level
use Afzafri\PoslajuTrackingApi;

// Check if class exists
if (!class_exists('Afzafri\PoslajuTrackingApi')) {
    http_response_code(500);
    echo json_encode([
        'status' => 0,
        'message' => 'PoslajuTrackingApi class not found',
        'error' => 'Check src/PoslajuTrackingApi.php exists',
        'http_code' => 500
    ], JSON_PRETTY_PRINT);
    exit();
}

// Function to process tracking requests in parallel batches
function processTrackingRequestsParallel($requests) {
    $results = [];
    $batchSize = 20; // Process 20 requests concurrently to avoid overwhelming the API
    $batches = array_chunk($requests, $batchSize);

    foreach ($batches as $batch) {
        $mh = curl_multi_init();
        $curlHandles = [];
        $domain = "ttu-svc.pos.com.my";
        $url = "https://$domain/api/trackandtrace/v1/request";

        // Create curl handles for this batch
        foreach ($batch as $request) {
            $trackingNo = $request['tracking_no'];
            $postData = json_encode([
                "connote_ids" => [trim($trackingNo)],
                "culture" => "en"
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
            curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 600);
            curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 60);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'P-Request-ID: ' . generateUUID(),
                'Accept: application/json, text/plain, */*',
                'Content-Type: application/json',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Cache-Control: no-cache',
                'User-Agent: Mozilla/5.0 (compatible; PoslajuTracker/1.0)',
                'host: ' . $domain
            ]);

            curl_multi_add_handle($mh, $ch);
            $curlHandles[] = [
                'handle' => $ch,
                'index' => $request['index'],
                'tracking_no' => $trackingNo,
                'postcode' => $request['postcode']
            ];
        }

        // Execute this batch in parallel
        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        // Process results for this batch
        foreach ($curlHandles as $handleData) {
            $ch = $handleData['handle'];
            $result = curl_multi_getcontent($ch);
            $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $transfer_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $errormsg = curl_error($ch) ?: "No error";

            $trackres = [
                'http_code' => $httpstatus,
                'error_msg' => $errormsg,
                'execution_time' => round($transfer_time * 1000, 2) . 'ms',
                'transfer_time' => round($transfer_time * 1000, 2) . 'ms'
            ];

            if ($result === false) {
                $trackres['status'] = 0;
                $trackres['message'] = "Connection failed: " . $errormsg;
            } else {
                $jsonData = json_decode($result, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $trackres['status'] = 0;
                    $trackres['message'] = "Invalid JSON response: " . json_last_error_msg();
                } elseif ($httpstatus == 200 && !empty($jsonData) && isset($jsonData['data']) && !empty($jsonData['data'])) {
                    $trackingData = $jsonData['data'][0] ?? null;
                    if ($trackingData && isset($trackingData['tracking_data']) && !empty($trackingData['tracking_data'])) {
                        $trackres['status'] = 1;
                        $trackres['message'] = "Record Found";
                        $trackres['data'] = [];
                        foreach ($trackingData['tracking_data'] as $i => $event) {
                            $trackres['data'][$i] = [
                                'date_time' => isset($event['date']) ? $event['date'] : '',
                                'process' => isset($event['process']) ? $event['process'] : '',
                                'event' => isset($event['process_summary']) ? $event['process_summary'] : '',
                            ];
                        }
                        if (isset($trackingData['connote_id'])) {
                            $trackres['tracking_number'] = $trackingData['connote_id'];
                        }
                        if (isset($trackingData['status'])) {
                            $trackres['overall_status'] = $trackingData['status'];
                        }

                        // Detect ready for self-collect
                        $trackres['ready_for_self_collect'] = false;
                        if (stripos($trackres['overall_status'], 'ready for collection') !== false ||
                            stripos($trackres['overall_status'], 'available for collection') !== false ||
                            stripos($trackres['overall_status'], 'self collect') !== false) {
                            $trackres['ready_for_self_collect'] = true;
                        }
                    } else {
                        $trackres['status'] = 0;
                        $trackres['message'] = "No tracking data found";
                    }
                } elseif ($httpstatus != 200) {
                    $trackres['status'] = 0;
                    $trackres['message'] = "API returned HTTP $httpstatus";
                } else {
                    $trackres['status'] = 0;
                    $trackres['message'] = "No Record Found";
                }
            }

            $trackres['timestamp'] = date('Y-m-d H:i:s T');
            $trackres['requested_tracking_no'] = $handleData['tracking_no'];
            $trackres['api_version'] = '2.0.0';

            $results[] = [
                'index' => $handleData['index'],
                'tracking_no' => $handleData['tracking_no'],
                'postcode' => $handleData['postcode'],
                'trackres' => $trackres
            ];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        // Small delay between batches to be respectful to the API
        usleep(100000); // 0.1 seconds
    }

    return $results;
}

// Optimized UUID generation
function generateUUID() {
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    } else {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

try {
    // Check if bulk request
    $input = json_decode(file_get_contents('php://input'), true);
    $isBulk = isset($input['tracking']) && is_array($input['tracking']);

    if ($isBulk) {
        // Handle bulk tracking
        $trackingList = $input['tracking'];

        // Validate batch size
        $maxBatchSize = 250; // Maximum 250 tracking numbers per request (parallel processing)
        if (count($trackingList) > $maxBatchSize) {
            http_response_code(400);
            echo json_encode([
                'status' => 0,
                'message' => 'Batch size too large',
                'error' => "Maximum {$maxBatchSize} tracking numbers per request",
                'provided_count' => count($trackingList),
                'max_allowed' => $maxBatchSize,
                'http_code' => 400,
                'error_msg' => 'Bad Request'
            ], JSON_PRETTY_PRINT);
            exit();
        }

        $results = [];
        $bulkInsertData = [];

        // Database connection for bulk
        $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4", $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // First pass: Process all tracking numbers and collect data (parallel processing)
        $validRequests = [];
        foreach ($trackingList as $index => $item) {
            $trackingNo = trim(strtoupper($item['tracking'] ?? $item['tracking_no'] ?? ''));
            $postcode = trim($item['postcode'] ?? '');

            $result = [
                'index' => $index,
                'tracking_no' => $trackingNo,
                'postcode' => $postcode,
                'status' => 0,
                'message' => '',
                'data' => null
            ];

            // Validate
            if (empty($trackingNo) || strlen($trackingNo) < 10 || strlen($trackingNo) > 20) {
                $result['message'] = 'Invalid tracking number';
                $results[] = $result;
                continue;
            }

            if (empty($postcode)) {
                $result['message'] = 'Postcode required';
                $results[] = $result;
                continue;
            }

            // Collect valid requests for parallel processing
            $validRequests[] = [
                'index' => $index,
                'tracking_no' => $trackingNo,
                'postcode' => $postcode,
                'result' => &$result
            ];
            $results[] = $result;
        }

        // Process valid requests in parallel
        if (!empty($validRequests)) {
            $parallelResults = processTrackingRequestsParallel($validRequests);

            // Process results and prepare database data
            foreach ($parallelResults as $requestData) {
                $index = $requestData['index'];
                $trackingNo = $requestData['tracking_no'];
                $postcode = $requestData['postcode'];
                $trackres = $requestData['trackres'];

                $result = &$results[$index];

                if ($trackres['status'] == 1) {
                    // Extract data for database
                    $shipDate = null;
                    $currentStatus = null;
                    $deliveredDate = null;
                    $slaCompliance = null;
                    $zone = null;
                    $slaMinDays = null;
                    $slaMaxDays = null;
                    $estDeadline = null;
                    $postcodeId = null;

                    if (!empty($trackres['data']) && is_array($trackres['data'])) {
                        $shipDates = [];
                        $sortingDates = [];
                        $deliveredDates = [];
                        $statuses = [];

                        // Collect all relevant dates and statuses
                        foreach ($trackres['data'] as $event) {
                            if (isset($event['process']) && isset($event['date_time'])) {
                                $process = strtolower($event['process']);
                                $eventDate = date('Y-m-d', strtotime($event['date_time']));

                                // Check for sorting in progress events (priority for ship date)
                                if (strpos($process, 'sorting in progress') !== false) {
                                    $sortingDates[] = $eventDate;
                                }

                                // Check for 'picked up' events (priority)
                                if (strpos(strtolower($process), 'picked up') !== false) {
                                    $shipDates[] = $eventDate;
                                }

                                // Check for delivery events
                                if (strpos($process, 'deliver') !== false || strpos($process, 'complete') !== false || strpos($process, 'success') !== false) {
                                    $deliveredDates[] = $eventDate;
                                }

                                // Check for collected events (self-collect completed)
                                if (strpos($process, 'collected') !== false && strpos($process, 'ready') === false) {
                                    $deliveredDates[] = $eventDate;
                                }

                                // Collect all statuses
                                $statuses[] = $event['process'];
                            }
                        }

                        // Ship date: earliest pickup date first, or first "Sorting in progress" date as fallback
                        if (!empty($shipDates)) {
                            $shipDate = min($shipDates);
                        } elseif (!empty($sortingDates)) {
                            $shipDate = min($sortingDates);
                        }

                        // Delivered date: latest delivery date
                        if (!empty($deliveredDates)) {
                            $deliveredDate = max($deliveredDates);
                        }

                        // Current status: most recent event
                        if (!empty($statuses)) {
                            $currentStatus = $statuses[0];
                        }
                    }

                    // Get postcode data
                    $stmt = $pdo->prepare("SELECT id, zone, sla_min_days, sla_max_days FROM postcode WHERE postcode = :postcode LIMIT 1");
                    $stmt->execute(['postcode' => $postcode]);
                    $postcodeData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$postcodeData) {
                        // Postcode not found - mark as failed
                        $result['message'] = 'Invalid postcode: ' . $postcode;
                        continue;
                    }

                    $postcodeId = $postcodeData['id'];
                    $zone = $postcodeData['zone'];
                    $slaMinDays = (int)$postcodeData['sla_min_days'];
                    $slaMaxDays = (int)$postcodeData['sla_max_days'];

                    if ($shipDate && $slaMaxDays) {
                        $estDeadline = date('Y-m-d', strtotime("$shipDate +$slaMaxDays days"));
                    }

                    if ($deliveredDate && $estDeadline) {
                        $slaCompliance = (strtotime($deliveredDate) <= strtotime($estDeadline)) ? 'On Time' : 'Late';
                    }

                    // Collect data for bulk insert
                    $bulkInsertData[] = [
                        'tracking_no' => $trackingNo,
                        'postcode_id' => $postcodeId,
                        'ship_date' => $shipDate,
                        'zone' => $zone,
                        'sla_min_days' => $slaMinDays,
                        'sla_max_days' => $slaMaxDays,
                        'est_deadline' => $estDeadline,
                        'current_status' => $currentStatus,
                        'delivered_date' => $deliveredDate,
                        'sla_compliance' => $slaCompliance,
                        'index' => $index
                    ];

                    $result['status'] = 1;
                    $result['message'] = 'Success';
                    $result['data'] = $trackres;
                } else {
                    $result['message'] = $trackres['message'] ?? 'Tracking failed';
                }
            }
        }

        // Second pass: Bulk database insert/update all at once
        if (!empty($bulkInsertData)) {
            try {
                // Begin transaction for atomic operation
                $pdo->beginTransaction();

                // Prepare bulk insert statement
                $placeholders = [];
                $values = [];
                foreach ($bulkInsertData as $i => $data) {
                    $placeholders[] = "(:tracking_no_{$i}, :postcode_id_{$i}, :ship_date_{$i}, :zone_{$i}, :sla_min_days_{$i}, :sla_max_days_{$i}, :est_deadline_{$i}, :current_status_{$i}, :delivered_date_{$i}, :sla_compliance_{$i}, NOW(), NOW())";
                    $values[":tracking_no_{$i}"] = $data['tracking_no'];
                    $values[":postcode_id_{$i}"] = $data['postcode_id'];
                    $values[":ship_date_{$i}"] = $data['ship_date'];
                    $values[":zone_{$i}"] = $data['zone'];
                    $values[":sla_min_days_{$i}"] = $data['sla_min_days'];
                    $values[":sla_max_days_{$i}"] = $data['sla_max_days'];
                    $values[":est_deadline_{$i}"] = $data['est_deadline'];
                    $values[":current_status_{$i}"] = $data['current_status'];
                    $values[":delivered_date_{$i}"] = $data['delivered_date'];
                    $values[":sla_compliance_{$i}"] = $data['sla_compliance'];
                }

                $sql = "INSERT INTO tracking (tracking_no, postcode_id, ship_date, zone, sla_min_days, sla_max_days, est_deadline, current_status, delivered_date, sla_compliance, created_at, upload_at)
                        VALUES " . implode(', ', $placeholders) . "
                        ON DUPLICATE KEY UPDATE
                            postcode_id = VALUES(postcode_id),
                            ship_date = VALUES(ship_date),
                            zone = VALUES(zone),
                            sla_min_days = VALUES(sla_min_days),
                            sla_max_days = VALUES(sla_max_days),
                            est_deadline = VALUES(est_deadline),
                            current_status = VALUES(current_status),
                            delivered_date = VALUES(delivered_date),
                            sla_compliance = VALUES(sla_compliance),
                            upload_at = NOW()";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);

                // Commit transaction
                $pdo->commit();

            } catch (Exception $e) {
                // Rollback on error
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // Mark all results as failed due to database error
                foreach ($results as &$result) {
                    if ($result['status'] == 1) {
                        $result['status'] = 0;
                        $result['message'] = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }

        // Calculate summary
        $successCount = 0;
        $errorCount = 0;
        $summary = [];

        foreach ($results as $result) {
            if ($result['status'] == 1) {
                $successCount++;
            } else {
                $errorCount++;
            }

            // Create summary with full data for successful tracking
            if ($result['status'] == 1 && isset($result['data'])) {
                $summary[] = [
                    'index' => $result['index'],
                    'tracking_no' => $result['tracking_no'],
                    'postcode' => $result['postcode'],
                    'status' => $result['status'],
                    'message' => $result['message'],
                    'data' => $result['data'] // Include full tracking data
                ];
            } else {
                $summary[] = [
                    'index' => $result['index'],
                    'tracking_no' => $result['tracking_no'],
                    'postcode' => $result['postcode'],
                    'status' => $result['status'],
                    'message' => $result['message']
                ];
            }
        }

        // Try to encode response, fallback to summary-only if too large
        $response = [
            'status' => 1,
            'message' => 'Bulk tracking completed',
            'total_processed' => count($results),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $summary,
            'timestamp' => date('Y-m-d H:i:s T')
        ];

        $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Check if response is too large (> 10MB), if so send summary only
        if (strlen($jsonResponse) > 10 * 1024 * 1024) {
            $response['results'] = array_map(function($r) {
                return [
                    'index' => $r['index'],
                    'tracking_no' => $r['tracking_no'],
                    'status' => $r['status'],
                    'message' => $r['status'] == 1 ? 'Success' : $r['message']
                ];
            }, $summary);
            $response['note'] = 'Response size limited - detailed data omitted';
            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        http_response_code(200);
        echo $jsonResponse;
        exit();
    }

    // Single tracking (existing logic)
    // Get tracking number and postcode from GET or POST
    $trackingNo = '';
    $postcode = '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $trackingNo = $_GET['trackingNo'] ?? '';
        $postcode = $_GET['postcode'] ?? '';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $trackingNo = $input['trackingNo'] ?? $input['tracking_no'] ?? '';
        $postcode = $input['postcode'] ?? '';
    }

    // Validate tracking number
    if (empty($trackingNo)) {
        http_response_code(400);
        echo json_encode([
            'status' => 0,
            'message' => 'Tracking number is required',
            'error' => 'Missing trackingNo parameter',
            'usage' => 'GET: ?trackingNo=EB123456789MY&postcode=50000 or POST: {"trackingNo":"EB123456789MY","postcode":"50000"}',
            'http_code' => 400,
            'error_msg' => 'Bad Request'
        ], JSON_PRETTY_PRINT);
        exit();
    }

    // Validate postcode
    if (empty($postcode)) {
        http_response_code(400);
        echo json_encode([
            'status' => 0,
            'message' => 'Postcode is required',
            'error' => 'Missing postcode parameter',
            'usage' => 'GET: ?trackingNo=EB123456789MY&postcode=50000 or POST: {"trackingNo":"EB123456789MY","postcode":"50000"}',
            'http_code' => 400,
            'error_msg' => 'Bad Request'
        ], JSON_PRETTY_PRINT);
        exit();
    }

    // Validate format
    $trackingNo = trim(strtoupper($trackingNo));
    if (strlen($trackingNo) < 10 || strlen($trackingNo) > 20) {
        http_response_code(400);
        echo json_encode([
            'status' => 0,
            'message' => 'Invalid tracking number format',
            'error' => 'Tracking number should be 10-20 characters',
            'provided' => $trackingNo,
            'length' => strlen($trackingNo),
            'http_code' => 400,
            'error_msg' => 'Bad Request'
        ], JSON_PRETTY_PRINT);
        exit();
    }

    // Call the tracking API
    $trackres = PoslajuTrackingApi::crawl($trackingNo, true);

    // Add timestamp and request info
    $trackres['timestamp'] = date('Y-m-d H:i:s T');
    $trackres['requested_tracking_no'] = $trackingNo;
    $trackres['api_version'] = '2.0.0';

    // Save to database always (even if no tracking data found)
    if (isset($trackres['status'])) {
        try {
            // Database connection
            $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4", $db_config['username'], $db_config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Extract data for tracking table
            $shipDate = null;
            $currentStatus = null;
            $deliveredDate = null;
            $slaCompliance = null;
            $zone = null;
            $slaMinDays = null;
            $slaMaxDays = null;
            $estDeadline = null;

            // Extract from API data if available
            if (!empty($trackres['data']) && is_array($trackres['data'])) {
                $shipDates = [];
                $sortingDates = [];
                $deliveredDates = [];
                $statuses = [];

                // Collect all relevant dates and statuses
                foreach ($trackres['data'] as $event) {
                    if (isset($event['process']) && isset($event['date_time'])) {
                        $process = strtolower($event['process']);
                        $eventDate = date('Y-m-d', strtotime($event['date_time']));

                        // Check for sorting in progress events (priority for ship date)
                        if (strpos($process, 'sorting in progress') !== false) {
                            $sortingDates[] = $eventDate;
                        }

                        // Check for 'picked up' events (priority)
                        if (strpos(strtolower($process), 'picked up') !== false) {
                            $shipDates[] = $eventDate;
                        }

                        // Check for delivery events
                        if (strpos($process, 'deliver') !== false || strpos($process, 'complete') !== false || strpos($process, 'success') !== false) {
                            $deliveredDates[] = $eventDate;
                        }

                        // Check for collected events (self-collect completed)
                        if (strpos($process, 'collected') !== false && strpos($process, 'ready') === false) {
                            $deliveredDates[] = $eventDate;
                        }

                        // Collect all statuses
                        $statuses[] = $event['process'];
                    }
                }

                        // Ship date: earliest pickup date first, or first "Sorting in progress" date as fallback
                        if (!empty($shipDates)) {
                            $shipDate = min($shipDates);
                        } elseif (!empty($sortingDates)) {
                            $shipDate = min($sortingDates);
                        }

                // Delivered date: latest delivery date
                if (!empty($deliveredDates)) {
                    $deliveredDate = max($deliveredDates);
                }

                // Current status: most recent event (assuming events are in reverse chronological order)
                if (!empty($statuses)) {
                    $currentStatus = $statuses[0];
                }
            }

            // Query postcode table for zone and SLA days
            $stmt = $pdo->prepare("SELECT id, zone, sla_min_days, sla_max_days FROM postcode WHERE postcode = :postcode LIMIT 1");
            $stmt->execute(['postcode' => $postcode]);
            $postcodeData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($postcodeData) {
                $postcodeId = $postcodeData['id'];
                $zone = $postcodeData['zone'];
                $slaMinDays = (int)$postcodeData['sla_min_days'];
                $slaMaxDays = (int)$postcodeData['sla_max_days'];
            }

            // Calculate estimated deadline using SLA max days (worst case scenario)
            if ($shipDate && $slaMaxDays) {
                $estDeadline = date('Y-m-d', strtotime("$shipDate +$slaMaxDays days"));
            }

            // Calculate SLA compliance if deliveredDate and estDeadline available
            if ($deliveredDate && $estDeadline) {
                $slaCompliance = (strtotime($deliveredDate) <= strtotime($estDeadline)) ? 'On Time' : 'Late';
            }

            // Insert or update tracking record
            $sql = "INSERT INTO tracking (tracking_no, postcode_id, ship_date, zone, sla_min_days, sla_max_days, est_deadline, current_status, delivered_date, sla_compliance, created_at, upload_at)
                    VALUES (:tracking_no, :postcode_id, :ship_date, :zone, :sla_min_days, :sla_max_days, :est_deadline, :current_status, :delivered_date, :sla_compliance, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        postcode_id = VALUES(postcode_id),
                        ship_date = VALUES(ship_date),
                        zone = VALUES(zone),
                        sla_min_days = VALUES(sla_min_days),
                        sla_max_days = VALUES(sla_max_days),
                        est_deadline = VALUES(est_deadline),
                        current_status = VALUES(current_status),
                        delivered_date = VALUES(delivered_date),
                        sla_compliance = VALUES(sla_compliance),
                        upload_at = NOW()";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tracking_no' => $trackingNo,
                ':postcode_id' => $postcodeId,
                ':ship_date' => $shipDate,
                ':zone' => $zone,
                ':sla_min_days' => $slaMinDays,
                ':sla_max_days' => $slaMaxDays,
                ':est_deadline' => $estDeadline,
                ':current_status' => $currentStatus,
                ':delivered_date' => $deliveredDate,
                ':sla_compliance' => $slaCompliance
            ]);
        } catch (PDOException $ex) {
            error_log("Database error saving tracking: " . $ex->getMessage());
            // Do not fail the API response on DB error
        }
    }

    // Return result
    http_response_code(200);
    echo json_encode($trackres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Log error details
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s T'),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
    ];

    error_log('Poslaju API Error: ' . json_encode($error_details));

    http_response_code(500);
    echo json_encode([
        'status' => 0,
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s T'),
        'http_code' => 500,
        'error_msg' => 'Internal Server Error',
        'debug_info' => [
            'php_version' => PHP_VERSION,
            'autoload_exists' => file_exists('vendor/autoload.php') ? 'Yes' : 'No',
            'class_exists' => class_exists('Afzafri\PoslajuTrackingApi') ? 'Yes' : 'No'
        ]
    ], JSON_PRETTY_PRINT);

} catch (Error $e) {
    // Handle PHP fatal errors
    error_log('Poslaju API Fatal Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 0,
        'message' => 'Fatal server error',
        'error' => 'Internal error occurred',
        'timestamp' => date('Y-m-d H:i:s T'),
        'http_code' => 500,
        'error_msg' => 'Internal Server Error'
    ], JSON_PRETTY_PRINT);
}

// Cleanup
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
?>
