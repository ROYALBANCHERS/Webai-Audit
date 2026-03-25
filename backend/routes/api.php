<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebsiteMonitorController;
use App\Http\Controllers\GovJobController;
use App\Http\Controllers\PublicSourceController;
use App\Http\Middleware\AdminSecretMiddleware;
use App\Http\Middleware\RateLimiterMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check (no rate limiting)
Route::get('/health', [AuditController::class, 'health'])->withoutMiddleware(['throttle:api']);

// Public Statistics (cached, rate limited)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/stats', [AuditController::class, 'stats']);
});

// Subscription endpoints
Route::prefix('subscription')->group(function () {
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/current', [SubscriptionController::class, 'current']);
    Route::get('/compare', [SubscriptionController::class, 'compare']);
    Route::get('/usage', [SubscriptionController::class, 'usage']);
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
});

// Website audit endpoints
Route::prefix('audit')->group(function () {
    Route::post('/', [AuditController::class, 'audit'])->name('audit.run');
    Route::get('/', [AuditController::class, 'index'])->name('audit.list');
    Route::get('/{id}', [AuditController::class, 'show'])->name('audit.show');
    Route::delete('/{id}', [AuditController::class, 'destroy'])->name('audit.delete');
});

// Analysis endpoints
Route::prefix('analyze')->group(function () {
    Route::post('/tech-stack', [AuditController::class, 'analyzeTechStack'])->name('analyze.tech-stack');
    Route::post('/seo', [AuditController::class, 'analyzeSeo'])->name('analyze.seo');
});

// Crawling
Route::post('/crawl', [AuditController::class, 'crawl'])->name('crawl');

// Competitor analysis
Route::post('/competitors', [AuditController::class, 'findCompetitors'])->name('competitors.find');

// GitHub integration
Route::prefix('github')->group(function () {
    Route::post('/search', [AuditController::class, 'searchGitHub'])->name('github.search');
    Route::get('/trending', [AuditController::class, 'trending'])->name('github.trending');
});

// Government Jobs endpoints (Public - No Authentication)
Route::prefix('gov-jobs')->group(function () {
    Route::get('/', [GovJobController::class, 'index'])->name('gov-jobs.list');
    Route::get('/{id}', [GovJobController::class, 'show'])->name('gov-jobs.show');
    Route::get('/categories/list', [GovJobController::class, 'getCategories'])->name('gov-jobs.categories');
    Route::get('/departments/list', [GovJobController::class, 'getDepartments'])->name('gov-jobs.departments');
    Route::get('/statistics', [GovJobController::class, 'getStatistics'])->name('gov-jobs.statistics');
    Route::get('/expiring-soon', [GovJobController::class, 'getExpiringSoon'])->name('gov-jobs.expiring-soon');
});

// Government Jobs endpoints (Protected - Require Authentication)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('gov-jobs')->group(function () {
    Route::get('/my-jobs', [GovJobController::class, 'myJobs'])->name('gov-jobs.my-jobs');
    Route::post('/subscribe', [GovJobController::class, 'subscribe'])->name('gov-jobs.subscribe');
    Route::get('/subscription', [GovJobController::class, 'getSubscription'])->name('gov-jobs.subscription');
    Route::post('/subscription/toggle', [GovJobController::class, 'toggleSubscription'])->name('gov-jobs.subscription.toggle');
    Route::post('/{jobId}/read', [GovJobController::class, 'markAsRead'])->name('gov-jobs.read');
    Route::post('/save', [GovJobController::class, 'saveJob'])->name('gov-jobs.save');
    Route::delete('/unsave', [GovJobController::class, 'unsaveJob'])->name('gov-jobs.unsave');
    Route::get('/saved', [GovJobController::class, 'getSavedJobs'])->name('gov-jobs.saved');
});

// Admin-only routes - require both auth and admin secret
Route::middleware(['auth:sanctum', 'throttle:10,1'])->prefix('admin')->group(function () {
    Route::post('/gov-jobs/crawl', [GovJobController::class, 'triggerCrawl'])->name('admin.gov-jobs.crawl');
});

// Legacy/compatibility endpoints
Route::post('/api/audit', [AuditController::class, 'audit']);
Route::get('/api/audits', [AuditController::class, 'index']);
Route::get('/api/audits/{id}', [AuditController::class, 'show']);
Route::post('/api/analyze', [AuditController::class, 'audit']);
Route::post('/api/crawl', [AuditController::class, 'crawl']);
Route::get('/api/health', [AuditController::class, 'health']);
Route::post('/api/competitors', [AuditController::class, 'findCompetitors']);
Route::post('/api/github/search', [AuditController::class, 'searchGitHub']);
Route::get('/api/stats', [AuditController::class, 'stats']);

// Website Monitoring endpoints
Route::prefix('monitor')->group(function () {
    Route::get('/', [WebsiteMonitorController::class, 'index'])->name('monitor.index');
    Route::post('/', [WebsiteMonitorController::class, 'store'])->name('monitor.store');
    Route::get('/stats', [WebsiteMonitorController::class, 'stats'])->name('monitor.stats');
    Route::get('/recent', [WebsiteMonitorController::class, 'recentChanges'])->name('monitor.recent');
    Route::get('/stream', [WebsiteMonitorController::class, 'stream'])->name('monitor.stream');
    Route::post('/check-all', [WebsiteMonitorController::class, 'checkAll'])->name('monitor.check-all');
    Route::get('/{id}', [WebsiteMonitorController::class, 'show'])->name('monitor.show');
    Route::delete('/{id}', [WebsiteMonitorController::class, 'destroy'])->name('monitor.destroy');
    Route::post('/{id}/check', [WebsiteMonitorController::class, 'checkChanges'])->name('monitor.check');
    Route::post('/{id}/toggle', [WebsiteMonitorController::class, 'toggle'])->name('monitor.toggle');
});

// Public Crawl Sources (TinyFish Integration) - Rate Limited
Route::middleware(['throttle:60,1'])->prefix('sources')->group(function () {
    // Public endpoints - anyone can access (rate limited)
    Route::get('/', [PublicSourceController::class, 'index'])->name('sources.list');
    Route::get('/statistics', [PublicSourceController::class, 'statistics'])->name('sources.statistics');
    Route::get('/{id}', [PublicSourceController::class, 'show'])->name('sources.show');
    Route::get('/{id}/jobs', [PublicSourceController::class, 'jobs'])->name('sources.jobs');

    // Test endpoint - requires admin secret
    Route::middleware([AdminSecretMiddleware::class])->post('/{id}/test', [PublicSourceController::class, 'test'])->name('sources.test');

    // Admin endpoints - require X-Admin-Secret header
    Route::middleware([AdminSecretMiddleware::class, 'throttle:20,1'])->group(function () {
        Route::post('/', [PublicSourceController::class, 'store'])->name('sources.create');
        Route::put('/{id}', [PublicSourceController::class, 'update'])->name('sources.update');
        Route::delete('/{id}', [PublicSourceController::class, 'destroy'])->name('sources.delete');
        Route::post('/{id}/toggle', [PublicSourceController::class, 'toggle'])->name('sources.toggle');
        Route::get('/{id}/logs', [PublicSourceController::class, 'logs'])->name('sources.logs');
        Route::post('/crawl-all', [PublicSourceController::class, 'crawlAll'])->name('sources.crawl-all');
    });
});

// Real-time job streaming (SSE)
Route::get('/jobs/stream', [GovJobController::class, 'stream'])->name('jobs.stream');
