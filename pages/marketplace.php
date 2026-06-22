<div class="section-headline"><span class="emoji">🏪</span> Marketplace</div>
<div class="page-sub" style="margin-bottom:24px">Data marketplace dari integrasi Ginee API</div>

<div id="marketplaceRoot">
    <div class="mkp-loading">Memuat data marketplace...</div>
</div>

<script>
    const API_ORDERS = 'api/ginee-proxy.php/orders';

    function fmtRupiah(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    function fmtDate(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function fmtPhone(n) {
        return n || '-';
    }

    function badge(status) {
        const map = {
            'DELIVERED': 'delivered',
            'CANCELLED': 'cancelled',
            'RETURNED': 'returned',
            'SHIPPING': 'shipping',
            'READY_TO_SHIP': 'shipping',
            'PAID': 'paid',
            'PENDING_PAYMENT': 'pending',
            'AWAITING_SHIPMENT': 'paid',
        };
        const cls = map[status] || 'pending';
        return `<span class="mkp-badge ${cls}">${status.replace(/_/g, ' ')}</span>`;
    }

    function channelLabel(id) {
        const map = {
            'TOKOPEDIA_ID': 'Tokopedia',
            'TIKTOK_ID': 'TikTok Shop',
            'SHOPEE_ID': 'Shopee',
            'LAZADA_ID': 'Lazada',
        };
        return map[id] || id;
    }

    function orderProducts(items) {
        if (!items || items.length === 0) return '-';
        return items.map(i => `${i.productName}${i.quantity > 1 ? ' x' + i.quantity : ''}`).join('<br>');
    }

    let currentPage = 0;
    let currentStatus = '';
    let currentSize = 20;

    function loadOrders() {
        const root = document.getElementById('marketplaceRoot');
        root.innerHTML = '<div class="mkp-loading">Memuat data marketplace...</div>';

        let url = API_ORDERS + '?page=' + currentPage + '&size=' + currentSize;
        if (currentStatus) url += '&orderStatus=' + encodeURIComponent(currentStatus);

        fetch(url)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    root.innerHTML = '<div class="mkp-error">Gagal memuat data: ' + (res.message || 'Unknown error') + '</div>';
                    return;
                }
                renderOrders(res.data);
            })
            .catch(err => {
                root.innerHTML = '<div class="mkp-error">Gagal terhubung ke server Ginee API<button class="btn-retry" onclick="loadOrders()">Coba Lagi</button></div>';
            });
    }

    function renderOrders(data) {
        const orders = data.orders || [];
        const total = data.totalElements || 0;
        const pages = data.totalPages || 1;

        const root = document.getElementById('marketplaceRoot');

        let rows = '';
        if (orders.length === 0) {
            rows = '<tr class="empty"><td colspan="7">Tidak ada data pesanan</td></tr>';
        } else {
            orders.forEach(o => {
                const items = o.orderItems || [];
                rows += `<tr>
                <td style="font-family:var(--mono);font-size:0.6875rem;white-space:nowrap">${o.externalOrderId || o.id || '-'}</td>
                <td>${orderProducts(items)}</td>
                <td>${badge(o.orderStatus)}</td>
                <td>${o.paymentMethod || '-'}</td>
                <td>${channelLabel(o.channelId)}</td>
                <td>${o.customerName || '-'}<br><span style="font-size:0.6875rem;color:var(--on-surface-muted)">${fmtPhone(o.customerMobile)}</span></td>
                <td style="white-space:nowrap;font-size:0.75rem">${fmtDate(o.externalCreateDatetime)}</td>
            </tr>`;
            });
        }

        root.innerHTML = `
        <div class="mkp-header">
            <div class="mkp-filters">
                <select id="statusFilter" onchange="currentStatus=this.value; currentPage=0; loadOrders()">
                    <option value="">Semua Status</option>
                    <option value="PAID">Paid</option>
                    <option value="READY_TO_SHIP">Ready to Ship</option>
                    <option value="SHIPPING">Shipping</option>
                    <option value="DELIVERED">Delivered</option>
                    <option value="CANCELLED">Cancelled</option>
                    <option value="RETURNED">Returned</option>
                </select>
                <select id="sizeFilter" onchange="currentSize=parseInt(this.value); currentPage=0; loadOrders()">
                    <option value="20">20 per halaman</option>
                    <option value="50">50 per halaman</option>
                    <option value="100">100 per halaman</option>
                </select>
            </div>
            <div class="mkp-info">Total ${total} pesanan</div>
        </div>
        <div class="mkp-table-wrap">
            <table class="mkp-table">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Produk</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Channel</th>
                        <th>Pembeli / No. Telp</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:14px">
            <button class="btn-secondary" style="padding:6px 14px;font-size:0.8125rem" onclick="prevPage()" ${currentPage <= 0 ? 'disabled' : ''}>Sebelumnya</button>
            <span style="font-size:0.8125rem;color:var(--on-surface-muted)">Halaman ${currentPage + 1} dari ${pages}</span>
            <button class="btn-secondary" style="padding:6px 14px;font-size:0.8125rem" onclick="nextPage()" ${currentPage >= pages - 1 ? 'disabled' : ''}>Selanjutnya</button>
        </div>
    `;
    }

    function prevPage() {
        if (currentPage > 0) { currentPage--; loadOrders(); }
    }

    function nextPage() {
        currentPage++;
        loadOrders();
        // reload will update pages count
    }

    loadOrders();
</script>