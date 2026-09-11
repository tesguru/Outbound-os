<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('domain')->nullable();
            $table->enum('status', ['saved', 'contacted', 'replied', 'sold', 'lost'])->default('saved');
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};