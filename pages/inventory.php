<div class="section-headline"><span class="emoji">📦</span> Dashboard Persediaan</div>
<div class="kpi-grid" id="invStats">
    <div class="kpi-card">
        <div class="kpi-label">Total Produk</div>
        <div class="kpi-value" id="statTotalProducts">-</div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">Total Stok</div>
        <div class="kpi-value" id="statTotalStock">-</div>
    </div>
    <div class="kpi-card" style="border-top-color:var(--warning);">
        <div class="kpi-label">Stok Menipis (10-20)</div>
        <div class="kpi-value" id="statCriticalStock">-</div>
    </div>
    <div class="kpi-card" style="border-top-color:var(--danger);">
        <div class="kpi-label">Stok Kritis (&lt; 10)</div>
        <div class="kpi-value" id="statLowStock">-</div>
    </div>
</div>

<div class="card" style="border-top:3px solid var(--warning); margin-top: 24px;">
    <div class="card-header">
        <div class="card-title"><span class="emoji">🔔</span> Stok Terbaru</div>
        <input type="text" id="stockSearch" placeholder="Cari produk..."
            style="height:32px;padding:0 10px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.8125rem;font-family:var(--font);outline:none;width:180px;max-width:100%;">
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="stockTableBody"></tbody>
        </table>
    </div>
</div>

<script>
    let pollingTimer = null;

    function fmtStock(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

    function getStatusBadge(stock) {
        if (stock <= 0) return '<span class="badge badge-neutral">Habis</span>';
        if (stock < 10) return '<span class="badge badge-blue" style="background:rgba(251,44,54,0.10);color:var(--danger);">Kritis</span>';
        if (stock <= 20) return '<span style="display:inline-block;padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:rgba(237,178,0,0.12);color:var(--warning);">Menipis</span>';
        return '<span class="badge badge-green">Aman</span>';
    }

    function updateKPI(id, value) {
        const el = document.getElementById(id);
        if (el && el.textContent !== fmtStock(value)) {
            el.textContent = fmtStock(value);
        }
    }

    function loadInvStats() {
        fetch(API_BASE + '/inventory_dashboard.php?type=stats', { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                updateKPI('statTotalProducts', data.totalProducts);
                updateKPI('statTotalStock', data.totalStock);
                updateKPI('statLowStock', data.lowStock);
                updateKPI('statCriticalStock', data.criticalStock || 0);
            })
            .catch(() => { });
    }

    function loadStockTable(search) {
        let url = API_BASE + '/inventory_dashboard.php?type=recent';
        if (search) url += '&search=' + encodeURIComponent(search);
        fetch(url, { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('stockTableBody');
                if (!data.items || data.items.length === 0) {
                    const html = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                    if (tbody.innerHTML !== html) tbody.innerHTML = html;
                    return;
                }
                const html = data.items.map(p =>
                    `<tr>
                    <td><strong>${p.product_code}</strong></td>
                    <td>${p.product_name}</td>
                    <td>${p.category}</td>
                    <td><strong>${fmtStock(p.current_stock)}</strong></td>
                    <td>${getStatusBadge(parseInt(p.current_stock))}</td>
                </tr>`
                ).join('');
                if (tbody.innerHTML !== html) tbody.innerHTML = html;
            })
            .catch(() => { });
    }

    function pollDashboard() {
        loadInvStats();
        loadStockTable();
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadInvStats();
        loadStockTable();

        const searchInput = document.getElementById('stockSearch');
        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => loadStockTable(this.value), 300);
            });
        }

        // Auto-refresh setiap 5 detik
        pollingTimer = setInterval(pollDashboard, 5000);
    });

    window.loadInvStats = loadInvStats;
    window.loadStockTable = loadStockTable;
</script>