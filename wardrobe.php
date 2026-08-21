<?php
session_start();

// Eğer kullanıcı giriş yapmamışsa direkt ana sayfaya (giriş ekranına) yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// 1. Veritabanı Bağlantısı
$host = 'localhost'; $dbname = 'vton_wardrobe'; $kullanici = 'root'; $sifre = '';
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
// Kıyafeti Dolaptan Silme İşlemi
if (isset($_GET['sil'])) {
    $sil_id = $_GET['sil'];
    $user_id = $_SESSION['user_id'];
    
    $sil_sorgu = $db->prepare("DELETE FROM garments WHERE id = :id AND user_id = :uid");
    $sil_sorgu->execute([':id' => $sil_id, ':uid' => $user_id]);
    
    header("Location: wardrobe.php");
    exit;
}

// 2. Kategori Filtresini Yakala ve Giriş Yapan ID'yi Al
$secilen_kategori = isset($_GET['kat']) ? $_GET['kat'] : 'tumu';
$giris_yapan_id = $_SESSION['user_id'];

// 3. Sepete Ekleme İşlemi
if (isset($_GET['sepet_ekle'])) {
    $eklenen_id = $_GET['sepet_ekle'];
    if (!isset($_SESSION['sepet'])) { $_SESSION['sepet'] = []; }
    if (!in_array($eklenen_id, $_SESSION['sepet'])) { $_SESSION['sepet'][] = $eklenen_id; }
    
    // Filtre bozulmasın diye aynı kategori sayfasına geri yönlendir
    header("Location: wardrobe.php?kat=" . $secilen_kategori);
    exit;
}

