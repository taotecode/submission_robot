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
        if (! Schema::hasTable('config')) {
            Schema::create('config', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->comment('配置表');
                $table->increments('id');
                $table->string('group', 100)->index('group')->comment('组');
                $table->string('name', 100)->index('name')->comment('名称');
                $table->text('value')->nullable()->comment('值');
                $table->string('description')->nullable()->comment('描述');
                $table->timestamps();

                $table->index(['group', 'name'], 'group_name');
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
        Schema::dropIfExists('config');
    }
};
