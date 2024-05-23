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
        if (! Schema::hasTable('bot_users')) {
            Schema::create('bot_users', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->bigIncrements('id');
                $table->unsignedInteger('bot_id')->nullable()->comment('所属机器人');
                $table->unsignedTinyInteger('type')->default(0)->comment('白名单/黑名单');
                $table->string('user_id', 100)->nullable()->comment('用户id');
                $table->json('user_data')->nullable()->comment('用户信息');
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
        Schema::dropIfExists('bot_users');
    }
};
