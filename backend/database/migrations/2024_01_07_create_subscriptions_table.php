<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Starter, Pro, Enterprise, etc.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('interval')->default('monthly'); // monthly, yearly
            $table->integer('credits_per_month')->default(0);
            $table->integer('max_websites')->default(1);
            $table->integer('max_pages_per_audit')->default(10);
            $table->boolean('can_analyze_competitors')->default(false);
            $table->boolean('can_use_github_search')->default(false);
            $table->boolean('can_export_reports')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        // Insert new subscription plans
        DB::table('subscriptions')->insert([
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for trying out WebAI Auditor - analyze up to 10 pages',
                'price' => 0,
                'currency' => 'USD',
                'interval' => 'monthly',
                'credits_per_month' => 10,
                'max_websites' => 1,
                'max_pages_per_audit' => 10,
                'can_analyze_competitors' => false,
                'can_use_github_search' => false,
                'can_export_reports' => false,
                'is_active' => true,
                'sort_order' => 1,
                'features' => json_encode([
                    '10 pages analysis per month',
                    '1 website tracking',
                    'SEO analysis',
                    'Tech stack detection',
                    'Basic reports',
                ]),
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'For individuals - analyze more pages with detailed insights',
                'price' => 3,
                'currency' => 'USD',
                'interval' => 'monthly',
                'credits_per_month' => 15,
                'max_websites' => 3,
                'max_pages_per_audit' => 15,
                'can_analyze_competitors' => false,
                'can_use_github_search' => false,
                'can_export_reports' => false,
                'is_active' => true,
                'sort_order' => 2,
                'features' => json_encode([
                    '15 pages analysis per month',
                    '3 websites tracking',
                    'SEO analysis',
                    'Tech stack detection',
                    'Detailed reports',
                    'Priority support',
                ]),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For professionals - includes competitor analysis',
                'price' => 5,
                'currency' => 'USD',
                'interval' => 'monthly',
                'credits_per_month' => 30,
                'max_websites' => 10,
                'max_pages_per_audit' => 30,
                'can_analyze_competitors' => true,
                'can_use_github_search' => false,
                'can_export_reports' => true,
                'is_active' => true,
                'sort_order' => 3,
                'features' => json_encode([
                    '30 pages analysis per month',
                    '10 websites tracking',
                    'SEO analysis',
                    'Tech stack detection',
                    'Competitor analysis',
                    'Export reports (PDF, CSV)',
                    'Priority support',
                ]),
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'For agencies and power users - unlimited pages',
                'price' => 9,
                'currency' => 'USD',
                'interval' => 'monthly',
                'credits_per_month' => 999, // Unlimited effectively
                'max_websites' => -1, // Unlimited
                'max_pages_per_audit' => -1, // Unlimited
                'can_analyze_competitors' => true,
                'can_use_github_search' => true,
                'can_export_reports' => true,
                'is_active' => true,
                'sort_order' => 4,
                'features' => json_encode([
                    'Unlimited page analysis',
                    'Unlimited websites tracking',
                    'SEO analysis',
                    'Tech stack detection',
                    'Competitor analysis',
                    'GitHub repository search',
                    'Export reports (PDF, CSV, JSON)',
                    'White-label reports',
                    'API access',
                    'Dedicated support',
                ]),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
