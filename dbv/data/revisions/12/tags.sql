ALTER TABLE  `tags` CHANGE  `created_user_id`  `created_user_id` BIGINT( 20 ) UNSIGNED NOT NULL ,
CHANGE  `approved_user_id`  `approved_user_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0';