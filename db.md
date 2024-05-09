php artisan migrate:generate --ignore="personal_access_tokens,password_resets" --with-has-table --use-db-collation

php artisan migrate


php artisan db:seed --class=ConfigSeeder
php artisan db:seed --class=KeyboardNameConfigSeeder
php artisan db:seed --class="Dcat\Admin\Models\AdminTablesSeeder"
php artisan db:seed --class=AdminMenuAddSeeder

php artisan migrate:generate --tables="bot_users" --with-has-table --use-db-collation

ALTER TABLE `submission_robot`.`bots`
ADD COLUMN `is_submission` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '是否开启投稿服务？' AFTER `is_message_text_preprocessing`,
ADD COLUMN `is_complaint` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '是否开启投诉服务？' AFTER `is_submission`,
ADD COLUMN `is_suggestion` tinyint(3) UNSIGNED NULL DEFAULT 1 COMMENT '是否开启建议服务？\n' AFTER `is_complaint`;
