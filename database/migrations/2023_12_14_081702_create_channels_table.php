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
        if (! Schema::hasTable('channels')) {
            Schema::create('channels', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable()->comment('公开频道用户名');
                $table->string('appellation')->nullable()->comment('频道名称');
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
        Schema::dropIfExists('channels');
    }
};
