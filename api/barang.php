<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'list':
        $stmt = $pdo->query("
            SELECT 
                b.*, 
                hj.harga_jual, 
                hj.margin_persen,
                COALESCE((SELECT harga_beli FROM riwayat_harga_beli WHERE barang_id = b.id ORDER BY tanggal_beli DESC LIMIT 1), 0) as harga_beli_terakhir
            FROM barang b
            LEFT JOIN harga_jual_aktif hj ON b.id = hj.barang_id
            ORDER BY b.id DESC
        ");
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nama = $_POST['nama_barang'];
            $stok = $_POST['stok'];
            $minimal = $_POST['stok_minimal'];
            $satuan = $_POST['satuan'];
            $harga = $_POST['harga_jual'];
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO barang (nama_barang, stok, stok_minimal, satuan) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama, $stok, $minimal, $satuan]);
                $id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO harga_jual_aktif (barang_id, harga_jual) VALUES (?, ?)");
                $stmt->execute([$id, $harga]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Barang berhasil ditambahkan']);
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $nama = $_POST['nama_barang'];
            $minimal = $_POST['stok_minimal'];
            $satuan = $_POST['satuan'];
            $harga = $_POST['harga_jual'];
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE barang SET nama_barang = ?, stok_minimal = ?, satuan = ? WHERE id = ?");
                $stmt->execute([$nama, $minimal, $satuan, $id]);
                $stmt = $pdo->prepare("INSERT INTO harga_jual_aktif (barang_id, harga_jual) VALUES (?, ?) ON DUPLICATE KEY UPDATE harga_jual = ?");
                $stmt->execute([$id, $harga, $harga]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Barang berhasil diupdate']);
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'delete':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            try {
                // Hapus barang (cascade akan menghapus data terkait)
                $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true, 'message' => 'Barang berhasil dihapus']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus barang']);
                }
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>