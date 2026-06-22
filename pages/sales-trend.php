<div class="section-headline"><span class="emoji">📈</span> Tren Penjualan</div>
<div class="grid-2 grid-full">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><span class="emoji">📊</span> Penjualan Bulanan</div>
            <div class="filter-tags">
                <span class="filter-tag" onclick="switchTrend(this,'daily')">Harian</span>
                <span class="filter-tag" onclick="switchTrend(this,'weekly')">Mingguan</span>
                <span class="filter-tag active" onclick="switchTrend(this,'monthly')">Bulanan</span>
            </div>
        </div>
        <div class="chart-box" style="height:320px"><canvas id="trendChart"></canvas></div>
    </div>
</div>
<div class="grid-2">
    <div class="card" style="border-top:3px solid var(--success);">
        <div class="card-header">
            <div class="card-title"><span class="emoji">🏆</span> Bulan Terbaik</div>
        </div>
        <div style="text-align:center;padding:12px 0;">
            <div style="font-size:0.8125rem;color:var(--on-surface-muted);">Penjualan tertinggi</div>
            <div style="font-size:2.5rem;font-weight:700;color:var(--on-bg);margin:6px 0;">—</div>
            <div style="font-size:1.125rem;font-weight:700;color:var(--primary-strong);">Rp 0</div>
            <div style="font-size:0.8125rem;color:var(--on-surface-muted);margin-top:6px;">—</div>
        </div>
    </div>
    <div class="card" style="border-top:3px solid var(--danger);">
        <div class="card-header">
            <div class="card-title"><span class="emoji">⚠️</span> Bulan Terburuk</div>
        </div>
        <div style="text-align:center;padding:12px 0;">
            <div style="font-size:0.8125rem;color:var(--on-surface-muted);">Penjualan terendah</div>
            <div style="font-size:2.5rem;font-weight:700;color:var(--on-bg);margin:6px 0;">—</div>
            <div style="font-size:1.125rem;font-weight:700;color:var(--danger);">Rp 0</div>
            <div style="font-size:0.8125rem;color:var(--on-surface-muted);margin-top:6px;">—</div>
        </div>
    </div>
</div>
