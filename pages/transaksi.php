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

$barang = $pdo->query("SELECT b.*, hj.harga_jual FROM barang b LEFT JOIN harga_jual_aktif hj ON b.id = hj.barang_id ORDER BY b.nama_barang")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* ========== TRANSACTION SPECIFIC STYLES ========== */
        
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
        
        .stat-icon.primary { background: rgba(67,97,238,0.1); color: #4361ee; }
        
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
        
        .stat-trend {
            font-size: 11px;
            color: #10b981;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Cart Container */
        .cart-container {
            background: white;
            border-radius: 20px;
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .cart-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }
        
        .cart-items {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .cart-item-info { flex: 2; }
        .cart-item-name { font-weight: 600; color: #1e293b; }
        .cart-item-price { font-size: 12px; color: #64748b; }
        .cart-item-qty { width: 80px; text-align: center; }
        .cart-item-qty input { width: 60px; padding: 6px; text-align: center; border: 1px solid #e2e8f0; border-radius: 8px; }
        .cart-item-subtotal { width: 100px; text-align: right; font-weight: 700; color: #10b981; }
        .cart-item-remove { width: 40px; text-align: center; }
        
        .remove-btn {
            background: #fee2e2;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            cursor: pointer;
            color: #ef4444;
            transition: all 0.2s;
        }
        
        .remove-btn:hover {
            background: #ef4444;
            color: white;
        }
        
        .cart-footer {
            background: #f8fafc;
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-total-value {
            color: #10b981;
            font-size: 24px;
            font-weight: 800;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
        
        /* Payment Modern */
        .payment-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(102,126,234,0.2);
        }
        
        .total-modern {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
        }
        
        .total-modern .amount {
            font-size: 48px;
            font-weight: 800;
            color: white;
        }
        
        .payment-input-area {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .payment-input-label {
            color: rgba(255,255,255,0.9);
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .money-input-modern {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 5px 20px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .money-input-modern:focus-within {
            border-color: white;
            background: rgba(255,255,255,0.25);
        }
        
        .currency-modern {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-right: 10px;
        }
        
        .money-input-modern input {
            flex: 1;
            padding: 16px 0;
            font-size: 28px;
            font-weight: 700;
            border: none;
            background: transparent;
            color: white;
            text-align: right;
            outline: none;
        }
        
        .money-input-modern input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .quick-amounts-modern {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .quick-btn-modern {
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            color: white;
            transition: all 0.2s;
        }
        
        .quick-btn-modern:hover {
            background: rgba(255,255,255,0.35);
            transform: translateY(-2px);
        }
        
        .quick-btn-modern.exact {
            background: rgba(16,185,129,0.8);
        }
        
        .change-modern {
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            animation: fadeInUp 0.4s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .change-modern.success { background: rgba(16,185,129,0.9); color: white; }
        .change-modern.error { background: rgba(239,68,68,0.9); color: white; }
        .change-modern.warning { background: rgba(245,158,11,0.9); color: white; }
        
        .change-modern .change-amount {
            font-size: 36px;
            font-weight: 800;
        }
        
        /* Piutang Modern */
        .piutang-modern {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .piutang-modern.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .piutang-detail {
            background: #f8fafc;
            border-radius: 20px;
            padding: 20px;
            margin-top: 15px;
            border-left: 4px solid #f59e0b;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
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
        
        .btn-add-cart {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: all 0.2s;
        }
        
        .btn-add-cart:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-transaksi {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            margin-bottom: 25px;
        }
        
        .btn-transaksi:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16,185,129,0.3);
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
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            padding: 0;
        }
        
        .transaksi-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 650px;
        }
        
        .transaksi-table th {
            background: #f8fafc;
            color: #1e293b;
            padding: 14px 16px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        
        .transaksi-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .transaksi-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .transaksi-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .trans-no {
            font-weight: 700;
            color: #1e293b;
            font-family: monospace;
            font-size: 13px;
        }
        
        .amount-text {
            font-weight: 700;
            color: #10b981;
        }
        
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
        
        /* Struk Container */
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
            .total-modern .amount {
                font-size: 32px;
            }
            .money-input-modern input {
                font-size: 20px;
            }
            .cart-item {
                flex-wrap: wrap;
                gap: 10px;
            }
            .cart-item-info {
                flex: 100%;
            }
            .transaksi-table th, .transaksi-table td {
                padding: 10px 12px;
                font-size: 11px;
            }
            .stat-value {
                font-size: 22px;
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
            .sidebar, .menu-toggle, .sidebar-overlay, .top-bar .btn, .no-print,
            .btn-primary, .btn-success, .btn-add-cart, .btn-transaksi,
            .quick-amounts-modern, .cart-item-remove, .btn-detail {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .payment-modern {
                background: #f0f0f0 !important;
            }
            .total-modern .amount {
                color: #333 !important;
            }
            .transaksi-table th, .transaksi-table td {
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <div class="dashboard">
        <!-- SIDEBAR -->
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
                <a href="transaksi.php" class="menu-item active">
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
                <h1 class="page-title">💰 Transaksi Penjualan</h1>
            </div>
            
            <!-- Stats Card Total Hari Ini - MENAMPILKAN TOTAL KESELURUHAN -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">💰</div>
                    <div class="stat-info">
                        <h3>TOTAL PENJUALAN HARI INI</h3>
                        <div class="stat-value" id="totalHariIni">Rp 0</div>
                        <div class="stat-trend" id="totalTransaksiHariIni">0 transaksi</div>
                    </div>
                </div>
            </div>
            
            <!-- Form Tambah Barang ke Keranjang -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🛒 Tambah Barang ke Keranjang</div>
                </div>
                <div style="padding: 0 24px 24px 24px;">
                    <div class="form-group">
                        <label>📦 Pilih Barang</label>
                        <select id="selectBarang" style="width:100%;padding:12px;border-radius:12px;border:2px solid #e2e8f0;">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach($barang as $b): ?>
                            <option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_jual'] ?>" data-stok="<?= $b['stok'] ?>" data-nama="<?= htmlspecialchars($b['nama_barang']) ?>" data-satuan="<?= $b['satuan'] ?>">
                                <?= htmlspecialchars($b['nama_barang']) ?> - Stok: <?= $b['stok'] ?> <?= $b['satuan'] ?> - <?= formatRupiah($b['harga_jual']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>🔢 Jumlah</label>
                        <input type="number" id="jumlahBarang" min="1" value="1" style="width:100%;padding:12px;border-radius:12px;border:2px solid #e2e8f0;">
                    </div>
                    <button type="button" class="btn-add-cart" onclick="tambahKeKeranjang()">+ Tambahkan ke Keranjang</button>
                </div>
            </div>
            
            <!-- Keranjang Belanja -->
            <div class="cart-container">
                <div class="cart-header">
                    <span>🛒 Keranjang Belanja</span>
                    <span id="cartCount">0 item</span>
                </div>
                <div id="cartItems" class="cart-items">
                    <div class="empty-cart">🛒 Keranjang masih kosong</div>
                </div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total Belanja</span>
                        <span class="cart-total-value" id="cartTotal">Rp 0</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Card Modern -->
            <div class="payment-modern">
                <div class="total-modern">
                    <div class="amount" id="totalDisplayBig">Rp 0</div>
                    <input type="hidden" id="totalHidden" value="0">
                </div>
                <div class="payment-input-area">
                    <div class="payment-input-label">💵 UANG YANG DIBAYARKAN</div>
                    <div class="money-input-modern">
                        <span class="currency-modern">Rp</span>
                        <input type="number" id="uangBayar" placeholder="0" oninput="hitungKembalian()" autocomplete="off">
                    </div>
                    <div class="quick-amounts-modern">
                        <button type="button" class="quick-btn-modern" onclick="setQuickAmount(2000)">Rp 2.000</button>
                        <button type="button" class="quick-btn-modern" onclick="setQuickAmount(5000)">Rp 5.000</button>
                        <button type="button" class="quick-btn-modern" onclick="setQuickAmount(10000)">Rp 10.000</button>
                        <button type="button" class="quick-btn-modern" onclick="setQuickAmount(20000)">Rp 20.000</button>
                        <button type="button" class="quick-btn-modern" onclick="setQuickAmount(50000)">Rp 50.000</button>
                        <button type="button" class="quick-btn-modern" onclick="setQuickAmount(100000)">Rp 100.000</button>
                        <button type="button" class="quick-btn-modern exact" onclick="setExactAmount()">🎯 Pas</button>
                    </div>
                </div>
                <div id="kembalianContainer"></div>
            </div>
        
            <!-- Piutang Option -->
            <div class="piutang-modern" id="piutangCard" onclick="togglePiutang()">
                <div style="display:flex;justify-content:space-between;">
                    <div>
                        <strong>📝 Catat sebagai Piutang</strong>
                        <div style="font-size:12px;">Pilih jika pelanggan bayar nanti</div>
                    </div>
                    <div id="piutangCheck" style="width:26px;height:26px;border-radius:50%;background:rgba(0,0,0,0.1);display:flex;align-items:center;justify-content:center;">☐</div>
                </div>
            </div>
            
            <!-- Piutang Fields -->
            <div id="piutangFields" style="display:none;">
                <div class="piutang-detail">
                    <h4 style="margin-bottom:20px;">📋 Data Piutang</h4>
                    <div class="form-group">
                        <label>👤 Nama Pelanggan *</label>
                        <input type="text" id="pelangganNama" placeholder="Contoh: Budi Santoso">
                    </div>
                    <div class="form-group">
                        <label>📱 Nomor WhatsApp *</label>
                        <input type="tel" id="noWhatsapp" placeholder="Contoh: 81234567890">
                        <small>Format: 81234567890 (tanpa 0 di depan)</small>
                    </div>
                    <div class="form-group">
                        <label>📅 Tanggal Jatuh Tempo *</label>
                        <input type="date" id="dueDate">
                    </div>
                </div>
            </div>
            
            <!-- Proses Transaksi Button -->
            <button type="button" class="btn-transaksi" id="transaksiBtn" onclick="prosesTransaksi()">
                <span>💳</span> Proses Transaksi
            </button>
            
            <!-- Transaksi Hari Ini -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Transaksi Hari Ini</div>
                    <button class="btn btn-primary btn-sm" onclick="loadTransaksi()">🔄 Refresh</button>
                </div>
                <div class="table-container">
                    <table class="transaksi-table">
                        <thead>
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Waktu</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="transaksiListBody">
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
    
    <!-- Modal Struk Belanja -->
    <div id="strukModal" class="modal">
        <div class="modal-content" style="max-width:450px;">
            <div class="modal-header">
                <h3>🧾 Struk Belanja</h3>
                <span class="modal-close" onclick="closeModal('strukModal')">&times;</span>
            </div>
            <div class="modal-body" id="strukContent"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('strukModal')">Tutup</button>
                <button class="btn btn-primary" onclick="printStruk()">🖨️ Cetak Struk</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        var cart = [];
        var isPiutangActive = false;
        var currentStrukData = null;
        
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
        
        function tambahKeKeranjang() {
            var select = document.getElementById('selectBarang');
            var opt = select.options[select.selectedIndex];
            var id = parseInt(opt.value);
            var nama = opt.getAttribute('data-nama');
            var harga = parseInt(opt.getAttribute('data-harga'));
            var stok = parseInt(opt.getAttribute('data-stok'));
            var satuan = opt.getAttribute('data-satuan');
            var qty = parseInt(document.getElementById('jumlahBarang').value) || 1;
            
            if (!id) { alert('Pilih barang!'); return; }
            if (qty <= 0) { alert('Jumlah harus > 0!'); return; }
            
            var existing = null;
            for (var i = 0; i < cart.length; i++) {
                if (cart[i].id === id) {
                    existing = cart[i];
                    break;
                }
            }
            var totalQty = (existing ? existing.qty : 0) + qty;
            if (totalQty > stok) { alert('Stok tidak cukup! Tersedia: ' + stok + ' ' + satuan); return; }
            
            if (existing) {
                existing.qty += qty;
                existing.subtotal = existing.qty * existing.harga;
            } else {
                cart.push({ id: id, nama: nama, harga: harga, qty: qty, satuan: satuan, subtotal: harga * qty });
            }
            
            document.getElementById('selectBarang').value = '';
            document.getElementById('jumlahBarang').value = '1';
            renderCart();
        }
        
        function renderCart() {
            var total = 0;
            for (var i = 0; i < cart.length; i++) {
                total += cart[i].subtotal;
            }
            document.getElementById('cartTotal').innerHTML = formatRupiah(total);
            document.getElementById('totalDisplayBig').innerHTML = formatRupiah(total);
            document.getElementById('totalHidden').value = total;
            document.getElementById('cartCount').innerHTML = cart.length + ' item';
            
            var container = document.getElementById('cartItems');
            if (cart.length === 0) {
                container.innerHTML = '<div class="empty-cart">🛒 Keranjang masih kosong</div>';
                return;
            }
            
            var html = '';
            for (var i = 0; i < cart.length; i++) {
                var item = cart[i];
                html += '<div class="cart-item">' +
                    '<div class="cart-item-info">' +
                        '<div class="cart-item-name">' + escapeHtml(item.nama) + '</div>' +
                        '<div class="cart-item-price">' + formatRupiah(item.harga) + ' / ' + item.satuan + '</div>' +
                    '</div>' +
                    '<div class="cart-item-qty">' +
                        '<input type="number" value="' + item.qty + '" min="1" onchange="updateQty(' + i + ', this.value)">' +
                    '</div>' +
                    '<div class="cart-item-subtotal">' + formatRupiah(item.subtotal) + '</div>' +
                    '<div class="cart-item-remove">' +
                        '<button class="remove-btn" onclick="removeFromCart(' + i + ')">✕</button>' +
                    '</div>' +
                '</div>';
            }
            container.innerHTML = html;
            hitungKembalian();
        }
        
        function updateQty(idx, newQty) {
            newQty = parseInt(newQty);
            if (newQty < 1) newQty = 1;
            cart[idx].qty = newQty;
            cart[idx].subtotal = newQty * cart[idx].harga;
            renderCart();
        }
        
        function removeFromCart(idx) {
            cart.splice(idx, 1);
            renderCart();
        }
        
        function setQuickAmount(amount) {
            document.getElementById('uangBayar').value = amount;
            hitungKembalian();
            document.getElementById('uangBayar').focus();
        }
        
        function setExactAmount() {
            var total = parseInt(document.getElementById('totalHidden').value) || 0;
            document.getElementById('uangBayar').value = total;
            hitungKembalian();
        }
        
        function hitungKembalian() {
            var total = parseInt(document.getElementById('totalHidden').value) || 0;
            var bayar = parseInt(document.getElementById('uangBayar').value) || 0;
            var container = document.getElementById('kembalianContainer');
            
            if (isPiutangActive) {
                container.innerHTML = '<div class="change-modern warning"><div>📝 STATUS PIUTANG</div><div class="change-amount">' + formatRupiah(total) + '</div><div>Akan dicatat sebagai piutang</div></div>';
                return;
            }
            if (bayar === 0) {
                container.innerHTML = '';
                return;
            }
            if (bayar >= total) {
                var kembali = bayar - total;
                container.innerHTML = '<div class="change-modern success"><div>💰 KEMBALIAN</div><div class="change-amount">' + formatRupiah(kembali) + '</div></div>';
            } else {
                var kurang = total - bayar;
                container.innerHTML = '<div class="change-modern error"><div>⚠️ UANG KAMU KURANG</div><div class="change-amount">' + formatRupiah(kurang) + '</div></div>';
            }
        }
        
        function togglePiutang() {
            isPiutangActive = !isPiutangActive;
            var piutangFields = document.getElementById('piutangFields');
            var piutangCard = document.getElementById('piutangCard');
            var piutangCheck = document.getElementById('piutangCheck');
            
            if (isPiutangActive) {
                piutangFields.style.display = 'block';
                piutangCard.classList.add('active');
                piutangCheck.innerHTML = '✓';
                piutangCheck.style.background = 'white';
                document.getElementById('uangBayar').value = '';
            } else {
                piutangFields.style.display = 'none';
                piutangCard.classList.remove('active');
                piutangCheck.innerHTML = '☐';
                piutangCheck.style.background = 'rgba(0,0,0,0.1)';
            }
            hitungKembalian();
        }
        
        // Load transaksi hari ini - MENGHITUNG TOTAL KESELURUHAN
        function loadTransaksi() {
            fetch('../api/transaksi.php?action=hari_ini')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('transaksiListBody');
                    
                    // PERBAIKAN: Menggunakan response terbaru dari API
                    var transactions = data.transactions || data;
                    var totalPenjualan = data.total_hari_ini || 0;
                    var jumlahTransaksi = data.jumlah_transaksi || 0;
                    
                    // Jika data masih dalam format array biasa, hitung manual
                    if (!data.total_hari_ini && Array.isArray(data)) {
                        totalPenjualan = 0;
                        jumlahTransaksi = data.length;
                        for (var i = 0; i < data.length; i++) {
                            totalPenjualan += data[i].total;
                        }
                        transactions = data;
                    }
                    
                    if (transactions.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">📭 Belum ada transaksi hari ini<\/td><\/tr>';
                        document.getElementById('totalHariIni').innerHTML = formatRupiah(0);
                        document.getElementById('totalTransaksiHariIni').innerHTML = '0 transaksi';
                        return;
                    }
                    
                    var html = '';
                    for (var i = 0; i < transactions.length; i++) {
                        var t = transactions[i];
                        var itemBadge = (t.item_count > 1) 
                            ? '<span class="item-badge multiple">' + t.item_count + ' item</span>' 
                            : '<span class="item-badge">' + t.item_count + ' item</span>';
                        
                        html += '<tr>' +
                            '<td><span class="trans-no">#' + escapeHtml(t.no_transaksi) + '</span></td>' +
                            '<td>' + t.tanggal + '</td>' +
                            '<td>' + itemBadge + '</td>' +
                            '<td><span class="amount-text">' + formatRupiah(t.total) + '</span></td>' +
                            '<td><button class="btn-detail" onclick="lihatStruk(' + t.id + ')">🧾 Lihat Struk</button></td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                    
                    // TAMPILKAN TOTAL KESELURUHAN PENJUALAN HARI INI
                    document.getElementById('totalHariIni').innerHTML = formatRupiah(totalPenjualan);
                    document.getElementById('totalTransaksiHariIni').innerHTML = jumlahTransaksi + ' transaksi';
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    var tbody = document.getElementById('transaksiListBody');
                    tbody.innerHTML = '<td><td colspan="5" class="empty-state" style="color:red;">❌ Gagal memuat data transaksi<\/td><\/tr>';
                    document.getElementById('totalHariIni').innerHTML = formatRupiah(0);
                    document.getElementById('totalTransaksiHariIni').innerHTML = '0 transaksi';
                });
        }
        
        function lihatStruk(id) {
            fetch('../api/transaksi.php?action=get_struk&id=' + id)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        currentStrukData = data.struk;
                        generateStrukHTML(data.struk);
                        openModal('strukModal');
                    } else {
                        alert('Gagal memuat struk');
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Gagal memuat struk');
                });
        }
        
        function generateStrukHTML(struk) {
            var now = new Date();
            var tanggal = now.toLocaleDateString('id-ID');
            var waktu = now.toLocaleTimeString('id-ID');
            
            var html = '<div class="struk-container" id="strukPrint">' +
                '<div class="struk-header">' +
                    '<h3>🏪 ' + escapeHtml(struk.nama_toko) + '</h3>' +
                    '<p>' + tanggal + ' | ' + waktu + '</p>' +
                    '<p>================================</p>' +
                '</div>' +
                '<div><strong>No. Transaksi:</strong> #' + struk.no_transaksi + '</div>' +
                '<div><strong>Kasir:</strong> ' + escapeHtml(struk.kasir) + '</div>' +
                '<div><strong>Metode:</strong> 💵 Tunai</div>';
            
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
            
            document.getElementById('strukContent').innerHTML = html;
        }
        
        function printStruk() {
            if (!currentStrukData) return;
            
            var win = window.open('', '_blank');
            var now = new Date();
            var tanggal = now.toLocaleDateString('id-ID');
            var waktu = now.toLocaleTimeString('id-ID');
            
            var printHtml = '<!DOCTYPE html>' +
                '<html>' +
                '<head>' +
                    '<title>Struk Belanja - ' + currentStrukData.no_transaksi + '</title>' +
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
                            '<h3>🏪 ' + escapeHtml(currentStrukData.nama_toko) + '</h3>' +
                            '<p>' + tanggal + ' | ' + waktu + '</p>' +
                            '<p>================================</p>' +
                        '</div>' +
                        '<div><strong>No. Transaksi:</strong> #' + currentStrukData.no_transaksi + '</div>' +
                        '<div><strong>Kasir:</strong> ' + escapeHtml(currentStrukData.kasir) + '</div>' +
                        '<div><strong>Metode:</strong> 💵 Tunai</div>';
            
            if (currentStrukData.is_piutang) {
                printHtml += '<div class="struk-piutang">' +
                    '<div style="font-weight:bold;">⚠️ STATUS PIUTANG</div>' +
                    '<div>Pelanggan: ' + escapeHtml(currentStrukData.pelanggan_nama || '-') + '</div>' +
                    '<div>No. WA: ' + escapeHtml(currentStrukData.no_whatsapp || '-') + '</div>' +
                    '<div>Jatuh Tempo: ' + (currentStrukData.due_date || '-') + '</div>' +
                '</div>';
            } else {
                printHtml += '<div>Status: ✅ Lunas</div>';
            }
            
            printHtml += '<p>================================</p>';
            
            for (var i = 0; i < currentStrukData.items.length; i++) {
                var item = currentStrukData.items[i];
                printHtml += '<div class="struk-item">' +
                    '<span>' + escapeHtml(item.nama) + ' (' + item.qty + ' x ' + formatRupiah(item.harga) + ')</span>' +
                    '<span>' + formatRupiah(item.subtotal) + '</span>' +
                '</div>';
            }
            
            printHtml += '<p>================================</p>' +
                '<div class="struk-item" style="font-weight:bold;">' +
                    '<span>TOTAL</span>' +
                    '<span>' + formatRupiah(currentStrukData.total) + '</span>' +
                '</div>';
            
            if (!currentStrukData.is_piutang && currentStrukData.uang_bayar > 0) {
                printHtml += '<div class="struk-item"><span>Bayar</span><span>' + formatRupiah(currentStrukData.uang_bayar) + '</span></div>' +
                        '<div class="struk-item"><span>Kembali</span><span>' + formatRupiah(currentStrukData.kembalian) + '</span></div>';
            }
            
            printHtml += '<div class="struk-footer">' +
                '<p>Terima kasih telah berbelanja!</p>' +
                '<p>================================</p>' +
                '<p>🏪 ' + escapeHtml(currentStrukData.nama_toko) + '</p>' +
            '</div></div>' +
            '<script>' +
                'window.onload = function() { window.print(); setTimeout(function() { window.close(); }, 500); };' +
            '<\/script>' +
            '</body></html>';
            
            win.document.write(printHtml);
            win.document.close();
        }
        
        function prosesTransaksi() {
            var total = parseInt(document.getElementById('totalHidden').value) || 0;
            var uangBayar = parseInt(document.getElementById('uangBayar').value) || 0;
            
            if (cart.length === 0) {
                alert('Keranjang masih kosong!');
                return;
            }
            if (!isPiutangActive && uangBayar < total) {
                alert('Uang bayar kurang!');
                return;
            }
            if (isPiutangActive) {
                var nama = document.getElementById('pelangganNama').value.trim();
                var wa = document.getElementById('noWhatsapp').value.trim();
                var due = document.getElementById('dueDate').value;
                if (!nama) { alert('Nama pelanggan harus diisi!'); return; }
                if (!wa) { alert('Nomor WhatsApp harus diisi!'); return; }
                if (!due) { alert('Tanggal jatuh tempo harus diisi!'); return; }
            }
            
            var submitBtn = document.getElementById('transaksiBtn');
            var originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Memproses...';
            
            var formData = new FormData();
            formData.append('items', JSON.stringify(cart));
            formData.append('total', total);
            formData.append('uang_bayar', uangBayar);
            formData.append('is_piutang', isPiutangActive ? '1' : '0');
            if (isPiutangActive) {
                formData.append('pelanggan_nama', document.getElementById('pelangganNama').value.trim());
                formData.append('no_whatsapp', document.getElementById('noWhatsapp').value.trim());
                formData.append('due_date', document.getElementById('dueDate').value);
            }
            
            fetch('../api/transaksi.php?action=jual_multiple', { method: 'POST', body: formData })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (data.success) {
                        alert('✅ Transaksi berhasil!\nTotal: ' + formatRupiah(data.total));
                        cart = [];
                        renderCart();
                        document.getElementById('uangBayar').value = '';
                        document.getElementById('kembalianContainer').innerHTML = '';
                        document.getElementById('pelangganNama').value = '';
                        document.getElementById('noWhatsapp').value = '';
                        document.getElementById('dueDate').value = '';
                        if (isPiutangActive) togglePiutang();
                        loadTransaksi();
                        if (data.struk) {
                            currentStrukData = data.struk;
                            generateStrukHTML(data.struk);
                            openModal('strukModal');
                        }
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(function(error) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    alert('❌ Error: ' + error.message);
                });
        }
        
        // Load transaksi saat halaman dibuka
        loadTransaksi();
        
        // Auto refresh setiap 10 detik untuk update total penjualan
        setInterval(function() {
            loadTransaksi();
        }, 10000);
    </script>
</body>
</html>