CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` text COLLATE utf8_bin NOT NULL,
  `name` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  `email` text COLLATE utf8_bin NOT NULL,
  `password_hash` varchar(60) COLLATE utf8_bin NOT NULL,
  `about` text COLLATE utf8_bin NOT NULL,
  `usermask` int(1) NOT NULL,
  `last_ip` varchar(15) COLLATE utf8_bin NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `last_active` datetime NOT NULL,
  `avatar_path` varchar(50) COLLATE utf8_bin NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`(30)),
  KEY `userlevel` (`usermask`),
  KEY `created_at` (`created_at`),
  KEY `last_active` (`last_active`),
  FULLTEXT KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin