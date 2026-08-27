<?php
session_start();

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// Veritabanı Bağlantısı
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

$giris_yapan_id = (int) $_SESSION['user_id'];
$secilen_kategori = $_GET['kat'] ?? 'tumu';

// 1. Kıyafeti Dolaptan Silme İşlemi
if (isset($_GET['sil'])) {
    $sil_id = (int) $_GET['sil'];
    
    $sil_sorgu = $db->prepare("DELETE FROM garments WHERE id = :id AND user_id = :uid");
    $sil_sorgu->execute([':id' => $sil_id, ':uid' => $giris_yapan_id]);
    
    // Silme işleminden sonra aynı kategoriye dön
    header("Location: wardrobe.php?kat=" . urlencode($secilen_kategori));
    exit;
}

// 2. Sepete Ekleme İşlemi (AJAX veya Normal İstek)
if (isset($_GET['sepet_ekle'])) {
    $eklenen_id = (int) $_GET['sepet_ekle'];
    
    if (!isset($_SESSION['sepet'])) { 
        $_SESSION['sepet'] = []; 
    }
    
    if (!in_array($eklenen_id, $_SESSION['sepet'])) { 
        $_SESSION['sepet'][] = $eklenen_id; 
    }
    
    // AJAX ile (fetch) çağrıldığında tarayıcı yönlendirmeyi arka planda takip eder, sayfa yenilenmez.
    header("Location: wardrobe.php?kat=" . urlencode($secilen_kategori));
    exit;
}

