<?php
session_start();

// 1. Kullanıcı giriş yapmamışsa işlemi durdur
if (!isset($_SESSION['user_id'])) {
    die("HATA: Kıyafet yüklemek için giriş yapmanız gerekmektedir. <a href='index.html'>Giriş Yap</a>");
}

$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    
    $kategori = isset($_POST['category']) ? $_POST['category'] : 'ust_giyim';
    $user_id = $_SESSION['user_id'];
    
    $hedef_klasor = "uploads/";
    if (!file_exists($hedef_klasor)) {
        mkdir($hedef_klasor, 0777, true);
    }

    $dosya_uzantisi = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $yeni_dosya_adi = $hedef_klasor . time() . "_" . uniqid() . "." . $dosya_uzantisi;

    $gecerli_uzantilar = array("jpg", "jpeg", "png", "webp");
    if(!in_array($dosya_uzantisi, $gecerli_uzantilar)) {
        die("HATA: Sadece JPG, JPEG, PNG ve WEBP dosyaları yüklenebilir. <a href='ekle.html'>Geri dön</a>");
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $yeni_dosya_adi)) {
        try {
            $sorgu = $db->prepare("INSERT INTO garments (user_id, category, image_path) VALUES (:uid, :kat, :img)");
            $sorgu->execute([
                ':uid' => $user_id, 
                ':kat' => $kategori, 
                ':img' => $yeni_dosya_adi
            ]);
            
            // --- BAŞARI EKRANI TASARIMI ---
            ?>
            <!DOCTYPE html>
            <html lang="tr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Başarıyla Eklendi - VTON Dolap</title>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        background-color: #f1f2f6;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    }
                    .basari-karti {
                        background: white;
                        padding: 40px;
                        border-radius: 12px;
                        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                        text-align: center;
                        max-width: 400px;
                        width: 90%;
                        animation: popup 0.4s ease-out;
                    }
                    @keyframes popup {
                        from { transform: scale(0.8); opacity: 0; }
                        to { transform: scale(1); opacity: 1; }
                    }
                    .basari-karti .icon {
                        font-size: 60px;
                        margin-bottom: 20px;
                        display: block;
                    }
                    .basari-karti h2 {
                        margin-top: 0;
                        color: #2ed573;
                        font-size: 24px;
                    }
                    .basari-karti p {
                        color: #576574;
                        margin-bottom: 30px;
                        font-weight: 500;
                    }
                    .btn-grup {
                        display: flex;
                        flex-direction: column;
                        gap: 15px;
                    }
                    .btn-devam {
                        background-color: #f1f2f6;
                        color: #2f3640;
                        text-decoration: none;
                        padding: 14px;
                        border-radius: 8px;
                        font-weight: bold;
                        transition: background 0.3s;
                    }
                    .btn-devam:hover {
                        background-color: #dfe4ea;
                    }
                    .btn-dolap {
                        background-color: #00a8ff;
                        color: white;
                        text-decoration: none;
                        padding: 14px;
                        border-radius: 8px;
                        font-weight: bold;
                        transition: background 0.3s;
                        box-shadow: 0 4px 10px rgba(0, 168, 255, 0.3);
                    }
                    .btn-dolap:hover {
                        background-color: #0097e6;
                    }
                </style>
            </head>
            <body>
                <div class="basari-karti">
                    <span class="icon">✨</span>
                    <h2>Kıyafet Başarıyla Eklendi!</h2>
                    <p>Yeni kıyafetin dolabına sorunsuz bir şekilde yerleştirildi. Şimdi ne yapmak istersin?</p>
                    <div class="btn-grup">
                        <a href="ekle.html" class="btn-devam">➕ Kıyafet Eklemeye Devam Et</a>
                        <a href="wardrobe.php" class="btn-dolap">👗 Dolabıma Git</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit; // İşlemi burada sonlandır
            
        } catch(PDOException $e) {
            echo "<h3>Veritabanı Kayıt Hatası:</h3>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<a href='ekle.html'>Geri dön</a>";
        }
    } else {
        echo "HATA: Dosya sunucuya yüklenirken bir problem oluştu. <a href='ekle.html'>Geri dön</a>";
    }
} else {
    echo "HATA: Lütfen bir dosya seçtiğinizden emin olun. <a href='ekle.html'>Geri dön</a>";
}
?>