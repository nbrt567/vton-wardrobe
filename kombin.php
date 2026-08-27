<?php
session_start();

// Kullanıcı oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// Veritabanı bağlantısı
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

// 1. Sepetten Ürün Çıkarma İşlemi
if (isset($_GET['cikar'])) {
    $cikar_id = (int) $_GET['cikar'];
    if (isset($_SESSION['sepet']) && ($key = array_search($cikar_id, $_SESSION['sepet'])) !== false) {
        unset($_SESSION['sepet'][$key]);
    }
    header("Location: kombin.php");
    exit;
}

// 2. Sepeti Tamamen Boşaltma İşlemi
if (isset($_GET['bosalt'])) {
    $_SESSION['sepet'] = [];
    header("Location: kombin.php");
    exit;
}

// 3. Sepetteki Ürünleri Çekme ve Kategorize Etme
$kombin = [
    'aksesuar' => [],
    'dis_giyim' => [],
    'ust_giyim' => [],
    'alt_giyim' => [],
    'ayakkabi' => []
];

$sepet = $_SESSION['sepet'] ?? [];
$sepet_sayisi = count($sepet);

if ($sepet_sayisi > 0) {
    // Güvenli IN sorgusu oluşturma
    $in = str_repeat('?,', $sepet_sayisi - 1) . '?';
    $sorgu = $db->prepare("SELECT * FROM garments WHERE id IN ($in)");
    $sorgu->execute(array_values($sepet));
    $urunler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    foreach ($urunler as $urun) {
        $kat = strtolower(trim($urun['category']));
        
        if (strpos($kat, 'aksesuar') !== false) {
            $kombin['aksesuar'][] = $urun;
        } elseif (strpos($kat, 'dis') !== false || strpos($kat, 'ceket') !== false) {
            $kombin['dis_giyim'][] = $urun;
        } elseif (strpos($kat, 'ust') !== false || strpos($kat, 'tisort') !== false) {
            $kombin['ust_giyim'][] = $urun;
        } elseif (strpos($kat, 'alt') !== false || strpos($kat, 'pantolon') !== false) {
            $kombin['alt_giyim'][] = $urun;
        } elseif (strpos($kat, 'ayakkabi') !== false) {
            $kombin['ayakkabi'][] = $urun;
        } else {
            $kombin['ust_giyim'][] = $urun; // Varsayılan
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kombin Panosu - VTON Dolap</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f5f6fa; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Navbar ve Profil */
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .profil-dropdown { position: relative; display: inline-block; }
        .btn-profil { background-color: #00a8ff; color: white !important; padding: 8px 18px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: background 0.3s; display: inline-block; }
        .btn-profil:hover { background-color: #0097e6; }
        .profil-menu { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 170px; box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px; z-index: 9999; padding-top: 5px; overflow: hidden; border: 1px solid #f1f2f6; }
        .profil-menu a { color: #2f3640 !important; padding: 12px 16px; text-decoration: none; display: block; font-size: 14px; font-weight: bold; transition: background 0.3s; border-bottom: 1px solid #f1f2f6; }
        .profil-menu a:hover { background-color: #f8f9fa; }
        .profil-dropdown:hover .profil-menu { display: block; }
        .profil-menu.kalici-acik { display: block !important; }
        .nav-link-active { color: #00a8ff; font-weight: bold; text-decoration: none; }
        .nav-link-cart { color: #ff4757; text-decoration: none; font-weight: bold; }

        /* Modallar */
        .ozel-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; }
        .modal-icerik { background-color: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 15px 30px rgba(0,0,0,0.2); max-width: 350px; width: 90%; animation: modalAcilis 0.3s ease-out; position: relative; }
        .modal-icerik-genis { max-width: 450px; }
        @keyframes modalAcilis { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-icerik h3 { margin-top: 0; color: #2f3640; font-size: 22px; margin-bottom: 15px; }
        .modal-icerik p { color: #576574; margin-bottom: 25px; font-weight: bold; }
        .modal-butonlar { display: flex; gap: 15px; justify-content: center; }
        
        .btn-iptal { background-color: #f1f2f6; color: #2f3640; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.3s; width: 100%; box-sizing: border-box; }
        .btn-iptal:hover { background-color: #dfe4ea; }
        .btn-evet { background-color: #ff4757; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; transition: background 0.3s; cursor: pointer; border: none; width: 100%; box-sizing: border-box; text-align: center; }
        .btn-evet:hover { background-color: #ff6b81; }
        .btn-kaydet { background-color: #2ed573; color: white; }
        .btn-kaydet:hover { background-color: #26c065; }
        .btn-mavi { background-color: #00a8ff; }
        .btn-mavi:hover { background-color: #0097e6; }
        
        .kapat-btn { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #a4b0be; }

        /* Kombin Panosu */
        .kombin-container { max-width: 700px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.05); text-align: center; }
        .kombin-container h2 { color: #2f3640; margin-top: 0; }
        .kombin-container p { color: #576574; margin-bottom: 30px; }
        
        .manken-tahtasi { display: flex; flex-direction: column; gap: 15px; background: #f8f9fa; padding: 30px 20px; border-radius: 15px; border: 2px dashed #dcdde1; margin-bottom: 30px; }
        .katman { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; min-height: 100px; background: white; padding: 20px; border-radius: 12px; position: relative; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #f1f2f6; }
        .katman::before { content: attr(data-isim); position: absolute; top: -12px; left: 20px; background: #2f3640; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        
        .kıyafet-kutu { position: relative; width: 100px; height: 100px; border-radius: 8px; border: 1px solid #e1e8ed; background: #fff; padding: 5px; }
        .kıyafet-kutu img { width: 100%; height: 100%; object-fit: contain; }
        .cikar-ikon { position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 10px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .cikar-ikon:hover { transform: scale(1.15); }
        .bos-mesaj { width: 100%; color: #a4b0be; font-size: 13px; font-style: italic; display: flex; align-items: center; justify-content: center; }

        .buton-grubu { display: flex; flex-direction: column; gap: 15px; }
        .btn-vton { background: #00a8ff; color: white; padding: 15px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px; transition: background 0.3s; box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3); border: none; cursor: pointer; }
        .btn-vton:hover { background: #0097e6; }
        
        .alt-linkler { display: flex; justify-content: center; gap: 20px; font-size: 14px; margin-top: 10px; }
        .alt-linkler a { color: #576574; text-decoration: none; font-weight: bold; }
        .alt-linkler a.kirmizi { color: #ff4757; }
        .alt-linkler a:hover { text-decoration: underline; }

        /* Boş Ekran (Empty State) */
        .bos-ekran { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; background: #f8f9fa; border-radius: 12px; border: 2px dashed #dcdde1; margin-top: 20px; }
        .bos-ekran-ikon { font-size: 60px; margin-bottom: 20px; animation: hafifSallanma 3s infinite ease-in-out; }
        .bos-ekran h3 { color: #2f3640; margin-bottom: 10px; font-size: 22px; }
        .bos-ekran p { color: #576574; margin-bottom: 25px; font-size: 15px; max-width: 450px; line-height: 1.5; }
        .btn-harekete-gec { background-color: #00a8ff; color: white; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 15px; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(0, 168, 255, 0.3); display: inline-block; }
        .btn-harekete-gec:hover { background-color: #36c5e5; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 168, 255, 0.4); }
        @keyframes hafifSallanma { 0%, 100% { transform: rotate(-5deg); } 50% { transform: rotate(5deg); } }

        /* VTON Yükleme Animasyonu */
        .vton-yukleniyor { display: none; padding: 30px 0; }
        .vton-sonuc { display: none; }
        .vton-gorsel { width: 100%; border-radius: 8px; margin-bottom: 15px; }
        .spinner { width: 60px; height: 60px; border: 6px solid #f1f2f6; border-top: 6px solid #00a8ff; border-radius: 50%; animation: vtonSpin 1s linear infinite; margin: 0 auto; }
        @keyframes vtonSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .modal-ikon-basari { font-size: 3.5rem; margin-bottom: 15px; }
        .basari-baslik { color: #2ed573; margin-bottom: 10px; }
        .modal-buton-grubu { display: flex; flex-direction: column; gap: 12px; }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            <a href="kaydedilen_kombinler.php" class="nav-link-active">✨ Kaydedilen Kombinler</a>
            <a href="kombin.php" class="nav-link-cart">🛒 Kombin Sepeti (<?= $sepet_sayisi ?>)</a>
            
            <div class="profil-dropdown">
                <a href="#" class="btn-profil" id="profilButonu">👤 Profilim</a>
                <div class="profil-menu" id="profilMenusu">
                    <a href="profil.php">🔑 Şifre Değiştir</a>
                    <a href="#" id="btnCikisAc" style="color: #ff4757 !important;">🚪 Çıkış Yap</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="kombin-container">
        <h2>✨ Kombin Panosu</h2>
        <p>Seçtiğiniz kıyafetler hiyerarşik olarak dizilmiştir. Eksik parçaları dolabınızdan tamamlayabilirsiniz.</p>

        <?php if ($sepet_sayisi > 0): ?>
            <div class="manken-tahtasi">
                
                <!-- AKSESUAR KATMANI -->
                <div class="katman" data-isim="Aksesuar (Şapka, Gözlük vb.)">
                    <?php if(!empty($kombin['aksesuar'])): foreach($kombin['aksesuar'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?= $k['id'] ?>" class="cikar-ikon">✕</a>
                            <img src="<?= htmlspecialchars($k['image_path']) ?>" alt="Aksesuar">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- DIŞ GİYİM KATMANI -->
                <div class="katman" data-isim="Dış Giyim (Ceket, Mont)">
                    <?php if(!empty($kombin['dis_giyim'])): foreach($kombin['dis_giyim'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?= $k['id'] ?>" class="cikar-ikon">✕</a>
                            <img src="<?= htmlspecialchars($k['image_path']) ?>" alt="Dış Giyim">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- ÜST GİYİM KATMANI -->
                <div class="katman" data-isim="Üst Giyim (Tişört, Gömlek)">
                    <?php if(!empty($kombin['ust_giyim'])): foreach($kombin['ust_giyim'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?= $k['id'] ?>" class="cikar-ikon">✕</a>
                            <img src="<?= htmlspecialchars($k['image_path']) ?>" alt="Üst Giyim">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- ALT GİYİM KATMANI -->
                <div class="katman" data-isim="Alt Giyim (Pantolon, Etek)">
                    <?php if(!empty($kombin['alt_giyim'])): foreach($kombin['alt_giyim'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?= $k['id'] ?>" class="cikar-ikon">✕</a>
                            <img src="<?= htmlspecialchars($k['image_path']) ?>" alt="Alt Giyim">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- AYAKKABI KATMANI -->
                <div class="katman" data-isim="Ayakkabı">
                    <?php if(!empty($kombin['ayakkabi'])): foreach($kombin['ayakkabi'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?= $k['id'] ?>" class="cikar-ikon">✕</a>
                            <img src="<?= htmlspecialchars($k['image_path']) ?>" alt="Ayakkabı">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="buton-grubu">
                <button id="btnVtonBaslat" class="btn-vton">✨ Kombini Manken Üzerinde Dene (VTON Başlat)</button>
                <div class="alt-linkler">
                    <a href="wardrobe.php">← Dolaba Dön ve Seçime Devam Et</a>
                    <a href="kombin.php?bosalt=1" class="kirmizi">Sepeti Tamamen Boşalt</a>
                </div>
            </div>

        <?php else: ?>
            <div class="bos-ekran">
                <div class="bos-ekran-ikon">🛒</div>
                <h3>Kombin Sepetiniz Boş</h3>
                <p>Manken üzerinde denemek için henüz bir kıyafet seçmediniz. Harika kombinler yaratmak için hemen dolabınıza gidip parçalar seçin!</p>
                <a href="wardrobe.php" class="btn-harekete-gec">✨ Dolaba Git ve Kıyafet Seç</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modallar -->
    
    <!-- Çıkış Modal -->
    <div id="cikisModal" class="ozel-modal">
        <div class="modal-icerik">
            <div style="font-size: 3rem; margin-bottom: 10px;">🚪</div>
            <h3>Çıkış Yap</h3>
            <p>Hesabınızdan çıkış yapmak istediğinize emin misiniz?</p>
            <div class="modal-butonlar">
                <button id="btnCikisIptal" class="btn-iptal">İptal</button>
                <a href="logout.php" class="btn-evet">Evet, Çıkış Yap</a>
            </div>
        </div>
    </div>

    <!-- VTON Simülasyon Modal -->
    <div id="vtonModal" class="ozel-modal">
        <div class="modal-icerik modal-icerik-genis">
            <span id="btnVtonKapatUst" class="kapat-btn">&times;</span>
            <h3 id="vtonBaslik">Sanal Kabin (VTON)</h3>
            
            <div id="vtonYukleniyor" class="vton-yukleniyor">
                <div class="spinner"></div>
                <p style="margin-top: 20px; color: #576574; font-weight: bold; line-height: 1.5;">
                    Yapay Zeka Kıyafeti Mankene Giydiriyor... <br>
                    <small style="color: #a4b0be;">Bu işlem yaklaşık 5-10 saniye sürebilir.</small>
                </p>
            </div>

            <div id="vtonSonuc" class="vton-sonuc">
                <img id="uretilenKombinGorseli" src="https://placehold.co/400x550/00a8ff/ffffff?text=Yapay+Zeka+Manken+Gorseli" alt="VTON Sonuç" class="vton-gorsel">
                <div class="modal-butonlar">
                    <button id="btnKombiniKaydet" class="btn-evet btn-kaydet">💾 Kombini Kaydet</button>
                    <button id="btnVtonKapatAlt" class="btn-iptal">🗑️ Sil / Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Başarı Modal -->
    <div id="basariModal" class="ozel-modal">
        <div class="modal-icerik">
            <div class="modal-ikon-basari">✨</div>
            <h3 class="basari-baslik">Kombin Başarıyla Kaydedildi!</h3>
            <p>Sanal kabindeki bu harika görünüm dolabınıza eklendi.</p>
            <div class="modal-buton-grubu">
                <a href="kaydedilen_kombinler.php" class="btn-evet btn-mavi">📂 Kombinlerime Git</a>
                <button id="btnBaskaKombin" class="btn-iptal">🔄 Başka Kombin Oluştur</button>
            </div>
        </div>
    </div>

    <!-- Toast Bildirim -->
    <div id="ozelBildirim" class="toast-bildirim">
        <span id="bildirimMetni"></span>
    </div>

    <!-- Javascript İşlemleri -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // Profil Menüsü
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

            // Çıkış Modalı
            const cikisModal = document.getElementById('cikisModal');
            const btnCikisAc = document.getElementById('btnCikisAc');
            const btnCikisIptal = document.getElementById('btnCikisIptal');

            if (btnCikisAc) {
                btnCikisAc.addEventListener('click', (e) => {
                    e.preventDefault();
                    cikisModal.style.display = 'flex';
                });
            }
            if (btnCikisIptal) {
                btnCikisIptal.addEventListener('click', () => cikisModal.style.display = 'none');
            }

            // Bildirim (Toast) Fonksiyonu
            const bildirimGoster = (mesaj, tur) => {
                const toast = document.getElementById("ozelBildirim");
                if(!toast) return; 
                
                document.getElementById("bildirimMetni").innerText = mesaj;
                toast.classList.toggle("toast-hata", tur === 'hata');
                toast.classList.add("goster");
                
                setTimeout(() => toast.classList.remove("goster"), 3500);
            };

            // VTON İşlemleri
            const vtonModal = document.getElementById('vtonModal');
            const btnVtonBaslat = document.getElementById('btnVtonBaslat');
            
            const vtonKapat = () => {
                if(vtonModal) vtonModal.style.display = 'none';
            };

            // VTON Modal Kapatma Butonları
            ['btnVtonKapatUst', 'btnVtonKapatAlt'].forEach(id => {
                const btn = document.getElementById(id);
                if(btn) btn.addEventListener('click', vtonKapat);
            });

            if (btnVtonBaslat) {
                btnVtonBaslat.addEventListener('click', (e) => {
                    e.preventDefault();
                    vtonModal.style.display = 'flex';
                    document.getElementById('vtonBaslik').innerText = 'Sanal Kabin (Yapay Zeka İşliyor...)';
                    document.getElementById('vtonYukleniyor').style.display = 'block';
                    document.getElementById('vtonSonuc').style.display = 'none';

                    // API İsteği
                    fetch('vton_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'istek_var=1' 
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('vtonYukleniyor').style.display = 'none';

                        if (data.status === 'success') {
                            document.getElementById('uretilenKombinGorseli').src = data.output_url;
                            document.getElementById('vtonSonuc').style.display = 'block';
                            document.getElementById('vtonBaslik').innerText = 'İşte Yapay Zeka Sonucu!';
                        } else {
                            bildirimGoster("Yapay Zeka Hatası: " + data.message, 'hata');
                            vtonKapat();
                        }
                    })
                    .catch(error => {
                        bildirimGoster('Sistem hatası oluştu, API’ye ulaşılamadı.', 'hata');
                        vtonKapat();
                    });
                });
            }

            // Kombin Kaydetme İşlemi
            const btnKombiniKaydet = document.getElementById('btnKombiniKaydet');
            const basariModal = document.getElementById('basariModal');

            if (btnKombiniKaydet) {
                btnKombiniKaydet.addEventListener('click', () => {
                    const gorselEl = document.getElementById('uretilenKombinGorseli');
                    if (!gorselEl) return;

                    fetch('kombin_kaydet.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'gorsel_url=' + encodeURIComponent(gorselEl.src)
                    })
                    .then(response => response.text())
                    .then(data => {
                        if(data.trim() === 'basarili') {
                            vtonKapat(); 
                            if(basariModal) basariModal.style.display = 'flex'; 
                        } else {
                            bildirimGoster('Sunucu Hatası: ' + data, 'hata');
                        }
                    })
                    .catch(() => bildirimGoster('Sistem Hatası oluştu.', 'hata'));
                });
            }

            // Başarı Modalı Kapatma
            const btnBaskaKombin = document.getElementById('btnBaskaKombin');
            if (btnBaskaKombin) {
                btnBaskaKombin.addEventListener('click', () => {
                    if(basariModal) basariModal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>