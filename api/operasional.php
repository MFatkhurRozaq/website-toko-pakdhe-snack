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
            $tanggal = $_POST['tanggal'];
            $kategori_id = $_POST['kategori_id'];
            $deskripsi = $_POST['deskripsi'];
            $jumlah = $_POST['jumlah'];
            $created_by = $_SESSION['username'];
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO operasional (tanggal, kategori_id, deskripsi, jumlah, created_by) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tanggal, $kategori_id, $deskripsi, $jumlah, $created_by]);
                
                echo json_encode(['success' => true, 'message' => 'Pengeluaran berhasil ditambahkan']);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'list':
        $bulan = $_GET['bulan'] ?? date('Y-m');
        $kategori = $_GET['kategori'] ?? '';
        
        $sql = "
            SELECT o.*, k.nama_kategori, k.icon 
            FROM operasional o
            JOIN kategori_operasional k ON o.kategori_id = k.id
            WHERE DATE_FORMAT(o.tanggal, '%Y-%m') = ?
        ";
        $params = [$bulan];
        
        if (!empty($kategori)) {
            $sql .= " AND o.kategori_id = ?";
            $params[] = $kategori;
        }
        
        $sql .= " ORDER BY o.tanggal DESC, o.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        echo json_encode($data);
        break;
        
    case 'stats_bulanan':
        $bulan = $_GET['bulan'] ?? date('Y-m');
        
        $stmt = $pdo->prepare("
            SELECT k.id, k.nama_kategori, k.icon, COALESCE(SUM(o.jumlah), 0) as total
            FROM kategori_operasional k
            LEFT JOIN operasional o ON k.id = o.kategori_id AND DATE_FORMAT(o.tanggal, '%Y-%m') = ?
            GROUP BY k.id
            ORDER BY total DESC
        ");
        $stmt->execute([$bulan]);
        $data = $stmt->fetchAll();
        
        echo json_encode($data);
        break;
        
    case 'total_per_bulan':
        $bulan = $_GET['bulan'] ?? date('Y-m');
        $kategori = $_GET['kategori'] ?? '';
        
        $sql = "SELECT COALESCE(SUM(jumlah), 0) as total FROM operasional WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?";
        $params = [$bulan];
        
        if (!empty($kategori)) {
            $sql .= " AND kategori_id = ?";
            $params[] = $kategori;
            
            // Ambil nama kategori untuk label
            $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori_operasional WHERE id = ?");
            $stmt->execute([$kategori]);
            $kategoriData = $stmt->fetch();
            $kategoriNama = $kategoriData ? $kategoriData['nama_kategori'] : '';
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        $response = [
            'total' => (int)$result['total'],
            'bulan' => $bulan
        ];
        
        if (!empty($kategori) && isset($kategoriNama)) {
            $response['kategori_nama'] = $kategoriNama;
        }
        
        echo json_encode($response);
        break;
        
    case 'total_bulan_ini':
        $bulan = date('Y-m');
        
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(jumlah), 0) as total
            FROM operasional
            WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
        ");
        $stmt->execute([$bulan]);
        $result = $stmt->fetch();
        
        echo json_encode([
            'total' => (int)$result['total'],
            'bulan' => $bulan
        ]);
        break;
        
    case 'get':
        $id = $_GET['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM operasional WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $tanggal = $_POST['tanggal'];
            $kategori_id = $_POST['kategori_id'];
            $deskripsi = $_POST['deskripsi'];
            $jumlah = $_POST['jumlah'];
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE operasional 
                    SET tanggal = ?, kategori_id = ?, deskripsi = ?, jumlah = ?
                    WHERE id = ?
                ");
                $stmt->execute([$tanggal, $kategori_id, $deskripsi, $jumlah, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Pengeluaran berhasil diupdate']);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'delete':
        $id = $_GET['id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM operasional WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Pengeluaran berhasil dihapus']);
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>