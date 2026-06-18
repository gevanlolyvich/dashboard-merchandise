<div class="page-header" style="display:none"></div>
<div class="section-headline"><span class="emoji">📊</span> Marketplace Dashboard</div>
<div class="page-sub" style="margin-bottom:24px">Executive Monitoring — Marketplace &amp; Order Health Overview</div>

<div id="md-dashboard">
    <div id="md-error" class="md-error" style="display:none">
        <div class="md-error-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
        </div>
        <div class="md-error-title">Gagal memuat data marketplace</div>
        <div class="md-error-desc">Silakan coba lagi atau hubungi tim teknis.</div>
        <button class="md-btn md-btn-retry" onclick="loadDashboard()">Refresh Data</button>
    </div>

    <!-- ROW 1: KPI Cards -->
    <div class="md-kpi-grid" id="mdKpiGrid">
        <div class="md-skeleton md-skeleton-kpi"></div>
        <div class="md-skeleton md-skeleton-kpi"></div>
        <div class="md-skeleton md-skeleton-kpi"></div>
        <div class="md-skeleton md-skeleton-kpi"></div>
        <div class="md-skeleton md-skeleton-kpi"></div>
        <div class="md-skeleton md-skeleton-kpi"></div>
    </div>

    <!-- ROW 2: Charts -->
    <div class="md-grid-2" style="margin-top:20px">
        <div class="md-card">
            <div class="md-card-header">
                <span class="md-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                </span>
                <span>Distribusi Status Order</span>
            </div>
            <div class="md-chart-container">
                <div class="md-skeleton md-skeleton-chart"></div>
                <canvas id="mdChartOrderStatus" style="display:none"></canvas>
            </div>
        </div>
        <div class="md-card">
            <div class="md-card-header">
                <span class="md-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                </span>
                <span>Perbandingan Order Marketplace</span>
            </div>
            <div class="md-chart-container">
                <div class="md-skeleton md-skeleton-chart"></div>
                <canvas id="mdChartOrderMarketplace" style="display:none"></canvas>
            </div>
        </div>
    </div>

    <!-- ROW 3: Timeline + Top Products -->
    <div class="md-grid-2" style="margin-top:20px">
        <div class="md-card">
            <div class="md-card-header">
                <span class="md-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                </span>
                <span>Aktivitas Marketplace Terbaru</span>
            </div>
            <div class="md-timeline" id="mdTimeline">
                <div class="md-skeleton md-skeleton-line"></div>
                <div class="md-skeleton md-skeleton-line"></div>
                <div class="md-skeleton md-skeleton-line"></div>
            </div>
        </div>
        <div class="md-card">
            <div class="md-card-header">
                <span class="md-card-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                </span>
                <span>Top 10 Produk Paling Banyak Dipesan</span>
            </div>
            <div class="md-ranking" id="mdTopProducts">
                <div class="md-skeleton md-skeleton-line"></div>
                <div class="md-skeleton md-skeleton-line"></div>
                <div class="md-skeleton md-skeleton-line"></div>
            </div>
        </div>
    </div>
</div>



