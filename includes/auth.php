<?php
session_start();

if (php_sapi_name() === 'cli-server') {
    define('BASE_URL', '');
} else {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', $scriptDir === '/' || $scriptDir === '\\' ? '' : $scriptDir);
}

// --- Clean URL Routing ---
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$url = strtok($url, '?'); // strip query string if any

$routeMap = [
    '' => 'summary',
    'summary' => 'summary',
    'sales-channel' => 'sales-channel',
    'product-analysis' => 'product-analysis',
    'customer-analysis' => 'customer-analysis',
    'sales-trend' => 'sales-trend',
    'marketplace' => 'marketplace',
    'finance' => 'finance',
    'inventory' => 'inventory',
    'inventory-in' => 'stock-in',
    'inventory-out' => 'stock-adjustments',
    'stock-in' => 'stock-in',
    'stock-adjustments' => 'stock-adjustments',
    'pos' => 'pos',
    'sales-opd' => 'sales-opd',
    'sales-bumd' => 'sales-bumd',
    'products' => 'products',
    'stock-mutations' => 'stock-mutations',
    'opd-customers' => 'opd-customers',
    'bumd-customers' => 'bumd-customers',
    'merchandise-jff-import' => 'merchandise-jff-import',
    'merchandise-jff-dashboard' => 'merchandise-jff-dashboard',
];

$isAdminRoute = preg_match('#^admin/[a-zA-Z0-9_-]+$#', $url);

if ($isAdminRoute) {
    $currentPage = 'admin';
} elseif ($url !== '') {
    // Clean URL mode
    $currentPage = $routeMap[$url] ?? 'summary';
} else {
    // Legacy query string or no parameter
    $currentPage = $_GET['page'] ?? 'summary';
}

// --- Filter visibility ---
$showFilter = in_array($currentPage, [
    'summary',
    'sales-channel',
    'product-analysis',
    'customer-analysis',
    'sales-trend',
    'marketplace',
    'finance',
    'inventory'
]);

// --- Session data ---
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';
$displayName = $_SESSION['display_name'] ?? '';
$username = $_SESSION['username'] ?? '';
