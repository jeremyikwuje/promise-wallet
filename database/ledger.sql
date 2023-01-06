DROP TABLE IF EXISTS `ledger`;
--
-- Table structure for table `ledger`
--
CREATE TABLE `ledger_account` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `customer_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `customer_id` (`customer_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8, AUTO_INCREMENT=1;

DROP TABLE IF EXISTS `ledger`;
--
-- Table structure for table `ledger`
--
CREATE TABLE `ledger` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `ledger_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `customer_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `deposit_address` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `currency` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `ledger_id` (`ledger_id`),
  UNIQUE KEY `deposit_address` (`deposit_address`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8, AUTO_INCREMENT=1;