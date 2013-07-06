ALTER TABLE  `anime_tags` CHANGE  `tag_id`  `tag_id` BIGINT( 20 ) UNSIGNED NOT NULL ,
CHANGE  `anime_id`  `anime_id` BIGINT( 20 ) UNSIGNED NOT NULL ,
CHANGE  `created_user_id`  `created_user_id` BIGINT( 20 ) UNSIGNED NOT NULL;