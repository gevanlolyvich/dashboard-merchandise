<?php
/**
 * sync/sync_all.php — Master Runner Sinkronisasi
 *
 * File ini memanggil semua sync script secara berurutan:
 * 1. sync_orders.php
 * 2. sync_products.php (jika endpoint tersedia)
 *
 * File ini yang akan dipanggil oleh Cron Job setiap 1 menit.
 *
 * Cara pakai:
 *   php sync/sync_all.php
 *
 * Cron job (setiap menit):
 *   * * * * * /usr/bin/php /path/to/dashboard/sync/sync_all.php >> /path/to/dashboard/sync/logs/cron.log 2>&1
 *
 * Untuk Windows Task Scheduler:
 *   php C:\WEB\htdocs\dashboard-merchandise\sync\sync_all.php
 */

// Tandai bahwa ini dipanggil dari sync_all (agar script tidak mencetak output CLI sendiri)
$GLOBALS['called_from_sync_all'] = true;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/api_client.php';

// ===== Konfigurasi =====
define('SYNC_ALL_LOCK_FILE', sys_get_temp_dir() . '/dashboard_sync.lock');

// ===== Fungsi Bantuan =====

/**
 * Cegah multiple execution dengan lock file
 */
function acquireLock(): bool
{
    $lockFile = SYNC_ALL_LOCK_FILE;

    // Cek apakah lock file masih fresh (< 5 menit)
    if (file_exists($lockFile)) {
        $mtime = filemtime($lockFile);
        if (time() - $mtime < 300) { // 5 menit timeout
            $pid = @file_get_contents($lockFile);
            echo "[" . date('Y-m-d H:i:s') . "] Sync sedang berjalan (PID: {$pid}). Dilewati." . PHP_EOL;
            return false;
        } else {
            // Lock file sudah expired, hapus
            @unlink($lockFile);
        }
    }

    file_put_contents($lockFile, getmypid());
    return true;
}

function releaseLock(): void
{
    $lockFile = SYNC_ALL_LOCK_FILE;
    if (file_exists($lockFile)) {
        @unlink($lockFile);
    }
}

/**
 * Logging ke file
 */
function logMessage(string $message, string $level = 'info'): void
{
    if (!SYNC_LOG_ENABLED) {
        return;
    }

    $logDir = SYNC_LOG_DIR;
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/sync_all_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Buat tabel-tabel sync jika belum ada (migrasi otomatis)
 */
function migrateSyncTables(PDO $db): void
{
    $db->exec("
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

    $db->exec("
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

    $db->exec("
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

    $db->exec("
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

    $db->exec("
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
 * Hapus log yang lebih dari retention days
 */
function cleanOldLogs(): void
{
    $logDir = SYNC_LOG_DIR;
    if (!is_dir($logDir)) {
        return;
    }

    $retention = SYNC_LOG_RETENTION_DAYS;
    $cutoff = time() - ($retention * 86400);

    $files = glob($logDir . '/*.log');
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

// ===== Main Execution =====

echo "==========================================" . PHP_EOL;
echo " Dashboard Sync All - " . date('Y-m-d H:i:s') . PHP_EOL;
echo "==========================================" . PHP_EOL;

// Cegah tumpukan proses sync
if (!acquireLock()) {
    exit(0);
}

try {
    // ===== Migrasi: buat tabel jika belum ada =====
    $db = getSyncDB();
    migrateSyncTables($db);

    // Simpan log ke database
    $logStmt = $db->prepare("
        INSERT INTO sync_log (sync_type, status, started_at)
        VALUES ('sync_all', 'running', NOW())
    ");
    $logStmt->execute();
    $syncLogId = $db->lastInsertId();

    $totalInserted = 0;
    $totalUpdated = 0;
    $totalFailed = 0;
    $hasError = false;

    // ===== 1. Sync Orders =====
    echo PHP_EOL . ">>> [1/2] Sync Orders..." . PHP_EOL;
    logMessage('Memulai Sync Orders...', 'info');

    require_once __DIR__ . '/sync_orders.php';
    $syncOrders = new SyncOrders();
    $ordersStats = $syncOrders->run();

    $totalInserted += $ordersStats['orders_inserted'];
    $totalUpdated  += $ordersStats['orders_updated'];
    $totalFailed   += $ordersStats['orders_failed'];

    if ($ordersStats['error_message']) {
        $hasError = true;
        echo "  ⚠ Orders warning: {$ordersStats['error_message']}" . PHP_EOL;
    }

    echo "  ✓ Orders selesai (inserted: {$ordersStats['orders_inserted']}, updated: {$ordersStats['orders_updated']}, pages: {$ordersStats['pages_fetched']})" . PHP_EOL;

    // ===== 2. Sync Products =====
    echo PHP_EOL . ">>> [2/2] Sync Products..." . PHP_EOL;
    logMessage('Memulai Sync Products...', 'info');

    require_once __DIR__ . '/sync_products.php';
    $syncProducts = new SyncProducts();
    $productsStats = $syncProducts->run();

    $totalInserted += $productsStats['products_inserted'];
    $totalUpdated  += $productsStats['products_updated'];
    $totalFailed   += $productsStats['products_failed'];

    if ($productsStats['error_message'] && !str_starts_with($productsStats['error_message'], 'Skipped')) {
        $hasError = true;
        echo "  ⚠ Products warning: {$productsStats['error_message']}" . PHP_EOL;
    }

    echo "  ✓ Products selesai (inserted: {$productsStats['products_inserted']}, updated: {$productsStats['products_updated']})" . PHP_EOL;

    // ===== Update sync log =====
    $status = $hasError ? 'failed' : 'success';
    $updateStmt = $db->prepare("
        UPDATE sync_log SET
            status = :status,
            finished_at = NOW(),
            records_inserted = :inserted,
            records_updated = :updated,
            records_failed = :failed,
            total_records = :total,
            pages_fetched = :pages,
            error_message = :error
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':status'   => $status,
        ':inserted' => $totalInserted,
        ':updated'  => $totalUpdated,
        ':failed'   => $totalFailed,
        ':total'    => $ordersStats['total_orders'] ?? 0,
        ':pages'    => $ordersStats['pages_fetched'] ?? 0,
        ':error'    => $ordersStats['error_message'] ?? ($productsStats['error_message'] ?? null),
        ':id'       => $syncLogId,
    ]);

    // ===== Selesai =====
    echo PHP_EOL . "==========================================" . PHP_EOL;
    echo " Hasil: Inserted={$totalInserted} Updated={$totalUpdated} Failed={$totalFailed}" . PHP_EOL;
    echo " Status: " . ($hasError ? '⚠ SELESAI (dengan error)' : '✓ SUKSES') . PHP_EOL;
    echo "==========================================" . PHP_EOL;

    logMessage("Sync selesai. Inserted={$totalInserted} Updated={$totalUpdated} Failed={$totalFailed}", $hasError ? 'warning' : 'info');

    // Bersihkan log lama
    cleanOldLogs();

} catch (\Throwable $e) {
    echo PHP_EOL . "!!! FATAL ERROR: " . $e->getMessage() . PHP_EOL;
    logMessage('FATAL ERROR: ' . $e->getMessage(), 'error');

    // Update sync log jika ada
    if (isset($db) && isset($syncLogId)) {
        try {
            $updateStmt = $db->prepare("UPDATE sync_log SET status='failed', finished_at=NOW(), error_message=:error WHERE id=:id");
            $updateStmt->execute([':error' => $e->getMessage(), ':id' => $syncLogId]);
        } catch (\Throwable $ignored) {}
    }

    exit(1);
} finally {
    releaseLock();
}

exit(0);
