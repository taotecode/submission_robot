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
        if (Schema::hasTable('bots')) {
            Schema::table('bots', function (Blueprint $table) {
                $table->unsignedTinyInteger('is_link_preview')->nullable()->default(1)->comment('是否开启消息预览？')->after('is_message_text_preprocessing');
                $table->unsignedTinyInteger('is_disable_notification')->nullable()->default(1)->comment('是否开启静默方式发送消息？')->after('is_link_preview');
                $table->unsignedTinyInteger('is_protect_content')->nullable()->default(1)->comment('是否开启不被转发和保存？')->after('is_disable_notification');
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
