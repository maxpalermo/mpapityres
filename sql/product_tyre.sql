CREATE TABLE IF NOT EXISTS `{pfx}product_tyre` (
  `id_t24` int(11) NOT NULL AUTO_INCREMENT,
  `type` char(3) NOT NULL COMMENT 'API o CSV',
  `matchcode` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `active` tinyint(1) NOT NULL,
  `date_add` datetime NOT NULL,
  `date_upd` datetime DEFAULT NULL,
  PRIMARY KEY (`id_t24`,`type`) USING BTREE
) ENGINE=InnoDB