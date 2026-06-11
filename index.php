<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect admin routes to actual PHP files
if ($isAdminRoute) {
    $adminPage = substr($url, 6); // remove 'admin/'
    $adminFile = 'admin/' . $adminPage . '.php';
    if (file_exists(__DIR__ . '/' . $adminFile)) {
        header('Location: ' . BASE_URL . '/' . $adminFile);
        exit;
    }
    http_response_code(404);
    echo '<h1>404 - Halaman tidak ditemukan</h1>';
    exit;
}

$pageTitleMap = [
    'summary' => 'Executive Summary',
    'sales-channel' => 'Penjualan per Channel',
    'product-analysis' => 'Analisis Produk',
    'customer-analysis' => 'Analisis Customer',
    'sales-trend' => 'Tren Penjualan',
    'marketplace' => 'Marketplace',
    'pos' => 'POS',
    'sales-opd' => 'Penjualan OPD',
    'sales-bumd' => 'Penjualan BUMD',
    'finance' => 'Keuangan',
    'inventory' => 'Dashboard Persediaan',
    'products' => 'Master Produk',
    'stock-in' => 'Pemasukan Stok',
    'stock-mutations' => 'Riwayat Mutasi Stok',
    'stock-adjustments' => 'Penyesuaian Stok',
    'opd-customers' => 'Master OPD',
    'bumd-customers' => 'Master BUMD',
    'merchandise-jff-import' => 'Pemasukan Data Excel',
    'merchandise-jff-dashboard' => 'Dashboard Rekap Merchandise JFF',
];

$pageFileMap = [
    'summary' => 'pages/summary.php',
    'sales-channel' => 'pages/sales-channel.php',
    'product-analysis' => 'pages/product-analysis.php',
    'customer-analysis' => 'pages/customer-analysis.php',
    'sales-trend' => 'pages/sales-trend.php',
    'marketplace' => 'pages/marketplace.php',
    'pos' => 'pages/pos.php',
    'sales-opd' => 'pages/sales-opd.php',
    'sales-bumd' => 'pages/sales-bumd.php',
    'finance' => 'pages/finance.php',
    'inventory' => 'pages/inventory.php',
    'products' => 'pages/products.php',
    'stock-in' => 'pages/inventory-in.php',
    'stock-mutations' => 'pages/stock-mutations.php',
    'stock-adjustments' => 'pages/inventory-out.php',
    'opd-customers' => 'pages/opd-customers.php',
    'bumd-customers' => 'pages/bumd-customers.php',
    'merchandise-jff-import' => 'pages/merchandise-jff-import.php',
    'merchandise-jff-dashboard' => 'pages/merchandise-jff-dashboard.php',
];

$pageTitle = $pageTitleMap[$currentPage] ?? 'Dashboard Merchandise';
$pageFile = $pageFileMap[$currentPage] ?? null;

if (!$pageFile || !file_exists(__DIR__ . '/' . $pageFile)) {
    $currentPage = 'summary';
    $pageTitle = 'Executive Summary';
    $pageFile = 'pages/summary.php';
}

$inventoryPages = ['products', 'stock-in', 'stock-mutations', 'stock-adjustments'];
if (in_array($currentPage, $inventoryPages) && $userRole === 'user') {
    $currentPage = 'summary';
    $pageTitle = 'Executive Summary';
    $pageFile = 'pages/summary.php';
}

