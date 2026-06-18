<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">📥</span> Pemasukan Stok</div>
        <div class="page-sub">Catat pemasukan stok ke gudang</div>
    </div>
    <button class="btn-primary" onclick="showStockInModal()">+ Tambah Pemasukan</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="siSearch" placeholder="Kode, produk, supplier..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;min-width:200px;">
    </div>
    <div class="filter-group">
        <label>Bulan</label>
        <select id="siFilterMonth">
            <option value="">Semua</option>
            <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
            <option value="4">Apr</option><option value="5">Mei</option><option value="6">Jun</option>
            <option value="7">Jul</option><option value="8">Agu</option><option value="9">Sep</option>
            <option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Tahun</label>
        <select id="siFilterYear">
            <option value="">Semua</option>
            <option value="2025">2025</option>
            <option value="2026" selected>2026</option>
            <option value="2027">2027</option>
        </select>
    </div>
    <button class="btn-primary" onclick="loadStockIn(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Terapkan</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Produk</th>
                    <th>Qty</th>
                    <th>Sumber</th>
                    <th>Supplier</th>
                    <th>No. Referensi</th>
                    <th>Tgl Masuk</th>
                    <th>Dibuat Oleh</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="siTableBody"></tbody>
        </table>
    </div>
    <div id="siPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="siModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title" id="siModalTitle">Tambah Pemasukan Stok</div>
            <button class="modal-close" onclick="closeStockInModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Kode Produk <span style="color:var(--danger)">*</span></label>
                <select id="siProductCode" class="modal-select">
                    <option value="">-- Pilih Produk --</option>
                </select>
            </div>
            <div class="login-field">
                <label>Jumlah (PCS) <span style="color:var(--danger)">*</span></label>
                <input type="number" id="siQuantity" class="modal-select" placeholder="0" min="1">
            </div>
            <div class="login-field">
                <label>Sumber</label>
                <input type="text" id="siSource" class="modal-select" placeholder="Contoh: Produksi, Retur" value="Produksi">
            </div>
            <div class="login-field">
                <label>Supplier</label>
                <input type="text" id="siSupplier" class="modal-select" placeholder="Nama supplier">
            </div>
            <div class="login-field">
                <label>No. Referensi</label>
                <input type="text" id="siReference" class="modal-select" placeholder="No. PO / Surat Jalan">
            </div>
            <div class="login-field">
                <label>Tanggal Masuk <span style="color:var(--danger)">*</span></label>
                <input type="date" id="siDate" class="modal-select">
            </div>
            <div class="login-field">
                <label>Catatan</label>
                <textarea id="siNotes" class="modal-select" placeholder="Catatan tambahan..." rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>
            <div class="login-error" id="siError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeStockInModal()">Batal</button>
            <button class="btn-primary" onclick="saveStockIn()">Simpan</button>
        </div>
    </div>
</div>

<script>
let currentSiPage = 1;
let siProductMap = {};

function showStockInModal() {
    document.getElementById('siModal').style.display = 'flex';
    document.getElementById('siError').style.display = 'none';
    document.getElementById('siModalTitle').textContent = 'Tambah Pemasukan Stok';
    document.getElementById('siQuantity').value = '';
    document.getElementById('siSource').value = 'Produksi';
    document.getElementById('siSupplier').value = '';
    document.getElementById('siReference').value = '';
    document.getElementById('siDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('siNotes').value = '';
}

function closeStockInModal() {
    document.getElementById('siModal').style.display = 'none';
    document.getElementById('siError').style.display = 'none';
}

function loadProductDropdown() {
    fetch(API_BASE + '/products.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.items) return;
            siProductMap = {};
            data.items.forEach(p => { siProductMap[p.product_code] = p; });
            const select = document.getElementById('siProductCode');
            select.innerHTML = '<option value="">-- Pilih Produk --</option>' +
                data.items.map(p =>
                    `<option value="${p.product_code}">${p.product_code} - ${p.product_name} (stok: ${p.current_stock})</option>`
                ).join('');
        })
        .catch(() => {});
}

function saveStockIn() {
    const productCode = document.getElementById('siProductCode').value;
    const quantity = parseInt(document.getElementById('siQuantity').value);
    const source = document.getElementById('siSource').value.trim();
    const supplier = document.getElementById('siSupplier').value.trim();
    const referenceNumber = document.getElementById('siReference').value.trim();
    const date = document.getElementById('siDate').value;
    const notes = document.getElementById('siNotes').value.trim();
    const errEl = document.getElementById('siError');
    errEl.style.display = 'none';

    if (!productCode) { errEl.textContent = 'Produk wajib dipilih'; errEl.style.display = 'flex'; return; }
    if (!quantity || quantity <= 0) { errEl.textContent = 'Jumlah harus lebih dari 0'; errEl.style.display = 'flex'; return; }
    if (!date) { errEl.textContent = 'Tanggal wajib diisi'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('product_code', productCode);
    formData.append('quantity', quantity);
    formData.append('unit', 'PCS');
    formData.append('source', source);
    formData.append('supplier', supplier);
    formData.append('reference_number', referenceNumber);
    formData.append('received_date', date);
    formData.append('notes', notes);

    fetch(API_BASE + '/inventory_in.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeStockInModal();
            loadStockIn(currentSiPage);
            loadProductDropdown();
            showSuccess('Berhasil', data.message);
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function loadStockIn(page) {
    currentSiPage = page || 1;
    const search = document.getElementById('siSearch').value;
    const month = document.getElementById('siFilterMonth').value;
    const year = document.getElementById('siFilterYear').value;

    let url = API_BASE + '/inventory_in.php?page=' + currentSiPage;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (month) url += '&month=' + month;
    if (year) url += '&year=' + year;

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('siTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                document.getElementById('siPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map((item, i) => {
                const num = (data.page - 1) * 10 + i + 1;
                return `<tr>
                    <td>${num}</td>
                    <td><strong>${item.product_code}</strong></td>
                    <td><strong>${parseInt(item.quantity).toLocaleString('id-ID')}</strong></td>
                    <td>${item.source || '-'}</td>
                    <td>${item.supplier || '-'}</td>
                    <td>${item.reference_number || '-'}</td>
                    <td>${item.received_date}</td>
                    <td style="font-size:0.8125rem">${item.created_by_name || '-'}</td>
                    <td><div class="action-cell">
                        <button class="btn-danger" onclick="deleteStockIn(${item.id})">Hapus</button>
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadStockIn(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('siPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('siTableBody').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function deleteStockIn(id) {
    showConfirm('Hapus Pemasukan Stok', 'Yakin ingin menghapus pemasukan stok ini? Stok akan dikurangi secara otomatis.', 'Ya, Hapus', function() {
        fetch(API_BASE + '/inventory_in.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadStockIn(currentSiPage);
                loadProductDropdown();
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initStockInPage() {
    loadProductDropdown();
    loadStockIn(1);
    document.getElementById('siSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadStockIn(1);
    });
}

if (document.getElementById('siTableBody')) {
    initStockInPage();
}
</script>
