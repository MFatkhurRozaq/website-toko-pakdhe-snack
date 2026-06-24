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

// Ambil kategori operasional
$kategori = $pdo->query("SELECT * FROM kategori_operasional ORDER BY nama_kategori")->fetchAll();

// Generate option tahun dari 2020 sampai tahun depan
$currentYear = date('Y');
$years = range(2020, $currentYear + 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Operasional - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* ========== OPERASIONAL SPECIFIC STYLES ========== */
        
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
        
        .btn-edit-op {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            margin-right: 5px;
            transition: all 0.2s;
        }
        
        .btn-edit-op:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .btn-delete-op {
            background: #ef4444;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-delete-op:hover {
            background: #dc2626;
            transform: translateY(-1px);
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
        
        /* Info Box */
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        
        /* Stats Grid */
        .stats-grid-operasional {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card-operasional {
            background: white;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card-operasional:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon-operasional {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .stat-label-operasional {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .stat-value-operasional {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Total Operasional Card */
        .total-operasional-card {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(239,68,68,0.2);
        }
        
        .total-operasional-card .value {
            font-size: 36px;
            font-weight: 800;
        }
        
        /* Filter */
        .filter-operasional {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .filter-operasional select, 
        .filter-operasional input {
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .filter-operasional select:focus, 
        .filter-operasional input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
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
        
        /* Badge Kategori */
        .badge-kategori {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #e0e7ff;
            color: #1e40af;
        }
        
        /* Amount Style */
        .amount-text {
            font-weight: 700;
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
            font-family: inherit;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
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
            .total-operasional-card .value {
                font-size: 24px;
            }
            .stats-grid-operasional {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-operasional {
                flex-direction: column;
                align-items: stretch;
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
        
        @media (max-width: 480px) {
            .stats-grid-operasional {
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
            .btn-primary, .btn-outline, .filter-operasional, .btn-edit-op, .btn-delete-op {
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
                <a href="operasional.php" class="menu-item active">
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
                <h1 class="page-title">🛠️ Manajemen Operasional</h1>
                <button class="btn btn-primary" onclick="openModal('tambahModal')">
                    + Tambah Pengeluaran
                </button>
            </div>
            
            <!-- Info Box -->
            <div class="info-box">
                <div class="title">ℹ️ Informasi Pengeluaran Operasional</div>
                <div class="content">Catat semua pengeluaran operasional seperti listrik, air, internet, sewa, gaji karyawan, dan lain-lain. Data akan masuk ke laporan keuangan.</div>
            </div>
            
            <!-- Total Pengeluaran Bulan Ini -->
            <div class="total-operasional-card" id="totalPerBulan">
                <div class="label">💰 Total Pengeluaran Operasional Bulan Ini</div>
                <div class="value" id="totalValue">Rp 0</div>
            </div>
            
            <!-- Statistik per Kategori -->
            <div class="stats-grid-operasional" id="statsBulanan"></div>
            
            <!-- Form Filter - REAL TIME TAHUN -->
            <div class="filter-operasional">
                <select id="filterTahun">
                    <?php foreach($years as $year): ?>
                        <option value="<?= $year ?>" <?= $year == $currentYear ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterBulan">
                    <option value="01" <?= date('m') == '01' ? 'selected' : '' ?>>Januari</option>
                    <option value="02" <?= date('m') == '02' ? 'selected' : '' ?>>Februari</option>
                    <option value="03" <?= date('m') == '03' ? 'selected' : '' ?>>Maret</option>
                    <option value="04" <?= date('m') == '04' ? 'selected' : '' ?>>April</option>
                    <option value="05" <?= date('m') == '05' ? 'selected' : '' ?>>Mei</option>
                    <option value="06" <?= date('m') == '06' ? 'selected' : '' ?>>Juni</option>
                    <option value="07" <?= date('m') == '07' ? 'selected' : '' ?>>Juli</option>
                    <option value="08" <?= date('m') == '08' ? 'selected' : '' ?>>Agustus</option>
                    <option value="09" <?= date('m') == '09' ? 'selected' : '' ?>>September</option>
                    <option value="10" <?= date('m') == '10' ? 'selected' : '' ?>>Oktober</option>
                    <option value="11" <?= date('m') == '11' ? 'selected' : '' ?>>November</option>
                    <option value="12" <?= date('m') == '12' ? 'selected' : '' ?>>Desember</option>
                </select>
                <select id="filterKategori">
                    <option value="">Semua Kategori</option>
                    <?php foreach($kategori as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= $k['icon'] ?> <?= $k['nama_kategori'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadAllData()">Filter</button>
                <button class="btn btn-outline btn-sm" onclick="resetFilter()">Reset</button>
            </div>
            
            <!-- Daftar Pengeluaran - TABEL RAPI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Riwayat Pengeluaran Operasional</div>
                    <button class="btn btn-primary btn-sm" onclick="loadAllData()">🔄 Refresh</button>
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
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="operasionalListBody">
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
        </div>
    </div>
    
    <!-- Modal Tambah Pengeluaran -->
    <div id="tambahModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Tambah Pengeluaran Operasional</h3>
                <span class="modal-close" onclick="closeModal('tambahModal')">&times;</span>
            </div>
            <form id="tambahForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori_id" id="kategori_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= $k['icon'] ?> <?= $k['nama_kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" rows="3" placeholder="Contoh: Pembayaran listrik bulan April" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="jumlah" id="jumlah" min="0" required placeholder="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('tambahModal')">Batal</button>
                    <button type="submit" class="btn btn-primary" id="simpanBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Pengeluaran -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit Pengeluaran Operasional</h3>
                <span class="modal-close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form id="editForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" id="edit_tanggal" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori_id" id="edit_kategori_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= $k['icon'] ?> <?= $k['nama_kategori'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="jumlah" id="edit_jumlah" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary" id="updateBtn">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
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
        
        function getSelectedYearMonth() {
            var tahun = document.getElementById('filterTahun').value;
            var bulan = document.getElementById('filterBulan').value;
            return tahun + '-' + bulan;
        }
        
        function formatBulanYear(yearMonth) {
            var parts = yearMonth.split('-');
            var tahun = parts[0];
            var bulan = parts[1];
            var namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return namaBulan[parseInt(bulan) - 1] + ' ' + tahun;
        }
        
        function loadTotalPerBulan() {
            var yearMonth = getSelectedYearMonth();
            fetch('../api/operasional.php?action=total_per_bulan&bulan=' + yearMonth)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    document.getElementById('totalValue').innerHTML = formatRupiah(data.total);
                    document.querySelector('#totalPerBulan .label').innerHTML = '💰 Total Pengeluaran Operasional - ' + formatBulanYear(yearMonth);
                })
                .catch(function(error) { console.error('Error:', error); });
        }
        
        function loadStatsBulanan() {
            var yearMonth = getSelectedYearMonth();
            fetch('../api/operasional.php?action=stats_bulanan&bulan=' + yearMonth)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var container = document.getElementById('statsBulanan');
                    var filteredData = [];
                    for (var i = 0; i < data.length; i++) {
                        if (data[i].total > 0) {
                            filteredData.push(data[i]);
                        }
                    }
                    if (filteredData.length === 0) {
                        container.innerHTML = '<div class="stat-card-operasional" style="grid-column:1/-1; text-align:center;">Belum ada data pengeluaran bulan ini</div>';
                        return;
                    }
                    var html = '';
                    for (var i = 0; i < filteredData.length; i++) {
                        var s = filteredData[i];
                        html += '<div class="stat-card-operasional" onclick="filterByKategori(' + s.id + ')">' +
                            '<div class="stat-icon-operasional">' + s.icon + '</div>' +
                            '<div class="stat-label-operasional">' + escapeHtml(s.nama_kategori) + '</div>' +
                            '<div class="stat-value-operasional">' + formatRupiah(s.total) + '</div>' +
                        '</div>';
                    }
                    container.innerHTML = html;
                })
                .catch(function(error) { console.error('Error:', error); });
        }
        
        // Load operasional list - DENGAN TABEL RAPI
        function loadOperasionalList() {
            var yearMonth = getSelectedYearMonth();
            var kategori = document.getElementById('filterKategori').value;
            
            var url = '../api/operasional.php?action=list&bulan=' + yearMonth;
            if (kategori) {
                url += '&kategori=' + kategori;
            }
            
            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('operasionalListBody');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">📭 Belum ada pengeluaran operasional pada periode ini</td><\/tr>';
                        return;
                    }
                    
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var o = data[i];
                        html += '<tr>' +
                            '<td>' + o.tanggal + '</td>' +
                            '<td><span class="badge-kategori">' + o.icon + ' ' + escapeHtml(o.nama_kategori) + '</span></td>' +
                            '<td>' + escapeHtml(o.deskripsi) + '</td>' +
                            '<td class="amount-text">' + formatRupiah(o.jumlah) + '</td>' +
                            '<td>' + escapeHtml(o.created_by || '-') + '</td>' +
                            '<td>' +
                                '<button class="btn-edit-op" onclick="editOperasional(' + o.id + ')">✏️ Edit</button>' +
                                '<button class="btn-delete-op" onclick="deleteOperasional(' + o.id + ')">🗑️ Hapus</button>' +
                            '</td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    var tbody = document.getElementById('operasionalListBody');
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="color:red;">❌ Gagal memuat data pengeluaran operasional</td><\/tr>';
                });
        }
        
        function filterByKategori(kategoriId) {
            document.getElementById('filterKategori').value = kategoriId;
            loadAllData();
        }
        
        function resetFilter() {
            document.getElementById('filterKategori').value = '';
            document.getElementById('filterTahun').value = '<?= $currentYear ?>';
            document.getElementById('filterBulan').value = '<?= date('m') ?>';
            loadAllData();
        }
        
        function loadAllData() {
            loadTotalPerBulan();
            loadStatsBulanan();
            loadOperasionalList();
        }
        
        function editOperasional(id) {
            fetch('../api/operasional.php?action=get&id=' + id)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.data.id;
                        document.getElementById('edit_tanggal').value = data.data.tanggal;
                        document.getElementById('edit_kategori_id').value = data.data.kategori_id;
                        document.getElementById('edit_deskripsi').value = data.data.deskripsi;
                        document.getElementById('edit_jumlah').value = data.data.jumlah;
                        openModal('editModal');
                    } else {
                        alert('Gagal memuat data');
                    }
                });
        }
        
        function deleteOperasional(id) {
            if (confirm('⚠️ Yakin ingin menghapus pengeluaran ini?')) {
                fetch('../api/operasional.php?action=delete&id=' + id)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            alert('✅ ' + data.message);
                            loadAllData();
                        } else {
                            alert('❌ ' + data.message);
                        }
                    });
            }
        }
        
        document.getElementById('tambahForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var jumlah = parseInt(document.getElementById('jumlah').value);
            if (jumlah <= 0) {
                alert('Jumlah harus lebih dari 0!');
                return;
            }
            
            var submitBtn = document.getElementById('simpanBtn');
            var originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Menyimpan...';
            
            fetch('../api/operasional.php?action=tambah', { method: 'POST', body: new FormData(this) })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (data.success) {
                        alert('✅ ' + data.message);
                        closeModal('tambahModal');
                        document.getElementById('tambahForm').reset();
                        document.getElementById('tanggal').value = new Date().toISOString().split('T')[0];
                        loadAllData();
                    } else {
                        alert('❌ ' + data.message);
                    }
                });
        });
        
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var jumlah = parseInt(document.getElementById('edit_jumlah').value);
            if (jumlah <= 0) {
                alert('Jumlah harus lebih dari 0!');
                return;
            }
            
            var submitBtn = document.getElementById('updateBtn');
            var originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Mengupdate...';
            
            fetch('../api/operasional.php?action=update', { method: 'POST', body: new FormData(this) })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (data.success) {
                        alert('✅ ' + data.message);
                        closeModal('editModal');
                        loadAllData();
                    } else {
                        alert('❌ ' + data.message);
                    }
                });
        });
        
        document.getElementById('filterTahun').addEventListener('change', loadAllData);
        document.getElementById('filterBulan').addEventListener('change', loadAllData);
        document.getElementById('filterKategori').addEventListener('change', loadAllData);
        
        // Load data saat halaman dibuka
        loadAllData();
    </script>
</body>
</html>