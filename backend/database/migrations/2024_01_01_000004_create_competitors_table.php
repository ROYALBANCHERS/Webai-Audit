<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->string('competitor_url');
            $table->string('name')->nullable();
            $table->float('similarity_score');
            $table->json('features')->nullable();
            $table->json('tech_stack')->nullable();
            $table->integer('traffic_rank')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'similarity_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitors');
    }
};
