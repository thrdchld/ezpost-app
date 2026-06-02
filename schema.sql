-- Struktur Database untuk EZPost (TiDB Serverless / MySQL)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Memasukkan Data Login Anda (Sistem PHP akan otomatis meng-enkripsi password ini saat Anda login pertama kali)
INSERT IGNORE INTO `users` (`id`, `email`, `password_hash`) VALUES
(1, 'thirdchilddesigner@gmail.com', 'Alliswell95');

-- 2. Tabel Akun Sosial Media
CREATE TABLE IF NOT EXISTS `social_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `provider` enum('facebook','threads') NOT NULL,
  `access_token` text NOT NULL,
  `page_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_provider` (`user_id`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Memasukkan Long-Lived Token Meta Anda
INSERT IGNORE INTO `social_accounts` (`user_id`, `provider`, `access_token`, `page_id`) VALUES
(1, 'facebook', 'EAAWGZABi0lk0BRkABn1Cv09Rr3FrAV7gAirPeJ0It7FucZBVPZAy6orIwQXc94XU8rzR2URgXZCZBvEwY7t4SFHy2FWM55YuBVUd71vsn0Ep9mKV15FCoS2NGcVD5hyOcdHMm6iTHofgn5X1sBU0OZA0COt430HXczaioAKOSZBf8wEraasOlFZBwfVLHyiQJPRfZCnhgZBTsT', 'me'),
(1, 'threads', 'THAAN8cjtkpoxBYmJUaXRQemlXbkF6RjJiSkNFSjlTLUR5d01RWjc0ZAW9fTTd5T0YybnpnYnpLVl9mTHVZAMVhiMUVrTm1VUzVnQmlRaUNOV0RKcjJuTEFya09ZAZAlU3al9oeGpBd3FETkRkd2lIMnI1eFdXRng1WHpqZAlc3SDFoM29yUQZDZD', NULL);

-- 3. Tabel Posting
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `platform` enum('facebook','threads') NOT NULL,
  `content` text NOT NULL,
  `status` enum('draft','scheduled','published','failed') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `error_log` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Media untuk Posting
CREATE TABLE IF NOT EXISTS `post_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;