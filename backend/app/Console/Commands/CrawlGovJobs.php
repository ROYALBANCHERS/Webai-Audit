<?php

namespace App\Console\Commands;

use App\Services\GovJobService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrawlGovJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'govjobs:crawl {--force : Force crawl even if not due}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl government job websites for new postings';

    protected GovJobService $govJobService;

    /**
     * Create a new command instance.
     */
    public function __construct(GovJobService $govJobService)
    {
        parent::__construct();
        $this->govJobService = $govJobService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting government jobs crawl...');
        $this->newLine();

        try {
            $result = $this->govJobService->crawlAllSources();

            $this->info("✓ Sources crawled: {$result['sources_crawled']}");
            $this->info("✓ New jobs added: {$result['total_jobs_added']}");

            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->warn("  - {$error['source']}: {$error['error']}");
                }
            }

            $this->newLine();
            $this->info('Crawl completed successfully!');

            Log::info('Government jobs crawl completed', $result);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Crawl failed: ' . $e->getMessage());
            Log::error('Government jobs crawl failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
