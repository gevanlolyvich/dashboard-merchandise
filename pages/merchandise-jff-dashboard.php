<div class="section-headline"><span class="emoji">📊</span> Dashboard Rekap Merchandise JFF</div>
<div class="kpi-grid" id="jffKpiGrid">
    <div class="kpi-card orange">
        <div class="kpi-label">Total Produk</div>
        <div class="kpi-value" id="kpiTotalProduk">0</div>
        <div class="kpi-sub">Barang merchandise</div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">Total Terjual</div>
        <div class="kpi-value" id="kpiTotalTerjual">0</div>
        <div class="kpi-sub">Seluruh hari</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value" id="kpiTotalPendapatan">Rp 0</div>
        <div class="kpi-sub">Gabungan Day 1-3</div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Total Produksi</div>
        <div class="kpi-value" id="kpiTotalProduksi">0</div>
        <div class="kpi-sub">Unit diproduksi</div>
    </div>
    <div class="kpi-card amber">
        <div class="kpi-label">Stok Akhir</div>
        <div class="kpi-value" id="kpiStokAkhir">0</div>
        <div class="kpi-sub">Sisa stok</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Rata-rata Harga</div>
        <div class="kpi-value" id="kpiRataHarga">Rp 0</div>
        <div class="kpi-sub">Per produk</div>
    </div>
</div>

<div class="grid-2" style="margin-top:20px;">
    <div class="card">
        <div class="card-title" style="margin-bottom:12px;">Penjualan per Hari</div>
        <canvas id="chartPenjualanHari" height="200"></canvas>
    </div>
    <div class="card">
        <div class="card-title" style="margin-bottom:12px;">Pendapatan per Hari</div>
        <canvas id="chartPendapatanHari" height="200"></canvas>
    </div>
</div>

<div class="grid-2" style="margin-top:20px;">
    <div class="card">
        <div class="card-title" style="margin-bottom:12px;">Kategori Produk</div>
        <canvas id="chartKategori" height="200"></canvas>
    </div>
    <div class="card">
        <div class="card-title" style="margin-bottom:12px;">Top Produk Terlaris</div>
        <canvas id="chartTopProduk" height="200"></canvas>
    </div>
</div>

<script>
let jffChart1, jffChart2, jffChart3, jffChart4;

function loadJffDashboard() {
    fetch(API_BASE + '/merchandise_jff.php', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            const items = data.data || [];
            if (items.length === 0) {
                document.querySelector('.kpi-grid').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--on-surface-muted)">Belum ada data. Import Excel terlebih dahulu.</div>';
                return;
            }

            let totalTerjual = 0, totalPendapatan = 0, totalProduksi = 0, stokAkhir = 0;
            let sumHargaRitel = 0;
            let day1 = 0, day2 = 0, day3 = 0;
            let rev1 = 0, rev2 = 0, rev3 = 0;
            const kategoriMap = {};
            const produkMap = {};

            items.forEach(item => {
                const terjual = parseInt(item.barang_terjual) || 0;
                const d1 = parseInt(item.day_1_jff) || 0;
                const d2 = parseInt(item.day_2) || 0;
                const d3 = parseInt(item.day_3) || 0;
                const p1 = parseFloat(item.pendapatan) || 0;
                const p2 = parseFloat(item.pendapatan_2) || 0;
                const p3 = parseFloat(item.pendapatan_3) || 0;

                totalTerjual += terjual;
                totalPendapatan += (p1 + p2 + p3);
                totalProduksi += parseInt(item.produksi) || 0;
                stokAkhir += parseInt(item.stok_akhir) || 0;
                sumHargaRitel += parseFloat(item.harga_ritel) || 0;
                day1 += d1; day2 += d2; day3 += d3;
                rev1 += p1; rev2 += p2; rev3 += p3;

                const kat = item.tipe_kategori || 'Lainnya';
                kategoriMap[kat] = (kategoriMap[kat] || 0) + terjual;

                if (item.nama_barang) {
                    produkMap[item.nama_barang] = (produkMap[item.nama_barang] || 0) + terjual;
                }
            });

            const totalProduk = items.length;
            const rataHarga = totalProduk > 0 ? sumHargaRitel / totalProduk : 0;

            document.getElementById('kpiTotalProduk').textContent = totalProduk;
            document.getElementById('kpiTotalTerjual').textContent = totalTerjual.toLocaleString('id-ID');
            document.getElementById('kpiTotalPendapatan').textContent = 'Rp ' + totalPendapatan.toLocaleString('id-ID');
            document.getElementById('kpiTotalProduksi').textContent = totalProduksi.toLocaleString('id-ID');
            document.getElementById('kpiStokAkhir').textContent = stokAkhir.toLocaleString('id-ID');
            document.getElementById('kpiRataHarga').textContent = 'Rp ' + Math.round(rataHarga).toLocaleString('id-ID');

            renderPenjualanChart(day1, day2, day3);
            renderPendapatanChart(rev1, rev2, rev3);
            renderKategoriChart(kategoriMap);
            renderTopProdukChart(produkMap);
        })
        .catch(() => {
            document.querySelector('.kpi-grid').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--danger)">Gagal memuat data</div>';
        });
}

function renderPenjualanChart(d1, d2, d3) {
    const ctx = document.getElementById('chartPenjualanHari').getContext('2d');
    if (jffChart1) jffChart1.destroy();
    jffChart1 = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3'],
            datasets: [{
                label: 'Unit Terjual',
                data: [d1, d2, d3],
                backgroundColor: ['rgba(254,110,0,0.7)', 'rgba(48,128,255,0.7)', 'rgba(0,199,88,0.7)'],
                borderRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } } }
    });
}

function renderPendapatanChart(r1, r2, r3) {
    const ctx = document.getElementById('chartPendapatanHari').getContext('2d');
    if (jffChart2) jffChart2.destroy();
    jffChart2 = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3'],
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: [r1, r2, r3],
                backgroundColor: ['rgba(254,110,0,0.7)', 'rgba(48,128,255,0.7)', 'rgba(0,199,88,0.7)'],
                borderRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } },
            scales: { y: { ticks: { callback: v => 'Rp' + (v/1000).toFixed(0) + 'k' } } } }
    });
}

function renderKategoriChart(katMap) {
    const ctx = document.getElementById('chartKategori').getContext('2d');
    if (jffChart3) jffChart3.destroy();
    const labels = Object.keys(katMap);
    const values = Object.values(katMap);
    jffChart3 = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: ['#FE6E00', '#3080FF', '#00C758', '#EDB200', '#FB2C36', '#797067']
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
    });
}

function renderTopProdukChart(prodMap) {
    const ctx = document.getElementById('chartTopProduk').getContext('2d');
    if (jffChart4) jffChart4.destroy();
    const sorted = Object.entries(prodMap).sort((a, b) => b[1] - a[1]).slice(0, 5);
    const labels = sorted.map(s => s[0].length > 20 ? s[0].substring(0, 20) + '...' : s[0]);
    const values = sorted.map(s => s[1]);
    jffChart4 = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Terjual',
                data: values,
                backgroundColor: 'rgba(254,110,0,0.7)',
                borderRadius: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true, indexAxis: 'y',
            plugins: { legend: { display: false } } }
    });
}

if (document.getElementById('jffKpiGrid')) {
    loadJffDashboard();
}
</script>
