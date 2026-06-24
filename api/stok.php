<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'tambah':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $barang_id = $_POST['barang_id'];
            $qty = $_POST['qty'];
            $harga_beli = $_POST['harga_beli'];
            $total = $qty * $harga_beli;
            $keterangan = $_POST['keterangan'] ?? 'Pembelian stok';
            
            // Ambil nama barang untuk keterangan
            $stmt = $pdo->prepare("SELECT nama_barang, satuan FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang = $stmt->fetch();
            
            try {
                $pdo->beginTransaction();
                
                // Update stok barang
                $stmt = $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
                $stmt->execute([$qty, $barang_id]);
                
                // Catat riwayat harga beli
                $stmt = $pdo->prepare("INSERT INTO riwayat_harga_beli (barang_id, harga_beli, tanggal_beli) VALUES (?, ?, CURDATE())");
                $stmt->execute([$barang_id, $harga_beli]);
                
                // Catat pengeluaran (PENTING: untuk laporan keuangan)
                $stmt = $pdo->prepare("INSERT INTO pengeluaran (barang_id, jumlah, harga_beli, total, keterangan, tanggal) VALUES (?, ?, ?, ?, ?, CURDATE())");
                $stmt->execute([$barang_id, $qty, $harga_beli, $total, $keterangan]);
                
                // Catat log stok
                $stmt = $pdo->prepare("INSERT INTO log_stok (barang_id, tipe, qty, keterangan) VALUES (?, 'masuk', ?, ?)");
                $stmt->execute([$barang_id, $qty, $keterangan]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Restok {$barang['nama_barang']} berhasil! Total pengeluaran: Rp " . number_format($total, 0, ',', '.')
                ]);
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'rekomendasi':
        $stmt = $pdo->query("
            SELECT b.*, COALESCE(SUM(t.qty), 0) as terjual 
            FROM barang b 
            LEFT JOIN transaksi_penjualan t ON b.id = t.barang_id AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            WHERE b.stok <= b.stok_minimal * 2 
            GROUP BY b.id 
            ORDER BY terjual DESC
        ");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'pengeluaran':
        $stmt = $pdo->query("
            SELECT p.*, b.nama_barang, b.satuan 
            FROM pengeluaran p 
            JOIN barang b ON p.barang_id = b.id 
            ORDER BY p.tanggal DESC, p.id DESC 
            LIMIT 50
        ");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'log':
        $stmt = $pdo->query("
            SELECT l.*, b.nama_barang 
            FROM log_stok l 
            JOIN barang b ON l.barang_id = b.id 
            ORDER BY l.created_at DESC 
            LIMIT 50
        ");
        echo json_encode($stmt->fetchAll());
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>