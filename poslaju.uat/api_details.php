<?php
// API endpoint for details page AJAX loading
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$db_config = require_once 'config.php';

try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4", $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get parameters from POST JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $page = isset($input['page']) ? max(1, intval($input['page'])) : 1;
    $filter = isset($input['filter']) ? $input['filter'] : 'all';
    $perPage = isset($input['per_page']) ? min(100, max(10, intval($input['per_page']))) : 50; // Max 100, min 10, default 50
    $offset = ($page - 1) * $perPage;

    // Build query
    $whereClause = '';
    $params = [];

    if ($filter !== 'all') {
        if ($filter === 'on_time') {
            $whereClause = 'WHERE sla_compliance = :sla';
            $params[':sla'] = 'On Time';
        } elseif ($filter === 'late') {
            $whereClause = 'WHERE sla_compliance = :sla';
            $params[':sla'] = 'Late';
        }
    }

    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM tracking $whereClause";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);

    // Fetch records with pagination
    $query = "SELECT t.*, p.postcode FROM tracking t LEFT JOIN postcode p ON t.postcode_id = p.id $whereClause ORDER BY t.id DESC LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'page' => $page,
        'per_page' => $perPage,
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'filter' => $filter,
        'records' => $records
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'page' => $page ?? 1,
        'filter' => $filter ?? 'all'
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'page' => $page ?? 1,
        'filter' => $filter ?? 'all'
    ], JSON_PRETTY_PRINT);
}
?>
