<?php
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Serve existing files directly (CSS, JS, assets, api files, etc.)
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false;
}

// Rewrite all other requests to index.php
$_GET['url'] = ltrim($path, '/');
require __DIR__ . '/index.php';