$pageSubMap = [
    'summary' => 'PT Jakarta Tourisindo — Real-time operational overview',
    'sales-channel' => 'Analisis penjualan berdasarkan channel distribusi',
    'product-analysis' => 'Analisis performa produk merchandise',
    'customer-analysis' => 'Analisis perilaku dan demografi customer',
    'sales-trend' => 'Tren penjualan bulanan dan tahunan',
    'marketplace' => 'Data marketplace dari integrasi Ginee API',
    'pos' => 'Data penjualan dari integrasi POS pihak ketiga',
    'sales-opd' => 'Penjualan langsung ke instansi pemerintah',
    'sales-bumd' => 'Penjualan langsung ke perusahaan daerah',
    'finance' => 'Laporan keuangan dan profitabilitas',
    'inventory' => 'Monitoring stok dan persediaan',
    'products' => 'Kelola master produk merchandise',
    'stock-in' => 'Catat pemasukan stok ke gudang',
    'stock-mutations' => 'Riwayat seluruh pergerakan stok',
    'stock-adjustments' => 'Penyesuaian stok karena rusak, hilang, atau koreksi',
    'opd-customers' => 'Data instansi pemerintah daerah',
    'bumd-customers' => 'Data perusahaan daerah',
    'merchandise-jff-import' => 'Import file Excel rekap merchandise JFF',
    'merchandise-jff-dashboard' => 'Dashboard dan analisis rekap merchandise JFF',
];

$pageSub = $pageSubMap[$currentPage] ?? '';
?>
<!DOCTYPE html>
<html lang="id" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Jakarta Tourisindo — <?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const API_BASE = BASE_URL + '/api';
    </script>
</head>

<body>

    <!-- ===== LOGIN OVERLAY ===== -->
    <div class="login-overlay <?= $isLoggedIn ? 'hidden' : '' ?>" id="loginOverlay">
        <div class="login-card">
            <div class="login-card-inner">
                <div class="login-logo">
                    <span class="logo-badge">JXB</span>
                    <span class="logo-text">PT Jakarta Tourisindo</span>
                </div>
                <h2>Masuk</h2>
                <div class="login-sub">Dashboard Merchandise — PT Jakarta Tourisindo</div>

                <div class="login-field">
                    <label>Username</label>
                    <input type="text" id="loginUser" placeholder="superadmin / admin / user" autocomplete="off">
                </div>
                <div class="login-field">
                    <label>Password</label>
                    <input type="password" id="loginPass" placeholder="••••••" onkeydown="if(event.key==='Enter') doLogin()">
                </div>
                <div class="login-error" id="loginError">✕ Username atau password salah</div>
                <button class="login-btn" onclick="doLogin()">Masuk</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="shell-layout">

        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main">

            <?php if (!in_array($currentPage, ['stock-in', 'stock-adjustments', 'products', 'opd-customers', 'bumd-customers', 'sales-opd', 'sales-bumd', 'merchandise-jff-import', 'merchandise-jff-dashboard'])): ?>
            <div class="page-header">
                <div>
                    <div class="page-title"><?= htmlspecialchars($pageTitle) ?></div>
                    <div class="page-sub"><?= htmlspecialchars($pageSub) ?></div>
                </div>
                <button class="btn-primary" onclick="applyFilter()">Terapkan Filter</button>
            </div>
            <?php endif; ?>

            <?php if ($showFilter): ?>
                <?php include __DIR__ . '/includes/filter.php'; ?>
            <?php endif; ?>

            <?php
            if ($pageFile && file_exists(__DIR__ . '/' . $pageFile)) {
                include __DIR__ . '/' . $pageFile;
            } else {
                echo '<p>Halaman tidak ditemukan.</p>';
            }
            ?>

        </main>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- ===== GLOBAL CONFIRM / SUCCESS MODALS ===== -->
    <div class="modal-overlay" id="confirmModal" style="display:none">
        <div class="modal-card" style="max-width:380px">
            <div class="modal-header">
                <div class="modal-title" id="confirmTitle">Konfirmasi</div>
                <button class="modal-close" onclick="closeConfirmModal()">✕</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage" style="color:var(--on-surface-muted);font-size:0.9375rem;line-height:1.6"></p>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeConfirmModal()">Batal</button>
                <button class="btn-danger" id="confirmOkBtn" onclick="confirmAction()">Ya, Hapus</button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="successModal" style="display:none">
        <div class="modal-card" style="max-width:380px">
            <div class="modal-header">
                <div class="modal-title" id="successTitle">Berhasil</div>
            </div>
            <div class="modal-body">
                <p id="successMessage" style="color:var(--on-surface-muted);font-size:0.9375rem;line-height:1.6;white-space:pre-line;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeSuccessModal()">OK</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>
