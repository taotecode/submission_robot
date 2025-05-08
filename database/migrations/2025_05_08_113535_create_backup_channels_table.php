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
        if (! Schema::hasTable('backup_channels')) {
            Schema::create('backup_channels', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->increments('id');
                $table->unsignedInteger('channel_id')->comment('主频道ID');
                $table->string('backup_name')->nullable()->comment('备份频道用户名');
                $table->string('backup_appellation')->nullable()->comment('备份频道名称');
                $table->string('chat_id')->nullable()->comment('私有频道聊天ID');
                $table->unsignedTinyInteger('is_public')->default(1)->comment('是否公开频道 1:公开 0:私有');
                $table->unsignedInteger('sort_order')->default(0)->comment('排序');
                $table->timestamps();

                // 添加外键约束
                $table->foreign('channel_id')
                    ->references('id')
                    ->on('channels')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('backup_channels');
    }
};
