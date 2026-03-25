<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tech_stacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->string('category'); // framework, library, analytics, cdn, cms, etc.
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('confidence')->default('high'); // high, medium, low
            $table->json('detection_data')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tech_stacks');
    }
};
