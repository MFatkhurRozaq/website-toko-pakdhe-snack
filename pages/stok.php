<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
if (!$setting) {
    $pdo->query("INSERT INTO pengaturan_toko (nama_toko, promo_text, whatsapp_number) VALUES ('Pakdhe Snack', '', '')");
    $setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
}

// Ambil data user untuk menampilkan nama
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];

$barang = $pdo->query("SELECT * FROM barang ORDER BY nama_barang")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 20px;
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Button */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        /* Card */
        .card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fed7aa; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        
        /* Info Box */
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .info-box .title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .info-box .content {
            font-size: 13px;
            color: #475569;
        }
        
        /* Total Pengeluaran */
        .total-pengeluaran {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px 15px;
            margin-top: 10px;
        }
        .total-pengeluaran .value {
            font-size: 20px;
            font-weight: bold;
            color: #dc2626;
        }
        
        /* Button Restok */
        .btn-restok {
            background: #10b981;
            color: white;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .btn-restok:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        th {
            background: #f8fafc;
            color: #1e293b;
            padding: 12px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        /* Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 101;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            font-size: 24px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .spinner-large {
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top: 3px solid #4361ee;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 80px 16px 16px;
            }
            .menu-toggle {
                display: flex;
            }
            .page-title {
                font-size: 18px;
            }
            .top-bar {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
            th, td {
                padding: 8px;
                font-size: 11px;
            }
        }
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="dashboard">
        <!-- SIDEBAR MODERN DENGAN SPACING LEGA -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🏪</div>
                <h3><?= htmlspecialchars($setting['nama_toko']) ?></h3>
                <p><?= htmlspecialchars($display_name) ?></p>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <span class="menu-icon">📊</span>
                    <span class="menu-text">Dashboard</span>
                </a>
                <a href="barang.php" class="menu-item">
                    <span class="menu-icon">📦</span>
                    <span class="menu-text">Manajemen Barang</span>
                </a>
                <a href="stok.php" class="menu-item active">
                    <span class="menu-icon">📈</span>
                    <span class="menu-text">Manajemen Stok</span>
                </a>
                <a href="transaksi.php" class="menu-item">
                    <span class="menu-icon">💰</span>
                    <span class="menu-text">Transaksi</span>
                </a>
                <a href="piutang.php" class="menu-item">
                    <span class="menu-icon">📝</span>
                    <span class="menu-text">Piutang</span>
                </a>
                <a href="operasional.php" class="menu-item">
                    <span class="menu-icon">🛠️</span>
                    <span class="menu-text">Manajemen Operasional</span>
                </a>
                <a href="laporan.php" class="menu-item">
                    <span class="menu-icon">📄</span>
                    <span class="menu-text">Laporan</span>
                </a>
                <a href="setting.php" class="menu-item">
                    <span class="menu-icon">⚙️</span>
                    <span class="menu-text">Pengaturan</span>
                </a>
                <a href="logout.php" class="menu-item logout">
                    <span class="menu-icon">🚪</span>
                    <span class="menu-text">Logout</span>
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1 class="page-title">📈 Manajemen Stok</h1>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <div class="title">ℹ️ Informasi Restok Barang</div>
                <div class="content">Setiap pembelian stok (restok) akan dicatat sebagai pengeluaran dan akan mempengaruhi laporan keuangan. Pastikan harga beli diisi dengan benar.</div>
            </div>
            
            <!-- Form Tambah Stok -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📦 Restok Barang (Pembelian Stok)</div>
                </div>
                <form id="tambahStokForm">
                    <div class="form-group">
                        <label>Pilih Barang</label>
                        <select name="barang_id" id="barang_id" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach($barang as $b): ?>
                            <option value="<?= $b['id'] ?>" data-stok="<?= $b['stok'] ?>" data-minimal="<?= $b['stok_minimal'] ?>" data-satuan="<?= $b['satuan'] ?>">
                                <?= htmlspecialchars($b['nama_barang']) ?> (Stok: <?= $b['stok'] ?> <?= $b['satuan'] ?>, Minimal: <?= $b['stok_minimal'] ?> <?= $b['satuan'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Tambah</label>
                        <input type="number" name="qty" id="qty" min="1" required oninput="hitungTotal()">
                        <small id="rekomendasi" style="color: #f59e0b; display: none;"></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Harga Beli per Unit</label>
                        <input type="number" name="harga_beli" id="harga_beli" required oninput="hitungTotal()">
                    </div>
                    
                    <div class="total-pengeluaran">
                        <div class="label">💰 Total Pengeluaran</div>
                        <div class="value" id="total_display">Rp 0</div>
                        <input type="hidden" name="total" id="total_hidden" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="keterangan" rows="2" placeholder="Contoh: Pembelian dari distributor, nota #123"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-restok" id="tambahBtn">
                        💰 Restok Barang
                    </button>
                </form>
            </div>
            
            <!-- Rekomendasi Belanja -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📊 Rekomendasi Belanja</div>
                </div>
                <div class="table-container">
                    <table width="100%">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Stok</th>
                                <th>Minimal</th>
                                <th>Terjual (30 hari)</th>
                                <th>Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody id="rekomendasiList"></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Riwayat Pengeluaran Restok -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Riwayat Pengeluaran Restok</div>
                    <button class="btn btn-primary btn-sm" onclick="loadPengeluaran()">🔄 Refresh</button>
                </div>
                <div class="table-container">
                    <table width="100%">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Harga Beli</th>
                                <th>Total</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="pengeluaranList"></tbody>
                    </table>
                </div>
            </div>
            
            <!-- Riwayat Log Stok -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Riwayat Log Stok</div>
                </div>
                <div class="table-container">
                    <table width="100%">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Barang</th>
                                <th>Tipe</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="logList"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function toggleSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.querySelector('.sidebar-overlay');
        if (sidebar) {
            sidebar.classList.toggle('open');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        }
    }

    // Tutup sidebar saat klik di luar (mobile)
        document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            var sidebar = document.querySelector('.sidebar');
            var toggle = document.querySelector('.menu-toggle');
            var overlay = document.querySelector('.sidebar-overlay');
            if (sidebar && toggle && overlay && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        }
    });
        
        function formatRupiah(angka) {
            if (!angka || angka === 0) return 'Rp 0';
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function hitungTotal() {
            const qty = parseInt(document.getElementById('qty').value) || 0;
            const harga = parseInt(document.getElementById('harga_beli').value) || 0;
            const total = qty * harga;
            document.getElementById('total_display').innerHTML = formatRupiah(total);
            document.getElementById('total_hidden').value = total;
            
            // Rekomendasi jumlah
            const select = document.getElementById('barang_id');
            const opt = select.options[select.selectedIndex];
            const stok = parseInt(opt.getAttribute('data-stok')) || 0;
            const minimal = parseInt(opt.getAttribute('data-minimal')) || 0;
            
            if (stok <= minimal && qty > 0) {
                const rekomendasi = minimal * 2;
                if (qty < rekomendasi) {
                    document.getElementById('rekomendasi').innerHTML = `💡 Rekomendasi: beli ${rekomendasi - stok} agar stok aman`;
                    document.getElementById('rekomendasi').style.display = 'block';
                } else {
                    document.getElementById('rekomendasi').style.display = 'none';
                }
            } else {
                document.getElementById('rekomendasi').style.display = 'none';
            }
        }
        
        function loadRekomendasi() {
            fetch('../api/stok.php?action=rekomendasi')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('rekomendasiList');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Tidak ada rekomendasi</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(b => `
                        <tr>
                            <td><strong>${escapeHtml(b.nama_barang)}</strong></td>
                            <td style="${b.stok <= b.stok_minimal ? 'color:#ef4444;font-weight:bold' : ''}">${b.stok} ${b.satuan}</td>
                            <td>${b.stok_minimal} ${b.satuan}</td>
                            <td>${b.terjual}</td>
                            <td><span class="badge ${b.stok <= b.stok_minimal ? 'badge-danger' : 'badge-warning'}">${b.stok <= b.stok_minimal ? '⚠️ Segera Belanja!' : '📦 Persiapkan'}</span></td>
                        </tr>
                    `).join('');
                });
        }
        
        function loadPengeluaran() {
            fetch('../api/stok.php?action=pengeluaran')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('pengeluaranList');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Belum ada pengeluaran restok</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(p => `
                        <tr>
                            <td>${p.tanggal}</td>
                            <td><strong>${escapeHtml(p.nama_barang)}</strong></td>
                            <td>${p.jumlah} ${p.satuan}</td>
                            <td>${formatRupiah(p.harga_beli)}</td>
                            <td style="color:#ef4444;font-weight:bold;">${formatRupiah(p.total)}</td>
                            <td>${escapeHtml(p.keterangan || '-')}</td>
                        </tr>
                    `).join('');
                });
        }
        
        function loadLog() {
            fetch('../api/stok.php?action=log')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('logList');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada log</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(l => `
                        <tr>
                            <td>${l.created_at}</td>
                            <td>${escapeHtml(l.nama_barang)}</td>
                            <td><span class="badge ${l.tipe === 'masuk' ? 'badge-success' : 'badge-danger'}">${l.tipe === 'masuk' ? '📦 Masuk' : '💰 Keluar'}</span></td>
                            <td>${l.qty}</td>
                            <td>${escapeHtml(l.keterangan || '-')}</td>
                        </tr>
                    `).join('');
                });
        }
        
        document.getElementById('tambahStokForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const barangSelect = document.getElementById('barang_id');
            const barangId = barangSelect.value;
            const qty = document.getElementById('qty').value;
            const hargaBeli = document.getElementById('harga_beli').value;
            const total = document.getElementById('total_hidden').value;
            
            if (!barangId) {
                alert('Pilih barang terlebih dahulu!');
                return;
            }
            
            if (qty <= 0) {
                alert('Jumlah harus lebih dari 0!');
                return;
            }
            
            if (hargaBeli <= 0) {
                alert('Harga beli harus diisi!');
                return;
            }
            
            if (confirm(`⚠️ Konfirmasi Restok\n\nTotal Pengeluaran: ${formatRupiah(total)}\n\nCatatan: Pengeluaran ini akan tercatat di laporan keuangan.`)) {
                const submitBtn = document.getElementById('tambahBtn');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Memproses...';
                
                const formData = new FormData(this);
                
                fetch('../api/stok.php?action=tambah', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        if (data.success) {
                            alert('✅ ' + data.message);
                            document.getElementById('tambahStokForm').reset();
                            document.getElementById('total_display').innerHTML = 'Rp 0';
                            document.getElementById('total_hidden').value = '0';
                            document.getElementById('rekomendasi').style.display = 'none';
                            loadRekomendasi();
                            loadPengeluaran();
                            loadLog();
                        } else {
                            alert('❌ ' + data.message);
                        }
                    });
            }
        });
        
        document.getElementById('barang_id').addEventListener('change', hitungTotal);
        document.getElementById('qty').addEventListener('input', hitungTotal);
        document.getElementById('harga_beli').addEventListener('input', hitungTotal);
        
        loadRekomendasi();
        loadPengeluaran();
        loadLog();
    </script>
</body>
</html>