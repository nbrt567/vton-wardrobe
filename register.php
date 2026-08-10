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
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $sorgu = $db->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
        // Fazladan tırnak işareti kaldırıldı:
        $sorgu->execute([':email' => $email, ':password' => $pass]);
        
        $user_id = $db->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        
        header("Location: wardrobe.php");
        exit;
    } catch(PDOException $e) {
        echo "<h3>Kayıt Hatası Detayı:</h3>";
        echo "<p style='color:red; font-family:monospace;'>" . $e->getMessage() . "</p>";
        echo "<br><a href='index.html'>Geri dön</a>";
    }
}
?>