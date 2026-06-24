<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$periode = $_GET['periode'] ?? 'hari';

switch($action) {
    case 'stats':
        switch($periode) {
            case 'minggu':
                $dateCondition = "tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'bulan':
                $dateCondition = "tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            default:
                $dateCondition = "tanggal = CURDATE()";
        }
        
        // Total pemasukan dari penjualan
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total_penjualan, 
                   COUNT(*) as jumlah_transaksi,
                   COALESCE(SUM(CASE WHEN is_piutang = 1 THEN total ELSE 0 END), 0) as total_piutang_dari_transaksi
            FROM transaksi_header
            WHERE $dateCondition
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        // ========== PERBAIKAN: Total Piutang Belum Lunas (REAL TIME) ==========
        // Hitung total piutang yang statusnya 'belum' (belum lunas)
        // Tidak terpengaruh periode, karena piutang adalah akumulasi dari semua transaksi yang belum lunas
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_utang), 0) as total_piutang_belum_lunas
            FROM piutang
            WHERE status = 'belum'
        ");
        $stmt->execute();
        $piutangResult = $stmt->fetch();
        $total_piutang_belum_lunas = $piutangResult['total_piutang_belum_lunas'];
        
        // Total pengeluaran dari restok barang
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total), 0) as total_pengeluaran_restok,
                   COUNT(*) as jumlah_restok
            FROM pengeluaran
            WHERE $dateCondition
        ");
        $stmt->execute();
        $pengeluaran_restok = $stmt->fetch();
        
        // Total pengeluaran operasional
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(jumlah), 0) as total_operasional,
                   COUNT(*) as jumlah_operasional
            FROM operasional
            WHERE $dateCondition
        ");
        $stmt->execute();
        $operasional = $stmt->fetch();
        
        // Total pengeluaran keseluruhan
        $total_pengeluaran = $pengeluaran_restok['total_pengeluaran_restok'] + $operasional['total_operasional'];
        
        // Hitung keuntungan bersih
        $keuntungan_bersih = $result['total_penjualan'] - $total_pengeluaran;
        
        echo json_encode([
            'total_penjualan' => (int)$result['total_penjualan'],
            'jumlah_transaksi' => (int)$result['jumlah_transaksi'],
            'total_piutang' => (int)$total_piutang_belum_lunas, // PERBAIKAN: total piutang real time
            'total_piutang_dari_transaksi' => (int)$result['total_piutang_dari_transaksi'],
            'total_pengeluaran_restok' => (int)$pengeluaran_restok['total_pengeluaran_restok'],
            'jumlah_restok' => (int)$pengeluaran_restok['jumlah_restok'],
            'total_operasional' => (int)$operasional['total_operasional'],
            'jumlah_operasional' => (int)$operasional['jumlah_operasional'],
            'total_pengeluaran' => $total_pengeluaran,
            'keuntungan_bersih' => $keuntungan_bersih
        ]);
        break;
        
    case 'detail':
        switch($periode) {
            case 'minggu':
                $dateCondition = "h.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'bulan':
                $dateCondition = "h.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            default:
                $dateCondition = "h.tanggal = CURDATE()";
        }
        
        $stmt = $pdo->prepare("
            SELECT h.id, h.no_transaksi, h.tanggal, h.total, h.is_piutang, COUNT(d.id) as item_count
            FROM transaksi_header h
            LEFT JOIN transaksi_penjualan d ON h.id = d.header_id
            WHERE $dateCondition
            GROUP BY h.id
            ORDER BY h.tanggal DESC, h.id DESC
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'pengeluaran_restok':
        switch($periode) {
            case 'minggu':
                $dateCondition = "p.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'bulan':
                $dateCondition = "p.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            default:
                $dateCondition = "p.tanggal = CURDATE()";
        }
        
        $stmt = $pdo->prepare("
            SELECT p.*, b.nama_barang, b.satuan 
            FROM pengeluaran p
            JOIN barang b ON p.barang_id = b.id
            WHERE $dateCondition
            ORDER BY p.tanggal DESC, p.id DESC
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'pengeluaran_operasional':
        switch($periode) {
            case 'minggu':
                $dateCondition = "o.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'bulan':
                $dateCondition = "o.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            default:
                $dateCondition = "o.tanggal = CURDATE()";
        }
        
        $stmt = $pdo->prepare("
            SELECT o.*, k.nama_kategori, k.icon 
            FROM operasional o
            JOIN kategori_operasional k ON o.kategori_id = k.id
            WHERE $dateCondition
            ORDER BY o.tanggal DESC, o.id DESC
        ");
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>