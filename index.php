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
    'marketplace-dashboard' => 'Marketplace Dashboard',
    'pos' => 'POS',
    'sales-opd' => 'Penjualan OPD',
    'sales-bumd' => 'Penjualan BUMD',
    'finance' => 'Keuangan',
    'inventory' => 'Dashboard Persediaan',
    'products' => 'Master Produk',
    'stock-in' => 'Pemasukan Stok',
    'stock-mutations' => 'Riwayat Mutasi Stok',
    'stock-adjustments' => 'Refund Produk',
    'refund' => 'Refund Produk',
    'opd-customers' => 'Master OPD',
    'bumd-customers' => 'Master BUMD',
    'merchandise-jff-import' => 'Pemasukan Data Excel',
    'merchandise-jff-dashboard' => 'Dashboard Rekap Merchandise JFF',
    'users' => 'Manajemen User',
];

$pageFileMap = [
    'summary' => 'pages/summary.php',
    'sales-channel' => 'pages/sales-channel.php',
    'product-analysis' => 'pages/product-analysis.php',
    'customer-analysis' => 'pages/customer-analysis.php',
    'sales-trend' => 'pages/sales-trend.php',
    'marketplace' => 'pages/marketplace.php',
    'marketplace-dashboard' => 'pages/marketplace-dashboard.php',
    'pos' => 'pages/pos.php',
    'sales-opd' => 'pages/sales-opd.php',
    'sales-bumd' => 'pages/sales-bumd.php',
    'finance' => 'pages/finance.php',
    'inventory' => 'pages/inventory.php',
    'products' => 'pages/products.php',
    'stock-in' => 'pages/inventory-in.php',
    'stock-mutations' => 'pages/stock-mutations.php',
    'stock-adjustments' => 'pages/refund.php',
    'refund' => 'pages/refund.php',
    'opd-customers' => 'pages/opd-customers.php',
    'bumd-customers' => 'pages/bumd-customers.php',
    'merchandise-jff-import' => 'pages/merchandise-jff-import.php',
    'merchandise-jff-dashboard' => 'pages/merchandise-jff-dashboard.php',
    'users' => 'pages/users.php',
];

$pageTitle = $pageTitleMap[$currentPage] ?? 'Dashboard Merchandise';
$pageFile = $pageFileMap[$currentPage] ?? null;

if (!$pageFile || !file_exists(__DIR__ . '/' . $pageFile)) {
    $currentPage = 'summary';
    $pageTitle = 'Executive Summary';
    $pageFile = 'pages/summary.php';
}

$inventoryPages = ['products', 'stock-in', 'stock-mutations', 'stock-adjustments', 'refund'];
if (in_array($currentPage, $inventoryPages) && $userRole === 'user') {
    $currentPage = 'summary';
    $pageTitle = 'Executive Summary';
    $pageFile = 'pages/summary.php';
}

if ($currentPage === 'users' && $userRole !== 'superadmin') {
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
    'marketplace-dashboard' => 'Ringkasan penjualan marketplace',
    'pos' => 'Data penjualan dari integrasi POS pihak ketiga',
    'sales-opd' => 'Penjualan langsung ke instansi pemerintah',
    'sales-bumd' => 'Penjualan langsung ke perusahaan daerah',
    'finance' => 'Laporan keuangan dan profitabilitas',
    'inventory' => 'Monitoring stok dan persediaan',
    'products' => 'Kelola master produk merchandise',
    'stock-in' => 'Catat pemasukan stok ke gudang',
    'stock-mutations' => 'Riwayat seluruh pergerakan stok',
    'stock-adjustments' => 'Kembalikan stok produk dari transaksi OPD/BUMD',
    'refund' => 'Kembalikan stok produk dari transaksi OPD/BUMD',
    'opd-customers' => 'Data instansi pemerintah daerah',
    'bumd-customers' => 'Data perusahaan daerah',
    'merchandise-jff-import' => 'Import file Excel rekap merchandise JFF',
    'merchandise-jff-dashboard' => 'Dashboard dan analisis rekap merchandise JFF',
    'users' => 'Kelola seluruh user yang terdaftar',
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const API_BASE = BASE_URL + '/api';
    </script>
</head>

<body>

    <!-- ===== LOGIN OVERLAY ===== -->
    <div class="login-overlay <?= $isLoggedIn ? 'hidden' : '' ?>" id="loginOverlay">
        <!-- KIRI: Brand Panel -->
        <div class="login-panel-left">
            <div class="login-panel-content">
                <div class="login-brand-mark">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </div>
                <div class="login-panel-badge">Dashboard Merchandise</div>
                <h1 class="login-panel-title">PT Jakarta<br>Tourisindo</h1>
                <p class="login-panel-sub">Platform manajemen & analitik penjualan merchandise terpadu untuk PT Jakarta
                    Tourisindo.</p>
                <div class="login-panel-features">
                    <div class="login-feature-item">
                        <div class="login-feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10" />
                                <line x1="12" y1="20" x2="12" y2="4" />
                                <line x1="6" y1="20" x2="6" y2="14" />
                            </svg>
                        </div>
                        <span>Analitik penjualan real-time</span>
                    </div>
                    <div class="login-feature-item">
                        <div class="login-feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                            </svg>
                        </div>
                        <span>Manajemen stok & persediaan</span>
                    </div>
                    <div class="login-feature-item">
                        <div class="login-feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1" />
                                <circle cx="20" cy="21" r="1" />
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                            </svg>
                        </div>
                        <span>Integrasi Marketplace & POS</span>
                    </div>
                    <div class="login-feature-item">
                        <div class="login-feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <span>Penjualan OPD & BUMD Jakarta</span>
                    </div>
                </div>
            </div>
            <div class="login-panel-footer">
                &copy; 2026 PT Jakarta Tourisindo
            </div>
        </div>

        <!-- KANAN: Form Login -->
        <div class="login-panel-right">
            <div class="login-form-wrap">
                <div class="login-form-header">
                    <div class="login-form-logo">
                        <span class="logo-badge">JXB</span>
                    </div>
                    <h2>Selamat datang</h2>
                    <p class="login-form-sub">Masuk ke Dashboard Merchandise</p>
                </div>

                <div class="login-field">
                    <label>Username</label>
                    <div class="login-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <input type="text" id="loginUser" placeholder="Masukkan username" autocomplete="off">
                    </div>
                </div>
                <div class="login-field">
                    <label>Password</label>
                    <div class="login-input-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <input type="password" id="loginPass" placeholder="••••••••"
                            onkeydown="if(event.key==='Enter') doLogin()">
                    </div>
                </div>

                <div class="login-error" id="loginError">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        style="width:14px;height:14px;flex-shrink:0">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    Username atau password salah
                </div>

                <button class="login-btn" onclick="doLogin()">
                    Masuk
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                        style="width:15px;height:15px;">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Sidebar backdrop for mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

    <div class="shell-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main">
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
                <p id="successMessage"
                    style="color:var(--on-surface-muted);font-size:0.9375rem;line-height:1.6;white-space:pre-line;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" onclick="closeSuccessModal()">OK</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>