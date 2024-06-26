<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('admin_role_menu')) {
            Schema::create('admin_role_menu', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->bigInteger('role_id');
                $table->bigInteger('menu_id');
                $table->timestamps();

                $table->unique(['role_id', 'menu_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_role_menu');
    }
};
