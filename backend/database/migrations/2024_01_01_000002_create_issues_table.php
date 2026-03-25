<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->string('type'); // seo, performance, accessibility, security
            $table->string('severity'); // critical, high, medium, low, info
            $table->string('title');
            $table->text('description');
            $table->string('location')->nullable();
            $table->text('recommendation')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'severity']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
