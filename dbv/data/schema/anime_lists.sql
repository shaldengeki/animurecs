CREATE TABLE `anime_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `anime_id` int(10) unsigned NOT NULL,
  `time` datetime NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `score` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `episode` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id,anime_id,time` (`user_id`,`anime_id`,`time`),
  KEY `user_id,status,score` (`user_id`,`status`,`score`),
  KEY `user_id,time` (`user_id`,`time`),
  KEY `user_id,anime_id,score` (`user_id`,`anime_id`,`score`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin