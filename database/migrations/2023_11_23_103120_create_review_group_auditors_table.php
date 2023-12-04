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
        Schema::create('review_group_auditors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('review_group_id')->comment('审核群ID');
            $table->unsignedInteger('auditor_id')->comment('审核员ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('review_group_auditors');
    }
};
