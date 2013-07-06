ALTER TABLE  `anime` CHANGE  `id`  `id` BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE  `approved_user_id`  `approved_user_id` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT  '0'