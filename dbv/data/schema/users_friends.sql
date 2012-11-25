CREATE TABLE `users_friends` (
  `user_id_1` int(10) unsigned NOT NULL,
  `user_id_2` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `time` datetime NOT NULL,
  `message` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`user_id_1`,`user_id_2`),
  UNIQUE KEY `user_id_1,user_id_2,status,time` (`user_id_1`,`user_id_2`,`status`,`time`),
  KEY `user_id_2,status,time` (`user_id_2`,`status`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin