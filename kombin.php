<?php
session_start();

// Giriş kontrolü
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

// 1. Sepetten Ürün Çıkarma İşlemi
if (isset($_GET['cikar'])) {
    $cikar_id = $_GET['cikar'];
    if (($key = array_search($cikar_id, $_SESSION['sepet'])) !== false) {
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

// 3. Sepetteki Ürünleri Veritabanından Çekip Kategorilere Ayırma
$kombin = [
    'aksesuar' => [],
    'dis_giyim' => [],
    'ust_giyim' => [],
    'alt_giyim' => [],
    'ayakkabi' => []
];

$sepet_sayisi = isset($_SESSION['sepet']) ? count($_SESSION['sepet']) : 0;

if ($sepet_sayisi > 0) {
    // Sadece sepetteki id'leri çekmek için SQL sorgusu oluşturuyoruz (Örn: IN (1, 5, 8))
    $in = str_repeat('?,', count($_SESSION['sepet']) - 1) . '?';
    $sorgu = $db->prepare("SELECT * FROM garments WHERE id IN ($in)");
    $sorgu->execute(array_values($_SESSION['sepet']));
    $urunler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    foreach ($urunler as $urun) {
        // Veritabanındaki değeri küçük harfe çevir ve boşlukları temizle
        $kat = strtolower(trim($urun['category']));
        
        // Basit bir eşleştirme haritası
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
            // Hiçbiri değilse varsayılan olarak üst giyime at
            $kombin['ust_giyim'][] = $urun;
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
        /* Navbar ve Profil Menüsü CSS (Bozulmaması İçin) */
        .nav-links { display: flex; align-items: center; gap: 20px; }
        .profil-dropdown { position: relative; display: inline-block; }
        .btn-profil { background-color: #00a8ff; color: white !important; padding: 8px 18px; border-radius: 6px; font-weight: bold; text-decoration: none; transition: background 0.3s; display: inline-block; }
        .btn-profil:hover { background-color: #0097e6; }
        .profil-menu { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 170px; box-shadow: 0px 10px 25px rgba(0,0,0,0.1); border-radius: 8px; z-index: 9999; padding-top: 5px; overflow: hidden; border: 1px solid #f1f2f6; }
        .profil-menu a { color: #2f3640 !important; padding: 12px 16px; text-decoration: none; display: block; font-size: 14px; font-weight: bold; transition: background 0.3s; border-bottom: 1px solid #f1f2f6; }
        .profil-menu a:last-child { border-bottom: none; }
        .profil-menu a:hover { background-color: #f8f9fa; }
        .profil-dropdown:hover .profil-menu { display: block; }
        .profil-menu.kalici-acik { display: block !important; }

        /* Özel Uyarı Modalı */
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

        /* KOMBİN PANOSU (MANKEN) STİLLERİ */
        .kombin-container { max-width: 700px; margin: 40px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.05); text-align: center; }
        .kombin-container h2 { color: #2f3640; margin-top: 0; }
        .kombin-container p { color: #576574; margin-bottom: 30px; }
        
        .manken-tahtasi {
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f8f9fa;
            padding: 30px 20px;
            border-radius: 15px;
            border: 2px dashed #dcdde1;
            margin-bottom: 30px;
        }

        .katman {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            min-height: 100px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            border: 1px solid #f1f2f6;
        }

        .katman::before {
            content: attr(data-isim);
            position: absolute;
            top: -12px;
            left: 20px;
            background: #2f3640;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kıyafet-kutu {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            border: 1px solid #e1e8ed;
            background: #fff;
            padding: 5px;
        }

        .kıyafet-kutu img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .kıyafet-kutu .cikar-ikon {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 10px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .kıyafet-kutu .cikar-ikon:hover { transform: scale(1.15); }

        .bos-mesaj {
            width: 100%;
            color: #a4b0be;
            font-size: 13px;
            font-style: italic;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .buton-grubu { display: flex; flex-direction: column; gap: 15px; }
        .btn-vton { background: #00a8ff; color: white; padding: 15px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px; transition: background 0.3s; box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3); }
        .btn-vton:hover { background: #0097e6; }
        
        .alt-linkler { display: flex; justify-content: center; gap: 20px; font-size: 14px; margin-top: 10px; }
        .alt-linkler a { color: #576574; text-decoration: none; font-weight: bold; }
        .alt-linkler a.kirmizi { color: #ff4757; }
        .alt-linkler a:hover { text-decoration: underline; }

        /* Boş Ekran (Empty State) Tasarımı */
        .bos-ekran {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #dcdde1;
            margin-top: 20px;
        }

        .bos-ekran-ikon {
            font-size: 60px;
            margin-bottom: 20px;
            animation: hafifSallanma 3s infinite ease-in-out;
        }

        .bos-ekran h3 {
            color: #2f3640;
            margin-bottom: 10px;
            font-size: 22px;
        }

        .bos-ekran p {
            color: #576574;
            margin-bottom: 25px;
            font-size: 15px;
            max-width: 450px;
            line-height: 1.5;
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
    <!-- Üst Menü -->
    <nav class="navbar">
        <a href="wardrobe.php" class="nav-brand">👗 VTON Dolap</a>
        <div class="nav-links">
            <a href="ekle.html">Kıyafet Ekle</a>
            <a href="wardrobe.php">Dolaba Git</a>
            <a href="kombin.php" style="color: #ff4757;">🛒 Kombin Sepeti (<?php echo $sepet_sayisi; ?>)</a>
            
            <div class="profil-dropdown">
                <a href="#" class="btn-profil" id="profilButonu">👤 Profilim</a>
                <div class="profil-menu" id="profilMenusu">
                    <a href="profil.php">🔑 Şifre Değiştir</a>
                    <a href="#" onclick="cikisOnayla(event)" style="color: #ff4757 !important;">🚪 Çıkış Yap</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- KOMBİN İÇERİĞİ -->
    <div class="kombin-container">
        <h2>✨ Kombin Panosu</h2>
        <p>Seçtiğiniz kıyafetler hiyerarşik olarak dizilmiştir. Eksik parçaları dolabınızdan tamamlayabilirsiniz.</p>

        <?php if ($sepet_sayisi > 0): ?>
            
            <div class="manken-tahtasi">
                
                <!-- AKSESUAR KATMANI -->
                <div class="katman" data-isim="Aksesuar (Şapka, Gözlük vb.)">
                    <?php if(!empty($kombin['aksesuar'])): foreach($kombin['aksesuar'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?php echo $k['id']; ?>" class="cikar-ikon">✕</a>
                            <img src="<?php echo htmlspecialchars($k['image_path']); ?>" alt="Aksesuar">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- DIŞ GİYİM KATMANI -->
                <div class="katman" data-isim="Dış Giyim (Ceket, Mont)">
                    <?php if(!empty($kombin['dis_giyim'])): foreach($kombin['dis_giyim'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?php echo $k['id']; ?>" class="cikar-ikon">✕</a>
                            <img src="<?php echo htmlspecialchars($k['image_path']); ?>" alt="Dış Giyim">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- ÜST GİYİM KATMANI -->
                <div class="katman" data-isim="Üst Giyim (Tişört, Gömlek)">
                    <?php if(!empty($kombin['ust_giyim'])): foreach($kombin['ust_giyim'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?php echo $k['id']; ?>" class="cikar-ikon">✕</a>
                            <img src="<?php echo htmlspecialchars($k['image_path']); ?>" alt="Üst Giyim">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- ALT GİYİM KATMANI -->
                <div class="katman" data-isim="Alt Giyim (Pantolon, Etek)">
                    <?php if(!empty($kombin['alt_giyim'])): foreach($kombin['alt_giyim'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?php echo $k['id']; ?>" class="cikar-ikon">✕</a>
                            <img src="<?php echo htmlspecialchars($k['image_path']); ?>" alt="Alt Giyim">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

                <!-- AYAKKABI KATMANI -->
                <div class="katman" data-isim="Ayakkabı">
                    <?php if(!empty($kombin['ayakkabi'])): foreach($kombin['ayakkabi'] as $k): ?>
                        <div class="kıyafet-kutu">
                            <a href="kombin.php?cikar=<?php echo $k['id']; ?>" class="cikar-ikon">✕</a>
                            <img src="<?php echo htmlspecialchars($k['image_path']); ?>" alt="Ayakkabı">
                        </div>
                    <?php endforeach; else: ?>
                        <div class="bos-mesaj">Bu alana henüz bir parça seçilmedi.</div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="buton-grubu">
                <a href="#" class="btn-vton">✨ Kombini Manken Üzerinde Dene (VTON Başlat)</a>
                <div class="alt-linkler">
                    <a href="wardrobe.php">← Dolaba Dön ve Seçime Devam Et</a>
                    <a href="kombin.php?bosalt=1" class="kirmizi">Sepeti Tamamen Boşalt</a>
                </div>
            </div>

       <?php else: ?>
            <!-- Gelişmiş Boş Ekran (Empty State) -->
            <div class="bos-ekran">
                <div class="bos-ekran-ikon">🛒</div>
                <h3>Kombin Sepetiniz Boş</h3>
                <p>Manken üzerinde denemek için henüz bir kıyafet seçmediniz. Harika kombinler yaratmak için hemen dolabınıza gidip parçalar seçin!</p>
                <a href="wardrobe.php" class="btn-harekete-gec">✨ Dolaba Git ve Kıyafet Seç</a>
            </div>
        <?php endif; ?>
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

    <!-- Scriptler -->
    <script>
        function cikisOnayla(event) {
            event.preventDefault();
            document.getElementById('cikisModal').style.display = 'flex';
        }

        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('profilButonu');
            var menu = document.getElementById('profilMenusu');

            btn.addEventListener('click', function(event) {
                event.preventDefault();
                menu.classList.toggle('kalici-acik');
            });

            document.addEventListener('click', function(event) {
                if (!btn.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.remove('kalici-acik');
                }
            });

            var modal = document.getElementById('cikisModal');
            var iptalBtn = document.getElementById('modalIptal');

            iptalBtn.addEventListener('click', function() { modal.style.display = 'none'; });
            window.addEventListener('click', function(event) {
                if (event.target == modal) { modal.style.display = 'none'; }
            });
        });
    </script>
</body>
</html>