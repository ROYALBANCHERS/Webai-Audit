<?php

namespace App\Jobs;

use App\Models\PublicCrawlSource;
use App\Services\TinyFishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapePublicSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sourceId;
    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(int $sourceId)
    {
        $this->sourceId = $sourceId;
        $this->onQueue('scraping');
    }

    /**
     * Execute the job.
     */
    public function handle(TinyFishService $tinyFishService): void
    {
        $source = PublicCrawlSource::find($this->sourceId);

        if (!$source) {
            Log::warning("Source {$this->sourceId} not found, skipping scrape job");
            return;
        }

        if (!$source->is_active || $source->status === 'paused') {
            Log::info("Source {$source->name} is inactive or paused, skipping");
            return;
        }

        Log::info("Starting scrape job for {$source->name}");

        try {
            // Mark as currently being crawled
            $source->markAsCrawling();

            // Perform scraping
            $result = $tinyFishService->scrapeWithFallback($source);

            if ($result['success']) {
                // Save jobs to database
                $jobsAdded = $tinyFishService->saveJobs($result['jobs'], $source);

                Log::info("Successfully scraped {$source->name}: " . count($result['jobs']) . " jobs found, {$jobsAdded} new");

                // Mark as success
                $source->markAsSuccess($jobsAdded);

                // Dispatch next job if auto-update is enabled
                if ($source->auto_update) {
                    $nextRun = now()->addSeconds($source->crawl_frequency);
                    Log::info("Scheduling next scrape for {$source->name} at {$nextRun}");

                    self::dispatch($this->sourceId)->delay($nextRun);
                }
            } else {
                throw new \Exception($result['error'] ?? 'Scraping failed without error message');
            }

        } catch (\Exception $e) {
            Log::error("Failed to scrape {$source->name}: {$e->getMessage()}");

            // Mark as failed
            $source->markAsFailed($e->getMessage());

            // Retry logic
            if ($this->attempts() < $this->tries) {
                $retryDelay = min(pow(2, $this->attempts()) * 300, 3600); // Exponential backoff, max 1 hour
                Log::info("Retrying {$source->name} in {$retryDelay} seconds (attempt {$this->attempts()}/{$this->tries})");

                $this->release($retryDelay);
            } else {
                Log::error("Max retries reached for {$source->name}, giving up");
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        $source = PublicCrawlSource::find($this->sourceId);

        if ($source) {
            $source->markAsFailed($exception->getMessage());
        }

        Log::error("Scrape job failed for source {$this->sourceId}: {$exception->getMessage()}");
    }
}
