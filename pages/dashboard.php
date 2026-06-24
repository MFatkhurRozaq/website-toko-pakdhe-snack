<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil setting toko
$setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
if (!$setting) {
    $pdo->query("INSERT INTO pengaturan_toko (nama_toko, promo_text, whatsapp_number) VALUES ('Pakdhe Snack', '', '')");
    $setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
}

$display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];

// Ambil data stok menipis
$stok_menipis = $pdo->query("SELECT * FROM barang WHERE stok <= stok_minimal LIMIT 5")->fetchAll();
$total_stok_menipis = count($stok_menipis);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* ========== DASHBOARD SPECIFIC STYLES ========== */
        
        /* Header Right */
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Clock Widget */
        .clock-widget {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 20px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .clock-widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        }
        
        .clock-icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .clock-info {
            text-align: center;
        }
        
        .clock-time {
            font-size: 22px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: #10b981;
            letter-spacing: 2px;
        }
        
        .clock-date {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }
        
        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 6px 16px 6px 8px;
            border-radius: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .user-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }
        
        .user-avatar-modern {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(102,126,234,0.3);
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name-modern {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 10px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .user-role::before {
            content: "●";
            color: #10b981;
            font-size: 8px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
        .stat-icon.success { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-icon.warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .stat-icon.danger { background: rgba(239,68,68,0.1); color: #ef4444; }
        .stat-icon.info { background: rgba(59,130,246,0.1); color: #3b82f6; }
        
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
        
        /* Stock Alert Card */
        .stock-alert-card {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3d4 100%);
            border-radius: 20px;
            margin-bottom: 25px;
            overflow: hidden;
            border-left: 5px solid #f59e0b;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            animation: slideInAlert 0.5s ease;
        }
        
        @keyframes slideInAlert {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .stock-alert-header {
            padding: 18px 24px;
            background: rgba(245,158,11,0.1);
            border-bottom: 1px solid rgba(245,158,11,0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stock-alert-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .stock-alert-icon {
            width: 45px;
            height: 45px;
            background: #f59e0b;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            animation: shake 0.5s ease infinite;
        }
        
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
        
        .stock-alert-title h3 {
            font-size: 18px;
            font-weight: 700;
            color: #92400e;
            margin: 0;
        }
        
        .stock-alert-title p {
            font-size: 13px;
            color: #b45309;
            margin: 4px 0 0;
        }
        
        .stock-alert-badge {
            background: #f59e0b;
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .stock-alert-body {
            padding: 20px 24px;
        }
        
        .stock-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .stock-item {
            background: white;
            border-radius: 16px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border: 1px solid #ffe0a3;
            cursor: pointer;
        }
        
        .stock-item:hover {
            transform: translateX(5px);
            border-color: #f59e0b;
            box-shadow: 0 5px 15px rgba(245,158,11,0.15);
        }
        
        .stock-item-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .stock-item-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }
        
        .stock-item-stok {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .stock-current {
            font-size: 13px;
            color: #ef4444;
            font-weight: 600;
        }
        
        .stock-minimal {
            font-size: 12px;
            color: #64748b;
        }
        
        .stock-progress {
            width: 120px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .stock-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ef4444, #f59e0b);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .stock-item-action {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .stock-item-action:hover {
            background: #d97706;
            transform: scale(1.02);
        }
        
        .stock-alert-footer {
            padding: 15px 24px;
            background: rgba(245,158,11,0.05);
            border-top: 1px solid rgba(245,158,11,0.15);
            text-align: center;
        }
        
        .stock-alert-footer a {
            color: #d97706;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .stock-alert-footer a:hover {
            color: #92400e;
            gap: 12px;
        }
        
        .no-stock-alert {
            background: #f0fdf4;
            border-left: 5px solid #10b981;
        }
        
        .no-stock-alert .stock-alert-icon {
            background: #10b981;
        }
        
        /* Card */
        .card {
            background: white;
            border-radius: 20px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 650px;
        }
        
        .recent-table th {
            background: #f8fafc;
            color: #1e293b;
            padding: 14px 16px;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        
        .recent-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .recent-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .recent-table tbody tr:hover {
            background: #f8fafc;
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
        
        /* Button Detail */
        .btn-detail {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-detail:hover {
            background: #5a67d8;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(102,126,234,0.3);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            padding: 20px 24px;
        }
        
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
        }
        
        .quick-action-btn.btn-success {
            background: #10b981;
            color: white;
        }
        
        .quick-action-btn.btn-success:hover {
            background: #059669;
            box-shadow: 0 5px 15px rgba(16,185,129,0.3);
        }
        
        .quick-action-btn.btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .quick-action-btn.btn-primary:hover {
            box-shadow: 0 5px 15px rgba(102,126,234,0.3);
        }
        
        .quick-action-btn.btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .quick-action-btn.btn-warning:hover {
            background: #d97706;
            box-shadow: 0 5px 15px rgba(245,158,11,0.3);
        }
        
        .quick-action-btn.btn-info {
            background: #3b82f6;
            color: white;
        }
        
        .quick-action-btn.btn-info:hover {
            background: #2563eb;
            box-shadow: 0 5px 15px rgba(59,130,246,0.3);
        }
        
        .quick-action-btn.btn-outline {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
        }
        
        .quick-action-btn.btn-outline:hover {
            background: #667eea;
            color: white;
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
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
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
        
        /* Empty State */
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
            .header-right {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .clock-widget {
                padding: 5px 12px;
            }
            .clock-time {
                font-size: 16px;
            }
            .clock-icon {
                width: 35px;
                height: 35px;
                font-size: 18px;
            }
            .stat-value {
                font-size: 22px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stock-items-grid {
                grid-template-columns: 1fr;
            }
            .stock-alert-header {
                flex-direction: column;
                text-align: center;
            }
            .quick-actions {
                justify-content: center;
            }
            .recent-table th, .recent-table td {
                padding: 10px 12px;
                font-size: 11px;
            }
            .card-header {
                flex-direction: column;
                text-align: center;
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
            .btn-primary, .btn-success, .btn-danger, .btn-warning, .btn-info, .btn-outline,
            .quick-actions, .stock-item-action, .stock-alert-footer, .btn-detail {
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
            .recent-table th, .recent-table td {
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
                <a href="dashboard.php" class="menu-item active">
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
                <h1 class="page-title">🏪 Dashboard <?= htmlspecialchars($setting['nama_toko']) ?></h1>
                
                <div class="header-right">
                    <div class="clock-widget">
                        <div class="clock-icon">🕐</div>
                        <div class="clock-info">
                            <div class="clock-time" id="liveClock">--:--:--</div>
                            <div class="clock-date" id="liveDate">--, -- --- ----</div>
                        </div>
                    </div>
                    
                    <a href="setting.php" class="user-profile">
                        <div class="user-avatar-modern">
                            <?= strtoupper(substr($display_name, 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name-modern"><?= htmlspecialchars($display_name) ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card" onclick="location.href='barang.php'">
                    <div class="stat-icon primary">📦</div>
                    <div class="stat-info">
                        <h3>TOTAL BARANG</h3>
                        <div class="stat-value" id="totalBarang">-</div>
                        <div class="stat-trend">📊 Active Products</div>
                    </div>
                </div>
                <div class="stat-card" onclick="location.href='stok.php'">
                    <div class="stat-icon success">📊</div>
                    <div class="stat-info">
                        <h3>TOTAL STOK</h3>
                        <div class="stat-value" id="totalStok">-</div>
                        <div class="stat-trend">📦 Items in Warehouse</div>
                    </div>
                </div>
                <div class="stat-card" onclick="location.href='transaksi.php'">
                    <div class="stat-icon warning">💰</div>
                    <div class="stat-info">
                        <h3>PENJUALAN HARI INI</h3>
                        <div class="stat-value" id="penjualanHari">-</div>
                        <div class="stat-trend">📈 Today's Sales</div>
                    </div>
                </div>
                <div class="stat-card" onclick="location.href='piutang.php'">
                    <div class="stat-icon danger">📝</div>
                    <div class="stat-info">
                        <h3>TOTAL PIUTANG</h3>
                        <div class="stat-value" id="totalPiutang">-</div>
                        <div class="stat-trend">⏳ Pending Payment</div>
                    </div>
                </div>
                <div class="stat-card" onclick="location.href='operasional.php'">
                    <div class="stat-icon info">🛠️</div>
                    <div class="stat-info">
                        <h3>OPERASIONAL BULAN INI</h3>
                        <div class="stat-value" id="totalOperasional">-</div>
                        <div class="stat-trend">💰 Monthly Operational</div>
                    </div>
                </div>
            </div>
            
            <!-- Stock Alert Widget -->
            <div id="alertContainer">
                <?php if($total_stok_menipis > 0): ?>
                <div class="stock-alert-card">
                    <div class="stock-alert-header">
                        <div class="stock-alert-title">
                            <div class="stock-alert-icon">⚠️</div>
                            <div>
                                <h3>Peringatan Stok Menipis!</h3>
                                <p>Ada <?= $total_stok_menipis ?> barang yang perlu segera di-restock</p>
                            </div>
                        </div>
                        <div class="stock-alert-badge">Perlu Tindakan!</div>
                    </div>
                    <div class="stock-alert-body">
                        <div class="stock-items-grid">
                            <?php foreach($stok_menipis as $item): ?>
                            <?php 
                            $progressWidth = min(100, ($item['stok'] / $item['stok_minimal']) * 100);
                            if ($item['stok'] <= 0) {
                                $statusText = 'HABIS!';
                                $statusColor = '#ef4444';
                            } elseif ($item['stok'] <= $item['stok_minimal'] / 2) {
                                $statusText = 'KRITIS!';
                                $statusColor = '#ef4444';
                            } else {
                                $statusText = 'MENIPIS';
                                $statusColor = '#f59e0b';
                            }
                            ?>
                            <div class="stock-item" onclick="location.href='stok.php'">
                                <div class="stock-item-info">
                                    <div class="stock-item-name"><?= htmlspecialchars($item['nama_barang']) ?></div>
                                    <div class="stock-item-stok">
                                        <span class="stock-current" style="color: <?= $statusColor ?>"><?= $statusText ?> <?= $item['stok'] ?> <?= $item['satuan'] ?></span>
                                        <span class="stock-minimal">(Minimal: <?= $item['stok_minimal'] ?> <?= $item['satuan'] ?>)</span>
                                    </div>
                                    <div class="stock-progress">
                                        <div class="stock-progress-bar" style="width: <?= $progressWidth ?>%"></div>
                                    </div>
                                </div>
                                <button class="stock-item-action" onclick="event.stopPropagation(); location.href='stok.php'">
                                    + Tambah Stok
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="stock-alert-footer">
                        <a href="stok.php">📦 Kelola Semua Stok →</a>
                    </div>
                </div>
                <?php else: ?>
                <div class="stock-alert-card no-stock-alert">
                    <div class="stock-alert-header">
                        <div class="stock-alert-title">
                            <div class="stock-alert-icon">✅</div>
                            <div>
                                <h3>Stok Semua Aman!</h3>
                                <p>Tidak ada barang dengan stok menipis</p>
                            </div>
                        </div>
                        <div class="stock-alert-badge">Good Job! 👍</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Aksi Cepat -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>⚡</span> Aksi Cepat
                    </div>
                </div>
                <div class="quick-actions">
                    <a href="transaksi.php" class="quick-action-btn btn-success">
                        <span>💰</span> Transaksi Baru
                    </a>
                    <a href="stok.php" class="quick-action-btn btn-primary">
                        <span>📦</span> Tambah Stok
                    </a>
                    <a href="barang.php" class="quick-action-btn btn-warning">
                        <span>🆕</span> Tambah Barang
                    </a>
                    <a href="operasional.php" class="quick-action-btn btn-info">
                        <span>🛠️</span> Tambah Operasional
                    </a>
                    <a href="piutang.php" class="quick-action-btn btn-outline">
                        <span>📝</span> Kelola Piutang
                    </a>
                </div>
            </div>
            
            <!-- Transaksi Terbaru - TABEL RAPI -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>📋</span> Transaksi Terbaru
                    </div>
                    <a href="transaksi.php" class="btn btn-primary btn-sm">Lihat Semua →</a>
                </div>
                <div class="table-container">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Tanggal</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="recentTransactions">
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="spinner-large"></div>
                                    <p>Memuat data...</p>
                                 \n
                            </tr>
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
                <button class="btn btn-primary" onclick="printStruk()">🖨️ Cetak Struk</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        var currentDetailStruk = null;
        
        // Update jam real-time
        function updateClock() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var seconds = String(now.getSeconds()).padStart(2, '0');
            var timeString = hours + ':' + minutes + ':' + seconds;
            
            var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            var dayName = days[now.getDay()];
            var day = now.getDate();
            var month = months[now.getMonth()];
            var year = now.getFullYear();
            var dateString = dayName + ', ' + day + ' ' + month + ' ' + year;
            
            document.getElementById('liveClock').innerText = timeString;
            document.getElementById('liveDate').innerText = dateString;
        }
        
        setInterval(updateClock, 1000);
        updateClock();
        
        // Format Rupiah
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
        
        // Load statistik dashboard
        function loadDashboardStats() {
            fetch('../api/dashboard.php?action=stats')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    document.getElementById('totalBarang').innerText = data.totalBarang;
                    document.getElementById('totalStok').innerText = data.totalStok;
                    document.getElementById('penjualanHari').innerHTML = formatRupiah(data.penjualanHari);
                    document.getElementById('totalPiutang').innerHTML = formatRupiah(data.totalPiutang);
                })
                .catch(function(error) { console.error('Error:', error); });
        }
        
        // Load total operasional bulan ini
        function loadTotalOperasional() {
            fetch('../api/operasional.php?action=total_bulan_ini')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    document.getElementById('totalOperasional').innerHTML = formatRupiah(data.total);
                })
                .catch(function(error) { console.error('Error:', error); });
        }
        
        // Generate detail HTML
        function generateDetailHTML(struk) {
            var now = new Date();
            var tanggal = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            var waktu = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            var html = '<div id="strukPrint" class="struk-container">' +
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
        
        // Print struk
        function printStruk() {
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
        
        // Load transaksi terbaru - DENGAN TABEL RAPI
        function loadRecentTransactions() {
            fetch('../api/dashboard.php?action=recent')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    var tbody = document.getElementById('recentTransactions');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">📭 Belum ada transaksi</td></tr>';
                        return;
                    }
                    
                    var html = '';
                    for (var i = 0; i < data.length; i++) {
                        var t = data[i];
                        var itemCount = t.item_count || 1;
                        var itemBadge = (itemCount > 1) 
                            ? '<span class="item-badge multiple">' + itemCount + ' item</span>' 
                            : '<span class="item-badge">' + itemCount + ' item</span>';
                        var statusBadge = (t.is_piutang == 1) 
                            ? '<span class="badge badge-warning">⚠️ Piutang</span>' 
                            : '<span class="badge badge-success">✅ Lunas</span>';
                        
                        html += '<tr>' +
                            '<td><span class="trans-no">#' + escapeHtml(t.no_transaksi) + '</span></td>' +
                            '<td>' + t.tanggal + '</td>' +
                            '<td>' + itemBadge + '</td>' +
                            '<td><span class="amount-text">' + formatRupiah(t.total) + '</span></td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td><button class="btn-detail" onclick="showDetail(' + t.id + ')">📋 Detail</button></td>' +
                        '</tr>';
                    }
                    tbody.innerHTML = html;
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    var tbody = document.getElementById('recentTransactions');
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-state" style="color:red;">❌ Gagal memuat data transaksi</td></tr>';
                });
        }
        
        // Load semua data
        loadDashboardStats();
        loadTotalOperasional();
        loadRecentTransactions();
        
        // Auto refresh setiap 30 detik
        setInterval(function() {
            loadDashboardStats();
            loadTotalOperasional();
            loadRecentTransactions();
        }, 30000);
    </script>
</body>
</html>