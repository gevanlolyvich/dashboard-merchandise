<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">👤</span> Manajemen User</div>
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

<div class="modal-overlay" id="userModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title" id="userModalTitle">Tambah User</div>
            <button class="modal-close" onclick="closeUserModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Username <span style="color:var(--danger)">*</span></label>
                <input type="text" id="umUsername" class="modal-select" placeholder="username" autocomplete="off">
            </div>
            <div class="login-field">
                <label>Password <span style="font-weight:400;text-transform:none;color:var(--on-surface-muted)">(kosongi jika tidak diubah)</span></label>
                <input type="password" id="umPassword" class="modal-select" placeholder="password">
            </div>
            <div class="login-field">
                <label>Nama Tampilan <span style="color:var(--danger)">*</span></label>
                <input type="text" id="umDisplayName" class="modal-select" placeholder="Nama Lengkap" autocomplete="off">
            </div>
            <div class="login-field" id="umRoleField">
                <label>Role <span style="color:var(--danger)">*</span></label>
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
