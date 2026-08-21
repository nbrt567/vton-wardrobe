<?php
session_start();

require_once 'db.php'; // veya kendi bağlantı dosyanın adı

$kullanici_id = $_SESSION['kullanici_id'] ?? $_SESSION['user_id'] ?? null;

if (isset($_POST['gorsel_url']) && $kullanici_id) {
    $gorsel_url = $_POST['gorsel_url'];
    
    try {
        $sorgu = $db->prepare("INSERT INTO kaydedilen_kombinler (kullanici_id, gorsel_url) VALUES (?, ?)");
        $sorgu->execute([$kullanici_id, $gorsel_url]);
        echo "basarili";
    } catch (PDOException $e) {
        echo "hata: " . $e->getMessage();
    }
} else {
    echo "yetkisiz";
}
?>