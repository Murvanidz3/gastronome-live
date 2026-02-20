-- Gastronome.live Database Dump

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `mobile` varchar(30) DEFAULT '',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES 
('1', 'Masho Pichkhaia', 'masho', '', '$2y$10$dqOZ7bt1xXE1Q9zukemNXOpiGa9h.IJlDWCULHQZcPRaexvTAIF/m', '2026-02-20 15:17:28', '2026-02-20 20:15:05');

DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `company_id_number` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_companies_user` (`user_id`),
  CONSTRAINT `fk_companies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `companies` VALUES 
('1', 'Adjara Group', '200200200', '26 may square', '20202020', '2026-02-20 19:14:35', '1'),
('2', 'Silk Road Group', '300300300', 'Kostava 25', '202020', '2026-02-20 19:22:52', '1');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) DEFAULT NULL,
  `barcode` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'GEL',
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_barcode` (`barcode`),
  KEY `idx_products_name` (`name`),
  KEY `fk_products_user` (`user_id`),
  CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` VALUES 
('61', 'https://media.s-bol.com/nqXA7p8WByw4/1wlq4Jq/584x1200.jpg', '8003299494767', 'Alessi - HALESIA, მაგიდის სანათი, ქრომი', '0', '299.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('62', 'https://images.byflou.com/13/3/images/products/0/0/ferm-living-pendler-collect-a-light-socket-pendant-cashmere-low-8828635.jpeg', '5704723033271', 'Ferm Living-გულსაკიდი ნათურა,მოკლე', '0', '99.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('63', 'https://images.byflou.com/13/3/images/products/0/0/ferm-living-pendler-collect-a-light-socket-pendant-cashmere-low-8828635.jpeg', '5704723033295', 'Ferm Living-გულსაკიდი ნათურა,გრძელი', '0', '99.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('64', 'https://02.cdn37.se/OKM/images/2.96893/hay-neon-tube-led-slim-50.jpeg', '5710441294979', 'Hay-ნეონის სანათი 50, ლურჯი', '0', '89.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('65', 'https://lw-cdn.com/images/C133A938C45B/k_778e6a12074ff6364e22ace6f6a69ebe;w_575;h_575;q_90/70013911.webp', '5710441280347', 'Hay-ნეონის სანათი,ვარდისფერი', '0', '89.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('66', 'https://02.cdn37.se/OKM/images/2.118109/hay-rice-paper-shade-risboll-ellipse-70x48-exkl-upphang.jpeg', 'AA986-B061', 'Hay-ქაღალდის სანათი, ელიფსი', '0', '89.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('67', 'https://images.byflou.com/13/3/images/products/0/0/hay-lampeskaerm-rice-paper-shade-floor-shade-oe56-classic-white-3604356.jpeg?_gl=1*12zsi9y*_gcl_au*MTE3MjY3MjEyMi4xNzY2Mzk0NzAx*FPAU*MTE3MjY3MjEyMi4xNzY2Mzk0NzAx*_ga*MjU4NDk2MTk0LjE3NjYzOTQ3MDE.*_ga_450FV', '5710441311560', 'Hay-ქაღალდის დასადგამი სანათი, მრგვალი', '0', '79.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('68', 'https://lw-cdn.com/images/70B536B3140A/k_05a0cc582eb76ec195b84756218c2100;w_575;h_575;q_90/70037656.webp', '5710441365211', 'Hay-PC სანათი, ლურჯი/თეთრი', '0', '199.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('69', 'https://lw-cdn.com/images/CB6AA4E1559F/k_dde4a9addd1a961d61e3302d927d6940;w_1600;h_1600;q_90/70037650.webp', '5710441365198', 'Hay-PC სანათი, ატმისფერი', '0', '199.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('70', 'https://cdn.andlight.dk/images/4104312009000-pc-portable-olive-p.jpg?imgeng=/w_auto,1980/cmpr_10', '5710441365150', 'Hay-PC სანათი, OLIVE', '0', '199.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('71', 'https://lw-cdn.com/images/1BECB545FF69/k_bb10348e419c1e581e8882ae6ce56c49;w_1600;h_1600;q_90/4553077.webp', '5710441037798', 'Hay- Turn on სანათი , ალუმინის', '0', '389.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('72', 'https://lw-cdn.com/images/F456DB2B70CF/k_41d14c58dcb251adb97754897dcf5c3d;w_1600;h_1600;q_90/4553078.webp', '5710441037804', 'Hay- Turn on სანათი , ფორთოხლისფერი', '0', '389.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('73', 'https://lw-cdn.com/images/FCC738B448F2/k_ae27f50bf4ab9fc5dd46bff132ab886d;w_1600;h_1600;q_90/4553079.webp', '5710441037811', 'Hay- Turn on სანათი , მწვანე', '0', '389.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('74', 'https://lw-cdn.com/images/0DA0382C2167/k_999303fdb8b948cbba98cf019ea22bb5;w_1600;h_1600;q_90/70037649.webp', '5710441365181', 'Hay-PC სანათი, ლურჯი', '0', '219.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('75', 'https://images.byflou.com/13/3/images/products/0/0/menu-pendler-hashira-pendant-cluster-small-3415595.jpeg?_gl=1*145hz8w*_gcl_au*MTE3MjY3MjEyMi4xNzY2Mzk0NzAx*FPAU*MTE3MjY3MjEyMi4xNzY2Mzk0NzAx*_ga*MjU4NDk2MTk0LjE3NjYzOTQ3MDE.*_ga_450FV8Y6PQ*czE3NzE1ODI0MzM', '5709262044344', 'Menu-პატარა სანათი', '0', '1499.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('76', 'https://cdn.andlight.dk/images/Menu_Hashira_Cluster_Pendant_Stor-p.jpg?imgeng=/w_auto,1980/cmpr_10', '5709262044320', 'Menu-დიდი სანათი', '0', '2999.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('77', 'https://images.byflou.com/13/3/images/products/0/0/normann-copenhagen-lampe-superpose-lamp-white-8072079.jpeg?_gl=1*17gnw98*_gcl_au*MTE3MjY3MjEyMi4xNzY2Mzk0NzAx*FPAU*MTE3MjY3MjEyMi4xNzY2Mzk0NzAx*_ga*MjU4NDk2MTk0LjE3NjYzOTQ3MDE.*_ga_450FV8Y6PQ*czE3NzE1ODI0', '5712396056541', 'Normann-სანათი სუპერპოზური თეთრი', '0', '869.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('78', 'https://lw-cdn.com/images/1001B57ABB95/k_51a88e9767461e5a517126da5e9cf7c6;w_1600;h_1600;q_90/70013855.webp', '5712396079366', 'Normann-მაგიდის სანათი, თეთრი PORTA', '0', '249.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('79', 'https://lw-cdn.com/images/6F932695A85B/k_c2a813553e9210a34fff8a9253070ebb;w_1600;h_1600;q_90/70037502.webp', '5715396081131', 'Normann - Yo სანათი პატარა', '0', '249.00', 'GEL', '', '2026-02-20 15:20:50', '1'),
('80', 'https://media.fds.fi/product_image/2000/w203_Ilumina_white.jpg', '7330492009721', 'Wastberg-თეთრი მაგიდის სანათი', '0', '889.00', 'GEL', '', '2026-02-20 15:20:50', '1');

SET FOREIGN_KEY_CHECKS=1;
