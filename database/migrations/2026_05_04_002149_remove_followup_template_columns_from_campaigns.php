<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['followup_template_id']);
            $table->dropForeign(['personal_followup_template_id']);
            $table->dropForeign(['company_followup_template_id']);
            $table->dropColumn([
                'followup_template_id',
                'personal_followup_template_id',
                'company_followup_template_id',
            ]);
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('followup_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('personal_followup_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('company_followup_template_id')->nullable()->constrained('templates')->nullOnDelete();
        });
    }
};