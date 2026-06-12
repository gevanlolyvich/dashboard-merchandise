<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">🏷️</span> Master Produk</div>
        <div class="page-sub">Kelola master produk merchandise</div>
    </div>
    <button class="btn-primary" onclick="showProductModal()">+ Tambah Produk</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari Produk</label>
        <input type="text" id="prodSearch" placeholder="Kode, nama, kategori..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;width:220px;max-width:100%;">
    </div>
    <button class="btn-primary" onclick="loadProducts(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Cari</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Status Stok</th>
                    <th>Status</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="prodTableBody"></tbody>
        </table>
    </div>
    <div id="prodPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="prodModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title" id="prodModalTitle">Tambah Produk</div>
            <button class="modal-close" onclick="closeProductModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Kode Produk <span style="color:var(--danger)">*</span></label>
                <input type="text" id="prodCode" class="modal-select" placeholder="Contoh: PRD001">
            </div>
            <div class="login-field">
                <label>Nama Produk <span style="color:var(--danger)">*</span></label>
                <input type="text" id="prodName" class="modal-select" placeholder="Nama produk">
            </div>
            <div class="login-field">
                <label>Kategori</label>
                <input type="text" id="prodCategory" class="modal-select" placeholder="Contoh: Apparel, Drinkware">
            </div>
            <div class="login-field">
                <label>Deskripsi</label>
                <textarea id="prodDescription" class="modal-select" placeholder="Deskripsi produk..." rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>
            <div class="login-field">
                <label>Satuan</label>
                <input type="text" id="prodUnit" class="modal-select" placeholder="PCS" value="PCS">
            </div>
            <div class="login-field">
                <label>Status</label>
                <select id="prodStatus" class="modal-select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="login-error" id="prodError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeProductModal()">Batal</button>
            <button class="btn-primary" onclick="saveProduct()">Simpan</button>
        </div>
    </div>
</div>

<script>
let editingProdId = null;
let currentProdPage = 1;

function getStockBadge(stock) {
    if (stock <= 0) return '<span class="badge badge-neutral">Habis</span>';
    if (stock < 10) return '<span class="badge badge-blue" style="background:rgba(251,44,54,0.10);color:var(--danger);">Kritis</span>';
    if (stock <= 20) return '<span style="display:inline-block;padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:rgba(237,178,0,0.12);color:var(--warning);">Menipis</span>';
    return '<span class="badge badge-green">Aman</span>';
}

function showProductModal(data) {
    const modal = document.getElementById('prodModal');
    document.getElementById('prodError').style.display = 'none';

    if (data) {
        editingProdId = data.id;
        document.getElementById('prodModalTitle').textContent = 'Edit Produk';
        document.getElementById('prodCode').value = data.product_code;
        document.getElementById('prodName').value = data.product_name;
        document.getElementById('prodCategory').value = data.category || '';
        document.getElementById('prodDescription').value = data.description || '';
        document.getElementById('prodUnit').value = data.unit || 'PCS';
        document.getElementById('prodStatus').value = data.status || 'active';
    } else {
        editingProdId = null;
        document.getElementById('prodModalTitle').textContent = 'Tambah Produk';
        document.getElementById('prodCode').value = '';
        document.getElementById('prodName').value = '';
        document.getElementById('prodCategory').value = '';
        document.getElementById('prodDescription').value = '';
        document.getElementById('prodUnit').value = 'PCS';
        document.getElementById('prodStatus').value = 'active';
    }
    modal.style.display = 'flex';
}

function closeProductModal() {
    document.getElementById('prodModal').style.display = 'none';
    document.getElementById('prodError').style.display = 'none';
}

function saveProduct() {
    const code = document.getElementById('prodCode').value.trim();
    const name = document.getElementById('prodName').value.trim();
    const category = document.getElementById('prodCategory').value.trim();
    const description = document.getElementById('prodDescription').value.trim();
    const unit = document.getElementById('prodUnit').value.trim();
    const status = document.getElementById('prodStatus').value;
    const errEl = document.getElementById('prodError');
    errEl.style.display = 'none';

    if (!code) { errEl.textContent = 'Kode produk wajib diisi'; errEl.style.display = 'flex'; return; }
    if (!name) { errEl.textContent = 'Nama produk wajib diisi'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('product_code', code);
    formData.append('product_name', name);
    formData.append('category', category);
    formData.append('description', description);
    formData.append('unit', unit);
    formData.append('status', status);

    if (editingProdId) {
        formData.append('_method', 'PUT');
        formData.append('id', editingProdId);
    }

    fetch(API_BASE + '/products.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeProductModal();
            loadProducts(currentProdPage);
            showSuccess('Berhasil', data.message);
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function loadProducts(page) {
    currentProdPage = page || 1;
    const search = document.getElementById('prodSearch').value;

    let url = API_BASE + '/products.php?page=' + currentProdPage;
    if (search) url += '&search=' + encodeURIComponent(search);

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('prodTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                document.getElementById('prodPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map((item, i) => {
                const num = (data.page - 1) * 20 + i + 1;
                return `<tr>
                    <td>${num}</td>
                    <td><strong>${item.product_code}</strong></td>
                    <td>${item.product_name}</td>
                    <td>${item.category || '-'}</td>
                    <td><strong>${parseInt(item.current_stock).toLocaleString('id-ID')}</strong></td>
                    <td>${getStockBadge(parseInt(item.current_stock))}</td>
                    <td><span class="badge ${item.status === 'active' ? 'badge-green' : 'badge-neutral'}">${item.status}</span></td>
                    <td><div class="action-cell">
                        <button class="btn-edit" onclick="editProduct(${item.id})">Edit</button>
                        <button class="btn-danger" onclick="deleteProduct(${item.id})">Hapus</button>
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadProducts(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('prodPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('prodTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function editProduct(id) {
    fetch(API_BASE + '/products.php?id=' + id, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showSuccess('Gagal', data.error); return; }
            showProductModal(data.item);
        })
        .catch(() => showSuccess('Error', 'Gagal memuat data'));
}

function deleteProduct(id) {
    showConfirm('Hapus Produk', 'Yakin ingin menghapus produk ini?', 'Ya, Hapus', function() {
        fetch(API_BASE + '/products.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadProducts(currentProdPage);
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initProductsPage() {
    loadProducts(1);
    document.getElementById('prodSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadProducts(1);
    });
}

if (document.getElementById('prodTableBody')) {
    initProductsPage();
}
</script>
