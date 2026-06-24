<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil setting toko dengan aman
$setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
if (!$setting) {
    $pdo->query("INSERT INTO pengaturan_toko (nama_toko, promo_text, whatsapp_number) VALUES ('Pakdhe Snack', '', '')");
    $setting = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1")->fetch();
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];

// Ambil data barang
$barang_list = $pdo->query("
    SELECT b.*, hj.harga_jual, hj.margin_persen,
           (SELECT harga_beli FROM riwayat_harga_beli WHERE barang_id = b.id ORDER BY tanggal_beli DESC LIMIT 1) as harga_beli_terakhir
    FROM barang b
    LEFT JOIN harga_jual_aktif hj ON b.id = hj.barang_id
    ORDER BY b.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang - <?= htmlspecialchars($setting['nama_toko']) ?></title>
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
        
        /* Button */
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 10px rgba(102,126,234,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #475569;
        }
        
        .btn-outline:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }
        
        /* ========== TABEL - DIPASTIKAN HEADER MUNCUL ========== */
        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .table-container {
            overflow-x: auto;
            width: 100%;
        }
        
        /* Tabel styling - DIPASTIKAN TIDAK TERPENGARUH SIDEBAR */
        .barang-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 800px;
        }
        
        /* Header tabel - PASTI MUNCUL */
        .barang-table thead tr {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
        }
        
        .barang-table th {
            color: white !important;
            padding: 14px 12px !important;
            font-weight: 600 !important;
            text-align: left !important;
            white-space: nowrap !important;
            background: transparent !important;
        }
        
        .barang-table td {
            padding: 12px !important;
            border-bottom: 1px solid #e2e8f0 !important;
            vertical-align: middle !important;
        }
        
        .barang-table tbody tr:hover {
            background: #f8fafc !important;
        }
        
        /* Badge Stok */
        .badge-stok {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-aman { background: #d1fae5; color: #065f46; }
        .badge-menipis { background: #fed7aa; color: #92400e; }
        .badge-habis { background: #fee2e2; color: #991b1b; }
        
        .stok-number {
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .nama-barang {
            font-weight: 600;
            color: #1e293b;
        }
        
        .harga-text {
            font-family: monospace;
            font-weight: 600;
            color: #059669;
        }
        
        /* Tombol Tindakan */
        .tindakan-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .btn-tindakan {
            display: inline-block;
            padding: 5px 12px;
            font-size: 11px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
        }
        .btn-restock { background: #3b82f6; color: white; }
        .btn-beli { background: #f59e0b; color: white; }
        .btn-aman { background: #10b981; color: white; cursor: default; opacity: 0.7; }
        
        .rekomendasi-text {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
            text-align: center;
        }
        
        /* Tombol Aksi */
        .btn-edit, .btn-delete {
            padding: 5px 12px;
            font-size: 11px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-edit { background: #f59e0b; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-edit:hover, .btn-delete:hover { transform: translateY(-1px); }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
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
        }
        .modal-body { padding: 24px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .form-group { margin-bottom: 20px; }
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
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Loading & Empty */
        .loading-state, .empty-state {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
        }
        .spinner-large {
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                text-align: center;
            }
            .barang-table th, .barang-table td {
                padding: 8px !important;
                font-size: 11px !important;
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
                <a href="barang.php" class="menu-item active">
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
                <h1 class="page-title">📦 Manajemen Barang</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Tambah Barang</button>
            </div>
            
            <div class="card">
                <div class="table-container">
                    <table class="barang-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Status Stok</th>
                                <th>Minimal</th>
                                <th>Satuan</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Tindakan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($barang_list) > 0): ?>
                                <?php foreach ($barang_list as $index => $b): ?>
                                    <?php 
                                    $no = $index + 1;
                                    // Status stok
                                    if ($b['stok'] <= 0) {
                                        $badge_class = 'badge-habis';
                                        $badge_text = '❌ Habis';
                                        $tindakan = '<div class="tindakan-container"><a href="stok.php" class="btn-tindakan btn-restock">🚚 Restock</a><div class="rekomendasi-text">Barang habis!</div></div>';
                                    } elseif ($b['stok'] <= $b['stok_minimal']) {
                                        $badge_class = 'badge-menipis';
                                        $badge_text = '⚠️ Menipis';
                                        $tindakan = '<div class="tindakan-container"><a href="stok.php" class="btn-tindakan btn-beli">🛒 Tambah Stok</a><div class="rekomendasi-text">Stok menipis!</div></div>';
                                    } else {
                                        $badge_class = 'badge-aman';
                                        $badge_text = '✓ Aman';
                                        $tindakan = '<div class="tindakan-container"><span class="btn-tindakan btn-aman">✓ Stok Aman</span><div class="rekomendasi-text">Stok cukup</div></div>';
                                    }
                                    ?>
                                    <tr>
                                        <td style="text-align:center"><?= $no ?></td>
                                        <td class="nama-barang"><?= htmlspecialchars($b['nama_barang']) ?></td>
                                        <td>
                                            <span class="badge-stok <?= $badge_class ?>"><?= $badge_text ?></span>
                                            <div class="stok-number"><?= $b['stok'] ?> <?= $b['satuan'] ?></div>
                                        </td>
                                        <td><?= $b['stok_minimal'] ?> <?= $b['satuan'] ?></td>
                                        <td><?= $b['satuan'] ?></td>
                                        <td class="harga-text"><?= formatRupiah($b['harga_beli_terakhir'] ?? 0) ?></td>
                                        <td class="harga-text"><?= formatRupiah($b['harga_jual'] ?? 0) ?></td>
                                        <td><?= $tindakan ?></td>
                                        <td>
                                            <button class="btn-edit" onclick="editBarang(<?= $b['id'] ?>)">✏️ Edit</button>
                                            <button class="btn-delete" onclick="deleteBarang(<?= $b['id'] ?>)">🗑️ Hapus</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="empty-state">📦 Belum ada data barang</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah/Edit Barang -->
    <div id="barangModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Barang</h3>
                <span class="modal-close" onclick="closeModal()">&times;</span>
            </div>
            <form id="barangForm" method="POST">
                <input type="hidden" name="id" id="barangId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Barang *</label>
                        <input type="text" name="nama_barang" id="namaBarang" required>
                    </div>
                    <div class="form-group">
                        <label>Stok Awal</label>
                        <input type="number" name="stok" id="stokBarang" value="0">
                    </div>
                    <div class="form-group">
                        <label>Stok Minimal *</label>
                        <input type="number" name="stok_minimal" id="stokMinimal" value="5" required>
                    </div>
                    <div class="form-group">
                        <label>Satuan *</label>
                        <input type="text" name="satuan" id="satuanBarang" value="pcs" required>
                    </div>
                    <div class="form-group">
                        <label>Harga Jual *</label>
                        <input type="number" name="harga_jual" id="hargaJual" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                </div>
            </form>
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
        
        function openModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Barang';
            document.getElementById('barangForm').reset();
            document.getElementById('barangId').value = '';
            document.getElementById('stokBarang').value = '0';
            document.getElementById('stokMinimal').value = '5';
            document.getElementById('satuanBarang').value = 'pcs';
            document.getElementById('barangModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('barangModal').classList.remove('show');
        }
        
        function editBarang(id) {
            fetch('../api/barang.php?action=list')
                .then(res => res.json())
                .then(data => {
                    const barang = data.find(b => b.id == id);
                    if (barang) {
                        document.getElementById('modalTitle').innerText = 'Edit Barang';
                        document.getElementById('barangId').value = barang.id;
                        document.getElementById('namaBarang').value = barang.nama_barang;
                        document.getElementById('stokBarang').value = barang.stok;
                        document.getElementById('stokMinimal').value = barang.stok_minimal;
                        document.getElementById('satuanBarang').value = barang.satuan;
                        document.getElementById('hargaJual').value = barang.harga_jual;
                        openModal();
                    }
                });
        }
        
        function deleteBarang(id) {
            if (confirm('⚠️ Yakin ingin menghapus barang ini? Semua data terkait akan ikut terhapus!')) {
                fetch('../api/barang.php?action=delete&id=' + id)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ ' + data.message);
                            location.reload();
                        } else {
                            alert('❌ ' + data.message);
                        }
                    });
            }
        }
        
        document.getElementById('barangForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('barangId').value;
            const url = id ? '../api/barang.php?action=update' : '../api/barang.php?action=add';
            const formData = new FormData(this);
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Memproses...';
            
            fetch(url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (data.success) {
                        alert('✅ ' + data.message);
                        closeModal();
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    alert('❌ Error: ' + error.message);
                });
        });
        
        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            const modal = document.getElementById('barangModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>