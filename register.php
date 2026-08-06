<?php
session_start();
$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';
try { $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre); } catch(PDOException $e) { die("Hata: " . $e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // Şifreyi güvenli şekilde şifrele
    
    try {
        $sorgu = $db->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
        $sorgu->execute([':email' => $email, ':password' => $pass]);
        header("Location: index.html?kayit=basarili");
        exit;
    } catch(PDOException $e) {
        echo "Bu e-posta adresi zaten kayıtlı! <a href='index.html'>Geri dön</a>";
    }
}
?>