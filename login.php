<?php
session_start();
$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';
try { $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre); } catch(PDOException $e) { die("Hata: " . $e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    $sorgu = $db->prepare("SELECT * FROM users WHERE email = :email");
    $sorgu->execute([':email' => $email]);
    $user = $sorgu->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        header("Location: wardrobe.php");
        exit;
    } else {
        echo "Hatalı e-posta veya şifre! <a href='index.html'>Geri dön</a>";
    }
}
?>