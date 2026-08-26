<?php
session_start();

// Hata gösterme ayarları (gerekirse kapatabilirsin)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Doğrudan veritabanı bağlantısı
$host = 'localhost';
$dbname = 'vton_wardrobe';
$user = 'root';
$pass = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Veritabanı bağlantı hatası: " . $e->getMessage();
    exit;
}

// Oturum kontrolü - projende 'user_id' kullanılıyor
$kullanici_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Gelen POST isteğini kontrol et
if (isset($_POST['gorsel_url']) && $kullanici_id) {
    $gorsel_url = $_POST['gorsel_url'];
    
    try {
        $sorgu = $db->prepare("INSERT INTO kaydedilen_kombinler (kullanici_id, gorsel_url) VALUES (?, ?)");
        $sorgu->execute([$kullanici_id, $gorsel_url]);
        echo "basarili";
    } catch (PDOException $e) {
        echo "Veritabanına ekleme hatası: " . $e->getMessage();
    }
} else {
    echo "Hata: Gerekli veriler eksik veya kullanıcı girişi yapılmamış.";
}
?>