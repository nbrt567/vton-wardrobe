<?php
session_start();

// 1. Kullanıcı giriş yapmamışsa işlemi durdur (Güvenlik)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// Veritabanı yapılandırması
$host = 'localhost'; 
$dbname = 'vton_wardrobe'; 
$kullanici = 'root'; 
$sifre = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Sistem bakımda, lütfen daha sonra tekrar deneyin.");
}

// Arayüz için kullanılacak değişkenler
$islem_durumu = 'hata';
$baslik = 'Yükleme Başarısız';
$mesaj = 'Bilinmeyen bir hata oluştu.';
$ikon = '⚠️';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    
    $kategori = $_POST['category'] ?? 'ust_giyim';
    $user_id = $_SESSION['user_id'];
    $dosya = $_FILES['image'];
    
    // Yükleme sırasında PHP tabanlı bir hata oluştu mu?
    if ($dosya['error'] !== UPLOAD_ERR_OK) {
        $mesaj = "Dosya sunucuya iletilirken bir problem oluştu (Hata Kodu: " . $dosya['error'] . ").";
    } 
    // Dosya boyutu kontrolü (Maksimum 5MB)
    elseif ($dosya['size'] > 5 * 1024 * 1024) {
        $mesaj = "Seçtiğiniz fotoğraf çok büyük. Lütfen 5MB'dan küçük bir dosya yükleyin.";
    } 
    else {
        // Hedef klasör kontrolü
        $hedef_klasor = "uploads/";
        if (!file_exists($hedef_klasor)) {
            mkdir($hedef_klasor, 0777, true);
        }

        // GÜVENLİK: Dosyanın gerçek MIME türünü (içeriğini) kontrol et
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_tipi = $finfo->file($dosya['tmp_name']);
        
        $gecerli_mimeler = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($mime_tipi, $gecerli_mimeler)) {
            $mesaj = "Güvenlik İhlali: Sadece gerçek JPG, PNG veya WEBP görsel dosyaları yüklenebilir.";
        } else {
            // Güvenli ve benzersiz dosya adı oluşturma
            $dosya_uzantisi = strtolower(pathinfo($dosya["name"], PATHINFO_EXTENSION));
            // Sadece uzantı jpg, jpeg, png veya webp ise kabul et (Çift güvenlik)
            if (in_array($dosya_uzantisi, ['jpg', 'jpeg', 'png', 'webp'])) {
                $yeni_dosya_adi = $hedef_klasor . time() . "_" . bin2hex(random_bytes(8)) . "." . $dosya_uzantisi;

                // Dosyayı sunucuya taşı
                if (move_uploaded_file($dosya["tmp_name"], $yeni_dosya_adi)) {
                    try {
                        $sorgu = $db->prepare("INSERT INTO garments (user_id, category, image_path) VALUES (:uid, :kat, :img)");
                        $sorgu->execute([
                            ':uid' => $user_id, 
                            ':kat' => $kategori, 
                            ':img' => $yeni_dosya_adi
                        ]);
                        
                        // Her şey başarılı!
                        $islem_durumu = 'basari';
                        $baslik = 'Kıyafet Başarıyla Eklendi!';
                        $mesaj = 'Yeni kıyafetin dolabına sorunsuz bir şekilde yerleştirildi. Şimdi ne yapmak istersin?';
                        $ikon = '✨';
                        
                    } catch(PDOException $e) {
                        $mesaj = "Dosya yüklendi ancak veritabanına kaydedilirken bir hata oluştu.";
                    }
                } else {
                    $mesaj = "Dosya sunucu dizinine taşınamadı. Klasör yazma yetkilerini kontrol edin.";
                }
            } else {
                $mesaj = "Geçersiz dosya uzantısı.";
            }
        }
    }
} else {
    $mesaj = "Lütfen yüklemek için bir görsel seçtiğinizden emin olun.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $islem_durumu === 'basari' ? 'Başarılı' : 'Hata' ?> - VTON Dolap</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f2f6; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .sonuc-karti { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); text-align: center; max-width: 400px; width: 90%; animation: popup 0.4s ease-out; }
        @keyframes popup { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .icon { font-size: 60px; margin-bottom: 20px; display: block; }
        
        .baslik { margin-top: 0; font-size: 24px; }
        .baslik.basari { color: #2ed573; }
        .baslik.hata { color: #ff4757; }
        
        .metin { color: #576574; margin-bottom: 30px; font-weight: 500; line-height: 1.5; }
        
        .btn-grup { display: flex; flex-direction: column; gap: 15px; }
        .btn { text-decoration: none; padding: 14px; border-radius: 8px; font-weight: bold; transition: all 0.3s; }
        
        .btn-ikincil { background-color: #f1f2f6; color: #2f3640; }
        .btn-ikincil:hover { background-color: #dfe4ea; transform: translateY(-2px); }
        
        .btn-ana { background-color: #00a8ff; color: white; box-shadow: 0 4px 10px rgba(0, 168, 255, 0.3); }
        .btn-ana:hover { background-color: #0097e6; transform: translateY(-2px); }
        
        .btn-hata { background-color: #2f3640; color: white; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .btn-hata:hover { background-color: #1e272e; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="sonuc-karti">
        <span class="icon"><?= $ikon ?></span>
        <h2 class="baslik <?= $islem_durumu ?>"><?= $baslik ?></h2>
        <p class="metin"><?= htmlspecialchars($mesaj, ENT_QUOTES) ?></p>
        
        <div class="btn-grup">
            <?php if ($islem_durumu === 'basari'): ?>
                <a href="ekle.html" class="btn btn-ikincil">➕ Kıyafet Eklemeye Devam Et</a>
                <a href="wardrobe.php" class="btn btn-ana">👗 Dolabıma Git</a>
            <?php else: ?>
                <a href="ekle.html" class="btn btn-hata">Geri Dön ve Tekrar Dene</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>