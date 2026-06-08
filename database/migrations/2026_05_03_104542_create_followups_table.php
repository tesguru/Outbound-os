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
    Schema::create('followups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
        $table->foreignId('recipient_id')->constrained()->cascadeOnDelete();
        $table->foreignId('template_id')->constrained();
        $table->string('draft_id')->nullable();
        $table->string('thread_id')->nullable();
        $table->decimal('price', 10, 2)->nullable();
        $table->integer('sequence')->default(1);
        $table->enum('status', ['pending', 'draft_created', 'sent'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
