// ===== DATA =====
const products = [
    { name: 'Kaos Jakarta', qty: 1200, omzet: 120000000, category: 'Apparel', stock: 150 },
    { name: 'Tumbler', qty: 900, omzet: 130000000, category: 'Drinkware', stock: 200 },
    { name: 'Tote Bag', qty: 700, omzet: 56000000, category: 'Bag', stock: 300 },
    { name: 'Jaket', qty: 500, omzet: 150000000, category: 'Apparel', stock: 80 },
    { name: 'Topi', qty: 450, omzet: 36000000, category: 'Apparel', stock: 120 },
    { name: 'Mug', qty: 400, omzet: 32000000, category: 'Drinkware', stock: 250 },
    { name: 'Stiker', qty: 380, omzet: 7600000, category: 'Accesories', stock: 500 },
    { name: 'Gantungan Kunci', qty: 350, omzet: 7000000, category: 'Accesories', stock: 400 },
    { name: 'Notebook', qty: 300, omzet: 15000000, category: 'Accesories', stock: 180 },
    { name: 'Payung', qty: 280, omzet: 28000000, category: 'Accesories', stock: 90 },
    { name: 'Syal', qty: 200, omzet: 20000000, category: 'Apparel', stock: 60 },
    { name: 'Bantal Leher', qty: 150, omzet: 22500000, category: 'Accesories', stock: 40 }
];
const opdData = [
    { name: 'Dinas Pariwisata', omzet: 150000000, order: 30 },
    { name: 'Dinas Pendidikan', omzet: 120000000, order: 25 },
    { name: 'Dinas Kesehatan', omzet: 95000000, order: 20 },
    { name: 'Dinas PUPR', omzet: 80000000, order: 15 },
    { name: 'Dinas Perhubungan', omzet: 65000000, order: 12 }
];
const divisiData = [
    { name: 'Pemasaran', count: 180 },
    { name: 'Operasional', count: 120 },
    { name: 'Keuangan', count: 95 },
    { name: 'SDM', count: 80 },
    { name: 'IT', count: 65 }
];
const monthlyData = [
    { month: 'Jan', value: 62000000 }, { month: 'Feb', value: 45000000 },
    { month: 'Mar', value: 78000000 }, { month: 'Apr', value: 85000000 },
    { month: 'Mei', value: 92000000 }, { month: 'Jun', value: 105000000 },
    { month: 'Jul', value: 98000000 }, { month: 'Agu', value: 112000000 },
    { month: 'Sep', value: 128000000 }, { month: 'Okt', value: 135000000 },
    { month: 'Nov', value: 160000000 }, { month: 'Des', value: 180000000 }
];

function fmtIDR(n) {
    if (n >= 1e9) return 'Rp ' + (n / 1e9).toFixed(1) + ' M';
    if (n >= 1e6) return 'Rp ' + (n / 1e6).toFixed(0) + ' jt';
    if (n >= 1e3) return 'Rp ' + (n / 1e3).toFixed(0) + ' rb';
    return 'Rp ' + n;
}
function fmtNum(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

// ===== DARK MODE =====
let charts = {};

function toggleDark() {
    const h = document.documentElement;
    const isDark = h.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    const btn = document.querySelector('.shell-btn');
    if (btn) btn.textContent = isDark ? '☀️' : '🌙';
    updateChartTheme(isDark);
}

function updateChartTheme(isDark) {
    if (Object.keys(charts).length === 0) return;
    const gc = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
    const tc = isDark ? '#B9B3AC' : '#797067';
    const pb = isDark ? '#4A423A' : '#fff';
    Object.values(charts).forEach(c => {
        if (!c) return;
        if (c.options.scales) {
            Object.values(c.options.scales).forEach(s => {
                if (s.grid) s.grid.color = gc;
                if (s.ticks) s.ticks.color = tc;
            });
        }
        if (c.options.plugins?.legend?.labels) c.options.plugins.legend.labels.color = tc;
        if (c.options.plugins?.title) c.options.plugins.title.color = tc;
        if (c.data?.datasets) {
            c.data.datasets.forEach(ds => {
                if (ds.fill && ds.backgroundColor && ds.backgroundColor.addColorStop) {
                    ds.backgroundColor = c.ctx.createLinearGradient(0, 0, 0, 320);
                    ds.backgroundColor.addColorStop(0, isDark ? 'rgba(254,110,0,0.08)' : 'rgba(254,110,0,0.08)');
                    ds.backgroundColor.addColorStop(1, 'rgba(254,110,0,0)');
                }
                ds.pointBorderColor = pb;
            });
        }
        c.update();
    });
}

(function initTheme() {
    const saved = localStorage.getItem('theme');
    const isDark = saved !== null ? saved === 'dark' : false;
    if (isDark) {
        document.documentElement.classList.add('dark');
        const btn = document.querySelector('.shell-btn');
        if (btn) btn.textContent = '☀️';
    }
})();

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('theme')) {
        document.documentElement.classList.toggle('dark', e.matches);
        const btn = document.querySelector('.shell-btn');
        if (btn) btn.textContent = e.matches ? '☀️' : '🌙';
        if (Object.keys(charts).length) updateChartTheme(e.matches);
    }
});

