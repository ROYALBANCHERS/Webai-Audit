# Government Jobs Monitoring Feature

A complete government job monitoring and alert system for WebAI Auditor that crawls multiple government websites and provides real-time job updates to users.

## ✨ Features

- **🔍 Automatic Web Crawling** - Crawls government job websites hourly
- **📢 Real-time Alerts** - Email/Push/SMS notifications for matching jobs
- **🎯 Smart Matching** - AI-powered job matching based on user preferences
- **📊 Categories** - SSC, Banking, Railway, UPSC, Teaching, Defense, Police, Engineering
- **🔔 Expiring Soon** - Highlight jobs closing in next 7 days
- **📱 Mobile Responsive** - Works on all devices
- **🔎 Advanced Search** - Filter by category, department, qualification, location

## 🗂️ What's Included

### Backend (Laravel)
- ✅ GovJobService - Core crawling and matching logic
- ✅ GovJobController - RESTful API endpoints
- ✅ Models - GovJob, JobSubscription, JobMatch, CrawlSource
- ✅ Commands - Artisan command for manual crawling
- ✅ Scheduled Tasks - Automatic hourly crawling
- ✅ Database Schema - Complete SQL migrations

### Frontend (HTML/JS)
- ✅ Jobs Listing Page - Browse and search all jobs
- ✅ Subscription Page - Configure job alerts
- ✅ Category Filters - Quick category navigation
- ✅ Department Filter - Filter by organization
- ✅ Last Date Filter - Find jobs closing soon
- ✅ Statistics Dashboard - Real-time job counts

## 🚀 Quick Start

### 1. Setup Database

```bash
mysql -u root -p webai_auditor < create_gov_jobs_tables.sql
```

### 2. Install Dependencies

```bash
cd backend
composer install
php artisan key:generate
```

### 3. Configure Environment

Edit `backend/.env`:

```env
DB_DATABASE=webai_auditor
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Initial Crawl

```bash
php artisan govjobs:crawl
```

### 5. Start Servers

```bash
# Backend
cd backend
php artisan serve

# Frontend (new terminal)
cd frontend
python -m http.server 8080
```

### 6. Access the Feature

- **Jobs Page**: http://localhost:8080/gov-jobs.html
- **Subscription**: http://localhost:8080/job-subscription.html
- **API**: http://localhost:8000/api/gov-jobs

## 📁 File Overview

```
├── create_gov_jobs_tables.sql         # Database schema
├── GOV_JOBS_SETUP.md                  # Detailed setup guide
├── GOV_JOBS_FILE_STRUCTURE.md         # File structure reference
├── GOV_JOBS_README.md                 # This file
│
├── backend/
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   └── CrawlGovJobs.php      # Crawl command
│   │   ├── Http/Controllers/
│   │   │   └── GovJobController.php  # API controller
│   │   ├── Models/
│   │   │   ├── GovJob.php
│   │   │   ├── JobSubscription.php
│   │   │   ├── JobMatch.php
│   │   │   └── CrawlSource.php
│   │   └── Services/
│   │       └── GovJobService.php     # Core logic
│   └── routes/
│       └── api.php                    # API routes
│
└── frontend/
    ├── gov-jobs.html                  # Jobs listing
    └── job-subscription.html          # Alert setup
```

## 🔧 Configuration

### Adding New Job Sources

Edit the SQL file or add via API:

```sql
INSERT INTO crawl_sources (name, base_url, crawl_urls, selectors, crawl_frequency)
VALUES (
    'New Gov Site',
    'https://govsite.com',
    '["https://govsite.com/jobs"]',
    '{"title": ".job-title", "link": "a.job-link"}',
    3600
);
```

### Adjust Crawl Frequency

Edit `backend/app/Console/Kernel.php`:

```php
// Current: Every hour
$schedule->command('govjobs:crawl')->hourly();

// Change to every 6 hours:
$schedule->command('govjobs:crawl')->everySixHours();

// Change to every 30 minutes:
$schedule->command('govjobs:crawl')->everyThirtyMinutes();
```

## 📊 API Examples

### Get All Jobs

```bash
curl http://localhost:8000/api/gov-jobs
```

### Search Jobs

```bash
curl "http://localhost:8000/api/gov-jobs?keyword=SSC&category=SSC"
```

### Get Statistics

```bash
curl http://localhost:8000/api/gov-jobs/statistics
```

### Get Expiring Soon

```bash
curl http://localhost:8000/api/gov-jobs/expiring-soon?days=7
```

### Create Subscription (Authenticated)

```bash
curl -X POST http://localhost:8000/api/gov-jobs/subscribe \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "keywords": ["SSC", "Banking"],
    "categories": ["SSC", "Banking"],
    "qualifications": ["Graduate"],
    "notification_methods": ["email"]
  }'
```

## 🎯 Supported Job Categories

| Category | Description |
|----------|-------------|
| SSC | Staff Selection Commission jobs |
| Banking | Bank jobs (IBPS, SBI, RBI) |
| Railway | Railway Recruitment Board |
| UPSC | Union Public Service Commission |
| Teaching | Teaching jobs (TET, CTET) |
| Defense | Army, Navy, Air Force |
| Police | Police recruitment |
| Engineering | Engineering jobs (GATE, IES) |
| Other | Other government jobs |

## 🔔 Pre-configured Sources

1. **Employment News** - employmentnews.gov.in
2. **SSC Official** - ssc.nic.in
3. **UPSC Official** - upsc.gov.in
4. **Free Job Alert** - freejobalert.com

### Add More Sources

Common government job portals:
- Sarkari Result
- India.gov.in
- State government portals
- Public sector undertakings
- Banking job portals

## 🔐 Security Features

- ✅ SQL Injection Protection (Eloquent ORM)
- ✅ XSS Protection (Input sanitization)
- ✅ CSRF Protection (Laravel built-in)
- ✅ Authentication Required (User-specific endpoints)
- ✅ Rate Limiting (Can be added)
- ✅ Input Validation (Laravel validators)

## 📈 Performance Optimization

- Database indexes on frequently queried fields
- Pagination for large job lists
- Caching for statistics
- Efficient job matching algorithm
- Background job processing

## 🚀 Production Deployment

### 1. Optimize Application

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Setup Queue Worker

Use Supervisor to keep queue workers running:

```bash
sudo apt-get install supervisor
```

### 3. Setup Cron Job

Add to crontab:

```bash
* * * * * cd /path-to-app/backend && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Configure Web Server

Nginx configuration example provided in setup guide.

## 📚 Documentation

- **Setup Guide**: `GOV_JOBS_SETUP.md` - Complete installation instructions
- **File Structure**: `GOV_JOBS_FILE_STRUCTURE.md` - Detailed file reference
- **API Endpoints**: See below for complete API reference

## 🔍 Troubleshooting

### No jobs appearing?

1. Check database tables have data
2. Run manual crawl: `php artisan govjobs:crawl`
3. Check logs: `tail -f storage/logs/laravel.log`

### API not working?

1. Verify Laravel server is running
2. Check CORS settings
3. Ensure database is configured

### Email notifications not sending?

1. Configure mail settings in `.env`
2. Check mail logs
3. Verify user email addresses

## 🤝 Contributing

This is an open-source project. Feel free to:

1. Add more government job sources
2. Improve the matching algorithm
3. Add new features (exam calendar, syllabus, etc.)
4. Fix bugs and issues
5. Improve documentation

## 📄 License

MIT License - Free to use and modify

## 🙏 Credits

Built with:
- Laravel 11
- MySQL
- Tailwind CSS
- Font Awesome
- Vanilla JavaScript

## 📞 Support

For issues or questions:
- **GitHub**: https://github.com/ROYALBANCHERS/Webai-Audit
- **Email**: support@webaiauditor.com

---

**Status**: ✅ Ready to use
**Version**: 1.0.0
**Last Updated**: 2024

Made with ❤️ for government job aspirants
