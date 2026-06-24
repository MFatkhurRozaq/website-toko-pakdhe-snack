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
        $status = $_GET['status'] ?? 'belum';
        $stmt = $pdo->prepare("
            SELECT p.*, b.nama_barang 
            FROM piutang p 
            JOIN barang b ON p.barang_id = b.id 
            WHERE p.status = ? 
            ORDER BY p.due_date ASC
        ");
        $stmt->execute([$status]);
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'bayar':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['piutang_id'];
            $bayar = $_POST['jumlah_bayar'];
            
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT total_utang FROM piutang WHERE id = ?");
                $stmt->execute([$id]);
                $total = $stmt->fetch()['total_utang'];
                
                $stmt = $pdo->prepare("INSERT INTO pembayaran_piutang (piutang_id, jumlah_bayar, tanggal_bayar) VALUES (?, ?, CURDATE())");
                $stmt->execute([$id, $bayar]);
                
                if ($bayar >= $total) {
                    $stmt = $pdo->prepare("UPDATE piutang SET status = 'lunas' WHERE id = ?");
                    $stmt->execute([$id]);
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil dicatat']);
            } catch(Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>