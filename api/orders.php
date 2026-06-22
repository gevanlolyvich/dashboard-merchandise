<?php
/**
 * api/orders.php — Endpoint Data Order dari Database Lokal
 *
 * Menggantikan peran ginee-proxy.php dengan membaca data dari
 * tabel sync_orders yang sudah diisi oleh sistem sinkronisasi.
 *
 * Endpoint:
 *   count:  GET /api/orders.php?count=1
 *           GET /api/orders.php?count=1&start_date=2026-01-01&end_date=2026-06-22
 *   list:   GET /api/orders.php?page=0&size=20
 *           GET /api/orders.php?page=0&size=20&orderStatus=DELIVERED
 *           GET /api/orders.php?page=0&size=20&start_date=...&end_date=...
 *
 * Cache: File-based, TTL 60 detik. Dilewati jika ada filter tanggal.
 */

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

define('ORDERS_CACHE_DIR', __DIR__ . '/../sync/cache');
define('ORDERS_CACHE_TTL', 60);

// ---------------------------------------------------------------
//  HELPERS
// ---------------------------------------------------------------

function cacheGet(string $key): ?array
{
    $cacheFile = ORDERS_CACHE_DIR . '/orders_' . md5($key) . '.cache';
    if (!file_exists($cacheFile)) return null;
    if (time() - filemtime($cacheFile) > ORDERS_CACHE_TTL) {
        @unlink($cacheFile);
        return null;
    }
    $data = @file_get_contents($cacheFile);
    if ($data === false) return null;
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : null;
}

