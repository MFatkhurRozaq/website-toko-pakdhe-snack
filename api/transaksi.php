<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch($action) {
    // ========== TRANSAKSI MULTIPLE ITEMS ==========
    case 'jual_multiple':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $items = json_decode($_POST['items'], true);
            $total = $_POST['total'];
            $uang_bayar = $_POST['uang_bayar'];
            $is_piutang = isset($_POST['is_piutang']) ? $_POST['is_piutang'] : false;
            $pelanggan_nama = $_POST['pelanggan_nama'] ?? '';
            $no_whatsapp = $_POST['no_whatsapp'] ?? '';
            $due_date = $_POST['due_date'] ?? '';
            
            try {
                $pdo->beginTransaction();
                
                $no_transaksi = 'INV' . date('Ymd') . rand(100, 999);
                $kembalian = ($uang_bayar > 0 && !$is_piutang) ? $uang_bayar - $total : 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi_header 
                    (no_transaksi, tanggal, total, uang_bayar, kembalian, metode_bayar, is_piutang, pelanggan_nama, no_whatsapp, due_date, kasir) 
                    VALUES (?, CURDATE(), ?, ?, ?, 'Tunai', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $no_transaksi, $total, $uang_bayar, $kembalian, 
                    $is_piutang ? 1 : 0, $pelanggan_nama, $no_whatsapp, $due_date, $_SESSION['username']
                ]);
                $header_id = $pdo->lastInsertId();
                
                $detail_items = [];
                foreach ($items as $item) {
                    $stmt = $pdo->prepare("SELECT stok FROM barang WHERE id = ?");
                    $stmt->execute([$item['id']]);
                    $stok = $stmt->fetch()['stok'];
                    
                    if ($stok < $item['qty']) {
                        throw new Exception("Stok {$item['nama']} tidak mencukupi!");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                    $stmt->execute([$item['qty'], $item['id']]);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO transaksi_penjualan 
                        (header_id, barang_id, qty, harga_jual_saat_transaksi, total, tanggal) 
                        VALUES (?, ?, ?, ?, ?, CURDATE())
                    ");
                    $stmt->execute([$header_id, $item['id'], $item['qty'], $item['harga'], $item['subtotal']]);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO log_stok (barang_id, tipe, qty, keterangan) 
                        VALUES (?, 'keluar', ?, 'Penjualan #$no_transaksi')
                    ");
                    $stmt->execute([$item['id'], $item['qty']]);
                    
                    $detail_items[] = [
                        'nama' => $item['nama'],
                        'qty' => $item['qty'],
                        'harga' => $item['harga'],
                        'subtotal' => $item['subtotal']
                    ];
                }
                
                if ($is_piutang && $pelanggan_nama) {
                    $first_item = $items[0];
                    $stmt = $pdo->prepare("
                        INSERT INTO piutang (pelanggan_nama, no_whatsapp, barang_id, qty, total_utang, due_date) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$pelanggan_nama, $no_whatsapp, $first_item['id'], $first_item['qty'], $total, $due_date]);
                }
                
                $stmt = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1");
                $setting = $stmt->fetch();
                if (!$setting) {
                    $setting = ['nama_toko' => 'Pakdhe Snack', 'promo_text' => '', 'whatsapp_number' => ''];
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaksi berhasil!',
                    'total' => $total,
                    'struk' => [
                        'no_transaksi' => $no_transaksi,
                        'total' => $total,
                        'uang_bayar' => $uang_bayar,
                        'kembalian' => $kembalian,
                        'is_piutang' => $is_piutang,
                        'pelanggan_nama' => $pelanggan_nama,
                        'no_whatsapp' => $no_whatsapp,
                        'due_date' => $due_date,
                        'kasir' => $_SESSION['username'],
                        'nama_toko' => $setting['nama_toko'],
                        'items' => $detail_items
                    ]
                ]);
                
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
    
    // ========== GET TRANSAKSI HARI INI - DENGAN TOTAL KESELURUHAN ==========
    case 'hari_ini':
        // Ambil daftar transaksi
        $stmt = $pdo->prepare("
            SELECT h.id, h.no_transaksi, h.tanggal, h.total, h.is_piutang, COUNT(d.id) as item_count
            FROM transaksi_header h
            LEFT JOIN transaksi_penjualan d ON h.id = d.header_id
            WHERE h.tanggal = CURDATE()
            GROUP BY h.id
            ORDER BY h.id DESC
        ");
        $stmt->execute();
        $transactions = $stmt->fetchAll();
        
        // Hitung total keseluruhan penjualan hari ini
        $total_hari_ini = 0;
        foreach ($transactions as $trans) {
            $total_hari_ini += $trans['total'];
        }
        
        // Kirim response dengan data transaksi dan total keseluruhan
        echo json_encode([
            'transactions' => $transactions,
            'total_hari_ini' => $total_hari_ini,
            'jumlah_transaksi' => count($transactions)
        ]);
        break;
    
    // ========== GET DETAIL STRUK ==========
    case 'get_struk':
        $id = $_GET['id'] ?? 0;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID transaksi tidak ditemukan']);
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT * FROM transaksi_header WHERE id = ?");
        $stmt->execute([$id]);
        $header = $stmt->fetch();
        
        if (!$header) {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
            exit();
        }
        
        $stmt = $pdo->prepare("
            SELECT d.*, b.nama_barang 
            FROM transaksi_penjualan d
            JOIN barang b ON d.barang_id = b.id
            WHERE d.header_id = ?
        ");
        $stmt->execute([$id]);
        $details = $stmt->fetchAll();
        
        $items = [];
        foreach ($details as $d) {
            $items[] = [
                'nama' => $d['nama_barang'],
                'qty' => $d['qty'],
                'harga' => $d['harga_jual_saat_transaksi'],
                'subtotal' => $d['total']
            ];
        }
        
        $stmt = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1");
        $setting = $stmt->fetch();
        if (!$setting) {
            $setting = ['nama_toko' => 'Pakdhe Snack'];
        }
        
        echo json_encode([
            'success' => true,
            'struk' => [
                'no_transaksi' => $header['no_transaksi'],
                'total' => $header['total'],
                'uang_bayar' => $header['uang_bayar'],
                'kembalian' => $header['kembalian'],
                'is_piutang' => $header['is_piutang'] == 1,
                'pelanggan_nama' => $header['pelanggan_nama'],
                'no_whatsapp' => $header['no_whatsapp'],
                'due_date' => $header['due_date'],
                'kasir' => $header['kasir'],
                'nama_toko' => $setting['nama_toko'],
                'items' => $items
            ]
        ]);
        break;
    
    // ========== DEFAULT ==========
    default:
        echo json_encode(['error' => 'Action tidak ditemukan']);
}
?>