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
        if (!Schema::hasTable('keyboard_name_config')) {
            Schema::create('keyboard_name_config', function (Blueprint $table) {
                $table->collation = 'utf8_general_ci';
                $table->charset = 'utf8';

                $table->comment('一些可以改键盘名称的数据');
                $table->increments('id');
                $table->string('group', 100)->comment('组');
                $table->string('name')->comment('名称');
                $table->text('value')->nullable()->comment('值');
                $table->string('description')->nullable()->comment('描述');
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
        Schema::dropIfExists('keyboard_name_config');
    }
};
