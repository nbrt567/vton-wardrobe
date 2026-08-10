<?php
session_start();

// SADECE ADMİN GİREBİLİR KONTROLÜ
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
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

if (isset($_GET['delete_id'])) {
    $sil_id = $_GET['delete_id'];
    
    $kiyafet_sil = $db->prepare("DELETE FROM garments WHERE user_id = :id");
    $kiyafet_sil->execute([':id' => $sil_id]);
    
    $kullanici_sil = $db->prepare("DELETE FROM users WHERE id = :id");
    $kullanici_sil->execute([':id' => $sil_id]);
    
    header("Location: admin.php?durum=silindi");
    exit;
}

// SADECE 'USER' ROLÜNDEKİ KULLANICILARI ÇEK (Adminler listede görünmez)
$sorgu = $db->prepare("
    SELECT users.id, users.email, users.created_at, COUNT(garments.id) as kiyafet_sayisi 
    FROM users 
    LEFT JOIN garments ON users.id = garments.user_id 
    WHERE users.role = 'user'
    GROUP BY users.id 
    ORDER BY users.id DESC
");
$sorgu->execute();
$kullanicilar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - VTON Dolap</title>
    <style>
        /* CSS kodları bir öncekiyle tamamen aynı kalsın diye buraya özetliyorum, istersen CSS'i taşıyabilirsin. */
        body { background-color: #f1f2f6; font-family: sans-serif; }
        .admin-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .admin-header h2 { color: #2f3640; margin: 0; display: flex; align-items: center; gap: 10px; }
        .tablo-kutu { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background-color: #f8f9fa; border-bottom: 2px solid #e1e8ed; }
        th { padding: 15px 20px; color: #576574; font-size: 14px; text-transform: uppercase; }
        td { padding: 15px 20px; border-bottom: 1px solid #f1f2f6; color: #2f3640; }
        tr:hover { background-color: #fbfbfc; }
        .badge { background-color: #00a8ff; color: white; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .btn-sil { background-color: #ff4757; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        
        /* Modal */
        .ozel-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; }
        .modal-icerik { background-color: white; padding: 30px; border-radius: 12px; text-align: center; max-width: 350px; width: 90%; }
        .silinecek-email { background: #ffeaa7; color: #d63031; padding: 8px 15px; border-radius: 6px; margin: bottom: 25px; font-weight: bold; }
        .modal-butonlar { display: flex; gap: 15px; justify-content: center; margin-top:20px; }
        .btn-iptal { background-color: #f1f2f6; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .btn-onay { background-color: #ff4757; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .navbar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .navbar a { text-decoration: none; color: #2f3640; font-weight: bold; }
        .btn-cikis { color: #ff4757 !important; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="#" class="nav-brand">👗 VTON Dolap (Yönetici)</a>
        <div class="nav-links">
            <a href="logout.php" class="btn-cikis">🚪 Çıkış Yap</a>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h2>⚙️ Yönetim Paneli - Kullanıcı Listesi</h2>
            <div class="badge">Toplam Kullanıcı: <?php echo count($kullanicilar); ?></div>
        </div>

        <div class="tablo-kutu">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>E-Posta Adresi</th>
                        <th>Rol</th>
                        <th>Kayıt Tarihi</th>
                        <th style="text-align: center;">Dolabındaki Kıyafet</th>
                        <th style="text-align: right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kullanicilar as $kul): 
                        // Admin olmayanların rolünü belirlemek için küçük bir kontrol
                        $kul_rol = isset($kul['role']) ? $kul['role'] : 'user';
                    ?>
                    <tr>
                        <td>#<?php echo $kul['id']; ?></td>
                        <td><?php echo htmlspecialchars($kul['email']); ?></td>
                        <td><span class="badge" style="background-color: <?php echo $kul_rol == 'admin' ? '#ff4757' : '#2ed573'; ?>"><?php echo strtoupper($kul_rol); ?></span></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($kul['created_at'])); ?></td>
                        <td style="text-align: center;">
                            <span class="badge" style="background-color: <?php echo $kul['kiyafet_sayisi'] > 0 ? '#2ed573' : '#a4b0be'; ?>;">
                                <?php echo $kul['kiyafet_sayisi']; ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                             <button onclick="silmeOnayiAc(<?php echo $kul['id']; ?>, '<?php echo htmlspecialchars($kul['email']); ?>')" class="btn-sil">
                                🗑️ Sil
                             </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Özel Silme Onay Modalı -->
    <div id="silModal" class="ozel-modal">
        <div class="modal-icerik">
            <div style="font-size: 3rem; margin-bottom: 10px;">⚠️</div>
            <h3>Kullanıcıyı Sil</h3>
            <p>Bu hesabı ve dolabındaki tüm kıyafetleri kalıcı olarak silmek istediğinize emin misiniz?</p>
            <div class="silinecek-email" id="modalEmailGoster">email@adres.com</div>
            
            <div class="modal-butonlar">
                <button id="modalIptalBtn" class="btn-iptal">Vazgeç</button>
                <button id="modalOnayBtn" class="btn-onay">Evet, Kalıcı Olarak Sil</button>
            </div>
        </div>
    </div>

    <script>
        var silModal = document.getElementById('silModal');
        var iptalBtn = document.getElementById('modalIptalBtn');
        var onayBtn = document.getElementById('modalOnayBtn');
        var silinecekID = null;

        function silmeOnayiAc(id, email) {
            silinecekID = id; 
            document.getElementById('modalEmailGoster').innerText = email;
            silModal.style.display = 'flex';
        }

        iptalBtn.addEventListener('click', function() {
            silModal.style.display = 'none';
        });
        window.addEventListener('click', function(event) {
            if (event.target == silModal) {
                silModal.style.display = 'none';
            }
        });

        onayBtn.addEventListener('click', function() {
            if (silinecekID !== null) {
                window.location.href = "admin.php?delete_id=" + silinecekID;
            }
        });
    </script>
</body>
</html>