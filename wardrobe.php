<?php
<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa direkt ana sayfaya (giriş ekranna) yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

$secilen_kategori = isset($_GET['kat']) ? $_GET['kat'] : 'tumu';
$giris_yapan_id = $_SESSION['user_id'];

// Kıyafetleri giriş yapan kullanıcıya göre çek
if ($secilen_kategori === 'tumu') {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = :uid ORDER BY id DESC");
    $sorgu->execute([':uid' => $giris_yapan_id]);
} else {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = :uid AND category = :kat ORDER BY id DESC");
    $sorgu->execute([':uid' => $giris_yapan_id, ':kat' => $secilen_kategori]);
}
$kiyafetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
session_start();

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

// 2. Kategori Filtresini Yakala
$secilen_kategori = isset($_GET['kat']) ? $_GET['kat'] : 'tumu';

// 3. Sepete Ekleme İşlemi
if (isset($_GET['sepet_ekle'])) {
    $eklenen_id = $_GET['sepet_ekle'];
    if (!isset($_SESSION['sepet'])) { $_SESSION['sepet'] = []; }
    if (!in_array($eklenen_id, $_SESSION['sepet'])) { $_SESSION['sepet'][] = $eklenen_id; }
    
    // Filtre bozulmasın diye aynı kategori sayfasına geri yönlendir
    header("Location: wardrobe.php?kat=" . $secilen_kategori);
    exit;
}

// 4. Kıyafetleri Filtreye Göre Çek
if ($secilen_kategori === 'tumu') {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE $_SESSION['user_id'] ORDER BY id DESC");
    $sorgu->execute();
} else {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE $_SESSION['user_id'] AND category = :kat ORDER BY id DESC");
    $sorgu->execute([':kat' => $secilen_kategori]);
}
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
        /* Ana Taşıyıcı: Sol menü ve sağ içerik yan yana */
        .dolap-duzeni { display: flex; gap: 30px; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        /* Sol Menü (Sidebar) Tasarımı */
        .yan-menu { width: 250px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content; }
        .yan-menu h3 { margin-top: 0; color: #2f3640; border-bottom: 2px solid #f5f6fa; padding-bottom: 10px; font-size: 18px; }
        .kategori-liste { list-style: none; padding: 0; margin: 0; }
        .kategori-liste li { margin-bottom: 8px; }
        .kategori-liste a { display: block; padding: 10px 15px; color: #7f8fa6; text-decoration: none; font-weight: bold; border-radius: 6px; transition: all 0.3s; }
        .kategori-liste a:hover { background-color: #f1f2f6; color: #2f3640; }
        .kategori-liste a.aktif { background-color: #00a8ff; color: white; }
        
        /* Sağ İçerik ve Grid */
        .ana-icerik { flex: 1; }
        .dolap-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .kiyafet-kart { background: white; border-radius: 12px; padding: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; transition: transform 0.2s ease; }
        .kiyafet-kart:hover { transform: translateY(-5px); }
        .kiyafet-kart img { width: 100%; height: 200px; object-fit: contain; border-radius: 8px; margin-bottom: 15px; }
        .sec-btn { display: block; width: 100%; background-color: #4cd137; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; box-sizing: border-box; }
        .sec-btn:hover { background-color: #44bd32; }
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

    <div class="dolap-duzeni">
        <!-- SOL MENÜ -->
        <aside class="yan-menu">
            <h3>Kategoriler</h3>
            <ul class="kategori-liste">
                <li><a href="wardrobe.php?kat=tumu" class="<?php echo $secilen_kategori == 'tumu' ? 'aktif' : ''; ?>">Tüm Kıyafetler</a></li>
                <li><a href="wardrobe.php?kat=ust" class="<?php echo $secilen_kategori == 'ust' ? 'aktif' : ''; ?>">Üst Giyim</a></li>
                <li><a href="wardrobe.php?kat=alt" class="<?php echo $secilen_kategori == 'alt' ? 'aktif' : ''; ?>">Alt Giyim</a></li>
                <li><a href="wardrobe.php?kat=dis" class="<?php echo $secilen_kategori == 'dis' ? 'aktif' : ''; ?>">Dış Giyim</a></li>
                <li><a href="wardrobe.php?kat=ayakkabi" class="<?php echo $secilen_kategori == 'ayakkabi' ? 'aktif' : ''; ?>">Ayakkabı</a></li>
                <li><a href="wardrobe.php?kat=aksesuar" class="<?php echo $secilen_kategori == 'aksesuar' ? 'aktif' : ''; ?>">Aksesuar</a></li>
            </ul>
        </aside>

        <!-- SAĞ İÇERİK -->
        <main class="ana-icerik">
            <div class="dolap-grid">
                <?php if(count($kiyafetler) > 0): ?>
                    <?php foreach($kiyafetler as $kiyafet): ?>
                        <div class="kiyafet-kart">
                            <!-- Başlık kaldırıldı, sadece fotoğraf ve buton var -->
                            <img src="<?php echo htmlspecialchars($kiyafet['image_path']); ?>" alt="Kıyafet">
                            <a href="wardrobe.php?kat=<?php echo $secilen_kategori; ?>&sepet_ekle=<?php echo $kiyafet['id']; ?>" class="sec-btn">Sepete Ekle</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #7f8fa6;">Bu kategoride henüz kıyafet bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>