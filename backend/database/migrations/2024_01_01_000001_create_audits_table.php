<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->integer('overall_score')->nullable();
            $table->integer('pages_count')->default(1);
            $table->float('load_time')->nullable();
            $table->json('tech_stack')->nullable();
            $table->json('issues')->nullable();
            $table->integer('seo_score')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('url');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
