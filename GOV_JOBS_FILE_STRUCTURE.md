# Government Jobs Feature - File Structure

## What Was Created

### Backend Files (Laravel)

```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CrawlGovJobs.php          # Artisan command for manual crawling
│   ├── Http/
│   │   └── Controllers/
│   │       └── GovJobController.php      # API endpoints controller
│   ├── Models/
│   │   ├── GovJob.php                    # Government Job model
│   │   ├── JobSubscription.php           # User subscription model
│   │   ├── JobMatch.php                  # Job-user matching model
│   │   └── CrawlSource.php               # Crawl source configuration model
│   └── Services/
│       └── GovJobService.php             # Core business logic service
├── routes/
│   └── api.php                           # API route definitions (updated)
└── database/
    └── migrations/                       # Migration files (to be created)
```

### Frontend Files (HTML)

```
frontend/
├── gov-jobs.html                         # Main jobs listing page
└── job-subscription.html                 # User subscription management page
```

### Database Files

```
create_gov_jobs_tables.sql                # SQL schema for all tables
```

### Documentation

```
GOV_JOBS_SETUP.md                         # Complete setup guide
GOV_JOBS_FILE_STRUCTURE.md                # This file
```

## Database Tables

### gov_jobs
Stores all crawled government job postings

```php
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
    $table->timestamp('crawled_at');
    $table->timestamps();
});
```

### job_subscriptions
Stores user alert preferences

```php
Schema::create('job_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id');
    $table->json('keywords');
    $table->json('departments');
    $table->json('qualifications');
    $table->json('locations');
    $table->json('notification_methods');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_notified_at')->nullable();
    $table->timestamps();
});
```

### job_matches
Tracks which jobs match which users

```php
Schema::create('job_matches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('job_id');
    $table->foreignId('user_id');
    $table->integer('match_score')->default(0);
    $table->boolean('is_notified')->default(false);
    $table->timestamp('notified_at')->nullable();
    $table->timestamps();
});
```

### crawl_sources
Configuration for job sources

```php
Schema::create('crawl_sources', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('base_url');
    $table->json('crawl_urls');
    $table->json('selectors');
    $table->integer('crawl_frequency')->default(3600);
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_crawled_at')->nullable();
    $table->timestamps();
});
```

## API Endpoints

### Public Endpoints (No Authentication)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/gov-jobs` | GET | Get all jobs with optional filters |
| `/api/gov-jobs/{id}` | GET | Get specific job details |
| `/api/gov-jobs/categories/list` | GET | Get all categories with job counts |
| `/api/gov-jobs/departments/list` | GET | Get all departments with job counts |
| `/api/gov-jobs/statistics` | GET | Get overall statistics |
| `/api/gov-jobs/expiring-soon` | GET | Get jobs expiring in next 7 days |

### Protected Endpoints (Require Authentication)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/gov-jobs/my-jobs` | GET | Get personalized job matches |
| `/api/gov-jobs/subscribe` | POST | Create/update subscription |
| `/api/gov-jobs/subscription` | GET | Get user's subscription |
| `/api/gov-jobs/subscription/toggle` | POST | Pause/resume alerts |
| `/api/gov-jobs/{jobId}/read` | POST | Mark job as read |
| `/api/gov-jobs/save` | POST | Save/bookmark job |

### Admin Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/admin/gov-jobs/crawl` | POST | Trigger manual crawl |
| `/api/admin/stats` | GET | Get system statistics |

## Key Service Methods

### GovJobService

```php
// Crawling
crawlAllSources(): array                      // Crawl all active sources
crawlSource(CrawlSource $source): int         // Crawl specific source

// Job Matching
matchJobsWithSubscriptions(): int             // Match jobs with user preferences
isJobMatch(GovJob $job, array $prefs): bool   // Check if job matches
calculateMatchScore(GovJob $job, array $prefs): int // Calculate relevance score

// User Jobs
getJobsForUser(int $userId, int $page, int $perPage): array // Get personalized jobs

// Search & Filter
searchJobs(array $filters): array             // Search with filters
getStatistics(): array                        // Get statistics

// Notifications
sendNotificationsForNewMatches(): void        // Send email/push notifications
```

