CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type,type_id,created_at` (`type`,`type_id`,`created_at`),
  KEY `user_id,type,created_at` (`user_id`,`type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8