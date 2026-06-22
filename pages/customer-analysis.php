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
function loadOpdRanking() {
    const el = document.getElementById('opdRanking');
    if (!el) return;

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
                const sorted = Object.values(grouped).sort((a, b) => b.count - a.count).slice(0, 10);
                if (sorted.length === 0) throw new Error();
                const maxC = sorted[0].count || 1;
                el.innerHTML = sorted.map((o, i) => {
                    const cls = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
                    return `<div class="cust-row"><div class="avatar ${cls}">${i + 1}</div><div class="info"><div class="name">${o.name}</div><div class="sub">${o.count} transaksi</div></div></div>`;
                }).join('');
            } else {
                throw new Error();
            }
        })
        .catch(() => {
            el.innerHTML = '<div style="padding:16px;text-align:center;color:var(--on-surface-muted);">Tidak ada data</div>';
        });
}

function loadBumdRanking() {
    const el = document.getElementById('bumdRanking');
    if (!el) return;

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
                const sorted = Object.values(grouped).sort((a, b) => b.count - a.count).slice(0, 10);
                if (sorted.length === 0) throw new Error();
                el.innerHTML = sorted.map((o, i) => {
                    const cls = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
                    return `<div class="cust-row"><div class="avatar ${cls}">${i + 1}</div><div class="info"><div class="name">${o.name}</div><div class="sub">${o.count} transaksi</div></div></div>`;
                }).join('');
            } else {
                throw new Error();
            }
        })
        .catch(() => {
            el.innerHTML = '<div style="padding:16px;text-align:center;color:var(--on-surface-muted);">Tidak ada data</div>';
        });
}

function loadOpdChart() {
    const canvas = document.getElementById('opdChart');
    if (!canvas) return;

    fetch(API_BASE + '/sales_opd.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                const grouped = {};
                data.items.forEach(s => {
                    if (s.status === 'selesai') {
                        if (!grouped[s.opd_name]) grouped[s.opd_name] = { name: s.opd_name, total: 0 };
                        grouped[s.opd_name].total += s.total || 0;
                    }
                });
                const sorted = Object.values(grouped).sort((a, b) => b.total - a.total).slice(0, 5);
                if (sorted.length > 0) {
                    renderHorizontalChart(canvas, sorted.map(o => o.name.replace('Dinas ', '')), sorted.map(o => o.total / 1e6), 'Omzet (jt)');
                    return;
                }
            }
            throw new Error();
        })
        .catch(() => {
            renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (jt)');
        });
}

function loadBumdChart() {
    const canvas = document.getElementById('bumdChart');
    if (!canvas) return;

    fetch(API_BASE + '/sales_bumd.php?page=1&limit=999', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                const grouped = {};
                data.items.forEach(s => {
                    if (s.status === 'selesai') {
                        if (!grouped[s.bumd_name]) grouped[s.bumd_name] = { name: s.bumd_name, total: 0 };
                        grouped[s.bumd_name].total += s.total || 0;
                    }
                });
                const sorted = Object.values(grouped).sort((a, b) => b.total - a.total).slice(0, 5);
                if (sorted.length > 0) {
                    renderHorizontalChart(canvas, sorted.map(o => o.name), sorted.map(o => o.total / 1e6), 'Omzet (jt)');
                    return;
                }
            }
            throw new Error();
        })
        .catch(() => {
            renderHorizontalChart(canvas, ['Tidak ada data'], [0], 'Omzet (jt)');
        });
}

function renderHorizontalChart(canvas, labels, values, label) {
    const ctx = canvas.getContext('2d');
    const grid = gc(), text = tc();
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: values,
                backgroundColor: ['rgba(254,110,0,0.7)', 'rgba(254,110,0,0.5)', 'rgba(254,110,0,0.35)', 'rgba(254,110,0,0.25)', 'rgba(254,110,0,0.15)'],
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
                x: { beginAtZero: true, grid: { color: grid }, ticks: { color: text } },
                y: { grid: { display: false }, ticks: { color: text } }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    loadOpdRanking();
    loadBumdRanking();
    setTimeout(loadOpdChart, 100);
    setTimeout(loadBumdChart, 100);
});
</script>
