ALTER TABLE  `tag_types` ADD  `created_at` DATETIME NOT NULL AFTER  `created_user_id` ,
ADD  `updated_at` DATETIME NOT NULL AFTER  `created_at`