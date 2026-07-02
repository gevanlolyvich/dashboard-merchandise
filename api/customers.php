<?php
header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = [
    'http://127.0.0.1:5500', 'http://localhost:5500',
    'http://localhost', 'http://127.0.0.1',
    'http://localhost:8000', 'http://127.0.0.1:8000',
    'https://merchandise.jxboard.id',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->query("
        SELECT 
            id, name, mobile, total_spend, total_order,
            customer_group_id, customer_group_name,
            last_synced_at
        FROM sync_customers
        ORDER BY total_spend DESC
    ");
    $rows = $stmt->fetchAll();

    $customers = array_map(function ($r) {
        return [
            'id'             => $r['id'],
            'name'           => $r['name'],
            'mobile'         => $r['mobile'],
            'totalSpend'     => (float) $r['total_spend'],
            'totalOrder'     => (int) $r['total_order'],
            'customerGroup'  => $r['customer_group_name'] ? [
                'id'   => (int) $r['customer_group_id'],
                'name' => $r['customer_group_name'],
            ] : null,
            'lastSyncedAt'   => $r['last_synced_at'],
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'data'    => [
            'customers'    => $customers,
            'totalElements'=> count($customers),
            'totalPages'   => 1,
            'page'         => 0,
            'size'         => count($customers),
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
