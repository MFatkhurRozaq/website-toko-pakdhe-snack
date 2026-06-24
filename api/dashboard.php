<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'stats':
        $totalBarang = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
        $totalStok = $pdo->query("SELECT COALESCE(SUM(stok), 0) FROM barang")->fetchColumn();
        $penjualanHari = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM transaksi_header WHERE tanggal = CURDATE()")->fetchColumn();
        $totalPiutang = $pdo->query("SELECT COALESCE(SUM(total_utang), 0) FROM piutang WHERE status = 'belum'")->fetchColumn();
        $stokMenipis = $pdo->query("SELECT COUNT(*) FROM barang WHERE stok <= stok_minimal")->fetchColumn();
        
        echo json_encode([
            'totalBarang' => $totalBarang,
            'totalStok' => $totalStok,
            'penjualanHari' => $penjualanHari,
            'totalPiutang' => $totalPiutang,
            'stokMenipis' => $stokMenipis
        ]);
        break;
        
    case 'stok_menipis':
        $stmt = $pdo->query("SELECT * FROM barang WHERE stok <= stok_minimal ORDER BY (stok / stok_minimal) ASC LIMIT 5");
        $list = $stmt->fetchAll();
        $total = count($list);
        
        echo json_encode([
            'total' => $total,
            'list' => $list
        ]);
        break;
        
    case 'recent':
        $stmt = $pdo->prepare("
            SELECT h.id, h.no_transaksi, h.tanggal, h.total, h.is_piutang, COUNT(d.id) as item_count
            FROM transaksi_header h
            LEFT JOIN transaksi_penjualan d ON h.id = d.header_id
            GROUP BY h.id
            ORDER BY h.id DESC
            LIMIT 5
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>