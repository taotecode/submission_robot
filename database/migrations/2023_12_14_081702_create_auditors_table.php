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
        if (!Schema::hasTable('auditors')) {
            Schema::create('auditors', function (Blueprint $table) {
                $table->comment('审核人员表');
                $table->increments('id');
                $table->string('userId', 50)->comment('审核员TG ID');
                $table->string('name')->nullable()->comment('名称');
                $table->json('role')->nullable()->comment('权限');
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
        Schema::dropIfExists('auditors');
    }
};
