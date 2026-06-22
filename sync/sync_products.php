<?php
/**
 * sync/sync_products.php — Sinkronisasi Data Produk dari Swagger ke MySQL
 *
 * Catatan:
 * Endpoint /product saat ini belum digunakan oleh dashboard,
 * namun file ini disediakan untuk antisipasi kebutuhan ke depan.
 *
 * Jika API Swagger tidak menyediakan endpoint /product,
 * file ini bisa diabaikan atau disesuaikan dengan endpoint yang tersedia.
 *
 * Cara pakai:
 *   php sync/sync_products.php
 *   atau dari sync_all.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api_client.php';

class SyncProducts
{
    private PDO $db;
    private ApiClient $api;
    private array $stats = [
        'products_inserted' => 0,
        'products_updated'  => 0,
        'products_failed'   => 0,
        'started_at'        => null,
        'finished_at'       => null,
        'error_message'     => null,
    ];

    public function __construct()
    {
        $this->db  = getSyncDB();
        $this->api = new ApiClient();
    }

    /**
     * Jalankan sinkronisasi data produk
     */
    public function run(): array
    {
        $this->stats['started_at'] = date('Y-m-d H:i:s');
        $this->log('=== Sync Products dimulai ===', 'info');

        try {
            $this->ensureTablesExist();

            // Coba fetch produk dari endpoint /product
            // Jika endpoint tidak tersedia, lewati dengan graceful
            try {
                $response = $this->api->get('/product');
            } catch (\Throwable $e) {
                $this->log('Endpoint /product tidak tersedia atau gagal: ' . $e->getMessage(), 'warning');
                $this->log('Sync Products di-skip (non-fatal)', 'info');
                $this->stats['finished_at'] = date('Y-m-d H:i:s');
                $this->stats['error_message'] = 'Skipped: ' . $e->getMessage();
                return $this->stats;
            }

            if (!isset($response['success']) || !$response['success']) {
                throw new RuntimeException('Response /product tidak valid');
            }

            $products = $response['data']['products'] ?? $response['data'] ?? [];

            // Handle jika data produk berupa array of objects
            if (is_array($products)) {
                // Cek apakah ini array asosiatif (1 produk) atau array of products
                $productList = isset($products['id']) || isset($products['product_code'])
                    ? [$products]
                    : $products;

                foreach ($productList as $product) {
                    $this->upsertProduct($product);
                }
            }

            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('=== Sync Products selesai ===', 'info');

        } catch (\Throwable $e) {
            $this->stats['error_message'] = $e->getMessage();
            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('Sync Products GAGAL: ' . $e->getMessage(), 'error');
        }

        return $this->stats;
    }

    /**
     * Upsert satu produk
     */
    private function upsertProduct(array $product): void
    {
        try {
            $productCode = $product['product_code'] ?? $product['code'] ?? $product['id'] ?? '';
            $productName = $product['product_name'] ?? $product['name'] ?? 'Unknown';
            $category    = $product['category'] ?? '';
            $description = $product['description'] ?? '';
            $unit        = $product['unit'] ?? 'PCS';
            $price       = (float) ($product['price'] ?? $product['harga'] ?? 0);
            $stock       = (int) ($product['stock'] ?? $product['stok'] ?? 0);
            $status      = $product['status'] ?? 'active';

            if (empty($productCode)) {
                $this->stats['products_failed']++;
                return;
            }

            $rawJson = json_encode($product, JSON_UNESCAPED_UNICODE);

            $stmt = $this->db->prepare("
                INSERT INTO sync_products (
                    external_product_id, product_code, product_name,
                    category, description, unit, price, stock,
                    status, raw_json, first_synced_at, last_synced_at
                ) VALUES (
                    :external_product_id, :product_code, :product_name,
                    :category, :description, :unit, :price, :stock,
                    :status, :raw_json, NOW(), NOW()
                ) ON DUPLICATE KEY UPDATE
                    product_name   = VALUES(product_name),
                    category       = VALUES(category),
                    description    = VALUES(description),
                    unit           = VALUES(unit),
                    price          = VALUES(price),
                    stock          = VALUES(stock),
                    status         = VALUES(status),
                    raw_json       = VALUES(raw_json),
                    last_synced_at = NOW()
            ");

            $stmt->execute([
                ':external_product_id' => $productCode,
                ':product_code'        => $productCode,
                ':product_name'        => $productName,
                ':category'            => $category,
                ':description'         => $description,
                ':unit'                => $unit,
                ':price'               => $price,
                ':stock'               => $stock,
                ':status'              => $status,
                ':raw_json'            => $rawJson,
            ]);

            $affected = $stmt->rowCount();
            if ($affected === 1) {
                $this->stats['products_inserted']++;
            } else {
                $this->stats['products_updated']++;
            }

        } catch (\Throwable $e) {
            $this->stats['products_failed']++;
            $this->log("Gagal upsert produk: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Pastikan tabel sync_products sudah ada
     */
    private function ensureTablesExist(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sync_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                external_product_id VARCHAR(100) DEFAULT NULL,
                product_code VARCHAR(100) DEFAULT NULL,
                product_name VARCHAR(255) DEFAULT NULL,
                category VARCHAR(100) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                unit VARCHAR(20) DEFAULT 'PCS',
                price DECIMAL(15,2) DEFAULT 0,
                stock INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'active',
                raw_json LONGTEXT DEFAULT NULL,
                first_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_external_product_id (external_product_id),
                INDEX idx_product_code (product_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Logging ke file
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

        $logFile = $logDir . '/sync_products_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

// ===== CLI Entry Point =====
if (PHP_SAPI === 'cli' && !isset($GLOBALS['called_from_sync_all'])) {
    echo "Sync Products - " . date('Y-m-d H:i:s') . PHP_EOL;
    echo str_repeat('=', 50) . PHP_EOL;

    $sync = new SyncProducts();
    $stats = $sync->run();

    echo PHP_EOL . "Hasil:" . PHP_EOL;
    echo "  Products inserted: {$stats['products_inserted']}" . PHP_EOL;
    echo "  Products updated:  {$stats['products_updated']}" . PHP_EOL;
    echo "  Products failed:   {$stats['products_failed']}" . PHP_EOL;

    if ($stats['error_message']) {
        echo "  ERROR: {$stats['error_message']}" . PHP_EOL;
    }

    echo str_repeat('=', 50) . PHP_EOL;
    echo "Selesai: {$stats['finished_at']}" . PHP_EOL;
}
