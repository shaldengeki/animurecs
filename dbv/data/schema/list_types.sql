CREATE TABLE `list_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`(5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin