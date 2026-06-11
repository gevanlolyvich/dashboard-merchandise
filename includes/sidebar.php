<?php
// SVG icon helper — inline, clean stroke icons
function navIcon($type) {
    $icons = [
        'summary'     => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'channel'     => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'product'     => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'customer'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'trend'       => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'finance'     => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'marketplace' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
        'pos'         => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>',
        'opd'         => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'bumd'        => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
        'inventory'   => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
        'master'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'stock-in'    => '<polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>',
        'mutation'    => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'adjustment'  => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'import'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'jff'         => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/>',
        'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    ];
    $path = $icons[$type] ?? $icons['summary'];
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
?>
<nav class="shell-sidebar" id="sidebar">
    <div class="sidebar-section-label">Overview</div>
    <a href="<?= BASE_URL ?>/summary" class="nav-item <?= $currentPage == 'summary' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('summary') ?></span>
        <span>Executive Summary</span>
    </a>
    <a href="<?= BASE_URL ?>/sales-channel" class="nav-item <?= $currentPage == 'sales-channel' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('channel') ?></span>
        <span>Penjualan per Channel</span>
    </a>
    <a href="<?= BASE_URL ?>/product-analysis" class="nav-item <?= $currentPage == 'product-analysis' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('product') ?></span>
        <span>Analisis Produk</span>
    </a>
    <a href="<?= BASE_URL ?>/customer-analysis" class="nav-item <?= $currentPage == 'customer-analysis' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('customer') ?></span>
        <span>Analisis Customer</span>
    </a>

    <div class="sidebar-section-label">Analytics</div>
    <a href="<?= BASE_URL ?>/sales-trend" class="nav-item <?= $currentPage == 'sales-trend' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('trend') ?></span>
        <span>Tren Penjualan</span>
    </a>
    <a href="<?= BASE_URL ?>/finance" class="nav-item <?= $currentPage == 'finance' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('finance') ?></span>
        <span>Keuangan</span>
    </a>

    <?php if ($userRole !== 'user'): ?>
    <div class="sidebar-section-label">Penjualan</div>
    <a href="<?= BASE_URL ?>/marketplace" class="nav-item <?= $currentPage == 'marketplace' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('marketplace') ?></span>
        <span>Marketplace</span>
    </a>
    <a href="<?= BASE_URL ?>/pos" class="nav-item <?= $currentPage == 'pos' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('pos') ?></span>
        <span>POS</span>
    </a>
    <a href="<?= BASE_URL ?>/sales-opd" class="nav-item <?= $currentPage == 'sales-opd' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('opd') ?></span>
        <span>OPD</span>
    </a>
    <a href="<?= BASE_URL ?>/sales-bumd" class="nav-item <?= $currentPage == 'sales-bumd' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('bumd') ?></span>
        <span>BUMD</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">Persediaan</div>
    <a href="<?= BASE_URL ?>/inventory" class="nav-item <?= $currentPage == 'inventory' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('inventory') ?></span>
        <span>Dashboard Persediaan</span>
    </a>
    <?php if ($userRole !== 'user'): ?>
    <a href="<?= BASE_URL ?>/products" class="nav-item <?= $currentPage == 'products' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('master') ?></span>
        <span>Master Produk</span>
    </a>
    <a href="<?= BASE_URL ?>/stock-in" class="nav-item <?= $currentPage == 'stock-in' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('stock-in') ?></span>
        <span>Pemasukan Stok</span>
    </a>
    <a href="<?= BASE_URL ?>/stock-mutations" class="nav-item <?= $currentPage == 'stock-mutations' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('mutation') ?></span>
        <span>Riwayat Mutasi Stok</span>
    </a>
    <a href="<?= BASE_URL ?>/stock-adjustments" class="nav-item <?= $currentPage == 'stock-adjustments' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('adjustment') ?></span>
        <span>Penyesuaian Stok</span>
    </a>

    <div class="sidebar-section-label">Master Data</div>
    <a href="<?= BASE_URL ?>/opd-customers" class="nav-item <?= $currentPage == 'opd-customers' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('opd') ?></span>
        <span>OPD</span>
    </a>
    <a href="<?= BASE_URL ?>/bumd-customers" class="nav-item <?= $currentPage == 'bumd-customers' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('bumd') ?></span>
        <span>BUMD</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">JFF Merchandise</div>
    <?php if ($userRole !== 'user'): ?>
    <a href="<?= BASE_URL ?>/merchandise-jff-import" class="nav-item <?= $currentPage == 'merchandise-jff-import' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('import') ?></span>
        <span>Pemasukan Data Excel</span>
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/merchandise-jff-dashboard" class="nav-item <?= $currentPage == 'merchandise-jff-dashboard' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('jff') ?></span>
        <span>Dashboard Rekap JFF</span>
    </a>

    <?php if ($userRole === 'superadmin'): ?>
    <div class="sidebar-section-label">Admin</div>
    <a href="<?= BASE_URL ?>/admin/users" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
        <span class="nav-icon"><?= navIcon('user') ?></span>
        <span>User</span>
    </a>
    <?php endif; ?>
</nav>
