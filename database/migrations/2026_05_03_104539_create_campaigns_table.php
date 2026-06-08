<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('campaigns', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('gmail_label_id')->nullable();
        $table->string('gmail_label_name')->nullable();
        $table->enum('template_type', ['personal', 'company']);
        $table->foreignId('initial_template_id')->constrained('templates');
        $table->foreignId('followup_template_id')->nullable()->constrained('templates');
        $table->enum('status', ['active', 'paused', 'completed'])->default('active');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
