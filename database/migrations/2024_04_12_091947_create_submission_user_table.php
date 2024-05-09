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
        if (! Schema::hasTable('submission_user')) {
            Schema::create('submission_user', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->comment('submission_user');
                $table->increments('id');
                $table->integer('bot_id')->nullable()->comment('机器人ID');
                $table->unsignedTinyInteger('type')->nullable()->default(0)->comment('白名单/黑名单');
                $table->string('user_id', 100)->nullable()->comment('投稿人TG ID');
                $table->json('user_data')->nullable()->comment('用户信息');
                $table->string('name', 100)->nullable()->comment('名称');
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
        Schema::dropIfExists('submission_user');
    }
};
