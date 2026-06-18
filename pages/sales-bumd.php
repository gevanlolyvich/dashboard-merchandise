<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">🏢</span> Penjualan BUMD</div>
        <div class="page-sub">Penjualan langsung ke perusahaan daerah</div>
    </div>
    <button class="btn-primary" onclick="showBumdSaleModal()">+ Transaksi Baru</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="bumdSaleSearch" placeholder="No. transaksi, BUMD..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;min-width:200px;">
    </div>
    <div class="filter-group">
        <label>Status</label>
        <select id="bumdSaleStatus">
            <option value="">Semua</option>
            <option value="draft">Draft</option>
            <option value="diproses">Diproses</option>
            <option value="selesai">Selesai</option>
            <option value="dibatalkan">Dibatalkan</option>
            <option value="refund">Refund</option>
        </select>
    </div>
    <button class="btn-primary" onclick="loadBumdSales(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Terapkan</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>BUMD</th>
                    <th>Tanggal</th>
                    <th>Total Item</th>
                    <th>Status</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="bumdSaleTableBody"></tbody>
        </table>
    </div>
    <div id="bumdSalePagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="bumdSaleModal" style="display:none">
    <div class="modal-card" style="max-width:700px">
        <div class="modal-header">
            <div class="modal-title" id="bumdSaleModalTitle">Transaksi Baru BUMD</div>
            <button class="modal-close" onclick="closeBumdSaleModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>BUMD <span style="color:var(--danger)">*</span></label>
                <select id="bumdSaleBumd" class="modal-select">
                    <option value="">-- Pilih BUMD --</option>
                </select>
            </div>
            <div class="login-field">
                <label>Tanggal Transaksi <span style="color:var(--danger)">*</span></label>
                <input type="date" id="bumdSaleDate" class="modal-select">
            </div>
            <div class="login-field">
                <label>Catatan</label>
                <textarea id="bumdSaleNotes" class="modal-select" placeholder="Catatan transaksi..." rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>

            <div style="border-top:1px solid var(--outline);padding-top:16px;margin-top:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div class="card-title" style="font-size:0.875rem;">Produk</div>
                    <button class="btn-primary" onclick="addBumdSaleProductRow()" style="font-size:0.75rem;height:32px;padding:0 12px;">+ Tambah Produk</button>
                </div>
                <div id="bumdSaleProducts"></div>
            </div>

            <div class="login-error" id="bumdSaleError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeBumdSaleModal()">Batal</button>
            <button class="btn-primary" onclick="saveBumdSale('draft')">Simpan Draft</button>
            <button class="btn-primary" onclick="saveBumdSale('selesai')" style="background:var(--success);">Langsung Selesai</button>
        </div>
    </div>
</div>

<script>
let currentBumdSalePage = 1;
let bumdSaleProductRowCount = 0;
let bumdProductList = [];

function loadBumdProducts() {
    return fetch(API_BASE + '/products.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            bumdProductList = data.items || [];
        });
}

function loadBumdDropdown() {
    fetch(API_BASE + '/bumd_customers.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.items) return;
            const select = document.getElementById('bumdSaleBumd');
            select.innerHTML = '<option value="">-- Pilih BUMD --</option>' +
                data.items.map(o => `<option value="${o.id}">${o.name}</option>`).join('');
        });
}

