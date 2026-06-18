<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">↩️</span> Refund Produk</div>
        <div class="page-sub">Kembalikan stok produk dari transaksi OPD / BUMD yang sudah selesai</div>
    </div>
    <button class="btn-primary" onclick="showRefundModal()">+ Refund Baru</button>
</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="refundSearch" placeholder="No. transaksi, customer..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;min-width:200px;">
    </div>
    <div class="filter-group">
        <label>Tipe</label>
        <select id="refundFilterType">
            <option value="">Semua</option>
            <option value="opd">OPD</option>
            <option value="bumd">BUMD</option>
        </select>
    </div>
    <button class="btn-primary" onclick="loadRefunds(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Terapkan</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tgl Refund</th>
                    <th>No. Transaksi</th>
                    <th>Tipe</th>
                    <th>Customer</th>
                    <th>Catatan</th>
                    <th>Dibuat Oleh</th>
                    <th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody id="refundTableBody"></tbody>
        </table>
    </div>
    <div id="refundPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<div class="modal-overlay" id="refundModal" style="display:none">
    <div class="modal-card" style="max-width:700px">
        <div class="modal-header">
            <div class="modal-title">Refund Baru</div>
            <button class="modal-close" onclick="closeRefundModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="login-field">
                <label>Tipe Transaksi <span style="color:var(--danger)">*</span></label>
                <select id="refundTxType" class="modal-select" onchange="onRefundTxTypeChange()">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="opd">OPD</option>
                    <option value="bumd">BUMD</option>
                </select>
            </div>
            <div class="login-field">
                <label>No. Transaksi <span style="color:var(--danger)">*</span></label>
                <select id="refundTxNumber" class="modal-select" onchange="onRefundTxNumberChange()">
                    <option value="">-- Pilih Transaksi --</option>
                </select>
            </div>
            <div class="login-field">
                <label>Tanggal Refund <span style="color:var(--danger)">*</span></label>
                <input type="date" id="refundDate" class="modal-select">
            </div>
            <div class="login-field">
                <label>Catatan</label>
                <textarea id="refundNotes" class="modal-select" placeholder="Alasan refund..." rows="2" style="resize:vertical;padding:8px 12px;height:auto;min-height:44px;font-family:var(--font);"></textarea>
            </div>

            <div id="refundProductsSection" style="border-top:1px solid var(--outline);padding-top:16px;margin-top:12px;display:none;">
                <div class="card-title" style="font-size:0.875rem;margin-bottom:12px;">Produk yang akan di-refund</div>
                <div id="refundProductsList"></div>
            </div>

            <div class="login-error" id="refundError" style="display:none;align-items:center;gap:6px;padding:10px 12px;background:rgba(251,44,54,0.06);border-radius:8px;font-size:0.8125rem;color:var(--danger);margin-bottom:14px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeRefundModal()">Batal</button>
            <button class="btn-primary" onclick="saveRefund()" style="background:var(--warning);color:#000;">Proses Refund</button>
        </div>
    </div>
</div>

<script>
let currentRefundPage = 1;
let refundSelectedTxId = 0;
let refundSelectedTxType = '';
let refundOriginalItems = [];

