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
        if (!Schema::hasTable('admin_permission_menu')) {
            Schema::create('admin_permission_menu', function (Blueprint $table) {
                $table->bigInteger('permission_id');
                $table->bigInteger('menu_id');
                $table->timestamps();

                $table->unique(['permission_id', 'menu_id']);
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
        Schema::dropIfExists('admin_permission_menu');
    }
};
