<?php
session_start();

$host = 'localhost';
$dbname = 'vton_wardrobe';
$kullanici = 'root';
$sifre = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// 1. Sepetten Tek Bir Ürün Çıkarma İşlemi
if (isset($_GET['sil']) && isset($_SESSION['sepet'])) {
    $silinecek_id = $_GET['sil'];
    
    // Array içinde bu kıyafetin ID'sini bul
    $anahtar = array_search($silinecek_id, $_SESSION['sepet']);
    
    // Eğer bulunduysa diziden sil
    if ($anahtar !== false) {
        unset($_SESSION['sepet'][$anahtar]);
        // Dizide boşluk kalmaması için indeksleri yeniden sırala
        $_SESSION['sepet'] = array_values($_SESSION['sepet']);
    }
    
    // URL'yi temizlemek için sayfayı yenile
    header("Location: kombin.php");
    exit;
}

// 2. Sepeti Tamamen Boşaltma İşlemi
if (isset($_GET['temizle'])) {
    unset($_SESSION['sepet']);
    header("Location: kombin.php");
    exit;
}

// 3. Eğer sepette ürün varsa, onları veritabanından çek
$sepetteki_kiyafetler = [];
if (isset($_SESSION['sepet']) && count($_SESSION['sepet']) > 0) {
    $id_listesi = implode(',', $_SESSION['sepet']); 
    $sorgu = $db->query("SELECT * FROM garments WHERE id IN ($id_listesi)");
    $sepetteki_kiyafetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kombin Sepeti</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .kombin-alani { max-width: 900px; margin: 0 auto; text-align: center; padding: 40px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .sepet-grid { display: flex; justify-content: center; gap: 20px; margin: 30px 0; flex-wrap: wrap; }
        .sepet-item { border: 1px solid #dcdde1; border-radius: 8px; padding: 15px; width: 160px; background: #f8f9fa; }
        .sepet-item img { width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
        .vton-btn { display: inline-block; background-color: #00a8ff; color: white; padding: 15px 30px; border-radius: 8px; font-size: 18px; font-weight: bold; border: none; cursor: pointer; text-decoration: none; margin-top: 20px; }
        .vton-btn:hover { background-color: #0097e6; }
        .sepet-temizle { color: #e84118; text-decoration: none; font-size: 15px; margin-top: 15px; display: inline-block; font-weight: bold; }
        .sepet-temizle:hover { text-decoration: underline; }
        /* Yeni Eklenen Silme Butonu Stili */
        .sil-btn { display: block; margin-top: 10px; padding: 8px; background-color: #ff4757; color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; transition: background-color 0.2s; }
        .sil-btn:hover { background-color: #ff6b81; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            <a href="kombin.php" style="color: #ff4757;">🛒 Kombin Sepeti (<?php echo isset($_SESSION['sepet']) ? count($_SESSION['sepet']) : 0; ?>)</a>
        </div>
    </nav>

    <div class="kombin-alani">
        <h2>Kombin Sepeti (VTON Test Alanı)</h2>
        
        <?php if(count($sepetteki_kiyafetler) > 0): ?>
            <p>Seçtiğiniz kıyafetler aşağıda listelenmiştir. Hazırsanız manken üzerinde deneyebilirsiniz.</p>
            
            <div class="sepet-grid">
                <?php foreach($sepetteki_kiyafetler as $kiyafet): ?>
                    <div class="sepet-item">
                        <strong><?php echo $kiyafet['category'] == 'ust' ? 'Üst' : 'Alt'; ?></strong>
                        <img src="<?php echo htmlspecialchars($kiyafet['image_path']); ?>" alt="Seçilen Kıyafet">
                        
                        <!-- Tekli Silme Butonu -->
                        <a href="kombin.php?sil=<?php echo $kiyafet['id']; ?>" class="sil-btn">Sepetten Çıkar</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="vton-btn">✨ Kombini Manken Üzerinde Dene (VTON Başlat)</button>
            <br><br>
            <a href="wardrobe.php" class="sepet-temizle" style="color: #7f8fa6;">Dolaba Dön ve Seçime Devam Et</a>
            <span style="color: #ccc; margin: 0 10px;">|</span>
            <!-- Komple Sepeti Temizleme Butonu -->
            <a href="kombin.php?temizle=1" class="sepet-temizle">Sepeti Tamamen Boşalt</a>
            
        <?php else: ?>
            <p>Sepetinizde henüz kıyafet yok. Manken üzerinde deneme yapmak için dolaptan kıyafet seçin.</p>
            <br>
            <a href="wardrobe.php" class="vton-btn" style="background-color: #4cd137;">Dolaba Git</a>
        <?php endif; ?>
    </div>
</body>
</html>