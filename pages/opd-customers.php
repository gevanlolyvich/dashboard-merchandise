<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">🏛️</span> Master OPD</div>
        <div class="page-sub">Data instansi pemerintah daerah</div>
    </div>
    <button class="btn-primary" onclick="showOpdModal()">+ Tambah OPD</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="opdSearch" placeholder="Nama, PIC, telepon..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;min-width:200px;">
    </div>
    <button class="btn-primary" onclick="loadOpd(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Cari</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama OPD</th>
                    <th>Alamat</th>
                    <th>PIC</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="opdTableBody"></tbody>
        </table>
    </div>
    <div id="opdPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="opdModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title" id="opdModalTitle">Tambah OPD</div>
            <button class="modal-close" onclick="closeOpdModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Nama OPD <span style="color:var(--danger)">*</span></label>
                <input type="text" id="opdName" class="modal-select" placeholder="Contoh: Dinas Pendidikan">
            </div>
            <div class="login-field">
                <label>Alamat</label>
                <textarea id="opdAddress" class="modal-select" placeholder="Alamat lengkap" rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>
            <div class="login-field">
                <label>PIC</label>
                <input type="text" id="opdPic" class="modal-select" placeholder="Nama PIC">
            </div>
            <div class="login-field">
                <label>Nomor Telepon</label>
                <input type="text" id="opdPhone" class="modal-select" placeholder="Nomor telepon">
            </div>
            <div class="login-field">
                <label>Email</label>
                <input type="email" id="opdEmail" class="modal-select" placeholder="email@opd.go.id">
            </div>
            <div class="login-error" id="opdError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeOpdModal()">Batal</button>
            <button class="btn-primary" onclick="saveOpd()">Simpan</button>
        </div>
    </div>
</div>

<script>
let editingOpdId = null;
let currentOpdPage = 1;

function showOpdModal(data) {
    const modal = document.getElementById('opdModal');
    document.getElementById('opdError').style.display = 'none';

    if (data) {
        editingOpdId = data.id;
        document.getElementById('opdModalTitle').textContent = 'Edit OPD';
        document.getElementById('opdName').value = data.name;
        document.getElementById('opdAddress').value = data.address || '';
        document.getElementById('opdPic').value = data.pic_name || '';
        document.getElementById('opdPhone').value = data.phone || '';
        document.getElementById('opdEmail').value = data.email || '';
    } else {
        editingOpdId = null;
        document.getElementById('opdModalTitle').textContent = 'Tambah OPD';
        document.getElementById('opdName').value = '';
        document.getElementById('opdAddress').value = '';
        document.getElementById('opdPic').value = '';
        document.getElementById('opdPhone').value = '';
        document.getElementById('opdEmail').value = '';
    }
    modal.style.display = 'flex';
}

function closeOpdModal() {
    document.getElementById('opdModal').style.display = 'none';
    document.getElementById('opdError').style.display = 'none';
}

function saveOpd() {
    const name = document.getElementById('opdName').value.trim();
    const address = document.getElementById('opdAddress').value.trim();
    const picName = document.getElementById('opdPic').value.trim();
    const phone = document.getElementById('opdPhone').value.trim();
    const email = document.getElementById('opdEmail').value.trim();
    const errEl = document.getElementById('opdError');
    errEl.style.display = 'none';

    if (!name) { errEl.textContent = 'Nama OPD wajib diisi'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('address', address);
    formData.append('pic_name', picName);
    formData.append('phone', phone);
    formData.append('email', email);

    if (editingOpdId) {
        formData.append('_method', 'PUT');
        formData.append('id', editingOpdId);
    }

    fetch(API_BASE + '/opd_customers.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeOpdModal();
            loadOpd(currentOpdPage);
            showSuccess('Berhasil', data.message);
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function loadOpd(page) {
    currentOpdPage = page || 1;
    const search = document.getElementById('opdSearch').value;

    let url = API_BASE + '/opd_customers.php?page=' + currentOpdPage;
    if (search) url += '&search=' + encodeURIComponent(search);

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('opdTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                document.getElementById('opdPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map((item, i) => {
                const num = (data.page - 1) * 20 + i + 1;
                return `<tr>
                    <td>${num}</td>
                    <td><strong>${item.name}</strong></td>
                    <td style="font-size:0.8125rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.address || '-'}</td>
                    <td>${item.pic_name || '-'}</td>
                    <td>${item.phone || '-'}</td>
                    <td>${item.email || '-'}</td>
                    <td><div class="action-cell">
                        <button class="btn-edit" onclick="editOpd(${item.id})">Edit</button>
                        <button class="btn-danger" onclick="deleteOpd(${item.id})">Hapus</button>
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadOpd(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('opdPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('opdTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function editOpd(id) {
    fetch(API_BASE + '/opd_customers.php?id=' + id, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showSuccess('Gagal', data.error); return; }
            showOpdModal(data.item);
        })
        .catch(() => showSuccess('Error', 'Gagal memuat data'));
}

function deleteOpd(id) {
    showConfirm('Hapus OPD', 'Yakin ingin menghapus OPD ini?', 'Ya, Hapus', function() {
        fetch(API_BASE + '/opd_customers.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadOpd(currentOpdPage);
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initOpdPage() {
    loadOpd(1);
    document.getElementById('opdSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadOpd(1);
    });
}

if (document.getElementById('opdTableBody')) {
    initOpdPage();
}
</script>
