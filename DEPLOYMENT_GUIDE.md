# 🚀 Production Deployment Guide - RoyalBanchers WebAI Audit

## 📋 Overview

Aapki project mein 2 parts hain:
1. **Frontend** (gov-jobs.html) - Static HTML/CSS/JS
2. **Backend** (Laravel API) - PHP application with database

Yeh guide dono parts ko alag-alag deploy karne ka process explain karti hai.

---

## 🎨 Part 1: Frontend Deployment (GitHub Pages)

### Step 1: Create gh-pages Branch

```bash
cd C:/Users/user/Webai-Audit
git checkout -b gh-pages
git push origin gh-pages
```

### Step 2: Enable GitHub Pages

1. GitHub repo kholo: https://github.com/ROYALBANCHERS/Webai-Audit
2. Settings → Pages
3. Source: Select `gh-pages` branch
4. Folder: `/ (root)`
5. Save

Frontend live ho jayegi: `https://royalbanchers.github.io/Webai-Audit/gov-jobs.html`

---

## 🔧 Part 2: Backend Deployment (Free Options)

### Option A: Railway.app (Easiest - Recommended)

#### 1. Railway Account Create Karo
- Visit: https://railway.app
- Sign up with GitHub

#### 2. New Project Create Karo
- Click "New Project"
- Select "Deploy from GitHub repo"
- Select: `ROYALBANCHERS/Webai-Audit`

#### 3. Environment Variables Add Karo
```bash
APP_NAME=WebAI Auditor
APP_ENV=production
APP_KEY=base64:generate_with_php_artisan
APP_DEBUG=false
APP_URL=https://your-app.railway.app

# Database (Railway provides MySQL)
DB_CONNECTION=mysql
DB_HOST=<railway_provided>
DB_PORT=3306
DB_DATABASE=<railway_provided>
DB_USERNAME=<railway_provided>
DB_PASSWORD=<railway_provided>

# TinyFish
TINYFISH_API_KEY=bS6r2HMf-VwhcyBARDoIxIyctP35l6zyMHc_SAC476RWDIoBjud71w
TINYFISH_ENDPOINT=https://api.agentql.com/v1/query
TINYFISH_TIMEOUT=60

# Queue
QUEUE_CONNECTION=database

# Admin Secret
ADMIN_SECRET=generate_secure_random_secret
```

#### 4. Deploy Click Karo
- Railway automatically deploy karega
- URL mil jayega: `https://your-app.railway.app`

#### 5. Database Setup
```bash
# Railway mein PHP artisan commands run karo
php artisan migrate --force
```

---

### Option B: Render.com (Professional - Recommended for Production)

#### 1. Render Account Create Karo
- Visit: https://render.com
- Sign up with GitHub

#### 2. Web Service Create Karo
- Click "New" → "Web Service"
- Connect GitHub repo: `ROYALBANCHERS/Webai-Audit`

#### 3. Configure Build
```yaml
Environment: PHP
Build Command: composer install --no-dev
Start Command: php artisan serve --host=0.0.0.0 --port=10000
```

#### 4. Environment Variables
Same as Railway above

#### 5. Database Create Karo
- Render mein "PostgreSQL" create karo (Free tier available)
- Database URL copy karke env variables mein paste karo

#### 6. Deploy
- Render automatically deploy karega
- URL: `https://your-app.onrender.com`

---

### Option C: Koyeb (Global Free Tier)

#### 1. Koyeb Account Create Karo
- Visit: https://koyeb.com
- Sign up with GitHub

#### 2. App Create Karo
- Click "Create App"
- Select: GitHub
- Choose: `ROYALBANCHERS/Webai-Audit`

#### 3. Configuration
```yaml
Region: Singapore (or closest to India)
Instance: Nano (Free)
Build: Dockerfile
```

#### 4. Create Dockerfile
```dockerfile
FROM composer:2 as build
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev

FROM php:8.2-fpm
WORKDIR /var/www/html
COPY --from=build /app /var/www/html/
COPY backend/ /var/www/html/

RUN docker-php-ext-install pdo pdo_mysql
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
```

---

## 🔗 Part 3: Connect Frontend to Backend

