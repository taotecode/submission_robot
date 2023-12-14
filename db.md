php artisan migrate:generate --ignore="personal_access_tokens,password_resets" --with-has-table

php artisan migrate


php artisan db:seed --class=ConfigSeeder
php artisan db:seed --class="Dcat\Admin\Models\AdminTablesSeeder"
php artisan db:seed --class=AdminMenuAddSeeder
