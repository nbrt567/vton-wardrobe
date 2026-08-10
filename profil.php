<?php
session_start();

// Giriş yapmamışsa ana sayfaya at
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

$mesaj = "";

// Şifre değiştirme formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sifre_degistir'])) {
    $eski_sifre = $_POST['eski_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];

    // 1. Yeni şifreler uyuşuyor mu?
    if ($yeni_sifre !== $yeni_sifre_tekrar) {
        $mesaj = "<div style='color: #e84118; background: #ffeaa7; padding: 10px; border-radius: 6px; margin-bottom: 15px;'>Yeni şifreler birbiriyle eşleşmiyor!</div>";
    } else {
        // 2. Mevcut şifreyi veritabanından çek ve doğrula
        $sorgu = $db->prepare("SELECT password FROM users WHERE id = :id");
        $sorgu->execute([':id' => $_SESSION['user_id']]);
        $user = $sorgu->fetch(PDO::FETCH_ASSOC);

        if (password_verify($eski_sifre, $user['password'])) {
            // 3. Şifre doğruysa yeni şifreyi hash'le ve kaydet
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $guncelle = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
            $guncelle->execute([':pass' => $yeni_hash, ':id' => $_SESSION['user_id']]);
            $mesaj = "<div style='color: #155724; background: #d4edda; padding: 10px; border-radius: 6px; margin-bottom: 15px;'>Şifreniz başarıyla güncellendi!</div>";
        } else {
            $mesaj = "<div style='color: #e84118; background: #ffda79; padding: 10px; border-radius: 6px; margin-bottom: 15px;'>Mevcut şifrenizi yanlış girdiniz!</div>";
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
        /* Özel Çıkış Modalı Stilleri */
        .ozel-modal {
            display: none; /* Başlangıçta gizli */
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Yarı saydam siyah arka plan */
            justify-content: center;
            align-items: center;
        }

        .modal-icerik {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            max-width: 350px;
            width: 90%;
            animation: modalAcilis 0.3s ease-out;
        }

        @keyframes modalAcilis {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-icerik h3 { margin-top: 0; color: #2f3640; font-size: 22px; }
        .modal-icerik p { color: #576574; margin-bottom: 25px; font-weight: bold; }
        
        .modal-butonlar { display: flex; gap: 15px; justify-content: center; }
        
        .btn-iptal {
            background-color: #f1f2f6; color: #2f3640; border: none;
            padding: 10px 20px; border-radius: 6px; font-weight: bold;
            cursor: pointer; transition: background 0.3s;
        }
        .btn-iptal:hover { background-color: #dfe4ea; }
        
        .btn-evet {
            background-color: #ff4757; color: white; text-decoration: none;
            padding: 10px 20px; border-radius: 6px; font-weight: bold;
            transition: background 0.3s;
        }
        .btn-evet:hover { background-color: #ff6b81; }
        .profil-alani { max-width: 500px; margin: 60px auto; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; }
        .profil-alani h2 { margin-top: 0; color: #2f3640; }
        .bilgi-kutu { background: #f1f2f6; padding: 15px; border-radius: 8px; margin-bottom: 30px; color: #576574; font-weight: bold; }
        .form-grup { text-align: left; margin-bottom: 15px; }
        .form-grup label { display: block; font-size: 13px; font-weight: bold; color: #576574; margin-bottom: 5px; }
        .form-grup input { width: 100%; padding: 10px; border: 1px solid #dcdde1; border-radius: 6px; box-sizing: border-box; }
        .btn-guncelle { width: 100%; padding: 12px; background: #00a8ff; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; transition: background 0.3s; }
        .btn-guncelle:hover { background: #0097e6; }
        .btn-cikis { display: inline-block; margin-top: 30px; color: #ff4757; text-decoration: none; font-weight: bold; padding: 10px 20px; border: 1px solid #ff4757; border-radius: 6px; transition: all 0.3s; }
        .btn-cikis:hover { background: #ff4757; color: white; }
    </style>
    <style>
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .profil-dropdown { position: relative; display: inline-block; }
        
        .btn-profil {
            background-color: #00a8ff; color: white !important; padding: 8px 18px;
            border-radius: 6px; font-weight: bold; text-decoration: none;
            transition: background 0.3s; display: inline-block;
        }
        .btn-profil:hover { background-color: #0097e6; }

        .profil-menu {
            display: none; position: absolute; right: 0; top: 100%;
            background-color: white; min-width: 170px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px;
            z-index: 9999;
            /* Fare kayarken menünün kapanmaması için margin yerine padding kullanıyoruz */
            margin-top: 0; padding-top: 5px; 
            overflow: hidden; border: 1px solid #f1f2f6;
        }

        .profil-menu a {
            color: #2f3640 !important; padding: 12px 16px; text-decoration: none;
            display: block; font-size: 14px; font-weight: bold;
            transition: background 0.3s; border-bottom: 1px solid #f1f2f6;
        }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }

        /* Mouse üzerine gelince göster */
        .profil-dropdown:hover .profil-menu { display: block; }
        
        /* Tıklanınca JavaScript ile eklenecek kalıcı sınıf */
        .profil-menu.kalici-acik { display: block !important; }
    </style>
    <style>
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .profil-dropdown { position: relative; display: inline-block; }
        
        .btn-profil {
            background-color: #00a8ff; color: white !important; padding: 8px 18px;
            border-radius: 6px; font-weight: bold; text-decoration: none;
            transition: background 0.3s; display: inline-block;
        }
        .btn-profil:hover { background-color: #0097e6; }

        .profil-menu {
            display: none; position: absolute; right: 0; top: 100%;
            background-color: white; min-width: 170px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px;
            z-index: 9999;
            /* Fare kayarken menünün kapanmaması için margin yerine padding kullanıyoruz */
            margin-top: 0; padding-top: 5px; 
            overflow: hidden; border: 1px solid #f1f2f6;
        }

        .profil-menu a {
            color: #2f3640 !important; padding: 12px 16px; text-decoration: none;
            display: block; font-size: 14px; font-weight: bold;
            transition: background 0.3s; border-bottom: 1px solid #f1f2f6;
        }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }

        /* Mouse üzerine gelince göster */
        .profil-dropdown:hover .profil-menu { display: block; }
        
        /* Tıklanınca JavaScript ile eklenecek kalıcı sınıf */
        .profil-menu.kalici-acik { display: block !important; }
    </style>
    <style>
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .profil-dropdown { position: relative; display: inline-block; }
        
        .btn-profil {
            background-color: #00a8ff; color: white !important; padding: 8px 18px;
            border-radius: 6px; font-weight: bold; text-decoration: none;
            transition: background 0.3s; display: inline-block;
        }
        .btn-profil:hover { background-color: #0097e6; }

        .profil-menu {
            display: none; position: absolute; right: 0; top: 100%;
            background-color: white; min-width: 170px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px;
            z-index: 9999;
            /* Fare kayarken menünün kapanmaması için margin yerine padding kullanıyoruz */
            margin-top: 0; padding-top: 5px; 
            overflow: hidden; border: 1px solid #f1f2f6;
        }

        .profil-menu a {
            color: #2f3640 !important; padding: 12px 16px; text-decoration: none;
            display: block; font-size: 14px; font-weight: bold;
            transition: background 0.3s; border-bottom: 1px solid #f1f2f6;
        }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }

        /* Mouse üzerine gelince göster */
        .profil-dropdown:hover .profil-menu { display: block; }
        
        /* Tıklanınca JavaScript ile eklenecek kalıcı sınıf */
        .profil-menu.kalici-acik { display: block !important; }
    </style>
</head>
<body>
    <!-- Üst Menü -->
    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            <a href="kombin.php" style="color: #ff4757;">🛒 Kombin Sepeti (<?php echo isset($_SESSION['sepet']) ? count($_SESSION['sepet']) : 0; ?>)</a>
           <!-- Yeni Açılır Profil Menüsü -->
            <div class="profil-dropdown">
                <a href="#" class="btn-profil" id="profilButonu">👤 Profilim</a>
                <div class="profil-menu" id="profilMenusu">
                    <a href="profil.php">🔑 Şifre Değiştir</a>
                    <a href="#" onclick="cikisOnayla(event)" style="color: #ff4757 !important;">🚪 Çıkış Yap</a>
                </div>
            </div>
            </div>
        </div>
    </nav>

    <div class="profil-alani">
        <h2>Profil Ayarları</h2>
        
        <div class="bilgi-kutu">
            Kayıtlı E-Posta: <br>
            <span style="color: #2f3640; font-size: 18px;"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
        </div>

        <?php echo $mesaj; ?>

        <form action="profil.php" method="POST">
            <input type="hidden" name="sifre_degistir" value="1">
            
            <div class="form-grup">
                <label>Mevcut Şifreniz</label>
                <input type="password" name="eski_sifre" required>
            </div>
            <div class="form-grup">
                <label>Yeni Şifreniz</label>
                <input type="password" name="yeni_sifre" required>
            </div>
            <div class="form-grup">
                <label>Yeni Şifreniz (Tekrar)</label>
                <input type="password" name="yeni_sifre_tekrar" required>
            </div>
            
            <button type="submit" class="btn-guncelle">Şifremi Güncelle</button>
        </form>

        <a href="logout.php" class="btn-cikis">🚪 Hesaptan Çıkış Yap</a>
    </div>
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
    <!-- Profil Menüsü ve Özel Modal Etkileşim Scripti -->
    <script>
        // Çıkış yap butonuna tıklanınca özel modalı aç
        function cikisOnayla(event) {
            event.preventDefault();
            document.getElementById('cikisModal').style.display = 'flex';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Profil Menüsü İşlemleri
            var btn = document.getElementById('profilButonu');
            var menu = document.getElementById('profilMenusu');

            btn.addEventListener('click', function(event) {
                event.preventDefault();
                menu.classList.toggle('kalici-acik');
            });

            // Sayfada boş bir yere tıklanırsa menüyü kapat
            document.addEventListener('click', function(event) {
                if (!btn.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.remove('kalici-acik');
                }
            });

            // 2. Modal Kapatma İşlemleri (İptal butonu veya boşluğa tıklayınca)
            var modal = document.getElementById('cikisModal');
            var iptalBtn = document.getElementById('modalIptal');

            // İptal butonuna basılınca kutuyu gizle
            iptalBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            // Siyah arka plana basılınca kutuyu gizle
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</html>