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

$periode = $_GET['periode'] ?? 'hari';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* ========== LAPORAN SPECIFIC STYLES ========== */
        
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
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Buttons */
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
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .print-btn {
            background: #475569;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .print-btn:hover {
            background: #334155;
            transform: translateY(-2px);
        }
        
        .btn-detail {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-detail:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(102,126,234,0.3);
        }
        
        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .filter-btn {
            padding: 10px 25px;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 10px rgba(102,126,234,0.3);
        }
        
        .filter-btn:not(.active) {
            background: #f1f5f9;
            color: #475569;
        }
        
        .filter-btn:not(.active):hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        
        /* Stats Grid */
        .stats-grid-laporan {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card-laporan {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card-laporan:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon-laporan {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 28px;
        }
        
        .stat-icon-laporan.success { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-icon-laporan.danger { background: rgba(239,68,68,0.1); color: #ef4444; }
        .stat-icon-laporan.primary { background: rgba(67,97,238,0.1); color: #4361ee; }
        .stat-icon-laporan.warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .stat-icon-laporan.info { background: rgba(59,130,246,0.1); color: #3b82f6; }
        
        .stat-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
        }
        
        .stat-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 5px;
        }
        
        /* Card */
        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Table Styles - RAPI DAN KONSISTEN */
        .table-container {
            overflow-x: auto;
            padding: 0;
        }
        
        .laporan-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 700px;
        }
        
        .laporan-table th {
            background: #f8fafc;
            color: #1e293b;
            padding: 14px 16px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        
        .laporan-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .laporan-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .laporan-table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Transaction Number */
        .trans-no {
            font-weight: 700;
            color: #1e293b;
            font-family: monospace;
            font-size: 13px;
        }
        
        /* Amount Style */
        .amount-text {
            font-weight: 700;
            color: #10b981;
        }
        
        .amount-text-danger {
            font-weight: 700;
            color: #ef4444;
        }
        
        .amount-text-warning {
            font-weight: 700;
            color: #f59e0b;
        }
        
        /* Item Badge */
        .item-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #475569;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .item-badge.multiple {
            background: #667eea;
            color: white;
        }
        
        /* Status Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fed7aa;
            color: #92400e;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        /* Keuntungan */
        .keuntungan-positive {
            color: #10b981;
        }
        
        .keuntungan-negative {
            color: #ef4444;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .modal-close {
            cursor: pointer;
            font-size: 24px;
            color: #94a3b8;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            color: #ef4444;
            transform: rotate(90deg);
        }
        
        .modal-body { padding: 24px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Spinner */
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
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
        }
        
        /* Struk Container untuk Print */
        .struk-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            font-family: 'Courier New', monospace;
        }
        
        .struk-header {
            text-align: center;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        
        .struk-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .struk-footer {
            text-align: center;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
            margin-top: 15px;
            font-size: 11px;
            color: #666;
        }
        
        .struk-piutang {
            background: #fef3c7;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        /* Menu Toggle Mobile */
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
        
        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            backdrop-filter: blur(2px);
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }
            .main-content {
                margin-left: 0;
                padding: 80px 16px 16px;
            }
            .page-title {
                font-size: 18px;
            }
            .top-bar {
                flex-direction: column;
                text-align: center;
            }
            .stat-value {
                font-size: 20px;
            }
            .stat-icon-laporan {
                width: 45px;
                height: 45px;
                font-size: 22px;
            }
            .filter-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            .laporan-table th, .laporan-table td {
                padding: 10px 12px;
                font-size: 11px;
            }
            .card-header {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .top-bar, .filter-card, .print-btn, .no-print,
            .btn-primary, .btn-detail {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
            .stats-grid-laporan {
                page-break-inside: avoid;
            }
            .laporan-table th, .laporan-table td {
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <div class="dashboard">
        <!-- SIDEBAR MODERN - SAMA DENGAN DASHBOARD -->
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
                <a href="stok.php" class="menu-item">
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
                <a href="laporan.php" class="menu-item active">
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
                <h1 class="page-title">📄 Laporan Keuangan</h1>
                <button class="print-btn no-print" onclick="window.print()">
                    🖨️ Cetak Laporan
                </button>
            </div>
            
            <!-- Filter Periode -->
            <div class="filter-card no-print">
                <div class="filter-buttons">
                    <a href="?periode=hari" class="filter-btn <?= $periode == 'hari' ? 'active' : '' ?>" onclick="return changePeriode('hari')">📅 Hari Ini</a>
                    <a href="?periode=minggu" class="filter-btn <?= $periode == 'minggu' ? 'active' : '' ?>" onclick="return changePeriode('minggu')">📆 7 Hari</a>
                    <a href="?periode=bulan" class="filter-btn <?= $periode == 'bulan' ? 'active' : '' ?>" onclick="return changePeriode('bulan')">📆 30 Hari</a>
                </div>
            </div>
            
            <!-- Statistik Keuangan -->
            <div class="stats-grid-laporan" id="statsContainer"></div>
            
            <!-- Detail Transaksi Penjualan - TABEL RAPI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Detail Transaksi Penjualan</div>
                    <button class="btn btn-primary btn-sm no-print" onclick="loadTransaksi()">🔄 Refresh</button>
                </div>
                <div class="table-container">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Tanggal</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="transaksiListBody">
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="spinner-large"></div>
                                    <p>Memuat data...</p>
                                 \n
                             \n
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Riwayat Pengeluaran Restok Barang - TABEL RAPI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Riwayat Pengeluaran Restok Barang</div>
                    <button class="btn btn-primary btn-sm no-print" onclick="loadPengeluaranRestok()">🔄 Refresh</button>
                </div>
                <div class="table-container">
                    <table class="laporan-table">
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
                        <tbody id="restokListBody">
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="spinner-large"></div>
                                    <p>Memuat data...</p>
                                 \n
                             \n
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Riwayat Pengeluaran Operasional - TABEL RAPI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Riwayat Pengeluaran Operasional</div>
                    <button class="btn btn-primary btn-sm no-print" onclick="loadPengeluaranOperasional()">🔄 Refresh</button>
                </div>
                <div class="table-container">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah</th>
                                <th>Dicatat oleh</th>
                            </tr>
                        </thead>
                        <tbody id="operasionalListBody">
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <div class="spinner-large"></div>
                                    <p>Memuat data...</p>
                                 \n
                             \n
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail Transaksi -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🧾 Detail Transaksi</h3>
                <span class="modal-close" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div class="modal-body" id="detailContent"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('detailModal')">Tutup</button>
                <button class="btn btn-primary" onclick="printDetail()">🖨️ Cetak Struk</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        var currentDetailStruk = null;
        var periode = '<?= $periode ?>';
        
        function formatRupiah(angka) {
            if (!angka || angka === 0) return 'Rp 0';
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
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
        
        function closeSidebar() {
            var sidebar = document.querySelector('.sidebar');
            var overlay = document.querySelector('.sidebar-overlay');
            if (sidebar) {
                sidebar.classList.remove('open');
                if (overlay) {
                    overlay.classList.remove('active');
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
        
        function openModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
            }
        }
        
        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
            }
        }
        
        function changePeriode(newPeriode) {
            periode = newPeriode;
            loadLaporan();
            // Update URL tanpa reload
            var newUrl = window.location.pathname + '?periode=' + newPeriode;
            window.history.pushState({path: newUrl}, '', newUrl);
            
            // Update active class pada filter button
            document.querySelectorAll('.filter-btn').forEach(function(btn) {
                btn.classList.remove('active');
                if ((newPeriode === 'hari' && btn.innerText.includes('Hari Ini')) ||
                    (newPeriode === 'minggu' && btn.innerText.includes('7 Hari')) ||
                    (newPeriode === 'bulan' && btn.innerText.includes('30 Hari'))) {
                    btn.classList.add('active');
                }
            });
            return false;
        }
        
        // Load statistik keuangan dengan total piutang real time
        function loadStats() {
            fetch('../api/laporan.php?action=stats&periode=' + periode)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var keuntunganClass = (data.keuntungan_bersih >= 0) ? 'keuntungan-positive' : 'keuntungan-negative';
                    
                    var html = 
                        '<div class="stat-card-laporan">' +
                            '<div class="stat-icon-laporan success">💰</div>' +
                            '<div class="stat-label">Total Penjualan</div>' +
                            '<div class="stat-value">' + formatRupiah(data.total_penjualan) + '</div>' +
                            '<div class="stat-sub">' + data.jumlah_transaksi + ' transaksi</div>' +
                        '</div>' +
                        '<div class="stat-card-laporan">' +
                            '<div class="stat-icon-laporan danger">📦</div>' +
                            '<div class="stat-label">Pengeluaran Restok</div>' +
                            '<div class="stat-value" style="color:#ef4444;">' + formatRupiah(data.total_pengeluaran_restok) + '</div>' +
                            '<div class="stat-sub">' + data.jumlah_restok + ' kali restok</div>' +
                        '</div>' +
                        '<div class="stat-card-laporan">' +
                            '<div class="stat-icon-laporan warning">🛠️</div>' +
                            '<div class="stat-label">Pengeluaran Operasional</div>' +
                            '<div class="stat-value" style="color:#f59e0b;">' + formatRupiah(data.total_operasional) + '</div>' +
                            '<div class="stat-sub">' + data.jumlah_operasional + ' transaksi</div>' +
                        '</div>' +
                        '<div class="stat-card-laporan">' +
                            '<div class="stat-icon-laporan primary">📈</div>' +
                            '<div class="stat-label">Keuntungan Bersih</div>' +
                            '<div class="stat-value ' + keuntunganClass + '">' + formatRupiah(data.keuntungan_bersih) + '</div>' +
                            '<div class="stat-sub">Penjualan - (Restok + Operasional)</div>' +
                        '</div>' +
                        '<div class="stat-card-laporan">' +
                            '<div class="stat-icon-laporan info">📝</div>' +
                            '<div class="stat-label">Total Piutang Belum Lunas</div>' +
                            '<div class="stat-value" style="color:#ef4444;">' + formatRupiah(data.total_piutang) + '</div>' +
                            '<div class="stat-sub">Belum Lunas (Akumulasi)</div>' +
                        '</div>';
                    
                    document.getElementById('statsContainer').innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error loading stats:', error);
                });
        }
        
        // Load transaksi - DENGAN TABEL RAPI
        function loadTransaksi() {
            fetch('../api/laporan.php?action=detail&periode=' + periode)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('transaksiListBody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">📭 Belum ada transaksi penjualan pada periode ini<\/td><\/tr>';
                        return;
                    }
                    
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var t = data[i];
                        var statusBadge = (t.is_piutang == 1) 
                            ? '<span class="badge badge-warning">⚠️ Piutang</span>' 
                            : '<span class="badge badge-success">✅ Lunas</span>';
                        var itemBadge = (t.item_count > 1) 
                            ? '<span class="item-badge multiple">' + t.item_count + ' item</span>' 
                            : '<span class="item-badge">' + t.item_count + ' item</span>';
                        
                        html += '<tr>' +
                            '<td><span class="trans-no">#' + escapeHtml(t.no_transaksi) + '</span></td>' +
                            '<td>' + t.tanggal + '</td>' +
                            '<td>' + itemBadge + '</td>' +
                            '<td><span class="amount-text">' + formatRupiah(t.total) + '</span></td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td class="no-print"><button class="btn-detail" onclick="showDetail(' + t.id + ')">📋 Detail</button></td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error loading transaksi:', error);
                    var tbody = document.getElementById('transaksiListBody');
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="color:red;">❌ Gagal memuat data transaksi<\/td><\/tr>';
                });
        }
        
        // Load pengeluaran restok - DENGAN TABEL RAPI
        function loadPengeluaranRestok() {
            fetch('../api/laporan.php?action=pengeluaran_restok&periode=' + periode)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('restokListBody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">📭 Belum ada pengeluaran restok pada periode ini<\/td><\/tr>';
                        return;
                    }
                    
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var p = data[i];
                        html += '<tr>' +
                            '<td>' + p.tanggal + '</td>' +
                            '<td><strong>' + escapeHtml(p.nama_barang) + '</strong></td>' +
                            '<td>' + p.jumlah + ' ' + p.satuan + '</td>' +
                            '<td>' + formatRupiah(p.harga_beli) + '</td>' +
                            '<td class="amount-text-danger">' + formatRupiah(p.total) + '</td>' +
                            '<td>' + escapeHtml(p.keterangan || '-') + '</td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error loading restok:', error);
                    var tbody = document.getElementById('restokListBody');
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="color:red;">❌ Gagal memuat data pengeluaran restok<\/td><\/tr>';
                });
        }
        
        // Load pengeluaran operasional - DENGAN TABEL RAPI
        function loadPengeluaranOperasional() {
            fetch('../api/laporan.php?action=pengeluaran_operasional&periode=' + periode)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('operasionalListBody');
                    if (!data || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">📭 Belum ada pengeluaran operasional pada periode ini<\/td><\/tr>';
                        return;
                    }
                    
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var o = data[i];
                        html += '<td>' +
                            '<td>' + o.tanggal + '</td>' +
                            '<td><span class="badge badge-info">' + o.icon + ' ' + escapeHtml(o.nama_kategori) + '</span></td>' +
                            '<td>' + escapeHtml(o.deskripsi) + '</td>' +
                            '<td class="amount-text-warning">' + formatRupiah(o.jumlah) + '</td>' +
                            '<td>' + escapeHtml(o.created_by || '-') + '</td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error loading operasional:', error);
                    var tbody = document.getElementById('operasionalListBody');
                    tbody.innerHTML = '<tr><td colspan="5" class="empty-state" style="color:red;">❌ Gagal memuat data pengeluaran operasional<\/td><\/tr>';
                });
        }
        
        // Show detail transaksi
        function showDetail(id) {
            fetch('../api/transaksi.php?action=get_struk&id=' + id)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        currentDetailStruk = data.struk;
                        generateDetailHTML(data.struk);
                        openModal('detailModal');
                    } else {
                        alert('Gagal memuat detail transaksi');
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Gagal memuat detail transaksi');
                });
        }
        
        // Generate HTML detail transaksi
        function generateDetailHTML(struk) {
            var now = new Date();
            var tanggal = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            var waktu = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            var html = '<div id="strukDetailPrint" class="struk-container">' +
                '<div class="struk-header">' +
                    '<h3>🏪 ' + escapeHtml(struk.nama_toko) + '</h3>' +
                    '<p>' + tanggal + ' | ' + waktu + '</p>' +
                    '<p>================================</p>' +
                '</div>' +
                '<div><strong>No. Transaksi:</strong> #' + escapeHtml(struk.no_transaksi) + '</div>' +
                '<div><strong>Kasir:</strong> ' + escapeHtml(struk.kasir) + '</div>' +
                '<div><strong>Metode Bayar:</strong> 💵 Tunai</div>';
            
            if (struk.is_piutang) {
                html += '<div class="struk-piutang">' +
                    '<div style="font-weight:bold;">⚠️ STATUS PIUTANG</div>' +
                    '<div>Pelanggan: ' + escapeHtml(struk.pelanggan_nama || '-') + '</div>' +
                    '<div>No. WA: ' + escapeHtml(struk.no_whatsapp || '-') + '</div>' +
                    '<div>Jatuh Tempo: ' + (struk.due_date || '-') + '</div>' +
                '</div>';
            } else {
                html += '<div>Status: ✅ Lunas</div>';
            }
            
            html += '<p>================================</p>';
            
            for (var i = 0; i < struk.items.length; i++) {
                var item = struk.items[i];
                html += '<div class="struk-item">' +
                    '<span>' + escapeHtml(item.nama) + ' (' + item.qty + ' x ' + formatRupiah(item.harga) + ')</span>' +
                    '<span>' + formatRupiah(item.subtotal) + '</span>' +
                '</div>';
            }
            
            html += '<p>================================</p>' +
                '<div class="struk-item" style="font-weight:bold;">' +
                    '<span>TOTAL</span>' +
                    '<span>' + formatRupiah(struk.total) + '</span>' +
                '</div>';
            
            if (!struk.is_piutang && struk.uang_bayar > 0) {
                html += '<div class="struk-item"><span>Bayar</span><span>' + formatRupiah(struk.uang_bayar) + '</span></div>' +
                        '<div class="struk-item"><span>Kembali</span><span>' + formatRupiah(struk.kembalian) + '</span></div>';
            }
            
            html += '<div class="struk-footer">' +
                '<p>Terima kasih telah berbelanja!</p>' +
                '<p>================================</p>' +
                '<p>🏪 ' + escapeHtml(struk.nama_toko) + '</p>' +
            '</div></div>';
            
            document.getElementById('detailContent').innerHTML = html;
        }
        
        // Print detail struk
        function printDetail() {
            if (!currentDetailStruk) return;
            
            var printWindow = window.open('', '_blank');
            var now = new Date();
            var tanggal = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            var waktu = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            var printHtml = '<!DOCTYPE html>' +
                '<html>' +
                '<head>' +
                    '<title>Struk Belanja - ' + currentDetailStruk.no_transaksi + '</title>' +
                    '<meta charset="UTF-8">' +
                    '<style>' +
                        'body { font-family: \'Courier New\', monospace; padding: 20px; margin: 0; background: white; }' +
                        '.struk-container { max-width: 400px; margin: 0 auto; }' +
                        '.struk-header { text-align: center; border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin-bottom: 15px; }' +
                        '.struk-header h3 { margin: 5px 0; }' +
                        '.struk-header p { margin: 3px 0; font-size: 11px; color: #666; }' +
                        '.struk-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px; }' +
                        '.struk-footer { text-align: center; border-top: 1px dashed #ccc; padding-top: 10px; margin-top: 15px; font-size: 11px; color: #666; }' +
                        '.struk-piutang { background: #fef3c7; padding: 10px; border-radius: 8px; margin: 10px 0; }' +
                    '</style>' +
                '</head>' +
                '<body>' +
                    '<div class="struk-container">' +
                        '<div class="struk-header">' +
                            '<h3>🏪 ' + escapeHtml(currentDetailStruk.nama_toko) + '</h3>' +
                            '<p>' + tanggal + ' | ' + waktu + '</p>' +
                            '<p>================================</p>' +
                        '</div>' +
                        '<div><strong>No. Transaksi:</strong> #' + currentDetailStruk.no_transaksi + '</div>' +
                        '<div><strong>Kasir:</strong> ' + escapeHtml(currentDetailStruk.kasir) + '</div>' +
                        '<div><strong>Metode:</strong> 💵 Tunai</div>';
            
            if (currentDetailStruk.is_piutang) {
                printHtml += '<div class="struk-piutang">' +
                    '<div style="font-weight:bold;">⚠️ STATUS PIUTANG</div>' +
                    '<div>Pelanggan: ' + escapeHtml(currentDetailStruk.pelanggan_nama || '-') + '</div>' +
                    '<div>No. WA: ' + escapeHtml(currentDetailStruk.no_whatsapp || '-') + '</div>' +
                    '<div>Jatuh Tempo: ' + (currentDetailStruk.due_date || '-') + '</div>' +
                '</div>';
            } else {
                printHtml += '<div>Status: ✅ Lunas</div>';
            }
            
            printHtml += '<p>================================</p>';
            
            for (var i = 0; i < currentDetailStruk.items.length; i++) {
                var item = currentDetailStruk.items[i];
                printHtml += '<div class="struk-item">' +
                    '<span>' + escapeHtml(item.nama) + ' (' + item.qty + ' x ' + formatRupiah(item.harga) + ')</span>' +
                    '<span>' + formatRupiah(item.subtotal) + '</span>' +
                '</div>';
            }
            
            printHtml += '<p>================================</p>' +
                '<div class="struk-item" style="font-weight:bold;">' +
                    '<span>TOTAL</span>' +
                    '<span>' + formatRupiah(currentDetailStruk.total) + '</span>' +
                '</div>';
            
            if (!currentDetailStruk.is_piutang && currentDetailStruk.uang_bayar > 0) {
                printHtml += '<div class="struk-item"><span>Bayar</span><span>' + formatRupiah(currentDetailStruk.uang_bayar) + '</span></div>' +
                        '<div class="struk-item"><span>Kembali</span><span>' + formatRupiah(currentDetailStruk.kembalian) + '</span></div>';
            }
            
            printHtml += '<div class="struk-footer">' +
                '<p>Terima kasih telah berbelanja!</p>' +
                '<p>================================</p>' +
                '<p>🏪 ' + escapeHtml(currentDetailStruk.nama_toko) + '</p>' +
            '</div></div>' +
            '<script>' +
                'window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 500); };' +
            '<\/script>' +
            '</body></html>';
            
            printWindow.document.write(printHtml);
            printWindow.document.close();
        }
        
        // Load semua data laporan
        function loadLaporan() {
            loadStats();
            loadTransaksi();
            loadPengeluaranRestok();
            loadPengeluaranOperasional();
        }
        
        // Load data saat halaman dibuka
        loadLaporan();
        
        // Auto refresh setiap 30 detik (lebih sering untuk update piutang)
        setInterval(function() {
            loadLaporan();
        }, 30000);
    </script>
</body>
</html>