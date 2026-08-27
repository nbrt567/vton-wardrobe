<?php
session_start();

// Giriş kontrolü
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
    die("Veritabanı bağlantı hatası.");
}

$mesaj = "";
$mesajTuru = ""; // 'basari' veya 'hata' olacak

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sifre_degistir'])) {
    $eski_sifre = $_POST['eski_sifre'] ?? '';
    $yeni_sifre = $_POST['yeni_sifre'] ?? '';
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';

    // 1. Yeni şifreler eşleşiyor mu?
    if ($yeni_sifre !== $yeni_sifre_tekrar) {
        $mesaj = "Yeni şifreler birbiriyle eşleşmiyor!";
        $mesajTuru = "hata";
    } else {
        // 2. Mevcut şifreyi çek ve doğrula
        $sorgu = $db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $sorgu->execute([':id' => $_SESSION['user_id']]);
        $user = $sorgu->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($eski_sifre, $user['password'])) {
            // 3. Şifre doğruysa hash'le ve güncelle
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $guncelle = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $guncelle->execute([':pass' => $yeni_hash, ':id' => $_SESSION['user_id']]);
            
            $mesaj = "Şifreniz başarıyla güncellendi!";
            $mesajTuru = "basari";
        } else {
            $mesaj = "Mevcut şifrenizi yanlış girdiniz!";
            $mesajTuru = "hata";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - VTON Dolap</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f5f6fa; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Navbar ve Profil Menüsü */
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .profil-dropdown { position: relative; display: inline-block; }
        .btn-profil { background-color: #00a8ff; color: white !important; padding: 8px 18px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: background 0.3s; display: inline-block; }
        .btn-profil:hover { background-color: #0097e6; }
        
        .profil-menu { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 170px; box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px; z-index: 9999; margin-top: 0; padding-top: 5px; overflow: hidden; border: 1px solid #f1f2f6; }
        .profil-menu a { color: #2f3640 !important; padding: 12px 16px; text-decoration: none; display: block; font-size: 14px; font-weight: bold; transition: background 0.3s; border-bottom: 1px solid #f1f2f6; }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }
        .profil-dropdown:hover .profil-menu { display: block; }
        .profil-menu.kalici-acik { display: block !important; }

        /* Profil ve Form Alanı */
        .profil-alani { max-width: 500px; margin: 60px auto; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; }
        .profil-alani h2 { margin-top: 0; color: #2f3640; }
        .bilgi-kutu { background: #f1f2f6; padding: 15px; border-radius: 8px; margin-bottom: 30px; color: #576574; font-weight: bold; }
        
        /* Dinamik Mesaj Uyarıları */
        .uyari { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .uyari-hata { color: #e84118; background: #ffeaa7; }
        .uyari-basari { color: #155724; background: #d4edda; }

        .form-grup { text-align: left; margin-bottom: 15px; }
        .form-grup label { display: block; font-size: 13px; font-weight: bold; color: #576574; margin-bottom: 5px; }
        .form-grup input { width: 100%; padding: 10px; border: 1px solid #dcdde1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        
        .btn-guncelle { width: 100%; padding: 12px; background: #00a8ff; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer; margin-top: 10px; transition: background 0.3s; }
        .btn-guncelle:hover { background: #0097e6; }
        
        .btn-cikis { display: inline-block; margin-top: 30px; color: #ff4757; text-decoration: none; font-weight: bold; padding: 10px 20px; border: 1px solid #ff4757; border-radius: 6px; transition: all 0.3s; }
        .btn-cikis:hover { background: #ff4757; color: white; }

        /* Modal Stilleri */
        .ozel-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; }
        .modal-icerik { background-color: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 15px 30px rgba(0,0,0,0.2); max-width: 350px; width: 90%; animation: modalAcilis 0.3s ease-out; }
        @keyframes modalAcilis { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-icerik h3 { margin-top: 0; color: #2f3640; font-size: 22px; }
        .modal-icerik p { color: #576574; margin-bottom: 25px; font-weight: bold; }
        .modal-butonlar { display: flex; gap: 15px; justify-content: center; }
        .btn-iptal { background-color: #f1f2f6; color: #2f3640; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        .btn-iptal:hover { background-color: #dfe4ea; }
        .btn-evet { background-color: #ff4757; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; transition: background 0.3s; }
        .btn-evet:hover { background-color: #ff6b81; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            <a href="kombin.php" style="color: #ff4757; font-weight: bold; text-decoration: none;">🛒 Kombin Sepeti (<?= isset($_SESSION['sepet']) ? count($_SESSION['sepet']) : 0 ?>)</a>
            
            <div class="profil-dropdown">
                <a href="#" class="btn-profil" id="profilButonu">👤 Profilim</a>
                <div class="profil-menu" id="profilMenusu">
                    <a href="profil.php">🔑 Şifre Değiştir</a>
                    <a href="#" id="btnCikisAc" style="color: #ff4757 !important;">🚪 Çıkış Yap</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="profil-alani">
        <h2>Profil Ayarları</h2>
        
        <div class="bilgi-kutu">
            Kayıtlı E-Posta: <br>
            <span style="color: #2f3640; font-size: 18px;"><?= htmlspecialchars($_SESSION['user_email'], ENT_QUOTES) ?></span>
        </div>

        <?php if ($mesaj !== ""): ?>
            <div class="uyari uyari-<?= $mesajTuru ?>">
                <?= htmlspecialchars($mesaj, ENT_QUOTES) ?>
            </div>
        <?php endif; ?>

        <form action="profil.php" method="POST">
            <input type="hidden" name="sifre_degistir" value="1">
            
            <div class="form-grup">
                <label for="eski_sifre">Mevcut Şifreniz</label>
                <input type="password" id="eski_sifre" name="eski_sifre" required>
            </div>
            <div class="form-grup">
                <label for="yeni_sifre">Yeni Şifreniz</label>
                <input type="password" id="yeni_sifre" name="yeni_sifre" required>
            </div>
            <div class="form-grup">
                <label for="yeni_sifre_tekrar">Yeni Şifreniz (Tekrar)</label>
                <input type="password" id="yeni_sifre_tekrar" name="yeni_sifre_tekrar" required>
            </div>
            
            <button type="submit" class="btn-guncelle">Şifremi Güncelle</button>
        </form>

        <a href="#" id="btnCikisAlt" class="btn-cikis">🚪 Hesaptan Çıkış Yap</a>
    </main>

    <!-- Özel Çıkış Yap Modalı -->
    <div id="cikisModal" class="ozel-modal">
        <div class="modal-icerik">
            <div style="font-size: 3rem; margin-bottom: 10px;">🚪</div>
            <h3>Çıkış Yap</h3>
            <p>Hesabınızdan çıkış yapmak istediğinize emin misiniz?</p>
            <div class="modal-butonlar">
                <button id="modalIptal" class="btn-iptal">İptal</button>
                <a href="logout.php" class="btn-evet">Evet, Çıkış Yap</a>
            </div>
        </div>
    </div>

    <!-- Etkileşim Scriptleri -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // Profil Menüsü İşlemleri
            const btnProfil = document.getElementById('profilButonu');
            const profilMenu = document.getElementById('profilMenusu');

            if (btnProfil && profilMenu) {
                btnProfil.addEventListener('click', (e) => {
                    e.preventDefault();
                    profilMenu.classList.toggle('kalici-acik');
                });

                document.addEventListener('click', (e) => {
                    if (!btnProfil.contains(e.target) && !profilMenu.contains(e.target)) {
                        profilMenu.classList.remove('kalici-acik');
                    }
                });
            }

            // Modal İşlemleri
            const cikisModal = document.getElementById('cikisModal');
            const modalIptal = document.getElementById('modalIptal');
            
            const modalAc = (e) => {
                e.preventDefault();
                cikisModal.style.display = 'flex';
            };

            // Çıkış butonlarına event ekle
            document.getElementById('btnCikisAc')?.addEventListener('click', modalAc);
            document.getElementById('btnCikisAlt')?.addEventListener('click', modalAc);

            // İptal ve dışarı tıklama ile modalı kapat
            if (modalIptal) {
                modalIptal.addEventListener('click', () => cikisModal.style.display = 'none');
            }

            window.addEventListener('click', (e) => {
                if (e.target === cikisModal) {
                    cikisModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>