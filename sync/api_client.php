<?php
/**
 * sync/api_client.php — HTTP Client untuk Swagger API
 *
 * Fitur:
 * - Bearer Token authentication
 * - Retry logic (3x percobaan)
 * - Timeout per request
 * - Error handling dengan logging
 * - JSON response parsing
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class ApiClient
{
    private string $baseUrl;
    private string $token;
    private int $timeout;
    private int $retryMax;
    private int $retryDelayMs;

    public function __construct()
    {
        $this->baseUrl      = rtrim(API_BASE_URL, '/');
        $this->token        = API_BEARER_TOKEN;
        $this->timeout      = API_TIMEOUT;
        $this->retryMax     = API_RETRY_MAX;
        $this->retryDelayMs = API_RETRY_DELAY_MS;
    }

    /**
     * GET request ke API endpoint
     *
     * @param string $endpoint Contoh: '/orders/count' atau '/orders'
     * @param array  $params   Query parameters (contoh: ['page' => 0, 'size' => 100])
     * @return array Response yang sudah di-decode (associative array)
     *
     * @throws RuntimeException Jika semua percobaan gagal
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $lastError = '';

        for ($attempt = 1; $attempt <= $this->retryMax; $attempt++) {
            $startTime = microtime(true);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => $this->buildHeaders(),
                CURLOPT_USERAGENT      => 'DashboardMerchandise-Sync/1.0',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            // Log request
            $this->log("API Request [{$attempt}/{$this->retryMax}]: GET {$url} -> {$httpCode} ({$elapsed}ms)", 'debug');

            // Handle cURL error
            if ($curlError) {
                $lastError = "cURL error ({$curlErrno}): {$curlError}";
                $this->log("API Error: {$lastError}", 'error');

                if ($attempt < $this->retryMax) {
                    $this->log("Retrying in {$this->retryDelayMs}ms...", 'warning');
                    usleep($this->retryDelayMs * 1000);
                    continue;
                }

                throw new RuntimeException("API request failed after {$this->retryMax} attempts: {$lastError}");
            }

            // Decode JSON
            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $lastError = 'JSON decode error: ' . json_last_error_msg();
                $this->log("API Error: {$lastError}", 'error');

                if ($attempt < $this->retryMax) {
                    usleep($this->retryDelayMs * 1000);
                    continue;
                }

                throw new RuntimeException("API request failed after {$this->retryMax} attempts: {$lastError}");
            }

            // Handle HTTP error codes
            if ($httpCode >= 500) {
                $lastError = "HTTP {$httpCode}: " . ($decoded['message'] ?? 'Server error');
                $this->log("API Error: {$lastError}", 'error');

                if ($attempt < $this->retryMax) {
                    $this->log("Retrying in {$this->retryDelayMs}ms...", 'warning');
                    usleep($this->retryDelayMs * 1000);
                    continue;
                }

                throw new RuntimeException("API request failed after {$this->retryMax} attempts: {$lastError}");
            }

            if ($httpCode >= 400) {
                $message = $decoded['message'] ?? ($decoded['error'] ?? 'Client error');
                throw new RuntimeException("HTTP {$httpCode}: {$message}");
            }

            // Success
            $this->log("API Success: {$httpCode} ({$elapsed}ms)", 'debug');

            return $decoded;
        }

        throw new RuntimeException("Unexpected error in API client: {$lastError}");
    }

    /**
     * Cek koneksi ke API (ping test)
     */
    public function ping(): bool
    {
        try {
            $this->get('/orders/count', [], 5);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Bangun HTTP headers dengan Bearer token
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        return $headers;
    }

    /**
     * Logging sederhana ke file
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

        $logFile = $logDir . '/api_client_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
