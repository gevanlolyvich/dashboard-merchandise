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
    $type = $_GET['type'] ?? '';

    if ($type === 'transaction') {
        $txType = $_GET['tx_type'] ?? '';
        $txId = (int)($_GET['tx_id'] ?? 0);

        if ($txType === 'opd') {
            $stmt = $pdo->prepare("SELECT * FROM sales_opd WHERE id = ? AND status = 'selesai'");
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();
            if (!$tx) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaksi tidak ditemukan atau status bukan selesai']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT soi.*, p.current_stock FROM sales_opd_items soi LEFT JOIN products p ON soi.product_code = p.product_code WHERE soi.sales_opd_id = ?");
            $stmt->execute([$txId]);
            $items = $stmt->fetchAll();
            echo json_encode(['transaction' => $tx, 'items' => $items]);
        } elseif ($txType === 'bumd') {
            $stmt = $pdo->prepare("SELECT * FROM sales_bumd WHERE id = ? AND status = 'selesai'");
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();
            if (!$tx) {
                http_response_code(404);
                echo json_encode(['error' => 'Transaksi tidak ditemukan atau status bukan selesai']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT sbi.*, p.current_stock FROM sales_bumd_items sbi LEFT JOIN products p ON sbi.product_code = p.product_code WHERE sbi.sales_bumd_id = ?");
            $stmt->execute([$txId]);
            $items = $stmt->fetchAll();
            echo json_encode(['transaction' => $tx, 'items' => $items]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Tipe transaksi tidak valid']);
        }
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $txType = trim($_GET['tx_type'] ?? '');

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(r.id LIKE ? OR r.transaction_number LIKE ? OR r.customer_name LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s;
    }

    if ($txType) {
        $where[] = "r.transaction_type = ?";
        $params[] = $txType;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM refunds r $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $dataSql = "SELECT r.*, u.display_name as created_by_name
                FROM refunds r
                LEFT JOIN users u ON r.created_by = u.id
                $whereClause
                ORDER BY r.created_at DESC
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
    $txType = trim($_POST['transaction_type'] ?? '');
    $txId = (int)($_POST['transaction_id'] ?? 0);
    $refundDate = trim($_POST['refund_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $productsJson = trim($_POST['products'] ?? '[]');
    $products = json_decode($productsJson, true);

    $errors = [];
    if (!in_array($txType, ['opd', 'bumd'])) $errors[] = 'Tipe transaksi tidak valid';
    if ($txId <= 0) $errors[] = 'Transaksi wajib dipilih';
    if (!$refundDate) $errors[] = 'Tanggal refund wajib diisi';
    if (empty($products)) $errors[] = 'Minimal satu produk wajib di-refund';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => implode('. ', $errors)]);
        exit;
    }

    $table = $txType === 'opd' ? 'sales_opd' : 'sales_bumd';
    $itemsTable = $txType === 'opd' ? 'sales_opd_items' : 'sales_bumd_items';

    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ? AND status = 'selesai'");
    $stmt->execute([$txId]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        http_response_code(400);
        echo json_encode(['error' => 'Transaksi tidak ditemukan atau status bukan selesai']);
        exit;
    }

    $txNumber = $transaction['transaction_number'];
    $customerField = $txType === 'opd' ? 'opd_id' : 'bumd_id';
    $customerTable = $txType === 'opd' ? 'opd_customers' : 'bumd_customers';
    $stmt = $pdo->prepare("SELECT name FROM $customerTable WHERE id = ?");
    $stmt->execute([$transaction[$customerField]]);
    $customerName = $stmt->fetchColumn() ?: '';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO refunds (transaction_type, transaction_id, transaction_number, customer_name, refund_date, notes, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$txType, $txId, $txNumber, $customerName, $refundDate, $notes ?: null, $userId]);
        $refundId = $pdo->lastInsertId();

        foreach ($products as $prod) {
            $productCode = trim($prod['product_code'] ?? '');
            $quantity = (int)($prod['quantity'] ?? 0);

            if (!$productCode || $quantity <= 0) continue;

            $stmt = $pdo->prepare("INSERT INTO refund_items (refund_id, product_code, quantity, unit)
                                   VALUES (?, ?, ?, 'PCS')");
            $stmt->execute([$refundId, $productCode, $quantity]);

            $stmt = $pdo->prepare("INSERT INTO stock_mutations (product_code, mutation_type, quantity, unit, reference_type, reference_id, notes, created_by)
                                   VALUES (?, 'refund', ?, 'PCS', 'refund', ?, ?, ?)");
            $stmt->execute([$productCode, $quantity, $refundId, $notes ?: null, $userId]);

            $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_code = ?");
            $stmt->execute([$quantity, $productCode]);
        }

        $stmt = $pdo->prepare("UPDATE $table SET status = 'refund' WHERE id = ?");
        $stmt->execute([$txId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Refund $txNumber berhasil, stok dikembalikan"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal memproses refund: ' . $e->getMessage()]);
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

    $stmt = $pdo->prepare("SELECT * FROM refunds WHERE id = ?");
    $stmt->execute([$id]);
    $refund = $stmt->fetch();

    if (!$refund) {
        http_response_code(404);
        echo json_encode(['error' => 'Refund tidak ditemukan']);
        exit;
    }

    $table = $refund['transaction_type'] === 'opd' ? 'sales_opd' : 'sales_bumd';

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM stock_mutations WHERE reference_type = 'refund' AND reference_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("SELECT product_code, quantity FROM refund_items WHERE refund_id = ?");
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_code = ?")->execute([$item['quantity'], $item['product_code']]);
        }

        $pdo->prepare("UPDATE $table SET status = 'selesai' WHERE id = ?")->execute([$refund['transaction_id']]);

        $pdo->prepare("DELETE FROM refund_items WHERE refund_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM refunds WHERE id = ?")->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Refund berhasil dibatalkan, stok dikembalikan ke posisi sebelum refund']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal membatalkan refund']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
