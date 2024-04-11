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
        if (!Schema::hasTable('review_group_auditors')) {
            Schema::create('review_group_auditors', function (Blueprint $table) {
                $table->collation = 'utf8mb4_unicode_ci';
                $table->charset = 'utf8mb4';

                $table->increments('id');
                $table->unsignedInteger('review_group_id')->comment('审核群ID');
                $table->unsignedInteger('auditor_id')->comment('审核员ID');
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
        Schema::dropIfExists('review_group_auditors');
    }
};
