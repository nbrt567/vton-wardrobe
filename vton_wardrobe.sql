-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 27 Ağu 2026, 10:19:23
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `vton_wardrobe`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `garments`
--

CREATE TABLE `garments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `garments`
--

INSERT INTO `garments` (`id`, `user_id`, `image_path`, `category`) VALUES
(7, 3, 'uploads/1786384785_6a7a119105024.jpg', 'ust'),
(8, 3, 'uploads/1786385405_6a7a13fdc4605.jpg', 'ust'),
(10, 3, 'uploads/1786388735_6a7a20ffc3fe7.jpg', 'alt');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kaydedilen_kombinler`
--

CREATE TABLE `kaydedilen_kombinler` (
  `id` int(11) NOT NULL,
  `kullanici_id` int(11) NOT NULL,
  `gorsel_url` varchar(500) NOT NULL,
  `olusturulma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kaydedilen_kombinler`
--

INSERT INTO `kaydedilen_kombinler` (`id`, `kullanici_id`, `gorsel_url`, `olusturulma_tarihi`) VALUES
(8, 3, 'https://replicate.delivery/pbxt/KgwTlhCMvDagRrcVzZJbuozNJ8esPqiNAIJS3eMgHrYuHmW4/KakaoTalk_Photo_2024-04-04-21-44-45.png', '2026-08-27 07:48:50'),
(9, 3, 'https://replicate.delivery/pbxt/KgwTlhCMvDagRrcVzZJbuozNJ8esPqiNAIJS3eMgHrYuHmW4/KakaoTalk_Photo_2024-04-04-21-44-45.png', '2026-08-27 07:49:01');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `outfits`
--

CREATE TABLE `outfits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `top_id` int(11) DEFAULT NULL,
  `bottom_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `created_at`, `role`) VALUES
(3, 'deneme8@gmail.com', '$2y$10$VhlNWpTZ0xoar7upqcmHH.0Fm0kfTVi5u/sewhsfl.tsFOed1hYG6', '2026-08-10 17:17:10', 'user'),
(6, 'admin@admin.com', '$2y$10$eGlzFDrbPV6ypsvqqzFapO2flBGnFRDp3qpEXGTi0tgeLP24w3QHq', '2026-08-10 18:25:43', 'admin');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `garments`
--
ALTER TABLE `garments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `kaydedilen_kombinler`
--
ALTER TABLE `kaydedilen_kombinler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `outfits`
--
ALTER TABLE `outfits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `top_id` (`top_id`),
  ADD KEY `bottom_id` (`bottom_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `garments`
--
ALTER TABLE `garments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `kaydedilen_kombinler`
--
ALTER TABLE `kaydedilen_kombinler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `outfits`
--
ALTER TABLE `outfits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `garments`
--
ALTER TABLE `garments`
  ADD CONSTRAINT `garments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Tablo kısıtlamaları `outfits`
--
ALTER TABLE `outfits`
  ADD CONSTRAINT `outfits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `outfits_ibfk_2` FOREIGN KEY (`top_id`) REFERENCES `garments` (`id`),
  ADD CONSTRAINT `outfits_ibfk_3` FOREIGN KEY (`bottom_id`) REFERENCES `garments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
