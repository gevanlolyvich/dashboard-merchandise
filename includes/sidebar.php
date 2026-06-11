<nav class="shell-sidebar" id="sidebar">
    <div class="sidebar-section-label">Overview</div>
    <a href="<?= BASE_URL ?>/summary" class="nav-item <?= $currentPage == 'summary' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> <span>Executive Summary</span>
    </a>
    <a href="<?= BASE_URL ?>/sales-channel" class="nav-item <?= $currentPage == 'sales-channel' ? 'active' : '' ?>">
        <span class="nav-icon">🛒</span> <span>Penjualan per Channel</span>
    </a>
    <a href="<?= BASE_URL ?>/product-analysis" class="nav-item <?= $currentPage == 'product-analysis' ? 'active' : '' ?>">
        <span class="nav-icon">🏷️</span> <span>Analisis Produk</span>
    </a>
    <a href="<?= BASE_URL ?>/customer-analysis" class="nav-item <?= $currentPage == 'customer-analysis' ? 'active' : '' ?>">
        <span class="nav-icon">👥</span> <span>Analisis Customer</span>
    </a>

    <div class="sidebar-section-label">Analytics</div>
    <a href="<?= BASE_URL ?>/sales-trend" class="nav-item <?= $currentPage == 'sales-trend' ? 'active' : '' ?>">
        <span class="nav-icon">📈</span> <span>Tren Penjualan</span>
    </a>
    <a href="<?= BASE_URL ?>/finance" class="nav-item <?= $currentPage == 'finance' ? 'active' : '' ?>">
        <span class="nav-icon">💵</span> <span>Keuangan</span>
    </a>

    <?php if ($userRole !== 'user'): ?>
    <div class="sidebar-section-label">Penjualan</div>
    <a href="<?= BASE_URL ?>/marketplace" class="nav-item <?= $currentPage == 'marketplace' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🏪</span> <span>Marketplace</span>
    </a>
    <a href="<?= BASE_URL ?>/pos" class="nav-item <?= $currentPage == 'pos' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🛍️</span> <span>POS</span>
    </a>
    <a href="<?= BASE_URL ?>/sales-opd" class="nav-item <?= $currentPage == 'sales-opd' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🏛️</span> <span>OPD</span>
    </a>
    <a href="<?= BASE_URL ?>/sales-bumd" class="nav-item <?= $currentPage == 'sales-bumd' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🏢</span> <span>BUMD</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">Persediaan</div>
    <a href="<?= BASE_URL ?>/inventory" class="nav-item <?= $currentPage == 'inventory' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">📦</span> <span>Dashboard Persediaan</span>
    </a>
    <?php if ($userRole !== 'user'): ?>
    <a href="<?= BASE_URL ?>/products" class="nav-item <?= $currentPage == 'products' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🏷️</span> <span>Master Produk</span>
    </a>
    <a href="<?= BASE_URL ?>/stock-in" class="nav-item <?= $currentPage == 'stock-in' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">📥</span> <span>Pemasukan Stok</span>
    </a>
    <a href="<?= BASE_URL ?>/stock-mutations" class="nav-item <?= $currentPage == 'stock-mutations' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🔄</span> <span>Riwayat Mutasi Stok</span>
    </a>
    <a href="<?= BASE_URL ?>/stock-adjustments" class="nav-item <?= $currentPage == 'stock-adjustments' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">⚖️</span> <span>Penyesuaian Stok</span>
    </a>

    <div class="sidebar-section-label">Master Data</div>
    <a href="<?= BASE_URL ?>/opd-customers" class="nav-item <?= $currentPage == 'opd-customers' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🏛️</span> <span>OPD</span>
    </a>
    <a href="<?= BASE_URL ?>/bumd-customers" class="nav-item <?= $currentPage == 'bumd-customers' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">🏢</span> <span>BUMD</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">JFF Merchandise</div>
    <?php if ($userRole !== 'user'): ?>
    <a href="<?= BASE_URL ?>/merchandise-jff-import" class="nav-item <?= $currentPage == 'merchandise-jff-import' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">📥</span> <span>Pemasukan Data Excel</span>
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/merchandise-jff-dashboard" class="nav-item <?= $currentPage == 'merchandise-jff-dashboard' ? 'active' : '' ?>" style="padding-left:20px;">
        <span class="nav-icon">📊</span> <span>Dashboard Rekap JFF</span>
    </a>

    <?php if ($userRole === 'superadmin'): ?>
    <div class="sidebar-section-label">Admin</div>
    <a href="<?= BASE_URL ?>/admin/users" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> <span>User</span>
    </a>
    <?php endif; ?>
</nav>
