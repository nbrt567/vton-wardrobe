<?php
session_start();

// Veritabanı yapılandırması
$host = 'localhost';
$dbname = 'vton_wardrobe';
$user = 'root';
$pass = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Güvenlik: Canlı ortamda veritabanı hataları dışarıya basılmaz.
    die("Veritabanı bağlantı hatası.");
}

// Modern PHP ile oturum kontrolü
$kullanici_id = $_SESSION['user_id'] ?? null;

// Sadece POST isteklerini kabul et ve verileri doğrula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gorsel_url']) && $kullanici_id) {
    
    $gorsel_url = trim($_POST['gorsel_url']);
    
    try {
        $sorgu = $db->prepare("INSERT INTO kaydedilen_kombinler (kullanici_id, gorsel_url) VALUES (?, ?)");
        $sorgu->execute([$kullanici_id, $gorsel_url]);
        
        // JavaScript'in (Frontend) beklediği başarılı yanıtı
        echo "basarili";
        
    } catch (PDOException $e) {
        // Hata detayını gizle, sadece genel bir mesaj ver
        echo "Veritabanına ekleme hatası oluştu.";
    }
} else {
    echo "Hata: Gerekli veriler eksik veya yetkisiz işlem.";
}
?>