<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaign_followup_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['personal', 'company']);
            $table->integer('sequence');
            $table->timestamps();

            // Unique — one template per sequence per type per campaign
            $table->unique(['campaign_id', 'type', 'sequence']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_followup_sequences');
    }
};