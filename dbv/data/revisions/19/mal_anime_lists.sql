CREATE TABLE `mal_anime_lists` (
  `user_id` int(10) unsigned NOT NULL,
  `anime_id` int(10) unsigned NOT NULL,
  `started` datetime NOT NULL,
  `time` datetime NOT NULL,
  `finished` datetime NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `score` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `episode` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`, `anime_id`),
  KEY `score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;