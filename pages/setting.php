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

// Ambil data user untuk menampilkan nama di sidebar
$display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validasi password saat ini
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (!password_verify($current_password, $user_data['password'])) {
            $error = "Password saat ini salah!";
        } else {
            // Update profil
            $update_sql = "UPDATE users SET full_name = ?, email = ?";
            $params = [$full_name, $email];
            
            // Jika ada password baru
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = "Password baru dan konfirmasi tidak sama!";
                } else {
                    $update_sql .= ", password = ?";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
            
            $update_sql .= " WHERE id = ?";
            $params[] = $user_id;
            
            if (empty($error)) {
                $stmt = $pdo->prepare($update_sql);
                if ($stmt->execute($params)) {
                    $success = "Profil berhasil diperbarui!";
                    // Refresh data user
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    $display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
                } else {
                    $error = "Gagal memperbarui profil!";
                }
            }
        }
    }
    
    // Proses update setting toko
    if (isset($_POST['update_setting'])) {
        $stmt = $pdo->prepare("UPDATE pengaturan_toko SET nama_toko = ?, promo_text = ?, whatsapp_number = ? WHERE id = 1");
        $stmt->execute([$_POST['nama_toko'], $_POST['promo_text'], $_POST['whatsapp_number']]);
        $success = "Pengaturan toko berhasil disimpan!";
        // Refresh setting
        $setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - <?= htmlspecialchars($setting['nama_toko']) ?></title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <style>
        /* ========== SETTING SPECIFIC STYLES ========== */
        
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
        
        /* Settings Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .settings-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .settings-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .settings-card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .settings-card-header .icon {
            font-size: 28px;
        }
        
        .settings-card-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .settings-card-header p {
            font-size: 12px;
            opacity: 0.8;
            margin: 4px 0 0;
        }
        
        .settings-card-body {
            padding: 24px;
        }
        
        /* Info Box */
        .info-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
        }
        
        .info-box .label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .info-box .value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Divider */
        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 20px 0;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            color: #64748b;
        }
        
        .password-hint {
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
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Alert */
        .alert {
            padding: 14px 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideInAlert 0.4s ease;
        }
        
        @keyframes slideInAlert {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 12px;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .info-item .label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
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
            .settings-grid {
                grid-template-columns: 1fr;
            }
            .info-grid {
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
            .sidebar, .menu-toggle, .sidebar-overlay, .top-bar, .btn, .no-print {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .settings-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
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
                <a href="laporan.php" class="menu-item">
                    <span class="menu-icon">📄</span>
                    <span class="menu-text">Laporan</span>
                </a>
                <a href="setting.php" class="menu-item active">
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
                <h1 class="page-title">⚙️ Pengaturan</h1>
            </div>
            
            <?php if($success): ?>
                <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="settings-grid">
                <!-- Card Profil Admin -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <span class="icon">👤</span>
                        <div>
                            <h3>Profil Admin</h3>
                            <p>Ubah nama dan password Anda</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
                        <div class="info-box">
                            <div class="label">Username Login</div>
                            <div class="value"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="form-group">
                                <label>👤 Nama Lengkap</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Masukkan nama lengkap">
                                <small>Nama ini akan ditampilkan di dashboard</small>
                            </div>
                            
                            <div class="form-group">
                                <label>📧 Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Masukkan email">
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="form-group">
                                <label>🔒 Password Saat Ini <span style="color: red;">*</span></label>
                                <input type="password" name="current_password" required placeholder="Masukkan password saat ini">
                            </div>
                            
                            <div class="form-group">
                                <label>🔑 Password Baru</label>
                                <input type="password" name="new_password" placeholder="Kosongkan jika tidak ingin mengganti">
                                <div class="password-hint">Minimal 6 karakter</div>
                            </div>
                            
                            <div class="form-group">
                                <label>✓ Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" placeholder="Ulangi password baru">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                                💾 Simpan Perubahan Profil
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Card Pengaturan Toko -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <span class="icon">🏪</span>
                        <div>
                            <h3>Informasi Toko</h3>
                            <p>Ubah nama dan promo toko</p>
                        </div>
                    </div>
                    <div class="settings-card-body">
                        <form method="POST">
                            <input type="hidden" name="update_setting" value="1">
                            
                            <div class="form-group">
                                <label>🏪 Nama Toko</label>
                                <input type="text" name="nama_toko" value="<?= htmlspecialchars($setting['nama_toko']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>📢 Text Promo</label>
                                <textarea name="promo_text" rows="4" placeholder="Tulis promo toko di sini..."><?= htmlspecialchars($setting['promo_text']) ?></textarea>
                                <small>Text promo akan ditampilkan di struk belanja</small>
                            </div>
                            
                            <div class="form-group">
                                <label>📱 Nomor WhatsApp</label>
                                <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($setting['whatsapp_number']) ?>" placeholder="Contoh: 081234567890">
                                <small>Nomor untuk ditampilkan di struk dan pengingat piutang</small>
                            </div>
                            
                            <button type="submit" class="btn btn-success" style="width: 100%; padding: 12px;">
                                💾 Simpan Pengaturan Toko
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Informasi Sistem -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <span>ℹ️</span> Informasi Sistem
                    </div>
                </div>
                <div class="info-grid" style="padding: 0 24px 24px 24px;">
                    <div class="info-item">
                        <div class="label">Versi Aplikasi</div>
                        <div class="value">Warung Online v3.0</div>
                    </div>
                    <div class="info-item">
                        <div class="label">Terakhir Login</div>
                        <div class="value"><?= date('d/m/Y H:i:s') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">ID User</div>
                        <div class="value">#<?= $user_id ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Role</div>
                        <div class="value">Administrator</div>
                    </div>
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
        
        // Auto close alert setelah 5 detik
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(function() {
                        if (alert && alert.parentElement) {
                            alert.remove();
                        }
                    }, 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>