// ===== LOGIN (PHP Backend) =====
// API_BASE is now defined in the page head via BASE_URL

function doLogin() {
    const username = document.getElementById('loginUser').value.trim();
    const password = document.getElementById('loginPass').value.trim();
    const errEl = document.getElementById('loginError');
    if (errEl) errEl.classList.remove('show');

    if (!username || !password) {
        if (errEl) {
            errEl.textContent = 'Username dan password wajib diisi';
            errEl.classList.add('show');
        }
        return;
    }

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);

    fetch(`${API_BASE}/login.php`, { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                if (errEl) {
                    errEl.textContent = data.error;
                    errEl.classList.add('show');
                }
                return;
            }
            applyRole(data.user.role, data.user.display_name, data.user.username);
            const overlay = document.getElementById('loginOverlay');
            if (overlay) overlay.classList.add('hidden');
            document.documentElement.dataset.loggedIn = 'true';
            window.location.href = BASE_URL + '/summary';
        })
        .catch(() => {
            if (errEl) {
                errEl.textContent = 'Gagal terhubung ke server';
                errEl.classList.add('show');
            }
        });
}

function doLogout() {
    fetch(`${API_BASE}/logout.php`, { credentials: 'include' })
        .then(() => {
            window.location.href = BASE_URL + '/';
        });
}

function applyRole(role, displayName, username) {
    const badge = document.getElementById('userBadge');
    const logoutBtn = document.getElementById('logoutBtn');
    const avatar = document.getElementById('userAvatar');
    const userName = document.getElementById('userName');
    const userRole = document.getElementById('userRole');
    if (badge) badge.style.display = 'flex';
    if (logoutBtn) logoutBtn.style.display = 'inline-block';
    if (avatar) avatar.textContent = (username.charAt(0) || 'A').toUpperCase();
    if (userName) userName.textContent = displayName;
    if (userRole) userRole.textContent =
        role === 'superadmin' ? 'Superadmin' : role === 'admin' ? 'Admin' : 'User';
}

(function checkSession() {
    fetch(`${API_BASE}/check_session.php`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.logged_in) {
                applyRole(data.user.role, data.user.display_name, data.user.username);
                const overlay = document.getElementById('loginOverlay');
                if (overlay) overlay.classList.add('hidden');
                document.documentElement.dataset.loggedIn = 'true';
            }
        })
        .catch(() => { });
})();

