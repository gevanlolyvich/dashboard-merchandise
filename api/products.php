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

$userId = (int)$_SESSION['user_id'];
$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $getId = (int)($_GET['id'] ?? 0);
    if ($getId > 0) {
        $stmt = $pdo->prepare("SELECT p.*, u.display_name as created_by_name FROM products p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ?");
        $stmt->execute([$getId]);
        $item = $stmt->fetch();
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Produk tidak ditemukan']);
            exit;
        }
        echo json_encode(['item' => $item]);
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(p.product_code LIKE ? OR p.product_name LIKE ? OR p.category LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM products p $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $dataSql = "SELECT p.*, u.display_name as created_by_name
                FROM products p
                LEFT JOIN users u ON p.created_by = u.id
                $whereClause
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($dataSql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    echo json_encode([
        'items' => $items,
        'page' => $page,
        'totalPages' => $totalPages,
        'total' => $total
    ]);
    exit;
}

if ($method === 'POST') {
    $productCode = trim($_POST['product_code'] ?? '');
    $productName = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? 'PCS');
    $status = trim($_POST['status'] ?? 'active');

    $errors = [];
    if (!$productCode) $errors[] = 'Kode produk wajib diisi';
    if (!$productName) $errors[] = 'Nama produk wajib diisi';

    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
    $check->execute([$productCode]);
    if ($check->fetchColumn() > 0) $errors[] = 'Kode produk sudah ada';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => implode('. ', $errors)]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO products (product_code, product_name, category, description, unit, status, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$productCode, $productName, $category ?: null, $description ?: null, $unit, $status, $userId]);

    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan']);
    exit;
}

if ($method === 'PUT') {
    $id = (int)($_POST['id'] ?? 0);
    $productCode = trim($_POST['product_code'] ?? '');
    $productName = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? 'PCS');
    $status = trim($_POST['status'] ?? 'active');

    $errors = [];
    if ($id <= 0) $errors[] = 'ID tidak valid';
    if (!$productCode) $errors[] = 'Kode produk wajib diisi';
    if (!$productName) $errors[] = 'Nama produk wajib diisi';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => implode('. ', $errors)]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Produk tidak ditemukan']);
        exit;
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ? AND id != ?");
    $check->execute([$productCode, $id]);
    if ($check->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Kode produk sudah digunakan produk lain']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE products SET product_code=?, product_name=?, category=?, description=?, unit=?, status=? WHERE id=?");
    $stmt->execute([$productCode, $productName, $category ?: null, $description ?: null, $unit, $status, $id]);

    echo json_encode(['success' => true, 'message' => 'Produk berhasil diubah']);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Produk tidak ditemukan']);
        exit;
    }

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
