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
        if (! Schema::hasTable('bot_messages')) {
            Schema::create('bot_messages', function (Blueprint $table) {
                $table->collation = 'utf8_general_ci';
                $table->charset = 'utf8';

                $table->bigIncrements('id');
                $table->unsignedInteger('bot_id')->index('bot_id')->comment('机器人ID');
                $table->string('user_id', 100)->index('userID')->comment('用户ID');
                $table->json('user_data')->comment('用户信息');
                $table->json('data')->comment('消息整体');
                $table->timestamps();

                $table->index(['bot_id', 'user_id'], 'bot_id_user_id');
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
        Schema::dropIfExists('bot_messages');
    }
};
