<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class SyncProducts
{
    private PDO $db;
    private array $stats = [
        'products_inserted' => 0,
        'products_updated' => 0,
        'products_failed' => 0,
        'started_at' => null,
        'finished_at' => null,
        'error_message' => null,
    ];

    public function __construct()
    {
        $this->db = getSyncDB();
    }

    public function run(): array
    {
        set_time_limit(0);
        $this->stats['started_at'] = date('Y-m-d H:i:s');
        $this->log('=== Sync Products dimulai ===', 'info');

        try {
            $this->ensureTablesExist();

            $channels = ['TIKTOK_ID', 'TOKOPEDIA_ID', 'SHOPEE_ID', 'LAZADA_ID'];
            $baseUrl = rtrim(API_BASE_URL, '/') . '/products';
            $seen = [];
            $totalFetched = 0;

            foreach ($channels as $i => $channelId) {
                if ($i > 0)
                    sleep(2);
                $page = 0;
                $size = 100;

                while (true) {
                    $data = null;

                    for ($attempt = 0; $attempt < 3; $attempt++) {
                        $url = $baseUrl
                            . '?page=' . $page
                            . '&size=' . $size
                            . '&channelId=' . $channelId
                            . '&status=ACTIVE';

                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_CONNECTTIMEOUT => 10,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTPHEADER => ['Accept: application/json'],
                        ]);

                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $error = curl_error($ch);
                        curl_close($ch);

                        if ($httpCode === 429 && $attempt < 2) {
                            $delay = 5 * ($attempt + 1);
                            $this->log("Channel {$channelId} page {$page} - 429, retry in {$delay}s", 'warning');
                            sleep($delay);
                            continue;
                        }

                        if ($error || $httpCode >= 400) {
                            $err = $error ?: "HTTP {$httpCode}";
                            $this->log("Channel {$channelId} page {$page} gagal ({$err})", 'warning');
                            break 2;
                        }

                        $decoded = json_decode($response, true);
                        if (!is_array($decoded) || !($decoded['success'] ?? false)) {
                            $msg = is_array($decoded) ? json_encode($decoded) : 'Invalid response';
                            $this->log("Channel {$channelId} page {$page} - {$msg}", 'warning');
                            break 2;
                        }

                        $data = $decoded;
                        break;
                    }

                    if (!$data) {
                        $this->log("Channel {$channelId} page {$page} - no data after retries", 'warning');
                        break;
                    }

                    $products = $data['data']['products'] ?? [];
                    if (!is_array($products) || empty($products)) {
                        $this->log("Channel {$channelId} page {$page} - 0 products (selesai)", 'info');
                        break;
                    }

                    foreach ($products as $product) {
                        if (!is_array($product))
                            continue;
                        if (($product['status'] ?? '') !== 'ACTIVE')
                            continue;

                        $pid = $product['id'] ?? '';
                        if (empty($pid) || isset($seen[$pid]))
                            continue;
                        $seen[$pid] = true;

                        $this->upsertProduct($product);
                        $totalFetched++;
                    }

                    $totalPages = (int) ($data['data']['totalPages'] ?? 0);
                    $page++;
                    if ($totalPages > 0 && $page >= $totalPages)
                        break;
                    usleep(200000);
                }
            }

            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log("=== Sync Products selesai ({$totalFetched} produk) ===", 'info');

        } catch (\Throwable $e) {
            $this->stats['error_message'] = $e->getMessage();
            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('Sync Products GAGAL: ' . $e->getMessage(), 'error');
        }

        return $this->stats;
    }

    private function upsertProduct(array $product): void
    {
        try {
            $details = $product['details'] ?? [];
            $sku = '';
            $totalStock = 0;
            $price = 0;

            if (is_array($details)) {
                foreach ($details as $d) {
                    $totalStock += (int) ($d['stock'] ?? 0);
                    if (empty($sku) && !empty($d['sku'])) {
                        $sku = $d['sku'];
                    }
                    if ($price === 0 && !empty($d['price'])) {
                        $price = (float) $d['price'];
                    }
                }
            }

            $externalId = $product['id'] ?? '';
            $productCode = $sku ?: $externalId;
            $productName = $product['name'] ?? 'Unknown';
            $category = $product['categoryName'] ?? '';
            $status = strtolower($product['status'] ?? 'active');

            if (empty($productCode) && empty($externalId)) {
                $this->stats['products_failed']++;
                return;
            }

            $rawJson = json_encode($product, JSON_UNESCAPED_UNICODE);

            $stmt = $this->db->prepare("
                INSERT INTO sync_products (
                    external_product_id, product_code, product_name,
                    category, unit, price, stock,
                    status, raw_json, first_synced_at, last_synced_at
                ) VALUES (
                    :eid, :code, :name,
                    :cat, 'PCS', :price, :stock,
                    :status, :raw, NOW(), NOW()
                ) ON DUPLICATE KEY UPDATE
                    external_product_id = VALUES(external_product_id),
                    product_name        = VALUES(product_name),
                    category            = VALUES(category),
                    price               = VALUES(price),
                    stock               = VALUES(stock),
                    status              = VALUES(status),
                    raw_json            = VALUES(raw_json),
                    last_synced_at      = NOW()
            ");

            $stmt->execute([
                ':eid' => $externalId,
                ':code' => $productCode,
                ':name' => $productName,
                ':cat' => $category,
                ':price' => $price,
                ':stock' => $totalStock,
                ':status' => $status,
                ':raw' => $rawJson,
            ]);

            $affected = $stmt->rowCount();
            if ($affected === 1) {
                $this->stats['products_inserted']++;
            } else {
                $this->stats['products_updated']++;
            }

        } catch (\Throwable $e) {
            $this->stats['products_failed']++;
            $this->log("Gagal upsert: " . $e->getMessage(), 'error');
        }
    }

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
                UNIQUE KEY uk_product_code (product_code),
                INDEX idx_product_code (product_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migrasi UNIQUE KEY (kompatibel MySQL 5.7+)
        // 1. Hapus duplikat product_code — keep row dengan last_synced_at terbaru
        $this->db->exec("DELETE t1 FROM sync_products t1 INNER JOIN sync_products t2 WHERE t1.id < t2.id AND t1.product_code = t2.product_code AND t1.product_code IS NOT NULL");
        // 2. Drop old index jika masih ada
        $oldIdx = $this->db->query("SHOW INDEX FROM sync_products WHERE Key_name = 'uk_external_product_id'")->fetchAll();
        if (count($oldIdx) > 0) {
            $this->db->exec("ALTER TABLE sync_products DROP INDEX uk_external_product_id");
        }
        // 3. Add new index jika belum ada
        $newIdx = $this->db->query("SHOW INDEX FROM sync_products WHERE Key_name = 'uk_product_code'")->fetchAll();
        if (count($newIdx) === 0) {
            $this->db->exec("ALTER TABLE sync_products ADD UNIQUE INDEX uk_product_code (product_code)");
        }
    }

    private function log(string $message, string $level = 'info'): void
    {
        if (!SYNC_LOG_ENABLED)
            return;
        $levels = ['error' => 0, 'warning' => 1, 'info' => 2, 'debug' => 3];
        $configLevel = $levels[SYNC_LOG_LEVEL] ?? 2;
        $msgLevel = $levels[$level] ?? 2;
        if ($msgLevel > $configLevel)
            return;

        $logDir = SYNC_LOG_DIR;
        if (!is_dir($logDir))
            @mkdir($logDir, 0755, true);

        $logFile = $logDir . '/sync_products_' . date('Y-m-d') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . "] [{$level}] {$message}" . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

if (PHP_SAPI === 'cli' && !isset($GLOBALS['called_from_sync_all'])) {
    echo "Sync Products - " . date('Y-m-d H:i:s') . PHP_EOL;
    echo str_repeat('=', 50) . PHP_EOL;
    $sync = new SyncProducts();
    $stats = $sync->run();
    echo PHP_EOL . "Hasil:" . PHP_EOL;
    echo "  Products inserted: {$stats['products_inserted']}" . PHP_EOL;
    echo "  Products updated:  {$stats['products_updated']}" . PHP_EOL;
    echo "  Products failed:   {$stats['products_failed']}" . PHP_EOL;
    if ($stats['error_message'])
        echo "  ERROR: {$stats['error_message']}" . PHP_EOL;
    echo str_repeat('=', 50) . PHP_EOL;
    echo "Selesai: {$stats['finished_at']}" . PHP_EOL;
}
