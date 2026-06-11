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
        $stmt = $pdo->prepare("SELECT * FROM opd_customers WHERE id = ?");
        $stmt->execute([$getId]);
        $item = $stmt->fetch();
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'OPD tidak ditemukan']);
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
        $where[] = "(name LIKE ? OR pic_name LIKE ? OR phone LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM opd_customers $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $dataSql = "SELECT * FROM opd_customers $whereClause ORDER BY name ASC LIMIT $limit OFFSET $offset";
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
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $picName = trim($_POST['pic_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Nama OPD wajib diisi']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO opd_customers (name, address, pic_name, phone, email, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $address ?: null, $picName ?: null, $phone ?: null, $email ?: null, $userId]);

    echo json_encode(['success' => true, 'message' => 'OPD berhasil ditambahkan']);
    exit;
}

if ($method === 'PUT') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $picName = trim($_POST['pic_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($id <= 0 || !$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Data tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE opd_customers SET name=?, address=?, pic_name=?, phone=?, email=? WHERE id=?");
    $stmt->execute([$name, $address ?: null, $picName ?: null, $phone ?: null, $email ?: null, $id]);

    echo json_encode(['success' => true, 'message' => 'OPD berhasil diubah']);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM opd_customers WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'OPD tidak ditemukan']);
        exit;
    }

    $pdo->prepare("DELETE FROM opd_customers WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'OPD berhasil dihapus']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
