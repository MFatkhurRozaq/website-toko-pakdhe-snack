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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piutang - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* ========== PIUTANG SPECIFIC STYLES ========== */
        
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-icon.danger { background: rgba(239,68,68,0.1); color: #ef4444; }
        .stat-icon.success { background: rgba(16,185,129,0.1); color: #10b981; }
        
        .stat-info h3 {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
        }
        
        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
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
        
        /* Card */
        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 24px;
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
        
        .piutang-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 900px;
        }
        
        .piutang-table th {
            background: #f8fafc;
            color: #1e293b;
            padding: 14px 16px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        
        .piutang-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .piutang-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .piutang-table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Overdue Row */
        .overdue {
            background: #fee2e2 !important;
        }
        
        .overdue:hover {
            background: #fecaca !important;
        }
        
        /* Badge */
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
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Amount Style */
        .amount-text {
            font-weight: 700;
            color: #ef4444;
        }
        
        .amount-text-lunas {
            font-weight: 700;
            color: #10b981;
        }
        
        /* Transaction Number */
        .piutang-id {
            font-weight: 600;
            color: #1e293b;
            font-family: monospace;
            font-size: 13px;
        }
        
        /* Customer Name */
        .customer-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .overdue-tag {
            color: #ef4444;
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
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
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
        }
        
        .btn-wa {
            background: #25D366;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-wa:hover {
            background: #128C7E;
            transform: translateY(-1px);
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
            max-width: 400px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .form-group input:read-only {
            background: #f8fafc;
            cursor: not-allowed;
        }
        
        /* Spinner */
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
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
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
            .filter-buttons {
                justify-content: center;
            }
            .piutang-table th, .piutang-table td {
                padding: 10px 12px;
                font-size: 11px;
            }
            .stat-value {
                font-size: 22px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .card-header {
                flex-direction: column;
                text-align: center;
            }
            .btn-wa {
                padding: 4px 8px;
                font-size: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .menu-toggle, .sidebar-overlay, .top-bar .btn, .no-print,
            .btn-primary, .btn-success, .filter-buttons, .btn-wa, .btn-detail {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            .piutang-table th, .piutang-table td {
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
                <a href="piutang.php" class="menu-item active">
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
                <h1 class="page-title">📝 Manajemen Piutang</h1>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card" onclick="loadPiutang('belum')">
                    <div class="stat-icon danger">📝</div>
                    <div class="stat-info">
                        <h3>TOTAL PIUTANG BELUM LUNAS</h3>
                        <div class="stat-value" id="totalBelumLunas">Rp 0</div>
                    </div>
                </div>
                <div class="stat-card" onclick="loadPiutang('lunas')">
                    <div class="stat-icon success">✅</div>
                    <div class="stat-info">
                        <h3>TOTAL PIUTANG LUNAS</h3>
                        <div class="stat-value" id="totalLunas">Rp 0</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <button class="filter-btn active" id="btnBelum" onclick="loadPiutang('belum')">📋 Belum Lunas</button>
                <button class="filter-btn" id="btnLunas" onclick="loadPiutang('lunas')">✅ Riwayat Lunas</button>
            </div>
            
            <!-- Piutang List Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>📋</span> Daftar Piutang
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="loadPiutang(currentStatus)">🔄 Refresh</button>
                </div>
                <div class="table-container">
                    <table class="piutang-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pelanggan</th>
                                <th>No. WhatsApp</th>
                                <th>Barang</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="piutangListBody">
                            <tr>
                                <td colspan="9" class="empty-state">
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
    
    <!-- Modal Bayar Piutang -->
    <div id="bayarModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>💰 Bayar Piutang</h3>
                <span class="modal-close" onclick="closeModal('bayarModal')">&times;</span>
            </div>
            <form id="bayarForm">
                <input type="hidden" name="piutang_id" id="piutangId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Total Utang</label>
                        <input type="text" id="totalUtangDisplay" readonly style="font-size:20px;font-weight:bold;color:#10b981;">
                    </div>
                    <div class="form-group">
                        <label>Jumlah Bayar</label>
                        <input type="number" name="jumlah_bayar" id="jumlahBayar" required min="1" placeholder="Masukkan jumlah pembayaran">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('bayarModal')">Batal</button>
                    <button type="submit" class="btn btn-success" id="bayarBtn">Proses Bayar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        var currentStatus = 'belum';
        var totalBelumLunas = 0;
        var totalLunas = 0;
        
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
        
        function formatWhatsApp(no) {
            if (!no) return '';
            var cleanNo = no.replace(/\D/g, '');
            if (cleanNo.startsWith('0')) cleanNo = '62' + cleanNo.substring(1);
            if (!cleanNo.startsWith('62')) cleanNo = '62' + cleanNo;
            return cleanNo;
        }
        
        function sendWhatsApp(no, nama, total, dueDate) {
            var formattedNo = formatWhatsApp(no);
            var message = 'Halo *' + nama + '*,\n\nKami ingin mengingatkan bahwa Anda memiliki tagihan sebesar *' + formatRupiah(total) + '* yang jatuh tempo pada *' + dueDate + '*.\n\nMohon segera melakukan pembayaran. Terima kasih.\n\n*' + (document.querySelector('.sidebar-header h3')?.innerText || 'Pakdhe Snack') + '*';
            window.open('https://wa.me/' + formattedNo + '?text=' + encodeURIComponent(message), '_blank');
        }
        
        // Load total piutang - DIPERBAIKI DENGAN MENJUMLAHKAN SEMUA DATA
        function loadTotalPiutang() {
            // Ambil total belum lunas (menjumlahkan semua total_utang)
            fetch('../api/piutang.php?action=list&status=belum')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    totalBelumLunas = 0;
                    // Looping untuk menjumlahkan semua total_utang
                    for (var i = 0; i < data.length; i++) {
                        totalBelumLunas += parseFloat(data[i].total_utang);
                    }
                    document.getElementById('totalBelumLunas').innerHTML = formatRupiah(totalBelumLunas);
                })
                .catch(function(error) { 
                    console.error('Error:', error);
                    document.getElementById('totalBelumLunas').innerHTML = 'Rp 0';
                });
            
            // Ambil total lunas (menjumlahkan semua total_utang)
            fetch('../api/piutang.php?action=list&status=lunas')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    totalLunas = 0;
                    // Looping untuk menjumlahkan semua total_utang
                    for (var i = 0; i < data.length; i++) {
                        totalLunas += parseFloat(data[i].total_utang);
                    }
                    document.getElementById('totalLunas').innerHTML = formatRupiah(totalLunas);
                })
                .catch(function(error) { 
                    console.error('Error:', error);
                    document.getElementById('totalLunas').innerHTML = 'Rp 0';
                });
        }
        
        // Load piutang list - DENGAN TABEL RAPI
        function loadPiutang(status) {
            currentStatus = status;
            
            // Update active button
            var btnBelum = document.getElementById('btnBelum');
            var btnLunas = document.getElementById('btnLunas');
            if (status === 'belum') {
                btnBelum.classList.add('active');
                btnLunas.classList.remove('active');
            } else {
                btnBelum.classList.remove('active');
                btnLunas.classList.add('active');
            }
            
            fetch('../api/piutang.php?action=list&status=' + status)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('piutangListBody');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">📭 Tidak ada data piutang</td><\/tr>';
                        return;
                    }
                    
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var p = data[i];
                        var dueDate = new Date(p.due_date);
                        var isOverdue = (dueDate < today && p.status === 'belum');
                        
                        var rowClass = isOverdue ? 'overdue' : '';
                        var overdueTag = isOverdue ? '<div class="overdue-tag">⚠️ Lewat Jatuh Tempo</div>' : '';
                        
                        var whatsappColumn = '-';
                        if (p.no_whatsapp && p.status === 'belum') {
                            whatsappColumn = '<div style="display:flex;flex-direction:column;gap:5px;">' +
                                '<span style="font-size:12px;color:#64748b;">' + escapeHtml(p.no_whatsapp) + '</span>' +
                                '<button class="btn-wa" onclick="sendWhatsApp(\'' + p.no_whatsapp + '\', \'' + escapeHtml(p.pelanggan_nama) + '\', ' + p.total_utang + ', \'' + p.due_date + '\')">📱 WhatsApp</button>' +
                            '</div>';
                        } else if (p.no_whatsapp) {
                            whatsappColumn = '<span style="font-size:12px;color:#64748b;">' + escapeHtml(p.no_whatsapp) + '</span>';
                        }
                        
                        var statusBadge = (p.status === 'lunas') 
                            ? '<span class="badge badge-success">✅ Lunas</span>' 
                            : '<span class="badge badge-danger">⚠️ Belum</span>';
                        
                        var amountClass = (p.status === 'lunas') ? 'amount-text-lunas' : 'amount-text';
                        
                        var actionButton = (p.status === 'belum') 
                            ? '<button class="btn btn-success btn-sm" onclick="showBayar(' + p.id + ', ' + p.total_utang + ')">💰 Bayar</button>' 
                            : '-';
                        
                        html += '<tr class="' + rowClass + '">' +
                            '<td><span class="piutang-id">#' + p.id + '</span></td>' +
                            '<td>' +
                                '<div class="customer-name">' + escapeHtml(p.pelanggan_nama) + '</div>' +
                                overdueTag +
                            '</td>' +
                            '<td>' + whatsappColumn + '</td>' +
                            '<td>' + escapeHtml(p.nama_barang) + '</td>' +
                            '<td>' + p.qty + '</td>' +
                            '<td><span class="' + amountClass + '">' + formatRupiah(p.total_utang) + '</span></td>' +
                            '<td style="' + (isOverdue ? 'color:#ef4444;font-weight:bold;' : 'color:#64748b;') + '">' + 
                                '<span>' + p.due_date + '</span>' + 
                                (isOverdue ? ' <span style="color:#ef4444;">⚠️</span>' : '') + 
                            '</td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td>' + actionButton + '</td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    var tbody = document.getElementById('piutangListBody');
                    tbody.innerHTML = '<tr><td colspan="9" class="empty-state" style="color:red;">❌ Gagal memuat data piutang</td><\/tr>';
                });
        }
        
        function showBayar(id, total) {
            document.getElementById('piutangId').value = id;
            document.getElementById('totalUtangDisplay').value = formatRupiah(total);
            document.getElementById('jumlahBayar').value = total;
            openModal('bayarModal');
        }
        
        document.getElementById('bayarForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var bayarBtn = document.getElementById('bayarBtn');
            var originalText = bayarBtn.innerHTML;
            
            bayarBtn.disabled = true;
            bayarBtn.innerHTML = '<span class="spinner"></span> Memproses...';
            
            fetch('../api/piutang.php?action=bayar', { method: 'POST', body: formData })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    bayarBtn.disabled = false;
                    bayarBtn.innerHTML = originalText;
                    if (data.success) {
                        alert('✅ ' + data.message);
                        closeModal('bayarModal');
                        // Refresh total dan daftar setelah pembayaran
                        loadTotalPiutang();
                        loadPiutang(currentStatus);
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(function(error) {
                    bayarBtn.disabled = false;
                    bayarBtn.innerHTML = originalText;
                    alert('❌ Error: ' + error.message);
                });
        });
        
        // Load data saat halaman dibuka
        loadTotalPiutang();
        loadPiutang('belum');
    </script>
</body>
</html>