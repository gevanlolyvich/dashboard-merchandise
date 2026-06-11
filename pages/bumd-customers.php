<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">🏢</span> Master BUMD</div>
        <div class="page-sub">Data perusahaan daerah</div>
    </div>
    <button class="btn-primary" onclick="showBumdModal()">+ Tambah BUMD</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="bumdSearch" placeholder="Nama, PIC, telepon..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;min-width:200px;">
    </div>
    <button class="btn-primary" onclick="loadBumd(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Cari</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama BUMD</th>
                    <th>Alamat</th>
                    <th>PIC</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="bumdTableBody"></tbody>
        </table>
    </div>
    <div id="bumdPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="bumdModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title" id="bumdModalTitle">Tambah BUMD</div>
            <button class="modal-close" onclick="closeBumdModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Nama BUMD <span style="color:var(--danger)">*</span></label>
                <input type="text" id="bumdName" class="modal-select" placeholder="Contoh: Bank DKI">
            </div>
            <div class="login-field">
                <label>Alamat</label>
                <textarea id="bumdAddress" class="modal-select" placeholder="Alamat lengkap" rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>
            <div class="login-field">
                <label>PIC</label>
                <input type="text" id="bumdPic" class="modal-select" placeholder="Nama PIC">
            </div>
            <div class="login-field">
                <label>Nomor Telepon</label>
                <input type="text" id="bumdPhone" class="modal-select" placeholder="Nomor telepon">
            </div>
            <div class="login-field">
                <label>Email</label>
                <input type="email" id="bumdEmail" class="modal-select" placeholder="email@bumd.co.id">
            </div>
            <div class="login-error" id="bumdError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeBumdModal()">Batal</button>
            <button class="btn-primary" onclick="saveBumd()">Simpan</button>
        </div>
    </div>
</div>

<script>
let editingBumdId = null;
let currentBumdPage = 1;

function showBumdModal(data) {
    const modal = document.getElementById('bumdModal');
    document.getElementById('bumdError').style.display = 'none';

    if (data) {
        editingBumdId = data.id;
        document.getElementById('bumdModalTitle').textContent = 'Edit BUMD';
        document.getElementById('bumdName').value = data.name;
        document.getElementById('bumdAddress').value = data.address || '';
        document.getElementById('bumdPic').value = data.pic_name || '';
        document.getElementById('bumdPhone').value = data.phone || '';
        document.getElementById('bumdEmail').value = data.email || '';
    } else {
        editingBumdId = null;
        document.getElementById('bumdModalTitle').textContent = 'Tambah BUMD';
        document.getElementById('bumdName').value = '';
        document.getElementById('bumdAddress').value = '';
        document.getElementById('bumdPic').value = '';
        document.getElementById('bumdPhone').value = '';
        document.getElementById('bumdEmail').value = '';
    }
    modal.style.display = 'flex';
}

function closeBumdModal() {
    document.getElementById('bumdModal').style.display = 'none';
    document.getElementById('bumdError').style.display = 'none';
}

function saveBumd() {
    const name = document.getElementById('bumdName').value.trim();
    const address = document.getElementById('bumdAddress').value.trim();
    const picName = document.getElementById('bumdPic').value.trim();
    const phone = document.getElementById('bumdPhone').value.trim();
    const email = document.getElementById('bumdEmail').value.trim();
    const errEl = document.getElementById('bumdError');
    errEl.style.display = 'none';

    if (!name) { errEl.textContent = 'Nama BUMD wajib diisi'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('address', address);
    formData.append('pic_name', picName);
    formData.append('phone', phone);
    formData.append('email', email);

    if (editingBumdId) {
        formData.append('_method', 'PUT');
        formData.append('id', editingBumdId);
    }

    fetch(API_BASE + '/bumd_customers.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeBumdModal();
            loadBumd(currentBumdPage);
            showSuccess('Berhasil', data.message);
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function loadBumd(page) {
    currentBumdPage = page || 1;
    const search = document.getElementById('bumdSearch').value;

    let url = API_BASE + '/bumd_customers.php?page=' + currentBumdPage;
    if (search) url += '&search=' + encodeURIComponent(search);

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('bumdTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                document.getElementById('bumdPagination').innerHTML = '';
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
                        <button class="btn-edit" onclick="editBumd(${item.id})">Edit</button>
                        <button class="btn-danger" onclick="deleteBumd(${item.id})">Hapus</button>
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadBumd(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('bumdPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('bumdTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function editBumd(id) {
    fetch(API_BASE + '/bumd_customers.php?id=' + id, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showSuccess('Gagal', data.error); return; }
            showBumdModal(data.item);
        })
        .catch(() => showSuccess('Error', 'Gagal memuat data'));
}

function deleteBumd(id) {
    showConfirm('Hapus BUMD', 'Yakin ingin menghapus BUMD ini?', 'Ya, Hapus', function() {
        fetch(API_BASE + '/bumd_customers.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadBumd(currentBumdPage);
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initBumdPage() {
    loadBumd(1);
    document.getElementById('bumdSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadBumd(1);
    });
}

if (document.getElementById('bumdTableBody')) {
    initBumdPage();
}
</script>