<script>
    const API_COUNT = 'http://172.16.0.17:3100/api/v1/ginee/orders/count';
    const API_ORDERS = 'http://172.16.0.17:3100/api/v1/ginee/orders?page=0&size=100';

    const CHANNEL_LABELS = { TIKTOK_ID: 'TikTok Shop', TOKOPEDIA_ID: 'Tokopedia', SHOPEE_ID: 'Shopee' };
    const CHANNEL_COLORS = { TIKTOK_ID: '#010101', TOKOPEDIA_ID: '#00AA5B', SHOPEE_ID: '#EE4D2D' };
    const CHANNEL_CLASSES = { TIKTOK_ID: 'tiktok', TOKOPEDIA_ID: 'tokopedia', SHOPEE_ID: 'shopee' };

    const chartInstances = {};
    function destroyChart(key) { if (chartInstances[key]) { chartInstances[key].destroy(); delete chartInstances[key]; } }
    function fmtNum(n) { return Number(n).toLocaleString('id-ID'); }
    function showEl(id, show) { const el = document.getElementById(id); if (el) el.style.display = show ? '' : 'none'; }

    function loadDashboard() {
        showEl('md-error', false);

        fetch(API_COUNT)
            .then(r => r.json())
            .then(countRes => {
                if (!countRes || !countRes.success) throw new Error('Gagal muat');
                return fetch(API_ORDERS).then(r => r.json()).then(ordersRes => {
                    if (!ordersRes || !ordersRes.success) throw new Error('Gagal muat');

                    const orders = ordersRes.data.orders || [];
                    const countData = countRes.data || {};

                    renderKPI(countData);
                    renderOrderStatusDonut(orders);
                    renderOrderMarketplaceBar(orders);
                    renderTimeline(orders);
                    renderTopProducts(orders);
                });
            })
            .catch(err => {
                console.error(err);
                showEl('md-error', true);
                document.getElementById('mdKpiGrid').innerHTML = '';
            });
    }

    function renderKPI(countData) {
        const grid = document.getElementById('mdKpiGrid');
        const items = [
            { icon: '📋', cls: 'blue', label: 'Total Orders', value: fmtNum(countData.totalOrder || 0), sub: 'Semua status' },
            { icon: '✅', cls: 'green', label: 'Valid Orders', value: fmtNum(countData.totalValidOrder || 0), sub: 'Pendapatan ' + fmtRupiah(countData.totalValidAmount || 0) },
            { icon: '💰', cls: 'green', label: 'Total Pendapatan', value: fmtRupiah(countData.totalAmount || 0), sub: fmtRupiah(countData.totalValidAmount || 0) + ' valid' },
            { icon: '❌', cls: 'red', label: 'Dibatalkan', value: fmtNum(countData.totalCancelOrder || 0), sub: 'Rp ' + fmtNum(countData.totalCancelAmount || 0) },
            { icon: '📦', cls: 'purple', label: 'Quantity Terjual', value: fmtNum(countData.totalValidQuantity || 0), sub: 'Unit valid' },
        ];
        grid.innerHTML = items.map(i => `
    <div class="md-kpi-card is-${i.cls}">
      <div class="md-kpi-top">
        <div class="md-kpi-icon is-${i.cls}">${i.icon}</div>
      </div>
      <div class="md-kpi-label">${i.label}</div>
      <div class="md-kpi-value">${i.value}</div>
      <div class="md-kpi-sub">${i.sub}</div>
    </div>
  `).join('');
    }

    function fmtRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

    function getStatusGroup(os) {
        const m = { 'PENDING': 'Pending', 'PAID': 'Paid', 'READY_TO_SHIP': 'Ready To Ship', 'SHIPPING': 'Shipped', 'DELIVERED': 'Completed', 'CANCELLED': 'Cancelled', 'RETURNED': 'Refunded' };
        return m[(os || '').toUpperCase()] || os || 'Unknown';
    }

    function renderOrderStatusDonut(orders) {
        const canvas = document.getElementById('mdChartOrderStatus');
        if (!canvas) return;
        destroyChart('orderStatus');
        const parent = canvas.parentElement;
        parent.querySelectorAll('.md-skeleton').forEach(s => s.remove());
        canvas.style.display = '';

        if (!orders || orders.length === 0) {
            parent.innerHTML = '<div class="md-unavailable">Belum ada data</div>';
            return;
        }

        const groups = {};
        orders.forEach(o => { const k = getStatusGroup(o.orderStatus); groups[k] = (groups[k] || 0) + 1; });
        const colors = { 'Pending': '#d97706', 'Paid': '#ea580c', 'Ready To Ship': '#7c3aed', 'Shipped': '#2563eb', 'Completed': '#16a34a', 'Cancelled': '#dc2626', 'Refunded': '#9ca3af' };
        const labels = Object.keys(groups);
        const data = Object.values(groups);

        chartInstances['orderStatus'] = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: labels.map(l => colors[l] || '#6b7280'), borderWidth: 2, borderColor: '#fff' }] },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#6b7280', padding: 14, font: { size: 11 }, usePointStyle: true, pointStyleWidth: 8 } },
                    tooltip: { backgroundColor: '#fff', titleColor: '#1f2937', bodyColor: '#4b5563', padding: 10, cornerRadius: 8, borderColor: '#e5e7eb', borderWidth: 1 }
                }
            }
        });
    }

    function renderOrderMarketplaceBar(orders) {
        const canvas = document.getElementById('mdChartOrderMarketplace');
        if (!canvas) return;
        destroyChart('orderMarketplace');
        const parent = canvas.parentElement;
        parent.querySelectorAll('.md-skeleton').forEach(s => s.remove());
        canvas.style.display = '';

        if (!orders || orders.length === 0) {
            parent.innerHTML = '<div class="md-unavailable">Belum ada data</div>';
            return;
        }

        const groups = {};
        orders.forEach(o => {
            const l = CHANNEL_LABELS[o.channelId] || o.channelId || 'Unknown';
            groups[l] = (groups[l] || 0) + 1;
        });
        const labels = Object.keys(groups);
        const colors = labels.map(l => {
            const e = Object.entries(CHANNEL_LABELS).find(([, v]) => v === l);
            return e ? CHANNEL_COLORS[e[0]] : '#6B7280';
        });

        chartInstances['orderMarketplace'] = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Jumlah Order', data: Object.values(groups), backgroundColor: colors, borderRadius: 6, barPercentage: 0.5 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#fff', titleColor: '#1f2937', bodyColor: '#4b5563', padding: 8, cornerRadius: 6, borderColor: '#e5e7eb', borderWidth: 1 } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6b7280', font: { size: 11 } } },
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#6b7280', font: { size: 10 }, stepSize: 1 } }
                }
            }
        });
    }

    function renderTimeline(orders) {
        const container = document.getElementById('mdTimeline');
        if (!container) return;
        container.innerHTML = '';
        if (!orders || orders.length === 0) { container.innerHTML = '<div class="md-unavailable">Belum ada aktivitas</div>'; return; }

        const recent = [...orders].sort((a, b) => ((b.externalCreateDatetime || '')).localeCompare(a.externalCreateDatetime || '')).slice(0, 10);

        recent.forEach(o => {
            const status = (o.orderStatus || '').toLowerCase();
            const chClass = CHANNEL_CLASSES[o.channelId] || '';
            const chLabel = CHANNEL_LABELS[o.channelId] || o.channelId || '—';
            const date = o.externalCreateDatetime || '';
            const time = date ? date.slice(11, 16) + ' • ' + date.slice(0, 10) : '—';
            const shortId = (o.externalOrderId || o.id || '—').slice(-8);

            const item = document.createElement('div');
            item.className = 'md-timeline-item';
            item.innerHTML = `
      <div class="md-timeline-dot ${status}"></div>
      <div class="md-timeline-content">
        <div class="md-timeline-top">
          <span class="md-timeline-order">#${shortId}</span>
          <span class="md-timeline-marketplace ${chClass}">${chLabel}</span>
        </div>
        <div class="md-timeline-customer">${o.customerName || '—'} · ${status}</div>
        <div class="md-timeline-time">${time}</div>
      </div>
    `;
            container.appendChild(item);
        });
    }

    function renderTopProducts(orders) {
        const container = document.getElementById('mdTopProducts');
        if (!container) return;
        container.innerHTML = '';
        if (!orders || orders.length === 0) { container.innerHTML = '<div class="md-unavailable">Belum ada data</div>'; return; }

        const exclude = ['CANCELLED', 'RETURNED'];
        const counts = {};
        let maxQty = 0;

        orders.forEach(o => {
            if (exclude.includes(o.orderStatus)) return;
            (o.orderItems || []).forEach(item => {
                const name = item.productName || 'Unknown';
                const qty = item.quantity || 1;
                counts[name] = (counts[name] || 0) + qty;
                if (counts[name] > maxQty) maxQty = counts[name];
            });
        });

        const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 10);
        if (sorted.length === 0) { container.innerHTML = '<div class="md-unavailable">Belum ada produk dipesan</div>'; return; }

        sorted.forEach(([name, qty], i) => {
            const posClass = i === 0 ? 'top1' : i === 1 ? 'top2' : i === 2 ? 'top3' : '';
            const item = document.createElement('div');
            item.className = 'md-ranking-item';
            item.innerHTML = `
      <div class="md-ranking-pos ${posClass}">${i + 1}</div>
      <span class="md-ranking-name" title="${name}">${name.length > 35 ? name.slice(0, 32) + '...' : name}</span>
      <div class="md-ranking-stat">
        <div class="md-ranking-bar-wrap"><div class="md-ranking-bar" style="width:${maxQty > 0 ? (qty / maxQty * 100) : 0}%"></div></div>
        <span class="md-ranking-qty">${fmtNum(qty)}</span>
      </div>
    `;
            container.appendChild(item);
        });
    }

    document.addEventListener('DOMContentLoaded', loadDashboard);
</script>