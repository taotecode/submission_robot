php artisan migrate:generate --ignore="personal_access_tokens,password_resets" --with-has-table --use-db-collation

php artisan migrate


php artisan db:seed --class=ConfigSeeder
php artisan db:seed --class=KeyboardNameConfigSeeder
php artisan db:seed --class="Dcat\Admin\Models\AdminTablesSeeder"
php artisan db:seed --class=AdminMenuAddSeeder

php artisan migrate:generate --tables="bot_users" --with-has-table --use-db-collation


