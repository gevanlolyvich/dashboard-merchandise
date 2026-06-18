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

$type = $_GET['type'] ?? 'stats';

if ($type === 'stats') {
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $totalStock = $pdo->query("SELECT COALESCE(SUM(current_stock), 0) FROM products WHERE status = 'active'")->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE MONTH(received_date) = MONTH(CURRENT_DATE) AND YEAR(received_date) = YEAR(CURRENT_DATE)");
    $inThisMonth = (int)$stmt->fetchColumn();

    $outThisMonth = $pdo->query("SELECT COALESCE(SUM(ABS(quantity)), 0) FROM stock_mutations WHERE quantity < 0 AND MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)")->fetchColumn();

    $lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock BETWEEN 1 AND 9")->fetchColumn();
    $criticalStock = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock BETWEEN 10 AND 20")->fetchColumn();

    echo json_encode([
        'totalProducts' => (int)$totalProducts,
        'totalStock' => (int)$totalStock,
        'inThisMonth' => $inThisMonth,
        'outThisMonth' => (int)$outThisMonth,
        'lowStock' => (int)$lowStock,
        'criticalStock' => (int)$criticalStock,
        'outOfStock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock <= 0")->fetchColumn(),
    ]);
    exit;
}

if ($type === 'recent') {
    $search = trim($_GET['search'] ?? '');
    $sql = "SELECT product_code, product_name, category, current_stock FROM products WHERE status = 'active'";
    $params = [];
    if ($search) {
        $sql .= " AND (product_code LIKE ? OR product_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY current_stock ASC LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    echo json_encode(['items' => $items]);
    exit;
}

if ($type === 'chart') {
    $stmt = $pdo->query("SELECT DATE_FORMAT(received_date, '%Y-%m') as month, SUM(quantity) as total
                         FROM stock_in
                         WHERE received_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                         GROUP BY month
                         ORDER BY month ASC");
    $data = $stmt->fetchAll();

    $months = [];
    $values = [];
    foreach ($data as $row) {
        $months[] = $row['month'];
        $values[] = (int)$row['total'];
    }

    echo json_encode(['months' => $months, 'values' => $values]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid type']);
