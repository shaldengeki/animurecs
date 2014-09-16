ALTER TABLE mal_anime_lists
  DROP COLUMN started;

ALTER TABLE mal_anime_lists
  ADD COLUMN started DATE NULL;

ALTER TABLE mal_anime_lists
  DROP COLUMN finished;

ALTER TABLE mal_anime_lists
  ADD COLUMN finished DATE NULL;

