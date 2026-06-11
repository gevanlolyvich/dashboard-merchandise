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
    <div class="kpi-card blue">
        <div class="kpi-label">Pemasukan Bulan Ini</div>
        <div class="kpi-value" id="statInThisMonth">-</div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-label">Pengeluaran Bulan Ini</div>
        <div class="kpi-value" id="statOutThisMonth">-</div>
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

<div class="grid-2">
    <div class="card" style="border-top:3px solid var(--primary);">
        <div class="card-header">
            <div class="card-title"><span class="emoji">📊</span> Barang Masuk Per Bulan</div>
        </div>
        <div class="chart-box"><canvas id="invChart"></canvas></div>
    </div>
    <div class="card" style="border-top:3px solid var(--warning);">
        <div class="card-header">
            <div class="card-title"><span class="emoji">🔔</span> Stok Terbaru</div>
            <input type="text" id="stockSearch" placeholder="Cari produk..." style="height:32px;padding:0 10px;border-radius:6px;border:1px solid var(--outline);background:var(--surface-elevated);color:var(--on-surface);font-size:0.8125rem;font-family:var(--font);outline:none;width:180px;">
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
</div>

<script>
let invChartInstance = null;

function fmtStock(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

function getStatusBadge(stock) {
    if (stock <= 0) return '<span class="badge badge-neutral">Habis</span>';
    if (stock < 10) return '<span class="badge badge-blue" style="background:rgba(251,44,54,0.10);color:var(--danger);">Kritis</span>';
    if (stock <= 20) return '<span style="display:inline-block;padding:4px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:rgba(237,178,0,0.12);color:var(--warning);">Menipis</span>';
    return '<span class="badge badge-green">Aman</span>';
}

function loadInvStats() {
    fetch(API_BASE + '/inventory_dashboard.php?type=stats', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            document.getElementById('statTotalProducts').textContent = fmtStock(data.totalProducts);
            document.getElementById('statTotalStock').textContent = fmtStock(data.totalStock);
            document.getElementById('statInThisMonth').textContent = fmtStock(data.inThisMonth);
            document.getElementById('statOutThisMonth').textContent = fmtStock(data.outThisMonth || 0);
            document.getElementById('statLowStock').textContent = fmtStock(data.lowStock);
            document.getElementById('statCriticalStock').textContent = fmtStock(data.criticalStock || 0);
        })
        .catch(() => {});
}

function loadStockTable(search) {
    let url = API_BASE + '/inventory_dashboard.php?type=recent';
    if (search) url += '&search=' + encodeURIComponent(search);
    fetch(url, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('stockTableBody');
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--on-surface-muted)">Tidak ada data</td></tr>';
                return;
            }
            tbody.innerHTML = data.items.map(p =>
                `<tr>
                    <td><strong>${p.product_code}</strong></td>
                    <td>${p.product_name}</td>
                    <td>${p.category}</td>
                    <td><strong>${fmtStock(p.current_stock)}</strong></td>
                    <td>${getStatusBadge(parseInt(p.current_stock))}</td>
                </tr>`
            ).join('');
        })
        .catch(() => {});
}

function loadInvChart() {
    fetch(API_BASE + '/inventory_dashboard.php?type=chart', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('invChart');
            if (!ctx) return;
            if (invChartInstance) invChartInstance.destroy();

            const grid = gc(), text = tc();
            const labels = data.months || [];
            const values = data.values || [];

            invChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Barang Masuk',
                        data: values,
                        backgroundColor: 'rgba(254,110,0,0.6)',
                        borderColor: '#FE6E00',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: grid }, ticks: { color: text } },
                        x: { grid: { display: false }, ticks: { color: text } }
                    }
                }
            });
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', function () {
    loadInvStats();
    loadStockTable();
    loadInvChart();

    const searchInput = document.getElementById('stockSearch');
    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => loadStockTable(this.value), 300);
        });
    }
});

window.loadInvStats = loadInvStats;
window.loadStockTable = loadStockTable;
window.loadInvChart = loadInvChart;
</script>
