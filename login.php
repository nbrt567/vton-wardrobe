<?php
session_start();

// Veritabanı yapılandırması
$host = 'localhost'; 
$dbname = 'vton_wardrobe'; 
$kullanici = 'root'; 
$sifre = '';

try { 
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre); 
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { 
    // Güvenlik: Canlı ortamda veritabanı hataları dışarıya (kullanıcıya) basılmaz.
    die("Sistem bakımda, lütfen daha sonra tekrar deneyin."); 
}

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Gelen verileri temizle
    $email = trim($_POST['email'] ?? ''); 
    $pass = $_POST['password'] ?? '';
    
    // Güvenlik: Sadece gerekli sütunları çek ve LIMIT 1 ile sorguyu optimize et
    $sorgu = $db->prepare("SELECT id, email, password, role FROM users WHERE email = :email LIMIT 1");
    $sorgu->execute([':email' => $email]);
    $user = $sorgu->fetch(PDO::FETCH_ASSOC);
    
    // GÜVENLİK (Account Enumeration Önlemi): "E-posta yok" veya "Şifre yanlış" diye ayırmıyoruz.
    if ($user && password_verify($pass, $user['password'])) {
        
        // GÜVENLİK (Session Fixation Önlemi): Giriş başarılıysa oturum kimliğini yenile
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role']; 
        
        // Role göre yönlendirme
        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: wardrobe.php");
        }
        exit;
        
    } else {
        // Hata durumunda kullanıcıya gösterilecek modern uyarı ekranı
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Giriş Başarısız - VTON Dolap</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f5f6fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .hata-kutu { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 400px; border: 1px solid #f1f2f6; }
                .hata-ikon { font-size: 50px; margin-bottom: 15px; }
                .hata-baslik { color: #ff4757; margin-top: 0; font-size: 24px; }
                .hata-metin { color: #576574; margin-bottom: 30px; line-height: 1.5; font-size: 15px; }
                .btn-geri { background-color: #2f3640; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: background 0.3s; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .btn-geri:hover { background-color: #1e272e; transform: translateY(-2px); }
            </style>
        </head>
        <body>
            <div class="hata-kutu">
                <div class="hata-ikon">⚠️</div>
                <h3 class="hata-baslik">Giriş Başarısız</h3>
                <p class="hata-metin">Girdiğiniz e-posta adresi veya şifre hatalı. Lütfen bilgilerinizi kontrol edip tekrar deneyin.</p>
                <a href="index.html" class="btn-geri">Giriş Sayfasına Dön</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    // POST dışında bir yöntemle (örneğin URL'ye yazarak) gelinirse ana sayfaya at
    header("Location: index.html");
    exit;
}
?>