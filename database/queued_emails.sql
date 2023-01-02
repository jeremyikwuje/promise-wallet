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