<div class="section-headline"><span class="emoji">👥</span> Analisis Customer</div>
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="emoji">🏛️</span> Ranking OPD</div>
        </div>
        <div id="opdRanking"></div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="emoji">🏢</span> Ranking BUMD</div>
        </div>
        <div id="bumdRanking"></div>
    </div>
</div>
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="emoji">📈</span> Omzet per OPD</div>
        </div>
        <div class="chart-box-sm"><canvas id="opdChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="emoji">📈</span> Omzet per BUMD</div>
        </div>
        <div class="chart-box-sm"><canvas id="bumdChart"></canvas></div>
    </div>
</div>

<script>
function fmtRp(amount) {
    if (!amount) return '';
    if (amount >= 1000000) return (amount / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
    if (amount >= 1000) return (amount / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

function loadOpdRanking() {
    const el = document.getElementById('opdRanking');
    if (!el) return;
    let list = [];

    fetch(API_BASE + '/sales_opd.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                const grouped = {};
                data.items.forEach(s => {
                    if (s.status === 'selesai') {
                        if (!grouped[s.opd_name]) grouped[s.opd_name] = { name: s.opd_name, count: 0, total: 0 };
                        grouped[s.opd_name].count++;
                    }
                });
                Object.values(grouped).forEach(o => list.push({ name: o.name, count: o.count, tag: '' }));
            }
        })
        .catch(() => {})
        .then(() => fetch(API_BASE + '/customers.php', { credentials: 'include' }))
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.customers) {
                res.data.customers.forEach(c => {
                    if (c.customerGroup && c.customerGroup.name === 'OPD') {
                        list.push({ name: c.name || '-', count: c.totalOrder || 0, tag: 'OPD', amount: c.totalSpend || 0 });
                    }
                });
            }
        })
        .catch(() => {})
        .then(() => {
            if (list.length === 0) {
                el.innerHTML = '<div style="padding:16px;text-align:center;color:var(--on-surface-muted);">Tidak ada data</div>';
                return;
            }
            list.sort((a, b) => b.count - a.count);
            const top = list.slice(0, 10);
            el.innerHTML = top.map((o, i) => {
                const cls = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
                const tag = o.tag ? '<span style="font-size:9px;background:var(--surface-soft);padding:1px 5px;border-radius:3px;margin-left:6px;color:var(--on-surface-muted)">' + o.tag + '</span>' : '';
                const amt = o.amount ? '<div class="amount">' + fmtRp(o.amount) + '</div>' : '';
                return `<div class="cust-row"><div class="avatar ${cls}">${i + 1}</div><div class="info"><div class="name">${o.name}${tag}</div><div class="sub">${o.count} transaksi</div></div>${amt}</div>`;
            }).join('');
        });
}

function loadBumdRanking() {
    const el = document.getElementById('bumdRanking');
    if (!el) return;
    let list = [];

    fetch(API_BASE + '/sales_bumd.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                const grouped = {};
                data.items.forEach(s => {
                    if (s.status === 'selesai') {
                        if (!grouped[s.bumd_name]) grouped[s.bumd_name] = { name: s.bumd_name, count: 0, total: 0 };
                        grouped[s.bumd_name].count++;
                    }
                });
                Object.values(grouped).forEach(o => list.push({ name: o.name, count: o.count, tag: '' }));
            }
        })
        .catch(() => {})
        .then(() => fetch(API_BASE + '/customers.php', { credentials: 'include' }))
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.customers) {
                res.data.customers.forEach(c => {
                    if (c.customerGroup && c.customerGroup.name === 'BUMD') {
                        list.push({ name: c.name || '-', count: c.totalOrder || 0, tag: 'BUMD', amount: c.totalSpend || 0 });
                    }
                });
            }
        })
        .catch(() => {})
        .then(() => {
            if (list.length === 0) {
                el.innerHTML = '<div style="padding:16px;text-align:center;color:var(--on-surface-muted);">Tidak ada data</div>';
                return;
            }
            list.sort((a, b) => b.count - a.count);
            const top = list.slice(0, 10);
            el.innerHTML = top.map((o, i) => {
                const cls = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
                const tag = o.tag ? '<span style="font-size:9px;background:var(--surface-soft);padding:1px 5px;border-radius:3px;margin-left:6px;color:var(--on-surface-muted)">' + o.tag + '</span>' : '';
                const amt = o.amount ? '<div class="amount">' + fmtRp(o.amount) + '</div>' : '';
                return `<div class="cust-row"><div class="avatar ${cls}">${i + 1}</div><div class="info"><div class="name">${o.name}${tag}</div><div class="sub">${o.count} transaksi</div></div>${amt}</div>`;
            }).join('');
        });
}

function loadOpdChart() {
    const canvas = document.getElementById('opdChart');
    if (!canvas) return;

    fetch(API_BASE + '/customers.php', { credentials: 'include' })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.customers) {
                const list = res.data.customers
                    .filter(c => c.customerGroup && c.customerGroup.name === 'OPD' && (c.totalSpend || 0) > 0)
                    .map(c => ({ name: c.name || '-', total: c.totalSpend || 0 }))
                    .sort((a, b) => b.total - a.total)
                    .slice(0, 5);
                if (list.length > 0) {
                    renderHorizontalChart(canvas, list.map(o => o.name), list.map(o => o.total), 'Omzet (Rp)');
                } else {
                    renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (Rp)');
                }
            } else {
                renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (Rp)');
            }
        })
        .catch(() => {
            renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (Rp)');
        });
}

function loadBumdChart() {
    const canvas = document.getElementById('bumdChart');
    if (!canvas) return;

    fetch(API_BASE + '/customers.php', { credentials: 'include' })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.customers) {
                const list = res.data.customers
                    .filter(c => c.customerGroup && c.customerGroup.name === 'BUMD' && (c.totalSpend || 0) > 0)
                    .map(c => ({ name: c.name || '-', total: c.totalSpend || 0 }))
                    .sort((a, b) => b.total - a.total)
                    .slice(0, 5);
                if (list.length > 0) {
                    renderHorizontalChart(canvas, list.map(o => o.name), list.map(o => o.total), 'Omzet (Rp)');
                } else {
                    renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (Rp)');
                }
            } else {
                renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (Rp)');
            }
        })
        .catch(() => {
            renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (Rp)');
        });
}

function renderHorizontalChart(canvas, labels, values, label) {
    const ctx = canvas.getContext('2d');
    const grid = gc(), text = tc();
    Chart.getChart(canvas)?.destroy();
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: values,
                backgroundColor: ['rgba(37,99,235,0.7)', 'rgba(37,99,235,0.5)', 'rgba(37,99,235,0.35)', 'rgba(37,99,235,0.25)', 'rgba(37,99,235,0.15)'],
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: grid }, ticks: { color: text, callback: v => fmtRp(v) } },
                y: { grid: { display: false }, ticks: { color: text } }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    function reloadAll() {
        loadOpdRanking();
        loadBumdRanking();
        setTimeout(loadOpdChart, 200);
        setTimeout(loadBumdChart, 400);
    }
    reloadAll();
    setInterval(reloadAll, 60000);
});
</script>
