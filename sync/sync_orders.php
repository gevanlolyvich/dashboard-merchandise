<?php
/**
 * sync/sync_orders.php — Sinkronisasi Data Order dari Swagger ke MySQL
 *
 * Cara kerja:
 * 1. Panggil /orders/count untuk mendapat total order
 * 2. Simpan data ringkasan ke tabel sync_order_summary
 * 3. Loop setiap halaman dari /orders
 * 4. Upsert setiap order ke sync_orders
 * 5. Hapus item lama & insert item baru di sync_order_items
 *
 * Menghasilkan: jumlah inserted, updated, failed
 *
 * Cara pakai:
 *   php sync/sync_orders.php
 *   atau dari sync_all.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api_client.php';

class SyncOrders
{
    private PDO $db;
    private ApiClient $api;
    private array $stats = [
        'orders_inserted' => 0,
        'orders_updated'  => 0,
        'orders_failed'   => 0,
        'items_inserted'  => 0,
        'pages_fetched'   => 0,
        'total_orders'    => 0,
        'started_at'      => null,
        'finished_at'     => null,
        'error_message'   => null,
    ];

    public function __construct()
    {
        $this->db  = getSyncDB();
        $this->api = new ApiClient();
    }

    /**
     * Jalankan sinkronisasi data order
     */
    public function run(): array
    {
        $this->stats['started_at'] = date('Y-m-d H:i:s');
        $this->log('=== Sync Orders dimulai ===', 'info');

        try {
            $this->ensureTablesExist();

            // Step 1: Sync order summary dari endpoint /orders/count
            $this->syncSummary();

            // Step 2: Dapatkan total halaman dari API
            $pageCount = $this->getPageCount();
            $this->stats['total_orders'] = $pageCount['totalElements'] ?? 0;

            $totalPages = $pageCount['totalPages'] ?? 0;

            if (SYNC_MAX_PAGES > 0 && $totalPages > SYNC_MAX_PAGES) {
                $totalPages = SYNC_MAX_PAGES;
            }

            $this->log("Total halaman: {$totalPages}, Total order: {$this->stats['total_orders']}", 'info');

            // Step 3: Loop setiap halaman
            for ($page = 0; $page < $totalPages; $page++) {
                $this->syncOrdersPage($page);
                $this->stats['pages_fetched']++;

                // Progress log setiap 10 halaman
                if (($page + 1) % 10 === 0 || $page === $totalPages - 1) {
                    $this->log(
                        "Progress: halaman " . ($page + 1) . "/{$totalPages} | " .
                        "Inserted: {$this->stats['orders_inserted']} | " .
                        "Updated: {$this->stats['orders_updated']}",
                        'info'
                    );
                }
            }

            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('=== Sync Orders selesai ===', 'info');

        } catch (\Throwable $e) {
            $this->stats['error_message'] = $e->getMessage();
            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('Sync Orders GAGAL: ' . $e->getMessage(), 'error');
        }

        return $this->stats;
    }

    /**
     * Sinkronisasi data summary dari endpoint /orders/count
     */
    private function syncSummary(): void
    {
        try {
            $this->log('Mengambil data summary dari /orders/count...', 'info');
            $response = $this->api->get('/orders/count');

            if (!isset($response['success']) || !$response['success']) {
                throw new RuntimeException('Response /orders/count tidak valid');
            }

            $data = $response['data'] ?? [];

            $stmt = $this->db->prepare("
                INSERT INTO sync_order_summary (
                    id, total_order, total_valid_order, total_valid_amount,
                    total_amount, total_cancel_order, total_cancel_amount,
                    total_valid_quantity, synced_at
                ) VALUES (
                    1, :total_order, :total_valid_order, :total_valid_amount,
                    :total_amount, :total_cancel_order, :total_cancel_amount,
                    :total_valid_quantity, NOW()
                ) ON DUPLICATE KEY UPDATE
                    total_order         = VALUES(total_order),
                    total_valid_order   = VALUES(total_valid_order),
                    total_valid_amount  = VALUES(total_valid_amount),
                    total_amount        = VALUES(total_amount),
                    total_cancel_order  = VALUES(total_cancel_order),
                    total_cancel_amount = VALUES(total_cancel_amount),
                    total_valid_quantity= VALUES(total_valid_quantity),
                    synced_at           = NOW()
            ");

            $stmt->execute([
                ':total_order'         => (int) ($data['totalOrder'] ?? 0),
                ':total_valid_order'   => (int) ($data['totalValidOrder'] ?? 0),
                ':total_valid_amount'  => (float) ($data['totalValidAmount'] ?? 0),
                ':total_amount'        => (float) ($data['totalAmount'] ?? 0),
                ':total_cancel_order'  => (int) ($data['totalCancelOrder'] ?? 0),
                ':total_cancel_amount' => (float) ($data['totalCancelAmount'] ?? 0),
                ':total_valid_quantity'=> (int) ($data['totalValidQuantity'] ?? 0),
            ]);

            $this->log('Summary berhasil disimpan', 'info');

        } catch (\Throwable $e) {
            $this->log('Gagal sync summary (non-fatal): ' . $e->getMessage(), 'warning');
            // Non-fatal: kita tetap lanjut dengan sync orders
        }
    }

    /**
     * Dapatkan total halaman dari endpoint /orders
     */
    private function getPageCount(): array
    {
        $response = $this->api->get('/orders', [
            'page' => 0,
            'size' => 1,
        ]);

        $data = $response['data'] ?? [];

        return [
            'totalElements' => (int) ($data['totalElements'] ?? 0),
            'totalPages'    => (int) ($data['totalPages'] ?? 0),
        ];
    }

    /**
     * Sinkronisasi satu halaman order
     */
    private function syncOrdersPage(int $page): void
    {
        try {
            $response = $this->api->get('/orders', [
                'page' => $page,
                'size' => SYNC_PAGE_SIZE,
            ]);

            if (!isset($response['success']) || !$response['success']) {
                $this->log("Halaman {$page}: response success=false", 'warning');
                return;
            }

            $orders = $response['data']['orders'] ?? [];

            if (empty($orders)) {
                return;
            }

            foreach ($orders as $order) {
                $this->upsertOrder($order, $page);
            }

        } catch (\Throwable $e) {
            $this->stats['orders_failed'] += SYNC_PAGE_SIZE;
            $this->log("Gagal sync halaman {$page}: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Upsert satu order beserta item-itemnya
     *
     * Strategi:
     * 1. INSERT ON DUPLICATE KEY UPDATE untuk menghindari duplikat
     * 2. Hapus item lama lalu insert yang baru
     *    (karena data item bisa berubah jika order diupdate)
     */
    private function upsertOrder(array $order, int $page): void
    {
        try {
            $externalOrderId = $order['externalOrderId'] ?? ($order['id'] ?? '');
            if (empty($externalOrderId)) {
                $this->stats['orders_failed']++;
                return;
            }

            $orderStatus   = $order['orderStatus'] ?? '';
            $paymentMethod = $order['paymentMethod'] ?? '';
            $channelId     = $order['channelId'] ?? '';
            $customerName  = $order['customerName'] ?? '';
            $customerMobile= $order['customerMobile'] ?? '';
            $createDatetime= $order['externalCreateDatetime'] ?? null;

            // Parse datetime
            $createDate = null;
            if (!empty($createDatetime)) {
                $ts = strtotime($createDatetime);
                if ($ts !== false) {
                    $createDate = date('Y-m-d H:i:s', $ts);
                }
            }

            // Hitung total quantity dan amount dari items
            $items = $order['orderItems'] ?? [];
            $totalQty = 0;
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalQty += (int) ($item['quantity'] ?? 1);
            }

            // Ekstrak total amount dari order (field name bervariasi antar API)
            if (isset($order['sellerTotalAmount'])) {
                $totalAmount = (float) $order['sellerTotalAmount'];
            } elseif (isset($order['totalAmount'])) {
                $totalAmount = (float) $order['totalAmount'];
            } elseif (isset($order['totalPrice'])) {
                $totalAmount = (float) $order['totalPrice'];
            } elseif (isset($order['amount'])) {
                $totalAmount = (float) $order['amount'];
            } elseif (isset($order['grandTotal'])) {
                $totalAmount = (float) $order['grandTotal'];
            } elseif (isset($order['total'])) {
                $totalAmount = (float) $order['total'];
            } else {
                // Fallback: hitung dari item price * quantity
                foreach ($items as $item) {
                    if (isset($item['price'])) {
                        $totalAmount += (float) $item['price'] * (int) ($item['quantity'] ?? 1);
                    } elseif (isset($item['totalPrice'])) {
                        $totalAmount += (float) $item['totalPrice'];
                    } elseif (isset($item['total'])) {
                        $totalAmount += (float) $item['total'];
                    }
                }
            }

            // Simpan raw JSON untuk fleksibilitas ke depan
            $rawJson = json_encode($order, JSON_UNESCAPED_UNICODE);

            // UPSERT order
            $stmt = $this->db->prepare("
                INSERT INTO sync_orders (
                    external_order_id, order_status, payment_method,
                    channel_id, customer_name, customer_mobile,
                    external_create_datetime, total_quantity, total_amount,
                    raw_json, first_synced_at, last_synced_at
                ) VALUES (
                    :external_order_id, :order_status, :payment_method,
                    :channel_id, :customer_name, :customer_mobile,
                    :external_create_datetime, :total_quantity, :total_amount,
                    :raw_json, NOW(), NOW()
                ) ON DUPLICATE KEY UPDATE
                    order_status              = VALUES(order_status),
                    payment_method            = VALUES(payment_method),
                    channel_id                = VALUES(channel_id),
                    customer_name             = VALUES(customer_name),
                    customer_mobile           = VALUES(customer_mobile),
                    external_create_datetime  = VALUES(external_create_datetime),
                    total_quantity            = VALUES(total_quantity),
                    total_amount              = VALUES(total_amount),
                    raw_json                  = VALUES(raw_json),
                    last_synced_at            = NOW()
            ");

            $stmt->execute([
                ':external_order_id'        => $externalOrderId,
                ':order_status'             => $orderStatus,
                ':payment_method'           => $paymentMethod,
                ':channel_id'               => $channelId,
                ':customer_name'            => $customerName,
                ':customer_mobile'          => $customerMobile,
                ':external_create_datetime' => $createDate,
                ':total_quantity'           => $totalQty,
                ':total_amount'             => $totalAmount,
                ':raw_json'                 => $rawJson,
            ]);

            $affected = $stmt->rowCount();
            // rowCount = 1 untuk insert, 2 untuk update (pada MySQL dengan ON DUPLICATE KEY)
            if ($affected === 1) {
                $this->stats['orders_inserted']++;
            } else {
                $this->stats['orders_updated']++;
            }

            // Dapatkan ID order dari database
            $orderId = $this->db->lastInsertId();
            if (!$orderId) {
                // Jika update (bukan insert), cari ID yang sudah ada
                $findStmt = $this->db->prepare("SELECT id FROM sync_orders WHERE external_order_id = ?");
                $findStmt->execute([$externalOrderId]);
                $row = $findStmt->fetch();
                $orderId = $row['id'] ?? null;
            }

            // Sync order items: hapus lama, insert baru
            if ($orderId && !empty($items)) {
                $this->syncOrderItems($orderId, $externalOrderId, $items);
            }

        } catch (\Throwable $e) {
            $this->stats['orders_failed']++;
            $this->log("Gagal upsert order: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Sinkronisasi item-item dalam order
     * Strategi: hapus semua item lama, lalu insert item baru
     */
    private function syncOrderItems(int $orderId, string $externalOrderId, array $items): void
    {
        // Hapus item lama
        $deleteStmt = $this->db->prepare("DELETE FROM sync_order_items WHERE order_id = ?");
        $deleteStmt->execute([$orderId]);

        // Insert item baru
        $insertStmt = $this->db->prepare("
            INSERT INTO sync_order_items (
                order_id, external_order_id, product_name, quantity
            ) VALUES (
                :order_id, :external_order_id, :product_name, :quantity
            )
        ");

        foreach ($items as $item) {
            $productName = $item['productName'] ?? 'Unknown Product';
            $quantity    = (int) ($item['quantity'] ?? 1);

            $insertStmt->execute([
                ':order_id'           => $orderId,
                ':external_order_id'  => $externalOrderId,
                ':product_name'       => $productName,
                ':quantity'           => $quantity,
            ]);

            $this->stats['items_inserted']++;
        }
    }

    /**
     * Pastikan tabel-tabel yang diperlukan sudah ada
     */
    private function ensureTablesExist(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sync_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                external_order_id VARCHAR(100) NOT NULL,
                order_status VARCHAR(50) DEFAULT NULL,
                payment_method VARCHAR(100) DEFAULT NULL,
                channel_id VARCHAR(50) DEFAULT NULL,
                customer_name VARCHAR(255) DEFAULT NULL,
                customer_mobile VARCHAR(50) DEFAULT NULL,
                external_create_datetime DATETIME DEFAULT NULL,
                total_quantity INT DEFAULT 0,
                total_amount DECIMAL(15,2) DEFAULT 0,
                raw_json LONGTEXT DEFAULT NULL,
                first_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_external_order_id (external_order_id),
                INDEX idx_order_status (order_status),
                INDEX idx_channel_id (channel_id),
                INDEX idx_create_datetime (external_create_datetime)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sync_order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                external_order_id VARCHAR(100) DEFAULT NULL,
                product_name VARCHAR(255) DEFAULT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id),
                INDEX idx_external_order_id (external_order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sync_order_summary (
                id INT PRIMARY KEY DEFAULT 1,
                total_order INT DEFAULT 0,
                total_valid_order INT DEFAULT 0,
                total_valid_amount DECIMAL(15,2) DEFAULT 0,
                total_amount DECIMAL(15,2) DEFAULT 0,
                total_cancel_order INT DEFAULT 0,
                total_cancel_amount DECIMAL(15,2) DEFAULT 0,
                total_valid_quantity INT DEFAULT 0,
                synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sync_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sync_type VARCHAR(50) NOT NULL,
                status ENUM('running','success','failed') NOT NULL DEFAULT 'running',
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                finished_at DATETIME DEFAULT NULL,
                records_inserted INT DEFAULT 0,
                records_updated INT DEFAULT 0,
                records_failed INT DEFAULT 0,
                total_records INT DEFAULT 0,
                pages_fetched INT DEFAULT 0,
                error_message TEXT DEFAULT NULL,
                INDEX idx_sync_type (sync_type),
                INDEX idx_started_at (started_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Logging ke file dan ke tabel sync_log
     */
    private function log(string $message, string $level = 'info'): void
    {
        if (!SYNC_LOG_ENABLED) {
            return;
        }

        $levels = ['error' => 0, 'warning' => 1, 'info' => 2, 'debug' => 3];
        $configLevel = $levels[SYNC_LOG_LEVEL] ?? 2;
        $msgLevel = $levels[$level] ?? 2;

        if ($msgLevel > $configLevel) {
            return;
        }

        $logDir = SYNC_LOG_DIR;
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/sync_orders_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

// ===== CLI Entry Point =====
if (PHP_SAPI === 'cli' && !isset($GLOBALS['called_from_sync_all'])) {
    echo "Sync Orders - " . date('Y-m-d H:i:s') . PHP_EOL;
    echo str_repeat('=', 50) . PHP_EOL;

    $sync = new SyncOrders();
    $stats = $sync->run();

    echo PHP_EOL . "Hasil:" . PHP_EOL;
    echo "  Orders inserted: {$stats['orders_inserted']}" . PHP_EOL;
    echo "  Orders updated:  {$stats['orders_updated']}" . PHP_EOL;
    echo "  Orders failed:   {$stats['orders_failed']}" . PHP_EOL;
    echo "  Items inserted:  {$stats['items_inserted']}" . PHP_EOL;
    echo "  Pages fetched:   {$stats['pages_fetched']}" . PHP_EOL;

    if ($stats['error_message']) {
        echo "  ERROR: {$stats['error_message']}" . PHP_EOL;
    }

    echo str_repeat('=', 50) . PHP_EOL;
    echo "Selesai: {$stats['finished_at']}" . PHP_EOL;
}
