<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT $key FROM pengaturan_toko WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result[$key] : '';
}
?>