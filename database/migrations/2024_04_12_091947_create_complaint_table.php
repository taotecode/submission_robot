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
        if (!Schema::hasTable('complaint')) {
            Schema::create('complaint', function (Blueprint $table) {
                $table->collation = 'utf8_general_ci';
                $table->charset = 'utf8';

                $table->increments('id');
                $table->unsignedInteger('bot_id')->comment('机器人ID');
                $table->integer('channel_id')->comment('频道ID');
                $table->bigInteger('message_id')->comment('投诉消息ID');
                $table->string('type', 100)->comment('投诉消息类型');
                $table->text('text')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->comment('消息文本内容');
                $table->json('posted_by')->comment('投诉人数据');
                $table->unsignedInteger('posted_by_id')->comment('投诉人ID');
                $table->json('data')->comment('投诉整体');
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
        Schema::dropIfExists('complaint');
    }
};
