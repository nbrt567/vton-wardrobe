<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit;
}

$host = 'localhost';
$dbname = 'vton_wardrobe';
$user = 'root';
$pass = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

$kullanici_id = $_SESSION['kullanici_id'];

if (isset($_GET['sil'])) {
    $sil_id = intval($_GET['sil']);
    $sil_sorgu = $db->prepare("DELETE FROM kaydedilen_kombinler WHERE id = ? AND kullanici_id = ?");
    $sil_sorgu->execute([$sil_id, $kullanici_id]);
    header("Location: kaydedilen_kombinler.php");
    exit;
}

$sorgu = $db->prepare("SELECT * FROM kaydedilen_kombinler WHERE kullanici_id = ? ORDER BY olusturulma_tarihi DESC");
$sorgu->execute([$kullanici_id]);
$kombinler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaydedilen Kombinlerim</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .galeri-kapsayici { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .sayfa-baslik { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .sayfa-baslik h2 { color: #2f3640; margin: 0; }
        .kombin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
        .kombin-kart { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); display: flex; flex-direction: column; }
        .kombin-resim-alani { width: 100%; height: 360px; background: #f8f9fa; }
        .kombin-resim-alani img { width: 100%; height: 100%; object-fit: cover; }
        .kombin-bilgi { padding: 15px; display: flex; justify-content: space-between; align-items: center; background: white; }
        .kombin-tarih { font-size: 12px; color: #7f8fa6; font-weight: 600; }
        .btn-kombin-sil { background: #ff4757; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="wardrobe.php">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            <a href="kombin.php" style="color: #ff4757;">🛒 Kombin Sepeti</a>
            <a href="kaydedilen_kombinler.php" style="color: #00a8ff; font-weight: bold;">✨ Kaydedilen Kombinler</a>
            <div class="profil-dropdown">
                <a href="profil.php" class="btn-profil">👤 Profilim</a>
            </div>
        </div>
    </nav>

    <div class="galeri-kapsayici">
        <div class="sayfa-baslik">
            <h2>✨ Kaydedilen Sanal Kombinlerim</h2>
            <a href="kombin.php" class="btn-harekete-gec" style="padding: 8px 18px; font-size: 13px;">+ Yeni Kombin Yap</a>
        </div>

        <?php if (count($kombinler) > 0): ?>
            <div class="kombin-grid">
                <?php foreach ($kombinler as $kombin): ?>
                    <div class="kombin-kart">
                        <div class="kombin-resim-alani">
                            <img src="<?= htmlspecialchars($kombin['gorsel_url']) ?>" alt="Kaydedilen Kombin">
                        </div>
                        <div class="kombin-bilgi">
                            <span class="kombin-tarih">📅 <?= date('d.m.Y H:i', strtotime($kombin['olusturulma_tarihi'])) ?></span>
                            <a href="kaydedilen_kombinler.php?sil=<?= $kombin['id'] ?>" class="btn-kombin-sil" onclick="return confirm('Silmek istediğinize emin misiniz?');">🗑️ Sil</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bos-ekran">
                <div class="bos-ekran-ikon">✨</div>
                <h3>Henüz Kayıtlı Kombininiz Yok!</h3>
                <p>Sanal kabinde oluşturup beğendiğiniz kombinleri buraya ekleyebilirsiniz.</p>
                <a href="kombin.php" class="btn-harekete-gec">Hemen Kombin Oluştur</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>