// 4. Kıyafetleri giriş yapan kullanıcıya göre çek
if ($secilen_kategori === 'tumu') {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = :uid ORDER BY id DESC");
    $sorgu->execute([':uid' => $giris_yapan_id]);
} else {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = :uid AND category = :kat ORDER BY id DESC");
    $sorgu->execute([':uid' => $giris_yapan_id, ':kat' => $secilen_kategori]);
}
$kiyafetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <style>
        /* Kıyafet Kartı ve Hover (Üzerine Gelince) Efekti */
        .kiyafet-karti {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
        }

        /* Dolaptan Sil Butonu (Başlangıçta gizli veya şık durur) */
        .btn-dolaptan-sil {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 71, 87, 0.9);
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 10;
        }

        /* Kartın üzerine gelince sil butonu görünür olur */
        .kiyafet-karti:hover .btn-dolaptan-sil {
            opacity: 1;
        }

        .btn-dolaptan-sil:hover {
            background-color: #ff4757;
        }
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

        /* İskelet Yükleme (Skeleton) Animasyonu */
        .resim-tutucu {
            width: 100%;
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            margin-bottom: 15px;
        }
        
        .resim-tutucu img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: 0; /* Fotoğraf yüklenene kadar görünmez */
            transition: opacity 0.5s ease, transform 0.4s ease;
        }
        
        /* Sadece fotoğraf yüklendiğinde opaklığı 1 yap */
        .resim-tutucu img.yuklendi {
            opacity: 1; 
        }

        .skeleton {
            background-color: #f1f2f6;
            background-image: linear-gradient(90deg, #f1f2f6 0px, #ffffff 40px, #f1f2f6 80px);
            background-size: 200% 100%;
            animation: parlama 1.5s infinite linear;
        }

        @keyframes parlama {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Zoom efektinin bozulmaması için güncelledik */
        .kiyafet-karti:hover .resim-tutucu img {
            transform: scale(1.12);
        }
    </style>
<!-- Kodun geri kalanı buradan itibaren aynı şekilde devam edecek... -->
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
        /* Toast Bildirim Tasarımları */
        .toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 10000;
        }

        .toast {
            min-width: 280px;
            padding: 16px 24px;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateX(100%);
            animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toast.hide { animation: slideOut 0.4s ease forwards; }
        .toast.success { background-color: #2ed573; border-left: 6px solid #27ae60; }
        .toast.error { background-color: #ff4757; border-left: 6px solid #c0392b; }

        @keyframes slideIn { to { opacity: 1; transform: translateX(0); } }
        @keyframes slideOut { to { opacity: 0; transform: translateX(100%); } }

        /* Boş Ekran (Empty State) Tasarımı */
        .bos-ekran {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 2px dashed #e1e8ed;
            grid-column: 1 / -1; /* Grid içinde tam genişlik kaplaması için */
        }

        .bos-ekran-ikon {
            font-size: 60px;
            margin-bottom: 20px;
            color: #a4b0be;
            animation: hafifSallanma 3s infinite ease-in-out;
        }

        .bos-ekran h3 {
            color: #2f3640;
            margin-bottom: 10px;
            font-size: 22px;
        }

        .bos-ekran p {
            color: #7f8fa6;
            margin-bottom: 25px;
            font-size: 15px;
            max-width: 400px;
        }

        .btn-harekete-gec {
            background-color: #00a8ff;
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 168, 255, 0.3);
            display: inline-block;
        }

        .btn-harekete-gec:hover {
            background-color: #0097e6;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 168, 255, 0.4);
        }

        @keyframes hafifSallanma {
            0%, 100% { transform: rotate(-5deg); }
            50% { transform: rotate(5deg); }
        }
    </style>
</head>
<body>
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
        <div class="kiyafet-karti">
            
            <!-- 1. Dolaptan Sil Butonu -->
            <a href="wardrobe.php?sil=<?php echo $kiyafet['id']; ?>" class="btn-dolaptan-sil" onclick="return confirm('Bu kıyafeti dolabınızdan silmek istediğinize emin misiniz?');">🗑️ Sil</a>
            
            <!-- 2. YENİ: İskelet Tutucu ve Akıllı Görsel -->
            <div class="resim-tutucu skeleton" id="iskelet-<?php echo $kiyafet['id']; ?>">
                <img src="<?php echo htmlspecialchars($kiyafet['image_path']); ?>" alt="Kıyafet" onload="resimYuklendi(<?php echo $kiyafet['id']; ?>)">
            </div>
            
            <!-- 3. Sepete Ekle Butonu (AJAX) -->
            <a href="#" onclick="sepeteEkleAjax(event, <?php echo $kiyafet['id']; ?>)" style="width: 100%; background: #2ed573; color: white; padding: 10px; text-align: center; border-radius: 6px; text-decoration: none; font-weight: bold; display: block; transition: background 0.3s; margin-top: 15px;">Sepete Ekle</a>
            
        </div>
    <?php endforeach; ?>
                <?php else: ?>
        <!-- Gelişmiş Boş Ekran (Empty State) -->
        <div class="bos-ekran">
            <div class="bos-ekran-ikon">👕</div>
            <h3>Dolabınız Çok Boş Görünüyor!</h3>
            <p>Bu kategoride (veya dolabınızda) henüz hiçbir kıyafet bulunmuyor. Hemen yeni bir parça ekleyerek sanal dolabınızı oluşturmaya başlayın.</p>
            <a href="ekle.html" class="btn-harekete-gec">✨ İlk Kıyafetini Ekle</a>
        </div>
    <?php endif; ?>
            </div>
        </main>
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
    <!-- Profil Menüsü Etkileşim Scripti -->
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
        // Fotoğraf yüklendiğinde iskelet efektini kaldır
        function resimYuklendi(id) {
            const iskeletKutusu = document.getElementById('iskelet-' + id);
            if (iskeletKutusu) {
                // Parlama animasyonunu durdur
                iskeletKutusu.classList.remove('skeleton');
                
                // İçindeki görsele 'yuklendi' sınıfını ekleyerek görünür (opacity: 1) yap
                const resim = iskeletKutusu.querySelector('img');
                if (resim) {
                    resim.classList.add('yuklendi');
                }
            }
        }
    </script>
    <!-- Toast Bildirimlerinin Barınacağı Alan -->
    <div id="toast-container" class="toast-container"></div>
    <!-- Toast Bildirim Scripti -->
    <script>
        // Bildirimi Ekranda Gösteren Fonksiyon
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.classList.add('toast', type);

            toast.innerHTML = `
                <span>${message}</span>
                <span style="cursor:pointer; font-weight:bold; margin-left:15px; font-size:16px;" onclick="this.parentElement.remove()">✕</span>
            `;

            container.appendChild(toast);

            // 3 saniye sonra kaybolur
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => { toast.remove(); }, 400); 
            }, 3000);
        }

        // Arka Planda (Sayfa Yenilenmeden) Sepete Ekleme Fonksiyonu
        function sepeteEkleAjax(event, kiyafetId) {
            event.preventDefault(); // Sayfanın yukarı zıplamasını ve yenilenmesini engeller

            // Arka planda PHP'ye sepet_ekle komutunu gönderir
            fetch('wardrobe.php?sepet_ekle=' + kiyafetId)
                .then(response => {
                    // İşlem başarılıysa yeşil bildirimi çıkar
                    showToast('✨ Kıyafet kombin sepetine eklendi!', 'success');
                })
                .catch(error => {
                    showToast('Hata oluştu, tekrar deneyin.', 'error');
                });
        }
    </script>
    </body>
</html>