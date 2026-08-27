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
    // Güvenlik: Canlı ortamda veritabanı hataları dışarıya basılmaz.
    die("Sistem bakımda, lütfen daha sonra tekrar deneyin."); 
}

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = trim($_POST['email'] ?? '');
    $raw_password = $_POST['password'] ?? '';
    
    // 1. Veri Doğrulama (Validation)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($raw_password)) {
        $hata_mesaji = "Lütfen geçerli bir e-posta adresi ve şifre girin.";
    } else {
        // Şifreyi güvenli bir şekilde şifrele (Hash)
        $pass = password_hash($raw_password, PASSWORD_DEFAULT);
        
        try {
            $sorgu = $db->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
            $sorgu->execute([':email' => $email, ':password' => $pass]);
            
            $user_id = $db->lastInsertId();
            
            // 2. Güvenlik: Oturum sabitleme (Session Fixation) saldırısını engelle
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user'; // Yeni kayıtlara standart kullanıcı rolü ver
            
            header("Location: wardrobe.php");
            exit;
            
        } catch(PDOException $e) {
            // 3. Özel Hata Yakalama (23000: Unique kısıtlaması ihlali - Duplicate Entry)
            if ($e->getCode() == 23000) {
                $hata_mesaji = "Bu e-posta adresi ile zaten bir hesap oluşturulmuş.";
            } else {
                // Diğer veritabanı hataları
                $hata_mesaji = "Kayıt işlemi sırasında bir sistem hatası oluştu. Lütfen tekrar deneyin.";
            }
        }
    }
    
    // Hata oluştuysa modern hata ekranını göster
    if (isset($hata_mesaji)) {
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Kayıt Hatası - VTON Dolap</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f5f6fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .hata-kutu { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 400px; border: 1px solid #f1f2f6; width: 90%; }
                .hata-ikon { font-size: 50px; margin-bottom: 15px; }
                .hata-baslik { color: #ff4757; margin-top: 0; font-size: 24px; }
                .hata-metin { color: #576574; margin-bottom: 30px; line-height: 1.5; font-size: 15px; font-weight: bold; }
                .btn-geri { background-color: #2f3640; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: background 0.3s; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .btn-geri:hover { background-color: #1e272e; transform: translateY(-2px); }
            </style>
        </head>
        <body>
            <div class="hata-kutu">
                <div class="hata-ikon">⚠️</div>
                <h3 class="hata-baslik">Kayıt Başarısız</h3>
                <p class="hata-metin"><?= htmlspecialchars($hata_mesaji, ENT_QUOTES) ?></p>
                <a href="index.html" class="btn-geri">Giriş Sayfasına Dön</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    // POST dışı bir istekle (URL bar) gelinirse
    header("Location: index.html");
    exit;
}
?>