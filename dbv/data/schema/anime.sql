CREATE TABLE `anime` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `episode_count` int(10) unsigned NOT NULL,
  `episode_length` int(10) unsigned NOT NULL,
  `started_on` datetime DEFAULT NULL,
  `ended_on` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `image_path` varchar(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `approved_on` datetime DEFAULT NULL,
  `approved_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `started_on,title` (`started_on`,`title`(10)),
  KEY `approved_on,title` (`approved_on`,`title`(50)),
  FULLTEXT KEY `title,description` (`title`,`description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8