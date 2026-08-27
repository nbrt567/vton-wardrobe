<?php
session_start();

// Kullanıcı oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// Veritabanı yapılandırması
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

$kullanici_id = (int) $_SESSION['user_id'];

// Kombin silme işlemi
if (isset($_GET['sil'])) {
    $sil_id = (int) $_GET['sil'];
    
    $sil_sorgu = $db->prepare("DELETE FROM kaydedilen_kombinler WHERE id = ? AND kullanici_id = ?");
    $sil_sorgu->execute([$sil_id, $kullanici_id]);
    
    header("Location: kaydedilen_kombinler.php");
    exit;
}

// Kullanıcının kayıtlı kombinlerini getir
$sorgu = $db->prepare("SELECT * FROM kaydedilen_kombinler WHERE kullanici_id = ? ORDER BY olusturulma_tarihi DESC");
$sorgu->execute([$kullanici_id]);
$kombinler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaydedilen Kombinlerim - VTON Dolap</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f5f6fa; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Navbar ve Dropdown Menü */
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: #2f3640; }
        
        .nav-link-active { color: #00a8ff !important; font-weight: bold; }
        .nav-link-cart { color: #ff4757 !important; font-weight: bold; }
        
        .profil-dropdown { position: relative; display: inline-block; }
        .btn-profil { background-color: #00a8ff; color: white !important; padding: 8px 18px; border-radius: 6px; font-weight: bold; transition: background 0.3s; display: inline-block; }
        .btn-profil:hover { background-color: #0097e6; }
        
        .profil-menu { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 170px; box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px; z-index: 9999; padding-top: 5px; overflow: hidden; border: 1px solid #f1f2f6; }
        .profil-menu a { color: #2f3640 !important; padding: 12px 16px; display: block; font-size: 14px; font-weight: bold; transition: background 0.3s; border-bottom: 1px solid #f1f2f6; }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }
        
        .profil-dropdown:hover .profil-menu { display: block !important; }
        .btn-logout { color: #ff4757 !important; }

        /* Galeri ve Layout */
        .galeri-kapsayici { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .sayfa-baslik { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e1e8ed; padding-bottom: 15px; }
        .sayfa-baslik h2 { color: #2f3640; margin: 0; font-size: 24px; }

        .btn-harekete-gec { background-color: #00a8ff; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0, 168, 255, 0.2); border: none; cursor: pointer; }
        .btn-harekete-gec:hover { background-color: #0097e6; transform: translateY(-2px); }

        /* Grid Sistemi */
        .kombin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }

        .kombin-kart { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid #f1f2f6; }
        .kombin-kart:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }

        .kombin-resim-alani { width: 100%; height: 380px; background: #f8f9fa; border-bottom: 1px solid #f1f2f6; }
        .kombin-resim-alani img { width: 100%; height: 100%; object-fit: cover; }

        .kombin-bilgi { padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; background: white; }
        .kombin-tarih { font-size: 13px; color: #7f8fa6; font-weight: 600; }

        .btn-kombin-sil { background: #ff4757; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: bold; text-decoration: none; transition: background 0.3s; }
        .btn-kombin-sil:hover { background: #ff6b81; }

        /* Boş Durum (Empty State) */
        .bos-ekran { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 20px; text-align: center; background: white; border-radius: 12px; border: 2px dashed #dcdde1; margin-top: 20px; }
        .bos-ekran-ikon { font-size: 50px; margin-bottom: 15px; }
        .bos-ekran h3 { color: #2f3640; margin: 0 0 10px 0; font-size: 20px; }
        .bos-ekran p { color: #7f8fa6; margin: 0 0 25px 0; max-width: 400px; line-height: 1.5; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            
            <a href="kaydedilen_kombinler.php" class="nav-link-active">✨ Kaydedilen Kombinler</a>
            <a href="kombin.php" class="nav-link-cart">🛒 Kombin Sepeti (<?= isset($_SESSION['sepet']) ? count($_SESSION['sepet']) : 0 ?>)</a>
            
            <div class="profil-dropdown">
                <a href="#" class="btn-profil">👤 Profilim</a>
                <div class="profil-menu">
                    <a href="profil.php">🔑 Şifre Değiştir</a>
                    <a href="logout.php" class="btn-logout" id="btnCikisYap">🚪 Çıkış Yap</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="galeri-kapsayici">
        <div class="sayfa-baslik">
            <h2>✨ Kaydedilen Sanal Kombinlerim</h2>
            <a href="kombin.php" class="btn-harekete-gec">+ Yeni Kombin Yap</a>
        </div>

        <?php if (count($kombinler) > 0): ?>
            <div class="kombin-grid">
                <?php foreach ($kombinler as $kombin): ?>
                    <div class="kombin-kart">
                        <div class="kombin-resim-alani">
                            <img src="<?= htmlspecialchars($kombin['gorsel_url'], ENT_QUOTES) ?>" alt="Kaydedilen Kombin">
                        </div>
                        <div class="kombin-bilgi">
                            <span class="kombin-tarih">📅 <?= date('d.m.Y H:i', strtotime($kombin['olusturulma_tarihi'])) ?></span>
                            <a href="kaydedilen_kombinler.php?sil=<?= $kombin['id'] ?>" class="btn-kombin-sil silme-onayi">🗑️ Sil</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bos-ekran">
                <div class="bos-ekran-ikon">📂</div>
                <h3>Henüz Kayıtlı Kombininiz Yok!</h3>
                <p>Sanal kabinde oluşturup beğendiğiniz kombinleri "Kombini Kaydet" butonuyla dolabınıza ekleyebilirsiniz.</p>
                <a href="kombin.php" class="btn-harekete-gec">Hemen Kombin Oluştur</a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Kombin silme onayı
            const silmeButonlari = document.querySelectorAll('.silme-onayi');
            silmeButonlari.forEach(buton => {
                buton.addEventListener('click', (e) => {
                    if (!confirm('Bu kombini silmek istediğinize emin misiniz?')) {
                        e.preventDefault();
                    }
                });
            });

            // Çıkış yapma onayı
            const btnCikisYap = document.getElementById('btnCikisYap');
            if (btnCikisYap) {
                btnCikisYap.addEventListener('click', (e) => {
                    if (!confirm('Hesabınızdan çıkış yapmak istediğinize emin misiniz?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>