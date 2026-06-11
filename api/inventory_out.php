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
        $stmt = $pdo->prepare("SELECT a.*, u.display_name as created_by_name
                               FROM stock_adjustments a
                               LEFT JOIN users u ON a.created_by = u.id
                               WHERE a.id = ?");
        $stmt->execute([$getId]);
        $item = $stmt->fetch();
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Penyesuaian tidak ditemukan']);
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

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(a.product_code LIKE ? OR a.reason LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s;
    }

    if ($month) {
        $where[] = "MONTH(a.adjusted_date) = ?";
        $params[] = (int)$month;
    }

    if ($year) {
        $where[] = "YEAR(a.adjusted_date) = ?";
        $params[] = (int)$year;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM stock_adjustments a $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $dataSql = "SELECT a.*, u.display_name as created_by_name
                FROM stock_adjustments a
                LEFT JOIN users u ON a.created_by = u.id
                $whereClause
                ORDER BY a.created_at DESC
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
    $adjustmentType = trim($_POST['adjustment_type'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $adjustedDate = trim($_POST['adjusted_date'] ?? '');

    $errors = [];
    if (!$productCode) $errors[] = 'Kode produk wajib diisi';
    if (!in_array($adjustmentType, ['plus', 'minus'])) $errors[] = 'Tipe penyesuaian tidak valid';
    if ($quantity <= 0) $errors[] = 'Jumlah harus lebih dari 0';
    if (!$reason) $errors[] = 'Alasan penyesuaian wajib diisi';
    if (!$adjustedDate) $errors[] = 'Tanggal wajib diisi';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => implode('. ', $errors)]);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO stock_adjustments (product_code, adjustment_type, quantity, reason, notes, adjusted_date, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$productCode, $adjustmentType, $quantity, $reason, $notes ?: null, $adjustedDate, $userId]);
        $adjId = $pdo->lastInsertId();

        if ($adjustmentType === 'minus') {
            $stmt = $pdo->prepare("SELECT current_stock FROM products WHERE product_code = ?");
            $stmt->execute([$productCode]);
            $stok = (int)$stmt->fetchColumn();
            if ($stok < $quantity) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => "Stok tidak mencukupi untuk pengurangan (tersedia: $stok, dikurangi: $quantity)"]);
                exit;
            }
        }

        $mutationQty = $adjustmentType === 'plus' ? $quantity : -$quantity;
        $stmt = $pdo->prepare("INSERT INTO stock_mutations (product_code, mutation_type, quantity, unit, reference_type, reference_id, notes, created_by)
                               VALUES (?, 'penyesuaian', ?, 'PCS', 'stock_adjustment', ?, ?, ?)");
        $stmt->execute([$productCode, $mutationQty, $adjId, $notes ?: null, $userId]);

        if ($adjustmentType === 'plus') {
            $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_code = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_code = ?");
        }
        $stmt->execute([$quantity, $productCode]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Penyesuaian stok berhasil dicatat']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan data']);
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

    $stmt = $pdo->prepare("SELECT * FROM stock_adjustments WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Penyesuaian tidak ditemukan']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM stock_mutations WHERE reference_type = 'stock_adjustment' AND reference_id = ?")->execute([$id]);

        if ($item['adjustment_type'] === 'plus') {
            $stmt = $pdo->prepare("SELECT current_stock FROM products WHERE product_code = ?");
            $stmt->execute([$item['product_code']]);
            $stok = (int)$stmt->fetchColumn();
            if ($stok < $item['quantity']) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => "Stok tidak mencukupi untuk membatalkan penyesuaian (tersedia: $stok) - reversal sebelumnya mungkin sudah dipakai"]);
                exit;
            }
            $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_code = ?")->execute([$item['quantity'], $item['product_code']]);
        } else {
            $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_code = ?")->execute([$item['quantity'], $item['product_code']]);
        }

        $pdo->prepare("DELETE FROM stock_adjustments WHERE id = ?")->execute([$id]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Penyesuaian stok berhasil dihapus']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menghapus data']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
