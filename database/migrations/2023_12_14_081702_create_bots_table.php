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
        if (! Schema::hasTable('bots')) {
            Schema::create('bots', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('name')->comment('机器人用户名');
                $table->string('appellation')->nullable()->comment('机器人称号');
                $table->string('token')->comment('机器人token');
                $table->text('tail_content')->nullable()->comment('尾部内容');
                $table->unsignedInteger('review_num')->default(1)->comment('通过或拒绝的审核数');
                $table->json('channel_ids')->nullable()->comment('发布频道ID');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedTinyInteger('webhook_status')->default(0)->comment('Web hook 状态');
                $table->unsignedTinyInteger('is_auto_keyword')->nullable()->default(0)->comment('是否开启自动关键词？');
                $table->text('keyword')->nullable()->comment('关键词列表');
                $table->text('lexicon')->nullable()->comment('词库');
                $table->unsignedInteger('submission_timeout')->default(0)->comment('审核超时时间（小时）');
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
        Schema::dropIfExists('bots');
    }
};