### Update API Base URL in Frontend

#### 1. gov-jobs.html Update Karo
```javascript
// Find this line:
const TINYFISH_API_BASE = '/api/sources';

// Replace with your backend URL:
const TINYFISH_API_BASE = 'https://your-app.railway.app/api/sources';

// Also update:
const API_BASE_URL = 'https://your-app.railway.app/api';
```

#### 2. SSE Stream Update Karo
```javascript
// Find:
sseConnection = new EventSource('/api/jobs/stream');

// Replace:
sseConnection = new EventSource('https://your-app.railway.app/api/jobs/stream');
```

#### 3. Commit aur Push Karo
```bash
git add .
git commit -m "feat: Configure production API endpoints"
git push origin main
```

---

## 🗄️ Part 4: Database Migration (One-time Setup)

### Railway/Render/Koyeb mein SSH ya Console access le kar:

```bash
# 1. Database migration run karo
php artisan migrate --force

# 2. Generate app key (agar nahi hai)
php artisan key:generate

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 🎯 Part 5: First Sources Add Karo

### API endpoint use karke sources add karo:

```bash
# Using curl
curl -X POST https://your-app.railway.app/api/sources \
  -H "Content-Type: application/json" \
  -H "X-Admin-Secret: YOUR_ADMIN_SECRET" \
  -d '{
    "name": "SSC Official",
    "url": "https://ssc.nic.in",
    "category": "ssc",
    "scraping_strategy": "auto",
    "auto_update": true,
    "crawl_frequency": 3600,
    "is_featured": true
  }'

# Add more sources...
curl -X POST https://your-app.railway.app/api/sources \
  -H "Content-Type: application/json" \
  -H "X-Admin-Secret: YOUR_ADMIN_SECRET" \
  -d '{
    "name": "UPSC Official",
    "url": "https://upsc.gov.in",
    "category": "upsc",
    "scraping_strategy": "auto",
    "auto_update": true,
    "crawl_frequency": 3600,
    "is_featured": true
  }'
```

---

## ✅ Verification Checklist

### Frontend Check
- [ ] GitHub Pages live hai
- [ ] gov-jobs.html accessible hai
- [ ] UI properly load ho raha hai

### Backend Check
- [ ] Backend server running hai
- [ ] API endpoints working hain
- [ ] Database tables create hui hain

### Integration Check
- [ ] Frontend backend se connect ho raha hai
- [ ] Sources list load ho raha hai
- [ ] Test scraping work kar raha hai

### Real-time Check
- [ ] SSE stream working hai
- [ ] Job notifications aa rahe hain

---

## 🔧 Troubleshooting

### Issue: CORS Error
**Solution**: Backend mein CORS middleware enable karo
```php
// backend/app/Http/Middleware/Cors.php
return $next($request)
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Admin-Secret');
```

### Issue: Database Connection Error
**Solution**: Environment variables check karo, database credentials verify karo

### Issue: Queue Workers Not Running
**Solution**: Railway/Render mein "Worker" service create karo
```bash
# Start command for worker
php artisan queue:work --queue=scraping --sleep=3 --tries=3
```

---

## 📊 Deployment URLs Summary

### After Successful Deployment:

| Component | URL |
|-----------|-----|
| Frontend (GitHub Pages) | https://royalbanchers.github.io/Webai-Audit/gov-jobs.html |
| Backend (Railway) | https://webai-audit.up.railway.app |
| API Endpoint | https://webai-audit.up.railway.app/api/sources |
| SSE Stream | https://webai-audit.up.railway.app/api/jobs/stream |

---

## 🎉 Next Steps

1. **Frontend deploy** karo GitHub Pages pe
2. **Backend deploy** karo Railway/Render pe
3. **Database migration** run karo
4. **API endpoints** test karo
5. **First sources** add karo
6. **Real-time updates** verify karo

---

**Best Free Options for India:**
- Railway.app (Mumbai region available)
- Render.com (Good free tier)
- Koyeb.com (Global free tier)

**Recommended:** Railway.app sabse easy hai aur Mumbai region available hai!

---

Generated: March 21, 2026
Version: 1.0
