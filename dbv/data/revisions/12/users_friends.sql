ALTER TABLE  `users_friends` CHANGE  `user_id_1`  `user_id_1` BIGINT( 20 ) UNSIGNED NOT NULL ,
CHANGE  `user_id_2`  `user_id_2` BIGINT( 20 ) UNSIGNED NOT NULL ,
CHANGE  `status`  `status` TINYINT( 1 ) NOT NULL ,
CHANGE  `message`  `message` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL