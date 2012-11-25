CREATE TABLE `anime_tags` (
  `tag_id` int(10) unsigned NOT NULL,
  `anime_id` int(10) unsigned NOT NULL,
  `created_user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  UNIQUE KEY `tag_id,anime_id` (`tag_id`,`anime_id`),
  UNIQUE KEY `anime_id,tag_id` (`anime_id`,`tag_id`),
  KEY `created_user_id,created_at` (`created_user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin