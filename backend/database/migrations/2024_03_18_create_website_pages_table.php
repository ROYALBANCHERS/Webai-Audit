<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('monitored_websites')->onDelete('cascade');
            $table->string('url');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('content_hash', 64);
            $table->integer('status_code')->default(200);
            $table->timestamp('first_seen');
            $table->timestamp('last_seen');
            $table->timestamps();

            $table->unique(['website_id', 'url']);
            $table->index(['website_id', 'last_seen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
