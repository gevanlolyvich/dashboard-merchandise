<div class="page-header" style="margin-bottom:16px">
    <div>
        <div class="section-headline" style="margin-bottom:0"><span class="emoji">📥</span> Pemasukan Data Excel</div>
        <div class="page-sub">Import file Excel rekap merchandise JFF</div>
    </div>
</div>

<div class="card" style="padding:24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:6px;color:var(--on-surface-muted);">Pilih File Excel</label>
            <input type="file" id="excelFileInput" accept=".xlsx,.xls" style="width:100%;padding:8px 12px;border:1px solid var(--outline);border-radius:6px;background:var(--surface-elevated);color:var(--on-surface);font-size:0.875rem;">
        </div>
        <button class="btn-primary" onclick="importExcel()" style="margin-top:22px;height:40px;" id="importBtn">Import Data</button>
        <button class="btn-success" onclick="exportTemplate()" style="margin-top:22px;height:40px;" id="exportBtn">Export Template</button>
        <button class="btn-danger" onclick="clearData()" style="margin-top:22px;height:40px;" id="clearBtn">Hapus Semua Data</button>
    </div>
    <div id="importStatus" style="margin-top:12px;font-size:0.875rem;color:var(--on-surface-muted);"></div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Barang</th>
                    <th>Kategori</th>
                    <th>Nama Barang</th>
                    <th>Ukuran</th>
                    <th>HPP</th>
                    <th>Harga Institusi</th>
                    <th>Stok Awal</th>
                    <th>Barang Masuk</th>
                    <th>Terjual</th>
                    <th>Stok Akhir</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody id="jffTableBody"></tbody>
        </table>
    </div>
</div>

<script>
function importExcel() {
    const fileInput = document.getElementById('excelFileInput');
    const statusEl = document.getElementById('importStatus');
    const btn = document.getElementById('importBtn');

    if (!fileInput.files || !fileInput.files[0]) {
        statusEl.innerHTML = '<span style="color:var(--danger)">Silakan pilih file Excel terlebih dahulu</span>';
        return;
    }

    const formData = new FormData();
    formData.append('excel_file', fileInput.files[0]);
    btn.disabled = true;
    btn.textContent = 'Mengimport...';
    statusEl.innerHTML = '<span style="color:var(--info)">Sedang mengimport data...</span>';

    fetch(API_BASE + '/merchandise_jff.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                statusEl.innerHTML = '<span style="color:var(--danger)">Gagal: ' + data.error + '</span>';
            } else {
                statusEl.innerHTML = '<span style="color:var(--success)">' + data.message + '</span>';
                fileInput.value = '';
                loadJffData();
            }
        })
        .catch(() => {
            statusEl.innerHTML = '<span style="color:var(--danger)">Gagal terhubung ke server</span>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Import Data';
        });
}

function exportTemplate() {
    window.location.href = API_BASE + '/merchandise_jff.php?export=template';
}

function clearData() {
    showConfirm('Hapus Semua Data', 'Yakin ingin menghapus semua data merchandise JFF?', 'Ya, Hapus', function() {
        fetch(API_BASE + '/merchandise_jff.php', { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.message) {
                    showSuccess('Berhasil', data.message);
                    loadJffData();
                    document.getElementById('importStatus').innerHTML = '';
                }
            })
            .catch(() => showSuccess('Error', 'Gagal menghapus data'));
    });
}

function loadJffData() {
    const tbody = document.getElementById('jffTableBody');
    fetch(API_BASE + '/merchandise_jff.php', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Belum ada data. Import file Excel terlebih dahulu.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map((item, i) => {
                return `<tr>
                    <td>${i + 1}</td>
                    <td><strong>${item.kode_barang || '-'}</strong></td>
                    <td>${item.tipe_kategori || '-'}</td>
                    <td>${item.nama_barang || '-'}</td>
                    <td>${item.ukuran_varian || '-'}</td>
                    <td>${formatRupiah(item.hpp)}</td>
                    <td>${formatRupiah(item.harga_institusi)}</td>
                    <td>${item.stok_awal}</td>
                    <td>${item.barang_masuk}</td>
                    <td>${item.barang_terjual}</td>
                    <td><strong>${item.stok_akhir}</strong></td>
                    <td>${formatRupiah((parseFloat(item.pendapatan)||0) + (parseFloat(item.pendapatan_2)||0) + (parseFloat(item.pendapatan_3)||0))}</td>
                </tr>`;
            }).join('');
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>';
        });
}

function formatRupiah(val) {
    const num = parseFloat(val) || 0;
    if (num === 0) return '-';
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

if (document.getElementById('jffTableBody')) {
    loadJffData();
}
</script>