const headerDate = document.getElementById('headerDate');
if (headerDate) {
    headerDate.textContent =
        new Date().toLocaleDateString('id-ID', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
}

// ===== RENDER =====
function renderProducts() {
    const topQtyList = document.getElementById('topQtyList');
    const topOmzetList = document.getElementById('topOmzetList');
    const slowMovingList = document.getElementById('slowMovingList');
    if (!topQtyList || !topOmzetList || !slowMovingList) return;

    const byQty = [...products].sort((a, b) => b.qty - a.qty).slice(0, 10);
    const maxQ = byQty[0].qty;
    const byOmzet = [...products].sort((a, b) => b.omzet - a.omzet).slice(0, 10);
    const maxO = byOmzet[0].omzet;
    const slow = products.filter(p => p.stock > 100 && p.qty < 300);

    topQtyList.innerHTML = byQty.map(p =>
        `<div class="product-row"><div class="info"><div class="name">${p.name}</div><div class="bar-wrap"><div class="bar-fill" style="width:${p.qty / maxQ * 100}%"></div></div></div><div class="value">${fmtNum(p.qty)}</div></div>`
    ).join('');
    topOmzetList.innerHTML = byOmzet.map(p =>
        `<div class="product-row"><div class="info"><div class="name">${p.name}</div><div class="bar-wrap"><div class="bar-fill amber" style="width:${p.omzet / maxO * 100}%"></div></div></div><div class="value">${fmtIDR(p.omzet)}</div></div>`
    ).join('');
    slowMovingList.innerHTML = slow.map(p =>
        `<div class="slow-item"><div><div class="name">${p.name}</div><div class="detail">Penjualan: ${fmtNum(p.qty)} &middot; Stok: ${fmtNum(p.stock)}</div></div><div class="stock-val">${fmtNum(p.stock)}</div></div>`
    ).join('');
}

function renderCustomers() {
    const opdRanking = document.getElementById('opdRanking');
    const divisiList = document.getElementById('divisiList');
    if (!opdRanking || !divisiList) return;

    const sorted = [...opdData].sort((a, b) => b.omzet - a.omzet);
    const avClass = i => i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
    opdRanking.innerHTML = sorted.map((o, i) =>
        `<div class="cust-row"><div class="avatar ${avClass(i)}">${i + 1}</div><div class="info"><div class="name">${o.name}</div><div class="sub">${fmtNum(o.order)} order</div></div><div class="amount">${fmtIDR(o.omzet)}</div></div>`
    ).join('');

    const sd = [...divisiData].sort((a, b) => b.count - a.count);
    const mc = sd[0].count;
    divisiList.innerHTML = sd.map(d =>
        `<div class="product-row" style="padding:5px 8px;"><div class="info"><div class="name">${d.name}</div><div class="bar-wrap"><div class="bar-fill blue" style="width:${d.count / mc * 100}%"></div></div></div><div class="value" style="font-size:0.8125rem;">${fmtNum(d.count)}</div></div>`
    ).join('');
}

function renderInventory() {
    const reorderGrid = document.getElementById('reorderGrid');
    if (!reorderGrid) return;
    const alerts = products.filter(p => p.stock > 0 && p.stock <= 80).sort((a, b) => a.stock - b.stock);
    reorderGrid.innerHTML = alerts.map(a =>
        `<div class="inv-card ${a.stock <= 50 ? 'out' : 'low'}"><div class="name">${a.name}</div><div class="stock-num">${fmtNum(a.stock)}</div><div class="status-label">${a.stock <= 50 ? 'Habis' : 'Menipis'}</div></div>`
    ).join('');
}

// ===== CHARTS =====
function isDark() { return document.documentElement.classList.contains('dark'); }
function gc() { return isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)'; }
function tc() { return isDark() ? '#B9B3AC' : '#797067'; }
function pb() { return isDark() ? '#4A423A' : '#fff'; }

function initCharts() {
    const channelDonut = document.getElementById('channelDonut');
    const kategoriBar = document.getElementById('kategoriBar');
    const opdChart = document.getElementById('opdChart');
    const trendChart = document.getElementById('trendChart');
    const mkpOrderChart = document.getElementById('mkpOrderChart');
    const mkpOmzetChart = document.getElementById('mkpOmzetChart');

    const grid = gc(), text = tc(), pointB = pb();

    if (channelDonut) {
        const c1 = channelDonut.getContext('2d');
        charts.channel = new Chart(c1, {
            type: 'doughnut',
            data: { labels: ['OPD Jakarta', 'Karyawan', 'Marketplace'], datasets: [{ data: [60, 25, 15], backgroundColor: ['#FE6E00', '#423D38', '#3080FF'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, font: { size: 12, weight: '600' }, color: text } } }, cutout: '65%' }
        });
    }

    if (kategoriBar) {
        const cats = {};
        products.forEach(p => { cats[p.category] = (cats[p.category] || 0) + p.qty; });
        const kl = Object.keys(cats), kv = Object.values(cats);
        const c2 = kategoriBar.getContext('2d');
        charts.kategori = new Chart(c2, {
            type: 'bar',
            data: { labels: kl, datasets: [{ label: 'Unit', data: kv, backgroundColor: ['rgba(254,110,0,0.6)', 'rgba(66,61,56,0.5)', 'rgba(251,44,54,0.5)', 'rgba(48,128,255,0.5)', 'rgba(0,199,88,0.5)'], borderRadius: 4, borderSkipped: false }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { color: text } }, x: { grid: { display: false }, ticks: { color: text } } } }
        });
    }

    if (opdChart) {
        const ol = opdData.map(o => o.name.replace('Dinas ', ''));
        const ov = opdData.map(o => o.omzet / 1e6);
        const c3 = opdChart.getContext('2d');
        charts.opd = new Chart(c3, {
            type: 'bar',
            data: { labels: ol, datasets: [{ label: 'Omzet (jt)', data: ov, backgroundColor: ['rgba(254,110,0,0.7)', 'rgba(254,110,0,0.5)', 'rgba(254,110,0,0.35)', 'rgba(254,110,0,0.25)', 'rgba(254,110,0,0.15)'], borderRadius: 4, borderSkipped: false }] },
            options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { color: text } }, y: { grid: { display: false }, ticks: { color: text } } } }
        });
    }

    if (trendChart) {
        const months = monthlyData.map(m => m.month);
        const mvals = monthlyData.map(m => m.value / 1e6);
        const c4 = trendChart.getContext('2d');
        const grad = c4.createLinearGradient(0, 0, 0, 320);
        grad.addColorStop(0, 'rgba(254,110,0,0.10)');
        grad.addColorStop(1, 'rgba(254,110,0,0)');
        charts.trend = new Chart(c4, {
            type: 'line',
            data: { labels: months, datasets: [{ label: 'Penjualan (Rp juta)', data: mvals, borderColor: '#FE6E00', backgroundColor: grad, fill: true, tension: 0.3, pointBackgroundColor: '#FE6E00', pointBorderColor: pointB, pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6, borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { color: text } }, x: { grid: { display: false }, ticks: { color: text } } }, interaction: { intersect: false, mode: 'index' } }
        });
    }

    if (mkpOrderChart) {
        const c5 = mkpOrderChart.getContext('2d');
        charts.mkpOrder = new Chart(c5, {
            type: 'doughnut',
            data: { labels: ['Shopee', 'Tokopedia'], datasets: [{ data: [450, 150], backgroundColor: ['#EE4D2D', '#4AB17C'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Order', font: { size: 13, weight: '700' }, padding: 12, color: text }, legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 }, color: text } } }, cutout: '60%' }
        });
    }

    if (mkpOmzetChart) {
        const c6 = mkpOmzetChart.getContext('2d');
        charts.mkpOmzet = new Chart(c6, {
            type: 'doughnut',
            data: { labels: ['Shopee', 'Tokopedia'], datasets: [{ data: [150, 50], backgroundColor: ['#EE4D2D', '#4AB17C'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Omzet (Rp jt)', font: { size: 13, weight: '700' }, padding: 12, color: text }, legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 }, color: text } } }, cutout: '60%' }
        });
    }
}

function switchTrend(el) {
    const parent = el.parentElement;
    if (!parent) return;
    parent.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function applyFilter() { alert('Filter diterapkan — simulated'); }

// ===== USER CRUD =====
let editingUserId = null;

function showUserModal(user) {
    const modal = document.getElementById('userModal');
    const umError = document.getElementById('umError');
    if (!modal) return;
    if (umError) umError.style.display = 'none';
    const isSuper = user && user.role === 'superadmin';
    const pwHint = document.querySelector('#umPassword')?.closest('.login-field')?.querySelector('span');
    if (user) {
        editingUserId = user.id;
        const title = document.getElementById('userModalTitle');
        if (title) title.textContent = 'Edit User';
        const uu = document.getElementById('umUsername');
        if (uu) uu.value = user.username;
        const up = document.getElementById('umPassword');
        if (up) up.value = '';
        const ud = document.getElementById('umDisplayName');
        if (ud) ud.value = user.display_name;
    } else {
        editingUserId = null;
        const title = document.getElementById('userModalTitle');
        if (title) title.textContent = 'Tambah User';
        const uu = document.getElementById('umUsername');
        if (uu) uu.value = '';
        const up = document.getElementById('umPassword');
        if (up) up.value = '';
        const ud = document.getElementById('umDisplayName');
        if (ud) ud.value = '';
        const ur = document.getElementById('umRole');
        if (ur) ur.value = 'user';
    }
    if (isSuper) {
        const uu = document.getElementById('umUsername');
        if (uu) uu.disabled = true;
        const ud = document.getElementById('umDisplayName');
        if (ud) ud.disabled = true;
        const rf = document.getElementById('umRoleField');
        if (rf) rf.style.display = 'none';
        const si = document.getElementById('umSuperadminInfo');
        if (si) si.style.display = '';
        if (pwHint) pwHint.textContent = '(wajib diisi)';
    } else {
        const uu = document.getElementById('umUsername');
        if (uu) uu.disabled = false;
        const ud = document.getElementById('umDisplayName');
        if (ud) ud.disabled = false;
        const rf = document.getElementById('umRoleField');
        if (rf) rf.style.display = '';
        const si = document.getElementById('umSuperadminInfo');
        if (si) si.style.display = 'none';
        if (pwHint) pwHint.textContent = '(kosongi jika tidak diubah)';
        if (user) {
            const ur = document.getElementById('umRole');
            if (ur) ur.value = user.role;
        }
    }
    if (modal) modal.style.display = 'flex';
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    const umError = document.getElementById('umError');
    if (modal) modal.style.display = 'none';
    if (umError) umError.style.display = 'none';
}

function saveUser() {
    const username = document.getElementById('umUsername')?.value.trim();
    const password = document.getElementById('umPassword')?.value.trim();
    const displayName = document.getElementById('umDisplayName')?.value.trim();
    const siDisplay = document.getElementById('umSuperadminInfo')?.style.display;
    const isSuperEdit = editingUserId && siDisplay !== 'none' && siDisplay !== '';
    const role = isSuperEdit ? 'superadmin' : (document.getElementById('umRole')?.value || 'user');
    const errEl = document.getElementById('umError');
    if (errEl) errEl.style.display = 'none';

    if (!username || !displayName) {
        if (errEl) {
            errEl.textContent = 'Username dan Nama wajib diisi';
            errEl.style.display = 'flex';
        }
        return;
    }

    if (isSuperEdit && !password) {
        if (errEl) {
            errEl.textContent = 'Password wajib diisi untuk Superadmin';
            errEl.style.display = 'flex';
        }
        return;
    }

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password || '');
    formData.append('display_name', displayName);
    formData.append('role', role);
    const isEdit = !!editingUserId;
    formData.append('_method', isEdit ? 'PUT' : 'POST');
    if (editingUserId) formData.append('id', editingUserId);

    fetch(`${API_BASE}/users.php`, { method: 'POST', body: formData, credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                if (errEl) {
                    errEl.textContent = data.error;
                    errEl.style.display = 'flex';
                }
                return;
            }
            closeUserModal();
            loadUsers();
            const label = isEdit ? 'diubah' : 'dibuat';
            showSuccess('Berhasil', `User "${username}" berhasil ${label}.`);
        })
        .catch(() => {
            if (errEl) {
                errEl.textContent = 'Gagal terhubung ke server';
                errEl.style.display = 'flex';
            }
        });
}

function deleteUser(id, username) {
    showConfirm('Hapus User', `Yakin ingin menghapus user "${username}"? Tindakan ini tidak dapat dibatalkan.`, 'Ya, Hapus', function () {
        fetch(`${API_BASE}/users.php?id=${id}`, { method: 'DELETE', credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showSuccess('Gagal', data.error); return; }
                loadUsers();
                showSuccess('Berhasil', `User "${username}" berhasil dihapus.`);
            })
            .catch(() => showSuccess('Error', 'Gagal terhubung ke server'));
    });
}

function loadUsers() {
    const tbody = document.getElementById('userTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--on-surface-muted)">Memuat...</td></tr>';
    fetch(`${API_BASE}/users.php`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.error) { tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--danger)">${data.error}</td></tr>`; return; }
            if (!data.users || data.users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state">Belum ada user terdaftar</div></td></tr>';
                return;
            }
            tbody.innerHTML = data.users.map((u, i) => `
                <tr>
                    <td><strong>${i + 1}</strong></td>
                    <td>${u.username}</td>
                    <td>${u.display_name}</td>
                    <td><span class="badge ${u.role === 'superadmin' ? 'badge-orange' : u.role === 'admin' ? 'badge-blue' : 'badge-neutral'}">${u.role}</span></td>
                    <td style="font-size:0.8125rem">${u.created_at || '-'}</td>
                    <td><div class="action-cell"><button class="btn-edit" onclick='showUserModal(${JSON.stringify(u).replace(/'/g, "\\'")})'>Edit</button>${u.role !== 'superadmin' ? `<button class="btn-danger" onclick="deleteUser(${u.id},'${u.username}')">Hapus</button>` : ''}</div></td>
                </tr>
            `).join('');
        })
        .catch(() => { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--danger)">Gagal memuat data</td></tr>'; });
}

// ===== CONFIRM / SUCCESS MODALS =====
let confirmCallback = null;

function showConfirm(title, message, okLabel, cb) {
    const t = document.getElementById('confirmTitle');
    const m = document.getElementById('confirmMessage');
    const ok = document.getElementById('confirmOkBtn');
    const modal = document.getElementById('confirmModal');
    if (t) t.textContent = title;
    if (m) m.textContent = message;
    if (ok) ok.textContent = okLabel || 'Ya, Hapus';
    confirmCallback = cb;
    if (modal) modal.style.display = 'flex';
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'none';
    confirmCallback = null;
}

function confirmAction() {
    const cb = confirmCallback;
    closeConfirmModal();
    if (cb) cb();
}

document.getElementById('confirmModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeConfirmModal();
});

function showSuccess(title, message) {
    const t = document.getElementById('successTitle');
    const m = document.getElementById('successMessage');
    const modal = document.getElementById('successModal');
    if (t) t.textContent = title;
    if (m) m.textContent = message;
    if (modal) modal.style.display = 'flex';
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) modal.style.display = 'none';
}

document.getElementById('successModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeSuccessModal();
});

// ===== MODAL OVERLAY CLICKS =====
document.getElementById('userModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeUserModal();
});

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function () {
    renderProducts();
    renderCustomers();
    renderInventory();
    initCharts();

    if (document.getElementById('userTableBody')) {
        loadUsers();
    }
});

window.addEventListener('resize', () => { Object.values(charts).forEach(c => { if (c) c.resize(); }); });
