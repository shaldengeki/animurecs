CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `tag_type_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `created_user_id` int(10) unsigned NOT NULL,
  `approved_on` datetime DEFAULT NULL,
  `approved_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tag_type_id,name` (`tag_type_id`,`name`(30)),
  KEY `created_user_id,name` (`created_user_id`,`name`(30)),
  FULLTEXT KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8