## Scheduled Tasks

### Hourly (6 AM - 11 PM)
```bash
php artisan govjobs:crawl
```
Crawls all configured government job sources

### Daily at Midnight
Deactivates jobs older than 90 days

### Daily at 8 AM
Sends daily job digest emails (TODO)

### Weekly
Cleans up old job matches (older than 30 days)

## Frontend Features

### gov-jobs.html

- **Search bar** for keyword search
- **Category filters** (SSC, Banking, Railway, etc.)
- **Department filter** dropdown
- **Last date filter** (7 days, 30 days)
- **Sorting options** (Latest, Expiring Soon, By Category)
- **Real-time statistics** (Total jobs, Today, Expiring Soon)
- **Job cards** with key information
- **Pagination** for browsing
- **Direct apply links** to source websites

### job-subscription.html

- **Keyword tags** input (add/remove keywords)
- **Category checkboxes** (multi-select)
- **Qualification filters** (10th, 12th, Graduate, PG)
- **Department tags** input (SSC, UPSC, etc.)
- **Notification methods** (Email, Push, SMS)
- **Toggle subscription** (activate/pause)
- **How it works** guide

## Integration Points

### Existing WebAI Auditor Features

1. **User Dashboard** - Access government jobs from dashboard
2. **Authentication** - Use existing user system
3. **Database** - Shared MySQL database
4. **API** - Same Laravel backend

### New Features Added

1. **Crawler Service** - Specialized for government websites
2. **Job Matching Algorithm** - Relevance scoring
3. **Notification System** - Email/Push/SMS alerts
4. **Scheduled Tasks** - Automatic hourly crawling

## Configuration Files

### .env additions

```env
# Government Jobs Configuration
GOV_JOBS_CRON_ENABLED=true
GOV_JOBS_CRON_SCHEDULE="0 * * * *"  # Hourly
GOV_JOBS_RETENTION_DAYS=90
GOV_JOBS_NOTIFICATION_EMAIL=true
GOV_JOBS_NOTIFICATION_SMS=false
```

### config/govjobs.php (optional)

```php
return [
    'sources' => [
        'employment_news' => [
            'url' => 'https://employmentnews.gov.in',
            'enabled' => true,
        ],
        // Add more sources
    ],

    'categories' => [
        'SSC', 'Banking', 'Railway', 'UPSC',
        'Teaching', 'Defense', 'Police', 'Engineering'
    ],

    'notifications' => [
        'email' => true,
        'push' => false,
        'sms' => false,
    ],

    'retention' => [
        'days' => 90,
        'matches' => 30,
    ],
];
```

## Testing Checklist

### Backend Testing

- [ ] Migration runs successfully
- [ ] Crawl command executes without errors
- [ ] Jobs are being saved to database
- [ ] API endpoints return correct data
- [ ] Filtering works correctly
- [ ] Pagination works
- [ ] User subscription is saved
- [ ] Job matching works

### Frontend Testing

- [ ] Page loads without errors
- [ ] Search functionality works
- [ ] Filters apply correctly
- [ ] Categories display with counts
- [ ] Job cards render properly
- [ ] Apply links work
- [ ] Subscription form submits
- [ ] Tags can be added/removed
- [ ] Mobile responsive

## Quick Commands

```bash
# Run migrations
php artisan migrate

# Seed initial data
php artisan db:seed

# Manual crawl
php artisan govjobs:crawl

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run scheduler manually
php artisan schedule:run

# View logs
tail -f storage/logs/laravel.log
```

## Support & Issues

For help or to report issues:
1. Check the logs: `storage/logs/laravel.log`
2. Review the setup guide: `GOV_JOBS_SETUP.md`
3. Open an issue on GitHub
4. Contact: support@webaiauditor.com
