<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('url');
            $table->string('name')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('check_interval')->default(24); // hours
            $table->timestamp('last_checked_at')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_sites');
    }
};