function loadRefunds(page) {
    currentRefundPage = page || 1;
    const search = document.getElementById('refundSearch').value;
    const txType = document.getElementById('refundFilterType').value;

    let url = API_BASE + '/refund.php?page=' + currentRefundPage;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (txType) url += '&tx_type=' + encodeURIComponent(txType);

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('refundTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data refund</td></tr>';
                document.getElementById('refundPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map((item, i) => {
                const num = (data.page - 1) * 20 + i + 1;
                const typeBadge = item.transaction_type === 'opd' ? 'badge-blue' : 'badge-green';
                return `<tr>
                    <td>${num}</td>
                    <td>${item.refund_date}</td>
                    <td><strong>${item.transaction_number}</strong></td>
                    <td><span class="badge ${typeBadge}">${item.transaction_type.toUpperCase()}</span></td>
                    <td>${item.customer_name || '-'}</td>
                    <td style="font-size:0.8125rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.notes || '-'}</td>
                    <td style="font-size:0.8125rem">${item.created_by_name || '-'}</td>
                    <td><div class="action-cell">
                        <button class="btn-danger" onclick="deleteRefund(${item.id})">Hapus</button>
                    </div></td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadRefunds(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('refundPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('refundTableBody').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function showRefundModal() {
    document.getElementById('refundModal').style.display = 'flex';
    document.getElementById('refundError').style.display = 'none';
    document.getElementById('refundDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('refundNotes').value = '';
    document.getElementById('refundTxType').value = '';
    document.getElementById('refundTxNumber').innerHTML = '<option value="">-- Pilih Transaksi --</option>';
    document.getElementById('refundProductsSection').style.display = 'none';
    document.getElementById('refundProductsList').innerHTML = '';
    refundSelectedTxId = 0;
    refundSelectedTxType = '';
    refundOriginalItems = [];
}

function closeRefundModal() {
    document.getElementById('refundModal').style.display = 'none';
    document.getElementById('refundError').style.display = 'none';
}

function onRefundTxTypeChange() {
    const type = document.getElementById('refundTxType').value;
    const select = document.getElementById('refundTxNumber');
    select.innerHTML = '<option value="">-- Pilih Transaksi --</option>';
    document.getElementById('refundProductsSection').style.display = 'none';
    document.getElementById('refundProductsList').innerHTML = '';
    refundSelectedTxId = 0;
    refundOriginalItems = [];

    if (!type) return;

    const table = type === 'opd' ? 'sales_opd' : 'sales_bumd';
    const nameField = type === 'opd' ? 'opd_id' : 'bumd_id';
    const nameTable = type === 'opd' ? 'opd_customers' : 'bumd_customers';

    fetch(API_BASE + '/sales_' + type + '.php?page=1&limit=999&status=selesai', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.items) return;
            select.innerHTML = '<option value="">-- Pilih Transaksi --</option>' +
                data.items.map(t =>
                    `<option value="${t.id}">${t.transaction_number} - ${t[type === 'opd' ? 'opd_name' : 'bumd_name'] || '-'} (${t.transaction_date})</option>`
                ).join('');
        })
        .catch(() => {});
}

function onRefundTxNumberChange() {
    const select = document.getElementById('refundTxNumber');
    const txId = parseInt(select.value);
    const type = document.getElementById('refundTxType').value;

    document.getElementById('refundProductsSection').style.display = 'none';
    document.getElementById('refundProductsList').innerHTML = '';
    refundOriginalItems = [];

    if (!txId || !type) return;

    fetch(API_BASE + '/refund.php?type=transaction&tx_type=' + type + '&tx_id=' + txId, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { showSuccess('Gagal', data.error); return; }
            refundSelectedTxId = txId;
            refundSelectedTxType = type;
            refundOriginalItems = data.items || [];

            const list = document.getElementById('refundProductsList');
            list.innerHTML = data.items.map((item, i) => {
                const currentStock = parseInt(item.current_stock || 0);
                return `<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;padding:8px 12px;background:var(--surface-elevated);border-radius:8px;">
                    <div style="flex:2">
                        <strong>${item.product_code}</strong><br>
                        <span style="font-size:0.75rem;color:var(--on-surface-muted)">${item.product_name}</span>
                    </div>
                    <div style="flex:0.5;text-align:center;font-size:0.8125rem;color:var(--on-surface-muted)">
                        Stok: ${currentStock.toLocaleString('id-ID')}
                    </div>
                    <div style="flex:0.5;text-align:center">
                        <span style="font-size:0.75rem;color:var(--on-surface-muted)">Dibeli</span><br>
                        <strong>${parseInt(item.quantity).toLocaleString('id-ID')}</strong>
                    </div>
                    <div style="flex:0.5">
                        <span style="font-size:0.75rem;color:var(--on-surface-muted)">Refund</span>
                        <input type="number" class="refund-qty" data-code="${item.product_code}" value="${parseInt(item.quantity)}" min="1" max="${parseInt(item.quantity)}" style="width:70px;height:32px;padding:0 8px;border-radius:6px;border:1px solid var(--outline);background:var(--surface);color:var(--on-surface);font-size:0.8125rem;font-family:var(--font);">
                    </div>
                </div>`;
            }).join('');
            document.getElementById('refundProductsSection').style.display = 'block';
        })
        .catch(() => showSuccess('Error', 'Gagal memuat detail transaksi'));
}

function saveRefund() {
    const txType = document.getElementById('refundTxType').value;
    const txId = refundSelectedTxId;
    const date = document.getElementById('refundDate').value;
    const notes = document.getElementById('refundNotes').value.trim();
    const errEl = document.getElementById('refundError');
    errEl.style.display = 'none';

    if (!txType) { errEl.textContent = 'Tipe transaksi wajib dipilih'; errEl.style.display = 'flex'; return; }
    if (!txId) { errEl.textContent = 'No. transaksi wajib dipilih'; errEl.style.display = 'flex'; return; }
    if (!date) { errEl.textContent = 'Tanggal refund wajib diisi'; errEl.style.display = 'flex'; return; }

    const qtyInputs = document.querySelectorAll('.refund-qty');
    const products = [];
    qtyInputs.forEach(input => {
        const qty = parseInt(input.value);
        if (qty > 0) {
            products.push({ product_code: input.dataset.code, quantity: qty });
        }
    });

    if (products.length === 0) { errEl.textContent = 'Minimal satu produk harus di-refund'; errEl.style.display = 'flex'; return; }

    const formData = new FormData();
    formData.append('transaction_type', txType);
    formData.append('transaction_id', txId);
    formData.append('refund_date', date);
    formData.append('notes', notes);
    formData.append('products', JSON.stringify(products));

    fetch(API_BASE + '/refund.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { errEl.textContent = data.error; errEl.style.display = 'flex'; return; }
            closeRefundModal();
            loadRefunds(currentRefundPage);
            showSuccess('Berhasil', data.message);
        })
        .catch(() => { errEl.textContent = 'Gagal terhubung ke server'; errEl.style.display = 'flex'; });
}

function deleteRefund(id) {
    showConfirm('Hapus Refund', 'Yakin ingin menghapus refund ini? Stok akan dikembalikan ke posisi sebelum refund.', 'Ya, Hapus', function() {
        fetch(API_BASE + '/refund.php?id=' + id, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadRefunds(currentRefundPage);
                showSuccess('Berhasil', data.message);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function initRefundPage() {
    loadRefunds(1);
    document.getElementById('refundSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadRefunds(1);
    });
}

if (document.getElementById('refundTableBody')) {
    initRefundPage();
}
</script>
