<?php
require_once __DIR__ . '/../includes/auth.php';

if (!$isLoggedIn) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

if ($userRole !== 'superadmin') {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Akses Ditolak</title><link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css"></head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:32px;"><div><h1 style="font-size:2rem;margin-bottom:12px;">403</h1><p style="color:var(--on-surface-muted);margin-bottom:24px;">Anda tidak memiliki akses ke halaman ini.</p><a href="' . BASE_URL . '/summary" class="btn-primary" style="text-decoration:none;">Kembali ke Dashboard</a></div></body></html>';
    exit;
}

$pageTitle = 'Manajemen User';
?>
<!DOCTYPE html>
<html lang="id" data-logged-in="true">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Jakarta Tourisindo — <?= $pageTitle ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const API_BASE = BASE_URL + '/api';
    </script>
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

    <div class="shell-layout">

        <?php $currentPage = 'admin-users'; ?>
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="main">

            <div class="page-header">
                <div>
                    <div class="page-title"><?= $pageTitle ?></div>
                    <div class="page-sub">Kelola seluruh user yang terdaftar</div>
                </div>
                <button class="btn-primary" onclick="showUserModal()">+ Tambah User</button>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Dibuat</th>
                                <th style="text-align:center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- ===== USER MODAL ===== -->
            <div class="modal-overlay" id="userModal" style="display:none">
                <div class="modal-card">
                    <div class="modal-header">
                        <div class="modal-title" id="userModalTitle">Tambah User</div>
                        <button class="modal-close" onclick="closeUserModal()">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="login-field">
                            <label>Username</label>
                            <input type="text" id="umUsername" placeholder="username" autocomplete="off">
                        </div>
                        <div class="login-field">
                            <label>Password <span style="font-weight:400;text-transform:none;color:var(--on-surface-muted)">(kosongi jika tidak diubah)</span></label>
                            <input type="password" id="umPassword" placeholder="password">
                        </div>
                        <div class="login-field">
                            <label>Nama Tampilan</label>
                            <input type="text" id="umDisplayName" placeholder="Nama Lengkap" autocomplete="off">
                        </div>
                        <div class="login-field" id="umRoleField">
                            <label>Role</label>
                            <select id="umRole" class="modal-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="login-field" id="umSuperadminInfo" style="display:none">
                            <label>Role</label>
                            <div style="height:44px;display:flex;align-items:center;padding:0 14px;border-radius:8px;border:1.5px solid var(--outline);background:var(--surface-soft);color:var(--on-surface-muted);font-size:0.9375rem;font-weight:600;">Superadmin</div>
                        </div>
                        <div class="login-error" id="umError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-secondary" onclick="closeUserModal()">Batal</button>
                        <button class="btn-primary" onclick="saveUser()">Simpan</button>
                    </div>
                </div>
            </div>

            <!-- ===== CONFIRM MODAL ===== -->
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
                        <button class="btn-secondary" onclick="closeConfirmModal()" id="confirmCancelBtn">Batal</button>
                        <button class="btn-danger" onclick="confirmAction()" id="confirmOkBtn">Ya, Hapus</button>
                    </div>
                </div>
            </div>

            <!-- ===== SUCCESS MODAL ===== -->
            <div class="modal-overlay" id="successModal" style="display:none">
                <div class="modal-card" style="max-width:380px">
                    <div class="modal-header">
                        <div class="modal-title" id="successTitle">Berhasil</div>
                    </div>
                    <div class="modal-body">
                        <p id="successMessage" style="color:var(--on-surface-muted);font-size:0.9375rem;line-height:1.6"></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-primary" onclick="closeSuccessModal()">OK</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="../assets/js/app.js"></script>
</body>

</html>
