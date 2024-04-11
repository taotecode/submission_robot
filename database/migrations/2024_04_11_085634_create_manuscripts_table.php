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
        if (!Schema::hasTable('manuscripts')) {
            Schema::create('manuscripts', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->increments('id');
                $table->integer('bot_id')->comment('机器人ID');
                $table->unsignedInteger('channel_id')->comment('频道ID');
                $table->bigInteger('message_id')->nullable()->comment('发送频道消息ID');
                $table->string('type', 100)->comment('类型');
                $table->text('text')->comment('投稿内容');
                $table->json('posted_by')->comment('投稿人信息');
                $table->unsignedInteger('posted_by_id')->nullable()->comment('投稿人ID');
                $table->unsignedTinyInteger('is_anonymous')->default(0)->comment('是否匿名');
                $table->json('data')->comment('投稿整体信息');
                $table->json('appendix')->nullable()->comment('投稿附件');
                $table->json('approved')->nullable()->comment('通过审核员');
                $table->json('reject')->nullable()->comment('拒绝审核员');
                $table->json('one_approved')->nullable()->comment('快速通过审核员');
                $table->json('one_reject')->nullable()->comment('快速拒绝审核员');
                $table->unsignedTinyInteger('status')->default(0)->comment('状态');
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
        Schema::dropIfExists('manuscripts');
    }
};
