DROP INDEX `user_id,anime_id,time` ON anime_lists;
CREATE UNIQUE INDEX `user_id,anime_id,time` ON anime_lists (user_id,anime_id,time);