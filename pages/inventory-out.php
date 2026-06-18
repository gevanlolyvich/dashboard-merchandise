<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">⚖️</span> Penyesuaian Stok</div>
        <div class="page-sub">Penyesuaian stok karena rusak, hilang, stock opname, atau koreksi</div>
    </div>
    <button class="btn-primary" onclick="showAdjModal()">+ Tambah Penyesuaian</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="adjSearch" placeholder="Kode produk, alasan..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;min-width:200px;">
    </div>
    <div class="filter-group">
        <label>Bulan</label>
        <select id="adjFilterMonth">
            <option value="">Semua</option>
            <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
            <option value="4">Apr</option><option value="5">Mei</option><option value="6">Jun</option>
            <option value="7">Jul</option><option value="8">Agu</option><option value="9">Sep</option>
            <option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Tahun</label>
        <select id="adjFilterYear">
            <option value="">Semua</option>
            <option value="2025">2025</option>
            <option value="2026" selected>2026</option>
            <option value="2027">2027</option>
        </select>
    </div>
    <button class="btn-primary" onclick="loadAdj(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Terapkan</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Produk</th>
                    <th>Tipe</th>
                    <th>Qty</th>
                    <th>Alasan</th>
                    <th>Catatan</th>
                    <th>Tgl Penyesuaian</th>
                    <th>Dibuat Oleh</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="adjTableBody"></tbody>
        </table>
    </div>
    <div id="adjPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="adjModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">Tambah Penyesuaian Stok</div>
            <button class="modal-close" onclick="closeAdjModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Kode Produk <span style="color:var(--danger)">*</span></label>
                <select id="adjProductCode" class="modal-select">
                    <option value="">-- Pilih Produk --</option>
                </select>
            </div>
            <div class="login-field">
                <label>Tipe Penyesuaian <span style="color:var(--danger)">*</span></label>
                <select id="adjType" class="modal-select">
                    <option value="minus">Kurangi Stok (-)</option>
                    <option value="plus">Tambah Stok (+)</option>
                </select>
            </div>
            <div class="login-field">
                <label>Jumlah <span style="color:var(--danger)">*</span></label>
                <input type="number" id="adjQuantity" class="modal-select" placeholder="0" min="1">
            </div>
            <div class="login-field">
                <label>Alasan <span style="color:var(--danger)">*</span></label>
                <textarea id="adjReason" class="modal-select" placeholder="Contoh: Barang rusak, hilang, stock opname, koreksi stok" rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>
            <div class="login-field">
                <label>Catatan Tambahan</label>
                <textarea id="adjNotes" class="modal-select" placeholder="Catatan..." rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>
            <div class="login-field">
                <label>Tanggal Penyesuaian <span style="color:var(--danger)">*</span></label>
                <input type="date" id="adjDate" class="modal-select">
            </div>
            <div class="login-error" id="adjError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeAdjModal()">Batal</button>
            <button class="btn-primary" onclick="saveAdj()">Simpan</button>
        </div>
    </div>
</div>

<script>
let currentAdjPage = 1;

function showAdjModal() {
    document.getElementById('adjModal').style.display = 'flex';
    document.getElementById('adjError').style.display = 'none';
    document.getElementById('adjType').value = 'minus';
    document.getElementById('adjQuantity').value = '';
    document.getElementById('adjReason').value = '';
    document.getElementById('adjNotes').value = '';
    document.getElementById('adjDate').value = new Date().toISOString().split('T')[0];
}

function closeAdjModal() {
    document.getElementById('adjModal').style.display = 'none';
    document.getElementById('adjError').style.display = 'none';
}

function loadAdjProducts() {
    fetch(API_BASE + '/products.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.items) return;
            const select = document.getElementById('adjProductCode');
            select.innerHTML = '<option value="">-- Pilih Produk --</option>' +
                data.items.map(p =>
                    `<option value="${p.product_code}">${p.product_code} - ${p.product_name} (stok: ${p.current_stock})</option>`
                ).join('');
        })
        .catch(() => {});
}

function saveAdj() {
    const productCode = document.getElementById('adjProductCode').value;
    const adjType = document.getElementById('adjType').value;
    const quantity = parseInt(document.getElementById('adjQuantity').value);
    const reason = document.getElementById('adjReason').value.trim();
    const notes = document.getElementById('adjNotes').value.trim();
    const date = document.getElementById('adjDate').value;
    const errEl = document.getElementById('adjError');
    errEl.style.display = 'none';

    if (!productCode) { errEl.textContent = 'Produk wajib dipilih'; errEl.style.display = 'flex'; return; }
    if (!quantity || quantity <= 0) { errEl.textContent = 'Jumlah harus lebih dari 0'; errEl.style.display = 'flex'; return; }
    if (!reason) { errEl.textContent = 'Alasan penyesuaian wajib diisi'; errEl.style.display = 'flex'; return; }
    if (!date) { errEl.textContent = 'Tanggal wajib diisi'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('product_code', productCode);
    formData.append('adjustment_type', adjType);
    formData.append('quantity', quantity);
    formData.append('reason', reason);
    formData.append('notes', notes);
    formData.append('adjusted_date', date);

    fetch(API_BASE + '/inventory_out.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeAdjModal();
            loadAdj(currentAdjPage);
            loadAdjProducts();
            showSuccess('Berhasil', data.message);
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function loadAdj(page) {
    currentAdjPage = page || 1;
    const search = document.getElementById('adjSearch').value;
    const month = document.getElementById('adjFilterMonth').value;
    const year = document.getElementById('adjFilterYear').value;

    let url = API_BASE + '/inventory_out.php?page=' + currentAdjPage;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (month) url += '&month=' + month;
    if (year) url += '&year=' + year;

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('adjTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                document.getElementById('adjPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map((item, i) => {
                const num = (data.page - 1) * 10 + i + 1;
                const isPlus = item.adjustment_type === 'plus';
                return `<tr>
                    <td>${num}</td>
                    <td><strong>${item.product_code}</strong></td>
                    <td><span class="badge ${isPlus ? 'badge-green' : 'badge-blue'}" style="background:${isPlus ? 'rgba(0,199,88,0.10)' : 'rgba(251,44,54,0.10)'};color:${isPlus ? 'var(--success)' : 'var(--danger)'};">${isPlus ? 'Tambah' : 'Kurangi'}</span></td>
                    <td><strong>${parseInt(item.quantity).toLocaleString('id-ID')}</strong></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.reason}</td>
                    <td style="font-size:0.8125rem">${item.notes || '-'}</td>
                    <td>${item.adjusted_date}</td>
                    <td style="font-size:0.8125rem">${item.created_by_name || '-'}</td>
                    <td><div class="action-cell">
                        <button class="btn-danger" onclick="deleteAdj(${item.id})">Hapus</button>
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadAdj(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('adjPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('adjTableBody').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function deleteAdj(id) {
    showConfirm('Hapus Penyesuaian Stok', 'Yakin ingin menghapus penyesuaian stok ini? Stok akan dikembalikan ke posisi sebelumnya.', 'Ya, Hapus', function() {
        fetch(API_BASE + '/inventory_out.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadAdj(currentAdjPage);
                loadAdjProducts();
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initAdjPage() {
    loadAdjProducts();
    loadAdj(1);
    document.getElementById('adjSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadAdj(1);
    });
}

if (document.getElementById('adjTableBody')) {
    initAdjPage();
}
</script>
