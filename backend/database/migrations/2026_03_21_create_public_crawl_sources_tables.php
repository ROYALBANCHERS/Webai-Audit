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
        // Create public_crawl_sources table
        Schema::create('public_crawl_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('url', 2048);
            $table->enum('category', ['ssc', 'upsc', 'ibps', 'railway', 'defence', 'teaching', 'state', 'general']);
            $table->enum('scraping_strategy', ['auto', 'agentql', 'guzzle', 'rss'])->default('auto');
            $table->text('agentql_query')->nullable();
            $table->json('selectors')->nullable();
            $table->boolean('auto_update')->default(true);
            $table->integer('crawl_frequency')->default(3600);
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['pending', 'active', 'failed', 'paused'])->default('pending');
            $table->integer('total_jobs_found')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamp('next_crawl_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'status']);
            $table->index('next_crawl_at');
            $table->index('category');
        });

        // Create scraping_logs table
        Schema::create('scraping_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->string('url', 2048);
            $table->enum('method_used', ['agentql', 'guzzle', 'rss', 'puppeteer']);
            $table->integer('jobs_found')->default(0);
            $table->integer('duration_ms')->nullable();
            $table->enum('status', ['success', 'failed', 'partial']);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('source_id')->references('id')->on('public_crawl_sources')->onDelete('cascade');
            $table->index(['source_id', 'status']);
            $table->index('created_at');
        });

        // Create job_notifications table
        Schema::create('job_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->enum('type', ['new_job', 'expiring_soon', 'match_found'])->default('new_job');
            $table->json('channels')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('gov_jobs')->onDelete('cascade');
            $table->index(['status', 'priority']);
            $table->index('created_at');
        });

        // Add columns to gov_jobs table for user sources
        Schema::table('gov_jobs', function (Blueprint $table) {
            $table->enum('source_type', ['system', 'public'])->default('system')->after('is_active');
            $table->unsignedBigInteger('public_source_id')->nullable()->after('source_type');
            $table->string('scraping_method', 50)->nullable()->after('public_source_id');
            $table->decimal('confidence_score', 3, 2)->nullable()->after('scraping_method');

            $table->foreign('public_source_id')->references('id')->on('public_crawl_sources')->onDelete('set null');
            $table->index(['source_type', 'public_source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gov_jobs', function (Blueprint $table) {
            $table->dropForeign(['public_source_id']);
            $table->dropIndex(['source_type', 'public_source_id']);
            $table->dropColumn(['source_type', 'public_source_id', 'scraping_method', 'confidence_score']);
        });

        Schema::dropIfExists('job_notifications');
        Schema::dropIfExists('scraping_logs');
        Schema::dropIfExists('public_crawl_sources');
    }
};
