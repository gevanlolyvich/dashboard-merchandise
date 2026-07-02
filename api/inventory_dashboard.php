<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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

// Cek apakah sync_products ada datanya
$syncCount = (int) $pdo->query("SELECT COUNT(*) FROM sync_products")->fetchColumn();
$useSync = $syncCount > 0;

if ($type === 'stats') {
    if ($useSync) {
        $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM sync_products WHERE status = 'active'")->fetchColumn();
        $totalStock = (int) $pdo->query("SELECT COALESCE(SUM(stock), 0) FROM sync_products WHERE status = 'active'")->fetchColumn();
        $lowStock = (int) $pdo->query("SELECT COUNT(*) FROM sync_products WHERE status = 'active' AND stock BETWEEN 1 AND 9")->fetchColumn();
        $criticalStock = (int) $pdo->query("SELECT COUNT(*) FROM sync_products WHERE status = 'active' AND stock BETWEEN 10 AND 20")->fetchColumn();
        $outOfStock = (int) $pdo->query("SELECT COUNT(*) FROM sync_products WHERE status = 'active' AND stock <= 0")->fetchColumn();
    } else {
        $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
        $totalStock = (int) $pdo->query("SELECT COALESCE(SUM(current_stock), 0) FROM products WHERE status = 'active'")->fetchColumn();
        $lowStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock BETWEEN 1 AND 9")->fetchColumn();
        $criticalStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock BETWEEN 10 AND 20")->fetchColumn();
        $outOfStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND current_stock <= 0")->fetchColumn();
    }

    echo json_encode([
        'totalProducts' => $totalProducts,
        'totalStock' => $totalStock,
        'lowStock' => $lowStock,
        'criticalStock' => $criticalStock,
        'outOfStock' => $outOfStock,
        'dataSource' => $useSync ? 'sync_products' : 'products',
    ]);
    exit;
}

if ($type === 'recent') {
    $search = trim($_GET['search'] ?? '');

    if ($useSync) {
        $sql = "SELECT product_code, product_name, category, stock AS current_stock FROM sync_products WHERE status = 'active'";
    } else {
        $sql = "SELECT product_code, product_name, category, current_stock FROM products WHERE status = 'active'";
    }

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

    echo json_encode(['items' => $items, 'dataSource' => $useSync ? 'sync_products' : 'products']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid type']);
