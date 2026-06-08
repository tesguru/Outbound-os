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
    Schema::create('recipients', function (Blueprint $table) {
        $table->id();
        $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
        $table->foreignId('gmail_account_id')->constrained()->cascadeOnDelete();
        $table->string('email');
        $table->string('first_name')->nullable();
        $table->string('company_name')->nullable();
        $table->enum('personalization_type', ['first_name', 'company_name']);
        $table->string('thread_id')->nullable();
        $table->string('message_id')->nullable();
        $table->enum('status', ['pending', 'draft_created', 'sent', 'replied', 'bounced'])->default('pending');
        $table->boolean('is_bounced')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
