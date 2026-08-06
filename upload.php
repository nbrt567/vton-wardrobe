<?php
// 1. Veritabanı Bağlantısı (PDO kullanılarak)
$host = 'localhost';
$dbname = 'vton_wardrobe';
$kullanici = 'root';
$sifre = ''; // XAMPP'ta varsayılan şifre boştur

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $kullanici, $sifre);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// 2. Form Gönderildiğinde Çalışacak Kodlar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["garment_image"])) {
    
    $kategori = $_POST['category'];
    $dosya = $_FILES['garment_image'];
    
    // Dosya adını benzersiz yap (çakışmaları önlemek için)
    $dosya_uzantisi = pathinfo($dosya['name'], PATHINFO_EXTENSION);
    $yeni_dosya_adi = uniqid('vton_') . '.' . $dosya_uzantisi;
    $hedef_yol = 'uploads/' . $yeni_dosya_adi;

    // Dosyayı geçici klasörden uploads klasörüne taşı
    if (move_uploaded_file($dosya['tmp_name'], $hedef_yol)) {
        
        // Veritabanına kaydet (Şimdilik test için user_id'yi 1 olarak manuel veriyoruz)
        $sql = "INSERT INTO garments (user_id, image_path, category) VALUES (1, :image_path, :category)";
        $sorgu = $db->prepare($sql);
        
        $sorgu->execute([
            ':image_path' => $hedef_yol,
            ':category' => $kategori
        ]);

        echo "<h2>Tebrikler! Kıyafet başarıyla dolabınıza eklendi.</h2>";
        echo "<a href='index.html'>Geri Dön</a>";
        
    } else {
        echo "Dosya yüklenirken bir hata oluştu.";
    }
}
?>