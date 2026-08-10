<?php
session_start();
session_destroy(); // Tüm oturum verilerini siler
header("Location: index.html"); // Ana sayfaya (giriş ekranına) geri gönderir
exit;
?>