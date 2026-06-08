<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('personal_initial_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('personal_followup_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('company_initial_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('company_followup_template_id')->nullable()->constrained('templates')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'personal_initial_template_id',
                'personal_followup_template_id',
                'company_initial_template_id',
                'company_followup_template_id',
            ]);
        });
    }
};