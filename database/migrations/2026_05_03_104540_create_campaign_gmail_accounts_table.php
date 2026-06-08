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
    Schema::create('campaign_gmail_accounts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
        $table->foreignId('gmail_account_id')->constrained()->cascadeOnDelete();
        $table->integer('recipient_limit');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_gmail_accounts');
    }
};
