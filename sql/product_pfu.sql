CREATE TABLE IF NOT EXISTS `{pfx}product_pfu` (
  `id_product` int(11) NOT NULL,
  `id_pfu` int(11) NOT NULL,
  `price` decimal(20,6) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_product`,`id_pfu`)
) ENGINE=InnoDB;
