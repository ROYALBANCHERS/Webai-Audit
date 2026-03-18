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
        Schema::create('website_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained('monitored_websites')->onDelete('cascade');
            $table->string('change_type'); // new_page, page_changed, new_blog, blog_updated
            $table->string('url');
            $table->text('description')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->index(['website_id', 'detected_at']);
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_changes');
    }
};
