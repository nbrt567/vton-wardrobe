<?php
// 1. Veritabanı Bağlantısı
$host = 'localhost';
$dbname = 'vton_wardrobe';
$kullanici = 'root';
$sifre = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// 2. Kıyafetleri veritabanından çek (Kullanıcı ID = 1)
$sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = 1 ORDER BY id DESC");
$sorgu->execute();
$kiyafetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dijital Dolabım</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Dolap Grid Tasarımı (Sadece bu sayfaya özel) */
        .dolap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .kiyafet-kart {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease;
        }
        .kiyafet-kart:hover {
            transform: translateY(-5px);
        }
        .kiyafet-kart img {
            max-width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 8px;
        }
        .kategori-etiket {
            display: inline-block;
            margin-top: 15px;
            padding: 5px 12px;
            background-color: #00a8ff;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .baslik-alani {
            text-align: center;
            padding: 20px;
        }
        .yeni-ekle-btn {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            color: #00a8ff;
            font-weight: bold;
        }
        /* Dolap Grid Tasarımı (Sadece bu sayfaya özel) */
.dolap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 25px;
    padding: 20px;
    max-width: 1000px;
    margin: 0 auto;
}
.kiyafet-kart {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kiyafet-kart:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.kategori-baslik {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: #2f3640;
    border-bottom: 2px solid #f5f6fa;
    padding-bottom: 10px;
}
.kiyafet-kart img {
    width: 100%;
    height: 200px;
    object-fit: contain;
    border-radius: 8px;
    margin-bottom: 15px;
}
.sec-btn {
    display: block;
    width: 100%;
    background-color: #4cd137; /* Şık bir yeşil tonu */
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
    transition: background-color 0.3s ease;
}
.sec-btn:hover {
    background-color: #44bd32;
}
.baslik-alani {
    text-align: center;
    padding: 20px;
}
.yeni-ekle-btn {
    display: inline-block;
    margin-top: 10px;
    text-decoration: none;
    color: #00a8ff;
    font-weight: bold;
}
    </style>
</head>
<body>
    <body>
    <div class="baslik-alani">
        <h2>Sanal Dolabım</h2>
        <a href="index.html" class="yeni-ekle-btn">+ Yeni Kıyafet Ekle</a>
    </div>

    <div class="dolap-grid">
        <!-- PHP ile kıyafetleri döngüye alıp ekrana yazdırıyoruz -->
        <?php if(count($kiyafetler) > 0): ?>
            <?php foreach($kiyafetler as $kiyafet): ?>
                <div class="kiyafet-kart">
                    <!-- En üstte kategori başlığı -->
                    <h3 class="kategori-baslik">
                        <?php echo $kiyafet['category'] == 'ust' ? 'Üst Giyim' : 'Alt Giyim'; ?>
                    </h3>
                    
                    <!-- Ortada ürün fotoğrafı -->
                    <img src="<?php echo htmlspecialchars($kiyafet['image_path']); ?>" alt="Kıyafet">
                    
                    <!-- En altta seçim butonu -->
                    <button class="sec-btn">Seç</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; grid-column: 1 / -1; color: #7f8fa6;">
                Dolabınız şu an boş. Yeni kıyafetler ekleyerek dolabınızı oluşturmaya başlayın!
            </p>
        <?php endif; ?>
    </div>
</body>
</body>
</html>