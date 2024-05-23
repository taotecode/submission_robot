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
        if (! Schema::hasTable('review_groups')) {
            Schema::create('review_groups', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->increments('id');
                $table->unsignedInteger('bot_id')->comment('Bot ID');
                $table->string('group_id', 50)->comment('群组ID');
                $table->string('name')->nullable()->comment('公开群组用户名');
                $table->string('appellation')->nullable()->comment('群组名称');
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
        Schema::dropIfExists('review_groups');
    }
};
