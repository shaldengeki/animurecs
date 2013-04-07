ALTER TABLE  `anime_lists` DROP INDEX  `user_id,anime_id,time` ,
ADD INDEX  `user_id,anime_id,time` (  `user_id` ,  `anime_id` ,  `time` )