function addBumdSaleProductRow(data) {
    bumdSaleProductRowCount++;
    const id = bumdSaleProductRowCount;
    const container = document.getElementById('bumdSaleProducts');

    const opts = bumdProductList.map(p =>
        `<option value="${p.product_code}" data-name="${p.product_name}" data-stock="${p.current_stock}">${p.product_code} - ${p.product_name} (stok: ${p.current_stock})</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'product-row-entry';
    row.id = 'bumdSaleProductRow_' + id;
    row.style.cssText = 'display:flex;gap:8px;align-items:end;margin-bottom:8px;';

    row.innerHTML = `
        <div style="flex:2">
            <label style="font-size:0.75rem;color:var(--on-surface-muted);display:block;margin-bottom:2px;">Produk</label>
            <select class="modal-select" onchange="onBumdSaleProductChange(${id})" style="font-size:0.8125rem;height:36px;">
                <option value="">-- Pilih --</option>
                ${opts}
            </select>
        </div>
        <div style="flex:0.5">
            <label style="font-size:0.75rem;color:var(--on-surface-muted);display:block;margin-bottom:2px;">Qty</label>
            <input type="number" class="modal-select" placeholder="0" min="1" style="font-size:0.8125rem;height:36px;width:70px;">
        </div>
        <div style="flex:1">
            <label style="font-size:0.75rem;color:var(--on-surface-muted);display:block;margin-bottom:2px;">Harga</label>
            <input type="number" class="modal-select" placeholder="0" min="0" style="font-size:0.8125rem;height:36px;">
        </div>
        <div style="flex:0.3">
            <button class="btn-danger" onclick="removeBumdSaleProductRow(${id})" style="padding:6px 10px;font-size:0.75rem;margin-bottom:0;height:36px;">✕</button>
        </div>
    `;

    if (data) {
        const select = row.querySelector('select');
        select.value = data.product_code;
        const qtyInput = row.querySelectorAll('input[type=number]')[0];
        const priceInput = row.querySelectorAll('input[type=number]')[1];
        if (qtyInput) qtyInput.value = data.quantity;
        if (priceInput) priceInput.value = data.price || 0;
    }

    container.appendChild(row);
}

function onBumdSaleProductChange(rowId) {
    const row = document.getElementById('bumdSaleProductRow_' + rowId);
    if (!row) return;
    const select = row.querySelector('select');
    const selected = select.options[select.selectedIndex];
    if (selected && selected.dataset.name) {
        const nameInput = row.querySelectorAll('input[type=number]')[0];
        if (nameInput) nameInput.placeholder = selected.dataset.name;
    }
}

function removeBumdSaleProductRow(id) {
    const row = document.getElementById('bumdSaleProductRow_' + id);
    if (row) row.remove();
}

function showBumdSaleModal() {
    document.getElementById('bumdSaleModal').style.display = 'flex';
    document.getElementById('bumdSaleError').style.display = 'none';
    document.getElementById('bumdSaleDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('bumdSaleNotes').value = '';
    document.getElementById('bumdSaleProducts').innerHTML = '';
    bumdSaleProductRowCount = 0;
    loadBumdProducts().then(() => addBumdSaleProductRow());
}

function closeBumdSaleModal() {
    document.getElementById('bumdSaleModal').style.display = 'none';
    document.getElementById('bumdSaleError').style.display = 'none';
}

function saveBumdSale(status) {
    const bumdId = document.getElementById('bumdSaleBumd').value;
    const date = document.getElementById('bumdSaleDate').value;
    const notes = document.getElementById('bumdSaleNotes').value.trim();
    const errEl = document.getElementById('bumdSaleError');
    errEl.style.display = 'none';

    if (!bumdId) { errEl.textContent = 'BUMD wajib dipilih'; errEl.style.display = 'flex'; return; }
    if (!date) { errEl.textContent = 'Tanggal wajib diisi'; errEl.style.display = 'flex'; return; }

    const rows = document.querySelectorAll('#bumdSaleProducts .product-row-entry');
    const products = [];
    let hasError = false;

    rows.forEach(row => {
        const select = row.querySelector('select');
        const inputs = row.querySelectorAll('input[type=number]');
        const qty = parseInt(inputs[0]?.value || 0);
        const price = parseFloat(inputs[1]?.value || 0);

        if (select && select.value) {
            if (qty <= 0) { hasError = true; return; }
            const selected = select.options[select.selectedIndex];
            products.push({
                product_code: select.value,
                product_name: selected ? selected.text.split(' - ')[1] || select.value : select.value,
                quantity: qty,
                unit: 'PCS',
                price: price
            });
        }
    });

    if (hasError) { errEl.textContent = 'Jumlah produk harus lebih dari 0'; errEl.style.display = 'flex'; return; }
    if (products.length === 0) { errEl.textContent = 'Minimal satu produk wajib ditambahkan'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('bumd_id', bumdId);
    formData.append('transaction_date', date);
    formData.append('notes', notes);
    formData.append('products', JSON.stringify(products));
    formData.append('status', status);

    fetch(API_BASE + '/sales_bumd.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeBumdSaleModal();
            loadBumdSales(currentBumdSalePage);
            loadBumdProducts();
            showSuccess('Berhasil', data.message + ' (' + data.transaction_number + ')');
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function loadBumdSales(page) {
    currentBumdSalePage = page || 1;
    const search = document.getElementById('bumdSaleSearch').value;
    const status = document.getElementById('bumdSaleStatus').value;

    let url = API_BASE + '/sales_bumd.php?page=' + currentBumdSalePage;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (status) url += '&status=' + encodeURIComponent(status);

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('bumdSaleTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                document.getElementById('bumdSalePagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map(item => {
                const statusColors = { draft: 'badge-neutral', diproses: 'badge-blue', selesai: 'badge-green', dibatalkan: 'badge-orange', refund: 'badge-orange' };
                const statusClass = statusColors[item.status] || 'badge-neutral';
                return `<tr>
                    <td><strong>${item.transaction_number}</strong></td>
                    <td>${item.bumd_name || '-'}</td>
                    <td>${item.transaction_date}</td>
                    <td>${item.status === 'selesai' ? 'Stok terpotong' : item.status === 'refund' ? 'Di-refund' : 'Stok aman'}</td>
                    <td><span class="badge ${statusClass}">${item.status}</span></td>
                    <td><div class="action-cell">
                        <button class="btn-edit" onclick="viewBumdSale(${item.id})">Detail</button>
                        ${item.status === 'draft' ? `<button class="btn-primary" onclick="updateBumdSaleStatus(${item.id},'selesai')" style="font-size:0.75rem;height:30px;padding:0 10px;">Selesai</button>` : ''}
                        ${item.status === 'selesai' || item.status === 'dibatalkan' || item.status === 'refund' ? '' : `<button class="btn-danger" onclick="updateBumdSaleStatus(${item.id},'dibatalkan')" style="font-size:0.75rem;height:30px;padding:0 10px;">Batal</button>`}
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadBumdSales(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('bumdSalePagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('bumdSaleTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function viewBumdSale(id) {
    fetch(API_BASE + '/sales_bumd.php?id=' + id, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showSuccess('Gagal', data.error); return; }
            const s = data.item;
            showSuccess('Detail Transaksi: ' + s.transaction_number,
                `BUMD: ${s.bumd_name}\nTanggal: ${s.transaction_date}\nStatus: ${s.status}\n\nProduk:\n` +
                s.items.map(it => `- ${it.product_name} x ${it.quantity} @ Rp ${parseFloat(it.price).toLocaleString('id-ID')} = Rp ${parseFloat(it.total).toLocaleString('id-ID')}`).join('\n')
            );
        })
        .catch(() => showSuccess('Error', 'Gagal memuat detail'));
}

function updateBumdSaleStatus(id, status) {
    const label = status === 'selesai' ? 'menyelesaikan' : 'membatalkan';
    showConfirm('Ubah Status', `Yakin ingin ${label} transaksi ini?`, 'Ya', function() {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('id', id);
        formData.append('status', status);

        fetch(API_BASE + '/sales_bumd.php', { method: 'POST', body: formData, credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadBumdSales(currentBumdSalePage);
                loadBumdProducts();
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function deleteBumdSale(id) {
    showConfirm('Hapus Transaksi', 'Yakin ingin menghapus transaksi ini? Stok akan dikembalikan jika sudah terpotong.', 'Ya, Hapus', function() {
        fetch(API_BASE + '/sales_bumd.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadBumdSales(currentBumdSalePage);
                loadBumdProducts();
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initBumdSalePage() {
    loadBumdProducts();
    loadBumdDropdown();
    loadBumdSales(1);
    document.getElementById('bumdSaleSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadBumdSales(1);
    });
}

if (document.getElementById('bumdSaleTableBody')) {
    initBumdSalePage();
}
</script>
<style>
.product-row-entry select { height: 36px; font-size: 0.8125rem; width: 100%; }
.product-row-entry input { height: 36px; font-size: 0.8125rem; width: 100%; }
</style>
