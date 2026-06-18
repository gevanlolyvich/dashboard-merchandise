<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] === 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$where = [];
$params = [];

if ($search) {
    $where[] = "(m.product_code LIKE ? OR m.notes LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s;
}

if ($type) {
    $where[] = "m.mutation_type = ?";
    $params[] = $type;
}

if ($startDate) {
    $where[] = "DATE(m.created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $where[] = "DATE(m.created_at) <= ?";
    $params[] = $endDate;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM stock_mutations m $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

$mutationLabels = [
    'masuk' => 'Masuk',
    'opd' => 'OPD',
    'bumd' => 'BUMD',
    'marketplace' => 'Marketplace',
    'pos' => 'POS',
    'penyesuaian' => 'Penyesuaian',
    'refund' => 'Refund',
];

$dataSql = "SELECT m.*, u.display_name as created_by_name
            FROM stock_mutations m
            LEFT JOIN users u ON m.created_by = u.id
            $whereClause
            ORDER BY m.created_at DESC
            LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($dataSql);
$stmt->execute($params);
$items = $stmt->fetchAll();

foreach ($items as &$item) {
    $item['mutation_label'] = $mutationLabels[$item['mutation_type']] ?? $item['mutation_type'];
}
unset($item);

echo json_encode([
    'items' => $items,
    'page' => $page,
    'totalPages' => $totalPages,
    'total' => $total
]);
