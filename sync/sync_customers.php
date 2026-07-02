<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class SyncCustomers
{
    private PDO $db;
    private string $apiBase;
    private array $stats = [
        'customers_inserted' => 0,
        'customers_updated'  => 0,
        'customers_failed'   => 0,
        'pages_fetched'      => 0,
        'total_customers'    => 0,
        'started_at'         => null,
        'finished_at'        => null,
        'error_message'      => null,
    ];

    public function __construct()
    {
        $this->db = getSyncDB();
        $this->apiBase = rtrim(API_BASE_URL, '/');
    }

    public function run(): array
    {
        $this->stats['started_at'] = date('Y-m-d H:i:s');
        $this->log('=== Sync Customers dimulai ===', 'info');

        try {
            $this->ensureTablesExist();

            $page = 0;
            $size = SYNC_PAGE_SIZE;
            $maxPages = SYNC_MAX_PAGES > 0 ? SYNC_MAX_PAGES : PHP_INT_MAX;

            while ($page < $maxPages) {
                $result = $this->fetchCustomersPage($page, $size);
                if (!$result) break;

                $customers = $result['customers'] ?? [];
                if (empty($customers)) break;

                foreach ($customers as $c) {
                    $this->upsertCustomer($c);
                }

                $this->stats['pages_fetched']++;
                $page++;

                $totalElements = $result['totalElements'] ?? 0;
                $totalPages = $result['totalPages'] ?? 0;
                if ($totalPages > 0 && $page >= $totalPages) break;
                if ($totalElements === 0 && empty($customers)) break;

                $this->log("Progress: halaman {$page} | Inserted: {$this->stats['customers_inserted']} | Updated: {$this->stats['customers_updated']}", 'info');
            }

            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('=== Sync Customers selesai ===', 'info');

        } catch (\Throwable $e) {
            $this->stats['error_message'] = $e->getMessage();
            $this->stats['finished_at'] = date('Y-m-d H:i:s');
            $this->log('Sync Customers GAGAL: ' . $e->getMessage(), 'error');
        }

        return $this->stats;
    }

    private function fetchCustomersPage(int $page, int $size): ?array
    {
        $url = $this->apiBase . '/customers?page=' . $page . '&size=' . $size;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['keyword' => '', 'fullArea' => [], 'createDates' => [], 'groupIds' => []]),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("cURL error page {$page}: {$error}", 'error');
            return null;
        }

        if ($httpCode === 404) {
            $this->log("Page {$page} - Endpoint /customers belum tersedia (404)", 'warning');
            return null;
        }

        if ($httpCode >= 400) {
            $this->log("Page {$page} - HTTP {$httpCode}", 'error');
            return null;
        }

        $decoded = json_decode($response, true);
        if (!$decoded || !isset($decoded['success']) || !$decoded['success']) {
            $this->log("Page {$page} - Response tidak valid", 'warning');
            return null;
        }

        return $decoded['data'] ?? null;
    }

    private function upsertCustomer(array $c): void
    {
        try {
            $groupId = null;
            $groupName = null;
            if (isset($c['customerGroup'])) {
                $groupId = $c['customerGroup']['id'] ?? null;
                $groupName = $c['customerGroup']['name'] ?? null;
            }

            $createDt = !empty($c['createDateTime']) ? date('Y-m-d H:i:s', strtotime($c['createDateTime'])) : null;
            $updateDt = !empty($c['updateDatetime']) ? date('Y-m-d H:i:s', strtotime($c['updateDatetime'])) : null;
            $birthDt = !empty($c['birthDate']) ? $c['birthDate'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO sync_customers (
                    id, name, calling_code, mobile, email, merchant_id,
                    gender, birth_date, country, province, city, district,
                    full_area, create_datetime, update_datetime,
                    total_spend_currency, total_spend, total_order,
                    customer_group_id, customer_group_name, creator, black_status, raw_json,
                    first_synced_at, last_synced_at
                ) VALUES (
                    :id, :name, :calling_code, :mobile, :email, :merchant_id,
                    :gender, :birth_date, :country, :province, :city, :district,
                    :full_area, :create_datetime, :update_datetime,
                    :total_spend_currency, :total_spend, :total_order,
                    :customer_group_id, :customer_group_name, :creator, :black_status, :raw_json,
                    NOW(), NOW()
                ) ON DUPLICATE KEY UPDATE
                    name                  = VALUES(name),
                    calling_code          = VALUES(calling_code),
                    mobile                = VALUES(mobile),
                    email                 = VALUES(email),
                    merchant_id           = VALUES(merchant_id),
                    gender                = VALUES(gender),
                    birth_date            = VALUES(birth_date),
                    country               = VALUES(country),
                    province              = VALUES(province),
                    city                  = VALUES(city),
                    district              = VALUES(district),
                    full_area             = VALUES(full_area),
                    create_datetime       = VALUES(create_datetime),
                    update_datetime       = VALUES(update_datetime),
                    total_spend_currency  = VALUES(total_spend_currency),
                    total_spend           = VALUES(total_spend),
                    total_order           = VALUES(total_order),
                    customer_group_id     = VALUES(customer_group_id),
                    customer_group_name   = VALUES(customer_group_name),
                    creator               = VALUES(creator),
                    black_status          = VALUES(black_status),
                    raw_json              = VALUES(raw_json),
                    last_synced_at        = NOW()
            ");

            $stmt->execute([
                ':id'                   => $c['id'] ?? '',
                ':name'                 => $c['name'] ?? null,
                ':calling_code'         => $c['callingCode'] ?? null,
                ':mobile'               => $c['mobile'] ?? null,
                ':email'                => $c['email'] ?? null,
                ':merchant_id'          => $c['merchantId'] ?? null,
                ':gender'               => $c['gender'] ?? null,
                ':birth_date'           => $birthDt,
                ':country'              => $c['country'] ?? null,
                ':province'             => $c['province'] ?? null,
                ':city'                 => $c['city'] ?? null,
                ':district'             => $c['district'] ?? null,
                ':full_area'            => $c['fullArea'] ?? null,
                ':create_datetime'      => $createDt,
                ':update_datetime'      => $updateDt,
                ':total_spend_currency' => $c['totalSpendCurrency'] ?? null,
                ':total_spend'          => $c['totalSpend'] ?? 0,
                ':total_order'          => $c['totalOrder'] ?? 0,
                ':customer_group_id'    => $groupId,
                ':customer_group_name'  => $groupName,
                ':creator'              => $c['creator'] ?? null,
                ':black_status'         => $c['blackStatus'] ?? 0,
                ':raw_json'             => json_encode($c, JSON_UNESCAPED_UNICODE),
            ]);

            if ($stmt->rowCount() === 1) {
                $this->stats['customers_inserted']++;
            } else {
                $this->stats['customers_updated']++;
            }

        } catch (\Throwable $e) {
            $this->stats['customers_failed']++;
            $this->log("Gagal upsert customer {$c['id']}: " . $e->getMessage(), 'error');
        }
    }

    private function ensureTablesExist(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS sync_customers (
            id VARCHAR(50) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            calling_code VARCHAR(10) DEFAULT NULL,
            mobile VARCHAR(50) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            merchant_id VARCHAR(50) DEFAULT NULL,
            gender VARCHAR(10) DEFAULT NULL,
            birth_date DATE DEFAULT NULL,
            country VARCHAR(10) DEFAULT NULL,
            province VARCHAR(100) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            district VARCHAR(100) DEFAULT NULL,
            full_area TEXT DEFAULT NULL,
            create_datetime DATETIME DEFAULT NULL,
            update_datetime DATETIME DEFAULT NULL,
            total_spend_currency VARCHAR(10) DEFAULT NULL,
            total_spend DECIMAL(15,2) DEFAULT 0.00,
            total_order INT DEFAULT 0,
            customer_group_id INT DEFAULT NULL,
            customer_group_name VARCHAR(100) DEFAULT NULL,
            creator VARCHAR(255) DEFAULT NULL,
            black_status INT DEFAULT 0,
            raw_json LONGTEXT DEFAULT NULL,
            first_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_mobile (mobile),
            INDEX idx_group_name (customer_group_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function log(string $message, string $level = 'info'): void
    {
        if (!SYNC_LOG_ENABLED) return;
        $logDir = SYNC_LOG_DIR;
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/sync_customers_' . date('Y-m-d') . '.log';
        $line = '[' . date('Y-m-d H:i:s') . "] [{$level}] {$message}" . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

// CLI entry
if (!isset($GLOBALS['called_from_sync_all'])) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    echo "Sync Customers - " . date('Y-m-d H:i:s') . PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;
    $sync = new SyncCustomers();
    $stats = $sync->run();
    echo "Inserted: {$stats['customers_inserted']}" . PHP_EOL;
    echo "Updated:  {$stats['customers_updated']}" . PHP_EOL;
    echo "Failed:   {$stats['customers_failed']}" . PHP_EOL;
    echo "Pages:    {$stats['pages_fetched']}" . PHP_EOL;
    echo ($stats['error_message'] ? "Error: {$stats['error_message']}" : "Sukses!") . PHP_EOL;
}
