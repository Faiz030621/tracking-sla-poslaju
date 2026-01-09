<?php
// api_fallback.php - Fallback API that can be hosted on a server with API access
// This can be used if the main server cannot access Poslaju API directly

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

// Only allow requests from your domain for security
$allowedDomains = ['localhost', 'tracking.saudagaarasia.com', 'yourdomain.com'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowed = false;

foreach ($allowedDomains as $domain) {
    if (strpos($referer, $domain) !== false) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode([
        'status' => 0,
        'message' => 'Access denied',
        'error' => 'Unauthorized domain'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Load autoloader
if (!file_exists('vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'status' => 0,
        'message' => 'Composer autoload not found',
        'error' => 'Please run: composer install'
    ], JSON_PRETTY_PRINT);
    exit();
}

require 'vendor/autoload.php';

// USE statement
use Afzafri\PoslajuTrackingApi;

try {
    // Get tracking number and postcode from request
    $input = json_decode(file_get_contents('php://input'), true);
    $trackingNo = $input['trackingNo'] ?? $input['tracking_no'] ?? '';
    $postcode = $input['postcode'] ?? '';

    // Validate tracking number
    if (empty($trackingNo)) {
        http_response_code(400);
        echo json_encode([
            'status' => 0,
            'message' => 'Tracking number is required'
        ], JSON_PRETTY_PRINT);
        exit();
    }

    // Call the tracking API
    $trackres = PoslajuTrackingApi::crawl($trackingNo, true);

    // Add metadata
    $trackres['timestamp'] = date('Y-m-d H:i:s T');
    $trackres['requested_tracking_no'] = $trackingNo;
    $trackres['api_version'] = '2.0.0-fallback';

    // Return result
    http_response_code(200);
    echo json_encode($trackres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 0,
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s T')
    ], JSON_PRETTY_PRINT);
}
?>
