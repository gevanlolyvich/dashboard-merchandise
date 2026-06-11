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

function generateBumdTrxNumber($pdo) {
    $prefix = 'BUMD-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales_bumd WHERE transaction_number LIKE ?");
    $stmt->execute([$prefix . '%']);
    $count = (int)$stmt->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

if ($method === 'GET') {
    $getId = (int)($_GET['id'] ?? 0);
    if ($getId > 0) {
        $stmt = $pdo->prepare("SELECT s.*, b.name as bumd_name FROM sales_bumd s LEFT JOIN bumd_customers b ON s.bumd_id = b.id WHERE s.id = ?");
        $stmt->execute([$getId]);
        $sale = $stmt->fetch();
        if (!$sale) {
            http_response_code(404);
            echo json_encode(['error' => 'Transaksi tidak ditemukan']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM sales_bumd_items WHERE sales_bumd_id = ?");
        $stmt->execute([$getId]);
        $items = $stmt->fetchAll();

        $sale['items'] = $items;
        echo json_encode(['item' => $sale]);
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');

    $where = [];
    $params = [];

    if ($search) {
        $where[] = "(s.transaction_number LIKE ? OR b.name LIKE ?)";
        $s = "%$search%";
        $params[] = $s; $params[] = $s;
    }

    if ($status) {
        $where[] = "s.status = ?";
        $params[] = $status;
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM sales_bumd s LEFT JOIN bumd_customers b ON s.bumd_id = b.id $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $dataSql = "SELECT s.*, b.name as bumd_name
                FROM sales_bumd s
                LEFT JOIN bumd_customers b ON s.bumd_id = b.id
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
    $bumdId = (int)($_POST['bumd_id'] ?? 0);
    $transactionDate = trim($_POST['transaction_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];
    $status = trim($_POST['status'] ?? 'draft');

    $errors = [];
    if ($bumdId <= 0) $errors[] = 'BUMD wajib dipilih';
    if (!$transactionDate) $errors[] = 'Tanggal wajib diisi';
    if (empty($products)) $errors[] = 'Minimal satu produk wajib ditambahkan';

    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => implode('. ', $errors)]);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $trxNumber = generateBumdTrxNumber($pdo);

        $stmt = $pdo->prepare("INSERT INTO sales_bumd (transaction_number, bumd_id, transaction_date, status, notes, created_by)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$trxNumber, $bumdId, $transactionDate, $status, $notes ?: null, $userId]);
        $saleId = $pdo->lastInsertId();

        $insertItem = $pdo->prepare("INSERT INTO sales_bumd_items (sales_bumd_id, product_code, product_name, quantity, unit, price, total)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stockCheck = $pdo->prepare("SELECT current_stock FROM products WHERE product_code = ?");
        $stmtSub = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_code = ?");
        $stmtMut = $pdo->prepare("INSERT INTO stock_mutations (product_code, mutation_type, quantity, unit, reference_type, reference_id, notes, created_by)
                                  VALUES (?, 'bumd', ?, ?, 'sales_bumd', ?, ?, ?)");

        foreach ($products as $p) {
            $qty = (int)($p['quantity'] ?? 0);
            $price = (float)($p['price'] ?? 0);
            $total = $qty * $price;
            $insertItem->execute([$saleId, $p['product_code'], $p['product_name'], $qty, $p['unit'] ?? 'PCS', $price, $total]);

            if ($status === 'selesai') {
                $stockCheck->execute([$p['product_code']]);
                $stok = (int)$stockCheck->fetchColumn();
                if ($stok < $qty) {
                    throw new Exception("Stok {$p['product_name']} tidak mencukupi (tersedia: $stok, diminta: $qty)");
                }
                $stmtMut->execute([$p['product_code'], -$qty, $p['unit'] ?? 'PCS', $saleId, $notes ?: null, $userId]);
                $stmtSub->execute([$qty, $p['product_code']]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi BUMD berhasil disimpan', 'transaction_number' => $trxNumber]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
    }
    exit;
}

if ($method === 'PUT') {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($id <= 0 || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Data tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM sales_bumd WHERE id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaksi tidak ditemukan']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $oldStatus = $sale['status'];

        if ($oldStatus !== 'selesai' && $status === 'selesai') {
            $stmt = $pdo->prepare("SELECT * FROM sales_bumd_items WHERE sales_bumd_id = ?");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            $stockCheck = $pdo->prepare("SELECT current_stock FROM products WHERE product_code = ?");
            $stmtSub = $pdo->prepare("UPDATE products SET current_stock = current_stock - ? WHERE product_code = ?");
            $stmtMut = $pdo->prepare("INSERT INTO stock_mutations (product_code, mutation_type, quantity, unit, reference_type, reference_id, notes, created_by)
                                      VALUES (?, 'bumd', ?, ?, 'sales_bumd', ?, ?, ?)");

            foreach ($items as $item) {
                $stockCheck->execute([$item['product_code']]);
                $stok = (int)$stockCheck->fetchColumn();
                if ($stok < $item['quantity']) {
                    throw new Exception("Stok {$item['product_name']} tidak mencukupi (tersedia: $stok, diminta: {$item['quantity']})");
                }
                $stmtMut->execute([$item['product_code'], -$item['quantity'], $item['unit'], $id, $sale['notes'], $userId]);
                $stmtSub->execute([$item['quantity'], $item['product_code']]);
            }
        } elseif ($oldStatus === 'selesai' && $status !== 'selesai') {
            $stmt = $pdo->prepare("SELECT * FROM sales_bumd_items WHERE sales_bumd_id = ?");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_code = ?");
                $stmt->execute([$item['quantity'], $item['product_code']]);

                $pdo->prepare("DELETE FROM stock_mutations WHERE reference_type = 'sales_bumd' AND reference_id = ? AND product_code = ?")
                     ->execute([$id, $item['product_code']]);
            }
        }

        $stmt = $pdo->prepare("UPDATE sales_bumd SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Status transaksi berhasil diubah']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal mengubah status']);
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

    $stmt = $pdo->prepare("SELECT status FROM sales_bumd WHERE id = ?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaksi tidak ditemukan']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        if ($sale['status'] === 'selesai') {
            $stmt = $pdo->prepare("SELECT * FROM sales_bumd_items WHERE sales_bumd_id = ?");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();

            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE products SET current_stock = current_stock + ? WHERE product_code = ?");
                $stmt->execute([$item['quantity'], $item['product_code']]);
            }
        }

        $pdo->prepare("DELETE FROM stock_mutations WHERE reference_type = 'sales_bumd' AND reference_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM sales_bumd_items WHERE sales_bumd_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM sales_bumd WHERE id = ?")->execute([$id]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menghapus transaksi']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
