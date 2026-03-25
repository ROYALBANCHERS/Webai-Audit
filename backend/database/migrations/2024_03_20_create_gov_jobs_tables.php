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
        // Create gov_jobs table
        Schema::create('gov_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department')->nullable();
            $table->text('description');
            $table->string('qualification')->nullable();
            $table->integer('vacancy_count')->default(0);
            $table->date('last_date_to_apply')->nullable();
            $table->string('salary')->nullable();
            $table->string('location')->nullable();
            $table->string('source_url');
            $table->string('source_website');
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('crawled_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('category');
            $table->index('department');
            $table->index('last_date_to_apply');
            $table->index('source_url');
        });

        // Create job_subscriptions table
        Schema::create('job_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('keywords')->nullable();
            $table->json('departments')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('locations')->nullable();
            $table->json('notification_methods')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_active');
        });

        // Create job_matches table
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('gov_jobs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('match_score')->default(0);
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('job_id');
            $table->index('user_id');
            $table->index('is_notified');
            $table->index('match_score');

            $table->unique(['job_id', 'user_id']);
        });

        // Create crawl_sources table
        Schema::create('crawl_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->json('crawl_urls')->nullable();
            $table->json('selectors')->nullable();
            $table->integer('crawl_frequency')->default(3600);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('job_subscriptions');
        Schema::dropIfExists('crawl_sources');
        Schema::dropIfExists('gov_jobs');
    }
};
