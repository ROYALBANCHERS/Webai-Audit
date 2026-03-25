# Government Jobs Feature - Setup Guide

This guide will help you implement the government jobs monitoring feature in WebAI Auditor.

## Prerequisites

- PHP 8.2+
- MySQL/MariaDB
- Composer
- Laravel CLI
- Basic understanding of Laravel

## Installation Steps

### 1. Clone & Install Dependencies

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure Database

Edit `backend/.env`:

```env
DB_DATABASE=webai_auditor
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Run Database Migrations

```bash
# Create the tables
mysql -u root -p webai_auditor < create_gov_jobs_tables.sql

# OR if you have migrations set up
php artisan migrate
```

### 4. Create Laravel Migrations (Alternative)

Run these commands:

```bash
php artisan make:migration create_gov_jobs_table
php artisan make:migration create_job_subscriptions_table
php artisan make:migration create_job_matches_table
php artisan make:migration create_crawl_sources_table
```

Then edit the migration files with the schema provided in `create_gov_jobs_tables.sql`

### 5. Setup Cron Job

Edit your server's crontab:

```bash
crontab -e
```

Add this line:

```bash
* * * * * cd /path-to-project/backend && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Start Laravel Server

```bash
cd backend
php artisan serve
```

The API will be available at `http://localhost:8000`

### 7. Serve Frontend

```bash
cd frontend
python -m http.server 8080
# OR
php -S localhost:8080
```

The frontend will be available at `http://localhost:8080`

## Testing the Feature

### 1. Access the Government Jobs Page

Navigate to: `http://localhost:8080/gov-jobs.html`

### 2. Setup Job Alerts

Navigate to: `http://localhost:8080/job-subscription.html`

### 3. Test Manual Crawl

```bash
cd backend
php artisan govjobs:crawl
```

### 4. View API Endpoints

- Get all jobs: `GET http://localhost:8000/api/gov-jobs`
- Get statistics: `GET http://localhost:8000/api/gov-jobs/statistics`
- Get categories: `GET http://localhost:8000/api/gov-jobs/categories/list`

## API Documentation

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/gov-jobs` | Get all jobs (with filters) |
| GET | `/api/gov-jobs/{id}` | Get specific job |
| GET | `/api/gov-jobs/categories/list` | Get categories with counts |
| GET | `/api/gov-jobs/departments/list` | Get departments with counts |
| GET | `/api/gov-jobs/statistics` | Get job statistics |
| GET | `/api/gov-jobs/expiring-soon` | Get jobs expiring soon |

### Authenticated Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/gov-jobs/my-jobs` | Get personalized jobs |
| POST | `/api/gov-jobs/subscribe` | Create/update subscription |
| GET | `/api/gov-jobs/subscription` | Get user subscription |
| POST | `/api/gov-jobs/subscription/toggle` | Pause/resume alerts |
| POST | `/api/gov-jobs/{jobId}/read` | Mark job as read |

## Configuration Files

### Crawl Sources

Edit `backend/database/crawl_sources.sql` to add more government websites:

```sql
INSERT INTO crawl_sources (name, base_url, crawl_urls, selectors, crawl_frequency) VALUES
('Your Source', 'https://example.com',
 '["https://example.com/jobs"]',
 '{"title": ".job-title", "link": "a.job-link"}',
 3600);
```

### Scheduled Crawling

Edit `backend/app/Console/Kernel.php` to adjust crawl frequency:

```php
// Current: Every hour between 6 AM - 11 PM
$schedule->command('govjobs:crawl')
    ->hourly()
    ->between('6:00', '23:00');

// Change to every 6 hours:
$schedule->command('govjobs:crawl')
    ->everySixHours();
```

## Government Job Sources

The system comes pre-configured with these sources:

1. **Employment News** - https://employmentnews.gov.in
2. **SSC Official** - https://ssc.nic.in
3. **UPSC Official** - https://upsc.gov.in
4. **Free Job Alert** - https://www.freejobalert.com

### Adding More Sources

1. Find the website's RSS feed or job listing page
2. Inspect HTML structure for CSS selectors
3. Add to `crawl_sources` table
4. The system will automatically crawl it

## Frontend Pages

| Page | URL | Description |
|------|-----|-------------|
| Jobs Listing | `/gov-jobs.html` | Browse and search all jobs |
| Subscription | `/job-subscription.html` | Configure job alerts |
| Dashboard | `/dashboard.html` | View personalized jobs |

## Email Notifications (TODO)

To enable email notifications, update `GovJobService.php`:

```php
// In sendNotificationsForNewMatches() method
use Illuminate\Support\Facades\Mail;
use App\Mail\NewJobMatchMail;

// Replace the TODO with:
Mail::to($match->user->email)->send(new NewJobMatchMail($match));
```

Create the email template:

```bash
php artisan make:mail NewJobMatchMail
```

## Troubleshooting

### No jobs appearing?

1. Check crawl logs: `tail -f backend/storage/logs/laravel.log`
2. Run manual crawl: `php artisan govjobs:crawl`
3. Verify database tables have data

### Crawl failing?

1. Check internet connection
2. Verify source websites are accessible
3. Check if website structure has changed

### Notifications not sending?

1. Verify email configuration in `.env`
2. Check mail logs
3. Ensure user subscriptions are active

## Production Deployment

### 1. Optimize for Production

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Setup Queue Worker

```bash
php artisan queue:work --sleep=3 --tries=3
```

Use Supervisor to keep queue worker running:

```bash
sudo apt-get install supervisor
```

Create `/etc/supervisor/conf.d/webai-auditor.conf`:

```ini
[program:webai-auditor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-app/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-app/storage/logs/worker.log
```

### 3. Security Considerations

1. **Rate Limiting**: Add rate limiting to API endpoints
2. **Authentication**: Ensure all user-specific routes require authentication
3. **CORS**: Configure CORS properly for production domains
4. **Input Validation**: All inputs are validated via Laravel validators
5. **SQL Injection**: Use Laravel's Eloquent ORM (built-in protection)

## Legal Considerations

- Respect `robots.txt` of all government websites
- Don't overload servers with frequent requests
- Provide attribution to source websites
- Only aggregate publicly available job information
- Consider caching responses to reduce server load

## Future Enhancements

1. **SMS Notifications** - Integrate Twilio or similar
2. **Mobile App** - React Native or Flutter app
3. **Exam Calendar** - Track exam dates and reminders
4. **Syllabus Section** - Store and display exam syllabus
5. **Admit Card Alerts** - Notify when admit cards are released
6. **Result Updates** - Track and notify result declarations
7. **Question Papers** - Archive previous year papers
8. **Discussion Forum** - Community for aspirants

## Support

For issues or questions:
- GitHub: https://github.com/ROYALBANCHERS/Webai-Audit
- Email: support@webaiauditor.com

## License

MIT License - Feel free to use and modify for your needs!