// 3. Kıyafetleri Çekme İşlemi
if ($secilen_kategori === 'tumu') {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = :uid ORDER BY id DESC");
    $sorgu->execute([':uid' => $giris_yapan_id]);
} else {
    $sorgu = $db->prepare("SELECT * FROM garments WHERE user_id = :uid AND category = :kat ORDER BY id DESC");
    $sorgu->execute([':uid' => $giris_yapan_id, ':kat' => $secilen_kategori]);
}
$kiyafetler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
$sepet_sayisi = isset($_SESSION['sepet']) ? count($_SESSION['sepet']) : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dijital Dolabım - VTON</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f5f6fa; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Navbar ve Profil */
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .nav-links a { text-decoration: none; color: #2f3640; font-weight: bold; }
        .profil-dropdown { position: relative; display: inline-block; }
        .btn-profil { background-color: #00a8ff; color: white !important; padding: 8px 18px; border-radius: 6px; transition: background 0.3s; }
        .btn-profil:hover { background-color: #0097e6; }
        
        .profil-menu { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 170px; box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px; z-index: 9999; margin-top: 0; padding-top: 5px; overflow: hidden; border: 1px solid #f1f2f6; }
        .profil-menu a { color: #2f3640 !important; padding: 12px 16px; display: block; font-size: 14px; transition: background 0.3s; border-bottom: 1px solid #f1f2f6; }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }
        .profil-dropdown:hover .profil-menu { display: block; }
        .profil-menu.kalici-acik { display: block !important; }

        /* Dolap Düzeni (Grid ve Yan Menü) */
        .dolap-duzeni { display: flex; gap: 30px; max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .yan-menu { width: 250px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); height: fit-content; }
        .yan-menu h3 { margin-top: 0; color: #2f3640; border-bottom: 2px solid #f5f6fa; padding-bottom: 10px; font-size: 18px; }
        .kategori-liste { list-style: none; padding: 0; margin: 0; }
        .kategori-liste li { margin-bottom: 8px; }
        .kategori-liste a { display: block; padding: 10px 15px; color: #7f8fa6; text-decoration: none; font-weight: bold; border-radius: 6px; transition: all 0.3s; }
        .kategori-liste a:hover { background-color: #f1f2f6; color: #2f3640; }
        .kategori-liste a.aktif { background-color: #00a8ff; color: white; }

        .ana-icerik { flex: 1; }
        .dolap-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }

        /* Kıyafet Kartları */
        .kiyafet-karti { position: relative; overflow: hidden; border-radius: 12px; background: white; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column; align-items: center; padding: 15px; }
        
        .btn-dolaptan-sil { position: absolute; top: 10px; right: 10px; background-color: rgba(255, 71, 87, 0.9); color: white; border: none; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer; text-decoration: none; opacity: 0; transition: opacity 0.3s ease, background 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 10; }
        .kiyafet-karti:hover .btn-dolaptan-sil { opacity: 1; }
        .btn-dolaptan-sil:hover { background-color: #ff4757; }

        .resim-tutucu { width: 100%; height: 200px; border-radius: 8px; overflow: hidden; position: relative; margin-bottom: 15px; }
        .resim-tutucu img { width: 100%; height: 100%; object-fit: contain; opacity: 0; transition: opacity 0.5s ease, transform 0.4s ease; }
        .resim-tutucu img.yuklendi { opacity: 1; }
        .kiyafet-karti:hover .resim-tutucu img { transform: scale(1.12); }

        .skeleton { background-color: #f1f2f6; background-image: linear-gradient(90deg, #f1f2f6 0px, #ffffff 40px, #f1f2f6 80px); background-size: 200% 100%; animation: parlama 1.5s infinite linear; }
        @keyframes parlama { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        .btn-sepete-ekle { display: block; width: 100%; background: #2ed573; color: white; padding: 10px; text-align: center; border-radius: 6px; text-decoration: none; font-weight: bold; transition: background 0.3s; box-sizing: border-box; border: none; cursor: pointer; }
        .btn-sepete-ekle:hover { background: #26c065; }

        /* Boş Ekran */
        .bos-ekran { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 2px dashed #e1e8ed; grid-column: 1 / -1; }
        .bos-ekran-ikon { font-size: 60px; margin-bottom: 20px; color: #a4b0be; animation: hafifSallanma 3s infinite ease-in-out; }
        .bos-ekran h3 { color: #2f3640; margin-bottom: 10px; font-size: 22px; margin-top: 0; }
        .bos-ekran p { color: #7f8fa6; margin-bottom: 25px; font-size: 15px; max-width: 400px; }
        .btn-harekete-gec { background-color: #00a8ff; color: white; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 15px; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(0, 168, 255, 0.3); display: inline-block; }
        .btn-harekete-gec:hover { background-color: #0097e6; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 168, 255, 0.4); }
        @keyframes hafifSallanma { 0%, 100% { transform: rotate(-5deg); } 50% { transform: rotate(5deg); } }

        /* Toast Bildirim */
        .toast-container { position: fixed; bottom: 30px; right: 30px; display: flex; flex-direction: column; gap: 15px; z-index: 10000; }
        .toast { min-width: 280px; padding: 16px 24px; border-radius: 10px; color: #fff; font-size: 15px; font-weight: bold; box-shadow: 0 10px 30px rgba(0,0,0,0.15); opacity: 0; transform: translateX(100%); animation: slideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards; display: flex; justify-content: space-between; align-items: center; }
        .toast.hide { animation: slideOut 0.4s ease forwards; }
        .toast.success { background-color: #2ed573; border-left: 6px solid #27ae60; }
        .toast.error { background-color: #ff4757; border-left: 6px solid #c0392b; }
        @keyframes slideIn { to { opacity: 1; transform: translateX(0); } }
        @keyframes slideOut { to { opacity: 0; transform: translateX(100%); } }
        
        /* Modal */
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
    
    <!-- Üst Menü -->
    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand" style="font-size: 1.2rem; font-weight: bold; text-decoration: none; color: #2f3640;">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php" style="color: #00a8ff;">Dolaba Git</a>
            <a href="kaydedilen_kombinler.php" style="color: #00a8ff; text-decoration: none;">✨ Kaydedilen Kombinler</a>
            
            <a href="kombin.php" style="color: #ff4757; text-decoration: none;" id="sepetLink">
                🛒 Kombin Sepeti (<span id="sepetSayisi"><?= $sepet_sayisi ?></span>)
            </a>
            
            <div class="profil-dropdown">
                <a href="#" class="btn-profil" id="profilButonu">👤 Profilim</a>
                <div class="profil-menu" id="profilMenusu">
                    <a href="profil.php">🔑 Şifre Değiştir</a>
                    <a href="#" id="btnCikisAc" style="color: #ff4757 !important;">🚪 Çıkış Yap</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dolap-duzeni">
        
        <!-- SOL MENÜ -->
        <aside class="yan-menu">
            <h3>Kategoriler</h3>
            <ul class="kategori-liste">
                <li><a href="wardrobe.php?kat=tumu" class="<?= $secilen_kategori == 'tumu' ? 'aktif' : '' ?>">Tüm Kıyafetler</a></li>
                <li><a href="wardrobe.php?kat=ust" class="<?= $secilen_kategori == 'ust' ? 'aktif' : '' ?>">Üst Giyim</a></li>
                <li><a href="wardrobe.php?kat=alt" class="<?= $secilen_kategori == 'alt' ? 'aktif' : '' ?>">Alt Giyim</a></li>
                <li><a href="wardrobe.php?kat=dis" class="<?= $secilen_kategori == 'dis' ? 'aktif' : '' ?>">Dış Giyim</a></li>
                <li><a href="wardrobe.php?kat=ayakkabi" class="<?= $secilen_kategori == 'ayakkabi' ? 'aktif' : '' ?>">Ayakkabı</a></li>
                <li><a href="wardrobe.php?kat=aksesuar" class="<?= $secilen_kategori == 'aksesuar' ? 'aktif' : '' ?>">Aksesuar</a></li>
            </ul>
        </aside>

        <!-- SAĞ İÇERİK (GRİD) -->
        <main class="ana-icerik">
            <div class="dolap-grid">
                <?php if (count($kiyafetler) > 0): ?>
                    <?php foreach ($kiyafetler as $kiyafet): ?>
                        <div class="kiyafet-karti">
                            
                            <!-- Dolaptan Sil -->
                            <a href="wardrobe.php?sil=<?= $kiyafet['id'] ?>&kat=<?= urlencode($secilen_kategori) ?>" class="btn-dolaptan-sil btn-sil-onay">🗑️ Sil</a>
                            
                            <!-- İskelet Tutucu ve Görsel -->
                            <div class="resim-tutucu skeleton" id="iskelet-<?= $kiyafet['id'] ?>">
                                <img src="<?= htmlspecialchars($kiyafet['image_path'], ENT_QUOTES) ?>" alt="Kıyafet" class="kiyafet-gorsel" data-id="<?= $kiyafet['id'] ?>">
                            </div>
                            
                            <!-- Sepete Ekle Butonu -->
                            <button class="btn-sepete-ekle" data-id="<?= $kiyafet['id'] ?>">Sepete Ekle</button>
                            
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Boş Durum Ekranı -->
                    <div class="bos-ekran">
                        <div class="bos-ekran-ikon">👕</div>
                        <h3>Dolabınız Çok Boş Görünüyor!</h3>
                        <p>Bu kategoride henüz hiçbir kıyafet bulunmuyor. Hemen yeni bir parça ekleyerek sanal dolabınızı oluşturmaya başlayın.</p>
                        <a href="ekle.html" class="btn-harekete-gec">✨ İlk Kıyafetini Ekle</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Toast Bildirimlerinin Barınacağı Alan -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Çıkış Modal -->
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

    <!-- JavaScript Etkileşimleri -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // 1. Profil Menüsü
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

            // 2. Çıkış Modal
            const cikisModal = document.getElementById('cikisModal');
            const btnCikisAc = document.getElementById('btnCikisAc');
            const modalIptal = document.getElementById('modalIptal');

            if (btnCikisAc) {
                btnCikisAc.addEventListener('click', (e) => {
                    e.preventDefault();
                    cikisModal.style.display = 'flex';
                });
            }
            if (modalIptal) {
                modalIptal.addEventListener('click', () => cikisModal.style.display = 'none');
            }
            window.addEventListener('click', (e) => {
                if (e.target === cikisModal) cikisModal.style.display = 'none';
            });

            // 3. Silme Onayı
            document.querySelectorAll('.btn-sil-onay').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    if (!confirm('Bu kıyafeti dolabınızdan kalıcı olarak silmek istediğinize emin misiniz?')) {
                        e.preventDefault();
                    }
                });
            });

            // 4. Görsellerin Yüklenmesi (Skeleton Efekti Kaldırma)
            document.querySelectorAll('.kiyafet-gorsel').forEach(img => {
                // Eğer resim önbellekten hemen geldiyse
                if (img.complete) {
                    resimYuklendiEfekti(img);
                } else {
                    img.addEventListener('load', () => resimYuklendiEfekti(img));
                }
            });

            function resimYuklendiEfekti(imgElement) {
                const id = imgElement.getAttribute('data-id');
                const iskeletKutusu = document.getElementById('iskelet-' + id);
                if (iskeletKutusu) {
                    iskeletKutusu.classList.remove('skeleton');
                    imgElement.classList.add('yuklendi');
                }
            }

            // 5. Toast Bildirim Fonksiyonu
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.classList.add('toast', type);

                toast.innerHTML = `
                    <span>${message}</span>
                    <span style="cursor:pointer; font-weight:bold; margin-left:15px; font-size:16px;" onclick="this.parentElement.remove()">✕</span>
                `;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.classList.add('hide');
                    setTimeout(() => toast.remove(), 400); 
                }, 3000);
            }

            // 6. Sepete Ekleme AJAX (Fetch) İşlemi
            document.querySelectorAll('.btn-sepete-ekle').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const kiyafetId = btn.getAttribute('data-id');

                    fetch(`wardrobe.php?sepet_ekle=${kiyafetId}&kat=<?= urlencode($secilen_kategori) ?>`)
                        .then(response => {
                            if (response.ok) {
                                showToast('✨ Kıyafet kombin sepetine eklendi!', 'success');
                                
                                // Sepet sayısını görsel olarak arttır (sayfa yenilenmeden)
                                const sepetSayisiEl = document.getElementById('sepetSayisi');
                                if(sepetSayisiEl) {
                                    let guncelSayi = parseInt(sepetSayisiEl.innerText) || 0;
                                    sepetSayisiEl.innerText = guncelSayi + 1;
                                }
                            } else {
                                showToast('Hata oluştu, tekrar deneyin.', 'error');
                            }
                        })
                        .catch(() => showToast('Bağlantı hatası.', 'error'));
                });
            });
        });
    </script>
</body>
</html>