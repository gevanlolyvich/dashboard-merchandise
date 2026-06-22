<?php
/**
 * sync/config.php — Konfigurasi Database, API Swagger, dan Cache
 *
 * Semua pengaturan untuk sistem sinkronisasi data dari Swagger API
 * ke database MySQL lokal. Cukup edit file ini untuk menyesuaikan
 * dengan lingkungan deployment Anda.
 */

// ===== DATABASE =====
// Gunakan koneksi yang sama dengan api/db.php
define('SYNC_DB_HOST', 'localhost');
define('SYNC_DB_NAME', 'dashboard_merchandise');
define('SYNC_DB_USER', 'root');
define('SYNC_DB_PASS', '');
define('SYNC_DB_CHARSET', 'utf8mb4');

// ===== API SWAGGER / GINEE =====
// Base URL API Swagger (tanpa trailing slash)
define('API_BASE_URL', 'http://172.16.0.17:3100/api/v1/ginee');

// Token Bearer untuk autentikasi ke Swagger API
// Ganti dengan token yang valid dari sistem Ginee/Swagger Anda
define('API_BEARER_TOKEN', '');

// Timeout per request API dalam detik
define('API_TIMEOUT', 30);

// Maksimal percobaan ulang jika request gagal
define('API_RETRY_MAX', 3);

// Delay antar percobaan ulang dalam milidetik
define('API_RETRY_DELAY_MS', 1000);

// ===== SINKRONISASI =====
// Jumlah record per halaman saat mengambil data dari API
define('SYNC_PAGE_SIZE', 100);

// Batas maksimal halaman yang akan disync (0 = tidak terbatas)
define('SYNC_MAX_PAGES', 0);

// Format datetime yang digunakan API (untuk parsing)
define('API_DATETIME_FORMAT', 'Y-m-d\TH:i:s');

// ===== CACHE (untuk API endpoint) =====
// Direktori penyimpanan cache (pastikan writable)
define('CACHE_DIR', __DIR__ . '/cache');

// Cache TTL dalam detik (60 = 1 menit, sesuai interval cron)
define('CACHE_TTL', 60);

// ===== LOG =====
// Aktifkan logging ke file
define('SYNC_LOG_ENABLED', true);

// Direktori log (default: sync/logs/)
define('SYNC_LOG_DIR', __DIR__ . '/logs');

// Level log: 'error', 'warning', 'info', 'debug'
define('SYNC_LOG_LEVEL', 'info');

// Retensi log dalam hari
define('SYNC_LOG_RETENTION_DAYS', 30);
