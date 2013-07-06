ALTER TABLE  `anime_lists` CHANGE  `score`  `score` TINYINT( 2 ) UNSIGNED NOT NULL,
CHANGE  `status`  `status` TINYINT( 2 ) UNSIGNED NOT NULL,
CHANGE  `anime_id`  `anime_id` BIGINT( 20 ) UNSIGNED NOT NULL ,
CHANGE  `user_id`  `user_id` BIGINT( 20 ) UNSIGNED NOT NULL;