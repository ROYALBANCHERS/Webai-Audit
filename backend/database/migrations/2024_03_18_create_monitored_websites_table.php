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
        Schema::create('monitored_websites', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('name');
            $table->integer('check_interval')->default(3600);
            $table->integer('crawl_depth')->default(3);
            $table->boolean('monitor_blogs')->default(true);
            $table->boolean('monitor_pages')->default(true);
            $table->boolean('monitor_changes')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked')->nullable();
            $table->integer('page_count')->default(0);
            $table->integer('blog_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitored_websites');
    }
};
