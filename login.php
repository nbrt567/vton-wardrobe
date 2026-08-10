<?php
session_start();
$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';

try { 
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre); 
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { 
    die("Veritabanı bağlantı hatası: " . $e->getMessage()); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']); 
    $pass = $_POST['password'];
    
    $sorgu = $db->prepare("SELECT * FROM users WHERE email = :email");
    $sorgu->execute([':email' => $email]);
    $user = $sorgu->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<h3 style='color:red;'>HATA: E-Posta Bulunamadı!</h3>";
        echo "<p>Girdiğiniz <b>$email</b> adresi veritabanında kayıtlı değil.</p>";
        echo "<a href='index.html'>Geri dön</a>";
    } else {
        if (password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role']; // Rolü oturuma kaydediyoruz
            
            // Eğer adminse admin.php'ye, değilse wardrobe.php'ye yönlendir
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: wardrobe.php");
            }
            exit;
        } else {
            echo "<h3 style='color:orange;'>HATA: Şifre Yanlış!</h3>";
            echo "<p>E-posta adresi doğru bulundu ancak girdiğiniz şifre eşleşmiyor.</p>";
            echo "<a href='index.html'>Geri dön</a>";
        }
    }
}
?>