<header class="shell-header">
    <div class="shell-left">
        <div class="brand">
            <span class="brand-accent">JXB</span>
            <span>PT Jakarta Tourisindo</span>
        </div>
    </div>
    <div class="shell-right">
        <input class="shell-search" type="text" placeholder="Cari menu, produk...">
        <div class="user-badge" id="userBadge" style="display:none">
            <span class="avatar" id="userAvatar">A</span>
            <div>
                <div id="userName" style="font-size:0.8125rem;font-weight:600;line-height:1.2;">
                    <?= htmlspecialchars($displayName ?: 'User') ?></div>
                <div class="role-tag" id="userRole">
                    <?php if ($userRole === 'superadmin'): ?>
                        Superadmin
                    <?php elseif ($userRole === 'admin'): ?>
                        Admin
                    <?php elseif ($userRole === 'user'): ?>
                        User
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <button class="shell-btn" onclick="toggleDark()" title="Toggle theme">🌙</button>
        <button class="btn-logout" id="logoutBtn" onclick="doLogout()" style="display:none">Keluar</button>
        <span class="shell-date" id="headerDate"></span>
    </div>
</header>