function cacheSet(string $key, array $data): void
{
    $cacheDir = ORDERS_CACHE_DIR;
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cacheFile = $cacheDir . '/orders_' . md5($key) . '.cache';
    @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function jsonSuccess(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $statusCode = 502): void
{
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function emptyCountResult(): array
{
    return [
        'totalOrder'        => 0,
        'totalValidOrder'   => 0,
        'totalValidAmount'  => 0,
        'totalAmount'       => 0,
        'totalCancelOrder'  => 0,
        'totalCancelAmount' => 0,
        'totalValidQuantity'=> 0,
    ];
}

function checkSyncTableExists(PDO $pdo): bool
{
    try {
        $pdo->query("SELECT 1 FROM sync_orders LIMIT 0");
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

/** Parse optional start_date / end_date from query string, return [sql, params] */
function buildDateFilter(array &$params): string
{
    $sql = '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate   = $_GET['end_date']   ?? '';

    if (!empty($startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $sql .= ' AND o.external_create_datetime >= :start_date';
        $params[':start_date'] = $startDate . ' 00:00:00';
    }
    if (!empty($endDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $sql .= ' AND o.external_create_datetime <= :end_date';
        $params[':end_date'] = $endDate . ' 23:59:59';
    }
    return $sql;
}

/** Build WHERE clause and params for orders query */
function buildWhereClause(): array
{
    $where  = '';
    $params = [];

    $statusFilter = $_GET['orderStatus'] ?? '';
    if (!empty($statusFilter)) {
        $where .= ' AND o.order_status = :status';
        $params[':status'] = strtoupper($statusFilter);
    }

    $where .= buildDateFilter($params);

    if (!empty($where)) {
        $where = 'WHERE ' . substr($where, 5);
    }
    return [$where, $params];
}

/**
 * Compute count within a date range from sync_orders directly.
 */
function computeCountWithFilter(PDO $pdo, string $where, array $params): array
{
    $result = emptyCountResult();
    try {
        $sql  = "SELECT COUNT(*) FROM sync_orders o {$where}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result['totalOrder'] = (int) $stmt->fetchColumn();

        // Valid orders (not cancelled) — include total_amount & quantity
        $sql = "SELECT COUNT(*) as cnt, COALESCE(SUM(o.total_quantity), 0) as qty, COALESCE(SUM(o.total_amount), 0) as amt
                FROM sync_orders o {$where} AND o.order_status != 'CANCELLED'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        $result['totalValidOrder']   = (int) ($row['cnt'] ?? 0);
        $result['totalValidQuantity']= (int) ($row['qty'] ?? 0);
        $result['totalValidAmount']  = (float) ($row['amt'] ?? 0);

        // All orders — total amount (valid + cancelled + others)
        $sql = "SELECT COALESCE(SUM(o.total_amount), 0) as amt FROM sync_orders o {$where}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result['totalAmount'] = (float) $stmt->fetchColumn();

        // Cancelled orders — count & amount
        $sql = "SELECT COUNT(*) as cnt, COALESCE(SUM(o.total_amount), 0) as amt
                FROM sync_orders o {$where} AND o.order_status = 'CANCELLED'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        $result['totalCancelOrder']  = (int) ($row['cnt'] ?? 0);
        $result['totalCancelAmount'] = (float) ($row['amt'] ?? 0);
    } catch (\Throwable $e) {}
    return $result;
}

// ---------------------------------------------------------------
//  MAIN
// ---------------------------------------------------------------

try {
    $syncTableExists = checkSyncTableExists($pdo);
    $hasDateFilter   = !empty($_GET['start_date']) || !empty($_GET['end_date']);

    // ===================== COUNT =====================
    if (isset($_GET['count'])) {
        if ($hasDateFilter) {
            // Hitung langsung (tidak pakai cache summary)
            [$where, $params] = buildWhereClause();
            $result = computeCountWithFilter($pdo, $where, $params);
            jsonSuccess($result);
        }

        $cacheKey = 'count';
        $cached = cacheGet($cacheKey);
        if ($cached !== null) jsonSuccess($cached);

        $result = emptyCountResult();
        if ($syncTableExists) {
            $stmt = $pdo->query("SELECT * FROM sync_order_summary WHERE id = 1");
            $summary = $stmt->fetch();
            if ($summary && $summary['total_order'] > 0) {
                $result = [
                    'totalOrder'        => (int) $summary['total_order'],
                    'totalValidOrder'   => (int) $summary['total_valid_order'],
                    'totalValidAmount'  => (float) $summary['total_valid_amount'],
                    'totalAmount'       => (float) $summary['total_amount'],
                    'totalCancelOrder'  => (int) $summary['total_cancel_order'],
                    'totalCancelAmount' => (float) $summary['total_cancel_amount'],
                    'totalValidQuantity'=> (int) $summary['total_valid_quantity'],
                ];
            } else {
                $result = computeCountWithFilter($pdo, '', []);
            }
        }
        cacheSet($cacheKey, $result);
        jsonSuccess($result);
    }

    // ===================== ORDERS LIST =====================
    $page = max(0, (int) ($_GET['page'] ?? 0));
    $size = max(1, min(200, (int) ($_GET['size'] ?? 20)));

    // Cache key includes all filter params
    $cacheKey = 'list_' . md5(serialize($_GET));
    $cached = cacheGet($cacheKey);
    if ($cached !== null) jsonSuccess($cached);

    if (!$syncTableExists) {
        $result = ['orders' => [], 'totalElements' => 0, 'totalPages' => 0];
        cacheSet($cacheKey, $result);
        jsonSuccess($result);
    }

    [$where, $params] = buildWhereClause();

    // Count total
    $countSql = "SELECT COUNT(*) FROM sync_orders o {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalElements = (int) $stmt->fetchColumn();
    $totalPages = $size > 0 ? (int) ceil($totalElements / $size) : 0;

    // Fetch page
    $offset = $page * $size;
    $sql = "SELECT o.* FROM sync_orders o {$where} ORDER BY o.external_create_datetime DESC, o.id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $size, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    $ordersResult = [];
    if (!empty($orders)) {
        $orderIds = array_column($orders, 'id');
        $ph = implode(',', array_fill(0, count($orderIds), '?'));
        $itemsStmt = $pdo->prepare("SELECT order_id, product_name, quantity FROM sync_order_items WHERE order_id IN ({$ph}) ORDER BY id ASC");
        $itemsStmt->execute($orderIds);
        $allItems = $itemsStmt->fetchAll();

        $itemsByOrder = [];
        foreach ($allItems as $item) {
            $itemsByOrder[(int) $item['order_id']][] = [
                'productName' => $item['product_name'],
                'quantity'    => (int) $item['quantity'],
            ];
        }

        foreach ($orders as $order) {
            $oid = (int) $order['id'];
            $ordersResult[] = [
                'externalOrderId'          => $order['external_order_id'],
                'orderStatus'              => $order['order_status'],
                'paymentMethod'            => $order['payment_method'],
                'channelId'                => $order['channel_id'],
                'customerName'             => $order['customer_name'],
                'customerMobile'           => $order['customer_mobile'],
                'externalCreateDatetime'   => $order['external_create_datetime']
                    ? date('Y-m-d\TH:i:s', strtotime($order['external_create_datetime']))
                    : null,
                'orderItems'               => $itemsByOrder[$oid] ?? [],
            ];
        }
    }

    $result = ['orders' => $ordersResult, 'totalElements' => $totalElements, 'totalPages' => $totalPages];
    cacheSet($cacheKey, $result);
    jsonSuccess($result);

} catch (\Throwable $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
