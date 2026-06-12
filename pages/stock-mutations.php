<div class="section-headline"><span class="emoji">🔄</span> Riwayat Mutasi Stok</div>
<div class="page-sub" style="margin-bottom:16px">Seluruh pergerakan stok dari semua channel tercatat di sini</div>

<div class="filter-bar">
    <div class="filter-group">
        <label>Cari</label>
        <input type="text" id="mutSearch" placeholder="Kode produk..." style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;width:200px;max-width:100%;">
    </div>
    <div class="filter-group">
        <label>Jenis Mutasi</label>
        <select id="mutFilterType">
            <option value="">Semua</option>
            <option value="masuk">Masuk</option>
            <option value="opd">OPD</option>
            <option value="bumd">BUMD</option>
            <option value="marketplace">Marketplace</option>
            <option value="pos">POS</option>
            <option value="penyesuaian">Penyesuaian</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Dari Tanggal</label>
        <input type="date" id="mutStartDate" style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;">
    </div>
    <div class="filter-group">
        <label>Sampai Tanggal</label>
        <input type="date" id="mutEndDate" style="height:36px;padding:0 12px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;font-family:var(--font);outline:none;">
    </div>
    <button class="btn-primary" onclick="loadMutations(1)" style="height:36px;font-size:0.8125rem;align-self:end;">Terapkan</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Jenis</th>
                    <th>Qty</th>
                    <th>Referensi</th>
                    <th>Catatan</th>
                    <th>Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody id="mutTableBody"></tbody>
        </table>
    </div>
    <div id="mutPagination" style="display:flex;justify-content:center;gap:6px;padding:16px 0 0;"></div>
</div>

<script>
let currentMutPage = 1;

function loadMutations(page) {
    currentMutPage = page || 1;
    const search = document.getElementById('mutSearch').value;
    const type = document.getElementById('mutFilterType').value;
    const startDate = document.getElementById('mutStartDate').value;
    const endDate = document.getElementById('mutEndDate').value;

    let url = API_BASE + '/stock_mutations.php?page=' + currentMutPage;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (type) url += '&type=' + encodeURIComponent(type);
    if (startDate) url += '&start_date=' + startDate;
    if (endDate) url += '&end_date=' + endDate;

    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('mutTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Tidak ada data mutasi</td></tr>';
                document.getElementById('mutPagination').innerHTML = '';
                return;
            }
            tbody.innerHTML = data.items.map(item => {
                const qty = parseInt(item.quantity);
                const isPlus = qty > 0;
                const icon = item.mutation_type === 'masuk' ? '📥' : '📤';
                return `<tr>
                    <td>${item.created_at}</td>
                    <td><strong>${item.product_code}</strong></td>
                    <td>${icon} ${item.mutation_label}</td>
                    <td><strong style="color:${isPlus ? 'var(--success)' : 'var(--danger)'}">${isPlus ? '+' : ''}${qty.toLocaleString('id-ID')}</strong></td>
                    <td style="font-size:0.8125rem">${item.reference_type || '-'}</td>
                    <td style="font-size:0.8125rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.notes || '-'}</td>
                    <td style="font-size:0.8125rem">${item.created_by_name || '-'}</td>
                </tr>`;
            }).join('');

            let pag = '';
            for (let p = 1; p <= data.totalPages; p++) {
                pag += `<button class="btn-${p === data.page ? 'primary' : 'secondary'}" onclick="loadMutations(${p})" style="min-width:36px;height:36px;padding:0 10px;font-size:0.8125rem;">${p}</button>`;
            }
            document.getElementById('mutPagination').innerHTML = pag;
        })
        .catch(() => {
            document.getElementById('mutTableBody').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function initMutPage() {
    loadMutations(1);
    document.getElementById('mutSearch').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') loadMutations(1);
    });
}

if (document.getElementById('mutTableBody')) {
    initMutPage();
}
</script>
