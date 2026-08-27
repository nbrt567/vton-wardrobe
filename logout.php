<?php
session_start();

// 1. Mevcut oturum değişkenlerini boşalt
$_SESSION = [];

// 2. Tarayıcıdaki oturum çerezini (session cookie) tamamen sil (Güvenlik Önlemi)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Oturumu sunucu tarafında imha et
session_destroy();

// 4. Güvenli bir şekilde ana sayfaya yönlendir
header("Location: index.html");
exit;