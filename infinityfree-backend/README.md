# WebAI Auditor Backend - InfinityFree Deployment Guide

## 🚀 InfinityFree पर Backend Deploy करें

### Step 1: InfinityFree Account बनाएं

1. इस link पर जाएं: **https://www.infinityfree.net/**
2. **"Sign Up"** button पर click करें
3. अपना **Email**, **Password**, **Username** डालें
4. **"Sign Up"** पर click करें
5. Email verify करें

**✅ No Credit Card Required!**

---

### Step 2: New Website बनाएं

1. Login करने के बाद **Control Panel** में जाएं
2. **"Create New Website"** पर click करें
3. इन options select करें:

```
┌─────────────────────────────────────┐
│ Account Type:                       │
│ ⦿ Free Hosting (Forever Free)      │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Website Type:                       │
│ ⦿ Upload Own Website               │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Domain Name:                        │
│ Choose: webai-auditor (example)     │
│                                     │
│ Your URL will be:                   │
│ webai-auditor.infinityfreeapp.com   │
└─────────────────────────────────────┘
```

4. **"Create"** button press करें

---

### Step 3: Upload Backend Files

#### Option A: File Manager से Upload (Easiest)

1. Control Panel में **"File Manager"** open करें
2. **htdocs** folder में जाएं
3. इन 3 files upload करें:

```
htdocs/
├── api.php
├── index.php
└── .htaccess
```

**Upload Steps:**
- File Manager में **"Upload"** button click करें
- `api.php` file select करें और upload करें
- `index.php` upload करें
- `.htaccess` upload करें

#### Option B: FTP से Upload

1. Control Panel में से FTP details copy करें:
   - FTP Host
   - FTP Username
   - FTP Password
   - Port: 21

2. FileZilla या किसी FTP client में login करें
3. `htdocs` folder में files upload करें

---

### Step 4: Test करें

Upload के बाद अपने browser में खोलें:

```
https://your-site-name.infinityfreeapp.com/api/health
```

आपको ये response देखना चाहिए:

```json
{
    "status": "healthy",
    "service": "WebAI Auditor API",
    "version": "1.0.0",
    "timestamp": "2024-03-11T..."
}
```

---

### Step 5: Frontend में API URL Update करें

**File:** `assets/js/api.js` (Line 6)

```javascript
// अपने InfinityFree URL से replace करें
const API_BASE = 'https://your-site-name.infinityfreeapp.com/api';
```

---

## 📁 Files Structure

```
infinityfree-backend/
├── api.php          # Main API file (all endpoints)
├── index.php        # Root redirect
├── .htaccess        # URL rewriting
└── README.md        # This file
```

---

## 🔧 Available API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Health check |
| `/audit` | POST | Run full audit |
| `/analyze/seo` | POST/GET | SEO analysis |
| `/analyze/tech-stack` | POST/GET | Tech stack detection |
| `/stats` | GET | System statistics |

---

## 💡 API Usage Examples

### Run Audit
```javascript
fetch('https://your-site.infinityfreeapp.com/api/audit', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({url: 'https://example.com'})
})
```

### SEO Analysis
```javascript
fetch('https://your-site.infinityfreeapp.com/api/analyze/seo?url=https://example.com')
```

### Tech Stack Detection
```javascript
fetch('https://your-site.infinityfreeapp.com/api/analyze/tech-stack?url=https://example.com')
```

---

## ⚠️ InfinityFree Limitations (Free Plan)

| Feature | Limit |
|---------|-------|
| Storage | Unlimited |
| Bandwidth | Unlimited |
| Websites | Unlimited |
| MySQL Databases | 2 per account |
| PHP Version | 8.x |
| Uptime | 99.9% |
| Ads | Yes (InfinityFree adds ads) |

---

## 🎯 Next Steps

1. ✅ Account बना लिया
2. ✅ Website create कर लिया
3. ✅ Files upload कर दिए
4. ⏳ API test करें
5. ⏳ Frontend में URL update करें
6. ⏳ GitHub पर commit करें

---

## ❓ कोई Problem आ रही है?

### Common Issues:

**1. 404 Error**
- `.htaccess` file properly upload हुआ है
- File names case-sensitive हैं

**2. CORS Error**
- `.htaccess` में CORS headers check करें
- या api.php में CORS lines uncomment करें

**3. 500 Error**
- PHP version check करें (PHP 8.x required)
- File permissions check करें

---

## 🚀 Deploy हो गया!

आपका backend live है:
```
https://your-site-name.infinityfreeapp.com
```

अब आपका WebAI Auditor fully functional है! 🎉
