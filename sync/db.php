<?php
/**
 * sync/db.php — Koneksi Database untuk Sistem Sinkronisasi
 *
 * Mengembalikan objek PDO yang siap digunakan.
 * Gunakan require_once untuk mengimpor koneksi ini.
 */

require_once __DIR__ . '/config.php';

function getSyncDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            SYNC_DB_HOST,
            SYNC_DB_NAME,
            SYNC_DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, SYNC_DB_USER, SYNC_DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[SYNC DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}
