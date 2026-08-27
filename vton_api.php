<?php
session_start();

// 1. HTTP Başlıkları (Headers): Bu dosyanın bir HTML değil, JSON döndürdüğünü tarayıcıya bildirir.
header('Content-Type: application/json; charset=utf-8');

// 2. Yetki Kontrolü (401 Unauthorized)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); 
    echo json_encode([
        "status" => "error", 
        "message" => "Yetkisiz erişim: Lütfen oturum açın."
    ]);
    exit;
}

// 3. Metot Kontrolü: Sadece POST isteklerini kabul et (405 Method Not Allowed)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Geçersiz istek metodu. Sadece POST kabul edilmektedir."
    ]);
    exit;
}

// 4. Mock API Gecikmesi (Gerçek bir yapay zeka sunucusu yanıt süresini simüle eder)
sleep(5);

// 5. VTON (Virtual Try-On) Başarılı Yanıt Simülasyonu
// İleride gerçek bir API'ye bağlandığında bu kısım cURL veya Guzzle HTTP ile güncellenecektir.
$mock_output_url = "https://replicate.delivery/pbxt/KgwTlhCMvDagRrcVzZJbuozNJ8esPqiNAIJS3eMgHrYuHmW4/KakaoTalk_Photo_2024-04-04-21-44-45.png";

echo json_encode([
    "status" => "success",
    "output_url" => $mock_output_url
]);
exit;