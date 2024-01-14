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
        if (! Schema::hasTable('admin_menu')) {
            Schema::create('admin_menu', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('parent_id')->default(0);
                $table->integer('order')->default(0);
                $table->string('title', 50);
                $table->string('icon', 50)->nullable();
                $table->string('uri', 50)->nullable();
                $table->string('extension', 50)->default('');
                $table->tinyInteger('show')->default(1);
                $table->timestamps();
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
        Schema::dropIfExists('admin_menu');
    }
};
