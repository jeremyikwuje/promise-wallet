CREATE TABLE `api_users` (
  `id` int(11) NOT NULL,
  `access_key` varchar(255) NOT NULL,
  `access_name` varchar(255) NOT NULL,
  `role` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY(`id`),
  UNIQUE KEY(`access_key`),
  UNIQUE KEY(`access_name`)
) ENGINE=InnoDB;

INSERT INTO `api_users` (`id`, `access_key`, `access_name`, `role`) VALUES
(100, '$2y$10$fqYVu8c/qYTl9IjxE7Vjfu4GmTRUwEdq68f2eTWckHzgWxT2FjbWy', 'forward', 1);

--
-- default name/key : forward/3c1821bb500d32b54eae1c719ba80dc4
--

--
--  Drop any exist table
--
DROP TABLE IF EXISTS `queued_emails`;

--
-- Table structure for table `app_users`
--
CREATE TABLE `queued_emails` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `to` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `subject` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `type` varchar(20) NOT NULL,
  `send_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
