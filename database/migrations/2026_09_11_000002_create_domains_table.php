<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 4)->default('USD');
            $table->string('registrar')->nullable();
            $table->date('registered_at')->nullable();
            $table->date('renews_at')->nullable();
            $table->enum('status', ['available', 'outbounding', 'sold'])->default('outbounding');
            $table->decimal('sold_price', 12, 2)->nullable();
            $table->date('sold_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};