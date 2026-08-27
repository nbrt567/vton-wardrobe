<?php
session_start();

// Kullanıcı girişi kontrolü (Projenin güvenliği için)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Oturum açılmamış."]);
    exit;
}

// 1. İLLÜZYON: YAPAY ZEKA ÇALIŞIYOR HİSSİ
sleep(5);

// 2. ÖNCEDEN HAZIRLANMIŞ MÜKEMMEL SONUÇ GÖRSELİ
$hazir_sonuc_gorseli = "https://replicate.delivery/pbxt/KgwTlhCMvDagRrcVzZJbuozNJ8esPqiNAIJS3eMgHrYuHmW4/KakaoTalk_Photo_2024-04-04-21-44-45.png";

// 3. BAŞARILI YANIT DÖNDÜRME
// 5 saniye dolduktan sonra, sahte API'miz ön yüze sanki işlemi
// yeni bitirmiş gibi başarı mesajı ve görselin linkini yolluyor.
echo json_encode([
    "status" => "success",
    "output_url" => $hazir_sonuc_gorseli
]);
?>