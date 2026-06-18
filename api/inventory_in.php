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
        $stmt = $pdo->prepare("SELECT s.*, u.display_name as created_by_name
                               FROM stock_in s
                               LEFT JOIN users u ON s.created_by = u.id
                               WHERE s.id = ?");
        $stmt->execute([$getId]);
        $item = $stmt->fetch();
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaksi tidak ditemukan']);
            exit;
        }
        echo json_encode(['item' => $item]);
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $month = $_GET['month'] ?? '';
    $year = $_GET['year'] ?? '';
    $productSearch = trim($_GET['product_search'] ?? '');

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(s.product_code LIKE ? OR s.supplier LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s;
    }

    if ($productSearch) {
        $where[] = "s.product_code LIKE ?";
        $params[] = "%$productSearch%";
    }

    if ($month) {
        $where[] = "MONTH(s.received_date) = ?";
        $params[] = (int)$month;
    }

    if ($year) {
        $where[] = "YEAR(s.received_date) = ?";
        $params[] = (int)$year;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM stock_in s $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $dataSql = "SELECT s.*, u.display_name as created_by_name
                FROM stock_in s
                LEFT JOIN users u ON s.created_by = u.id
                $whereClause
                ORDER BY s.created_at DESC
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
    $quantity = (int)($_POST['quantity'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'PCS');
    $source = trim($_POST['source'] ?? '');
    $supplier = trim($_POST['supplier'] ?? '');
    $referenceNumber = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $receivedDate = trim($_POST['received_date'] ?? '');

    $errors = [];
    if (!$productCode) $errors[] = 'Kode produk wajib diisi';
    if ($quantity <= 0) $errors[] = 'Jumlah harus lebih dari 0';
    if (!$receivedDate) $errors[] = 'Tanggal wajib diisi';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => implode('. ', $errors)]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT product_code, product_name FROM products WHERE product_code = ?");
    $stmt->execute([$productCode]);
    $product = $stmt->fetch();

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO stock_in (product_code, quantity, unit, source, supplier, reference_number, notes, received_date, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$productCode, $quantity, $unit, $source ?: null, $supplier ?: null, $referenceNumber ?: null, $notes ?: null, $receivedDate, $userId]);
        $stockInId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO stock_mutations (product_code, mutation_type, quantity, unit, reference_type, reference_id, notes, created_by)
                               VALUES (?, 'masuk', ?, ?, 'stock_in', ?, ?, ?)");
        $stmt->execute([$productCode, $quantity, $unit, $stockInId, $notes ?: null, $userId]);

        $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_code = ?");
        $stmt->execute([$quantity, $productCode]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pemasukan stok berhasil dicatat']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM stock_in WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaksi tidak ditemukan']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT current_stock FROM products WHERE product_code = ?");
    $stmt->execute([$item['product_code']]);
    $product = $stmt->fetch();
    $remainingStock = $product ? (int)$product['current_stock'] : 0;

    if ($remainingStock < $item['quantity']) {
        http_response_code(400);
        echo json_encode(['error' => "Tidak dapat menghapus pemasukan stok {$item['quantity']} karena stok saat ini ($remainingStock) sudah digunakan oleh transaksi lain (penjualan/penyesuaian). Hapus transaksi terkait terlebih dahulu."]);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM stock_mutations WHERE reference_type = 'stock_in' AND reference_id = ?")->execute([$id]);
        $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_code = ?")->execute([$item['quantity'], $item['product_code']]);
        $pdo->prepare("DELETE FROM stock_in WHERE id = ?")->execute([$id]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pemasukan stok berhasil dihapus']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menghapus data']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
