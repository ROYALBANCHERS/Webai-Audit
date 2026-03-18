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
        Schema::create('website_blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('monitored_websites')->onDelete('cascade');
            $table->string('url');
            $table->string('title')->nullable();
            $table->timestamp('publish_date')->nullable();
            $table->string('content_hash', 64);
            $table->timestamp('discovered_at');
            $table->timestamps();

            $table->unique(['website_id', 'url']);
            $table->index(['website_id', 'discovered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_blogs');
    }
};
