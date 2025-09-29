CREATE TABLE IF NOT EXISTS `{pfx}product_tyre` (
  `id_t24` int(11) NOT NULL AUTO_INCREMENT,
  `matchcode` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `active` tinyint(1) NOT NULL,
  `date_add` datetime NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  PRIMARY KEY (`id_t24`),
  UNIQUE KEY `matchcode` (`matchcode`)
) ENGINE=InnoDB;