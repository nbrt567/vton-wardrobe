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

// 2. Seçilen kıyafeti URL'den yakala
$secilen_kiyafet = null;
if (isset($_GET['id'])) {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE id = :id");
    $sorgu->execute([':id' => $_GET['id']]);
    $secilen_kiyafet = $sorgu->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kombin Odası</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .kombin-alani {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .secilen-foto {
            max-width: 300px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Üst Menü -->
    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="index.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
        </div>
    </nav>

    <!-- Kombin İçeriği -->
    <div class="kombin-alani">
        <h2>Kombin Odası (Yapay Zeka Test Alanı)</h2>
        
        <?php if($secilen_kiyafet): ?>
            <p>Seçtiğiniz <strong><?php echo $secilen_kiyafet['category'] == 'ust' ? 'Üst Giyim' : 'Alt Giyim'; ?></strong> başarıyla mankene aktarıldı.</p>
            <img src="<?php echo htmlspecialchars($secilen_kiyafet['image_path']); ?>" class="secilen-foto" alt="Seçilen Kıyafet">
            <br>
            <button class="sec-btn" style="max-width: 200px; margin: 0 auto;">VTON İşlemini Başlat</button>
        <?php else: ?>
            <p>Henüz bir kıyafet seçilmedi. Lütfen dolaptan bir ürün seçin.</p>
        <?php endif; ?>
    </div>
</body>
</html>