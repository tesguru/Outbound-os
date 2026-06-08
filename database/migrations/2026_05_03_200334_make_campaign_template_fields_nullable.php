<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('template_type')->nullable()->change();
            $table->foreignId('initial_template_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('template_type')->nullable(false)->change();
            $table->foreignId('initial_template_id')->nullable(false)->change();
        });
    }
};