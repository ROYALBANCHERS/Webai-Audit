<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebsiteMonitorController;

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

// Health check
Route::get('/health', [AuditController::class, 'health']);

// Statistics
Route::get('/stats', [AuditController::class, 'stats']);

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
