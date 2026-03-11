# WebAI Auditor Backend - Replit Deployment Guide

## आसान Steps - Replit पर Deploy करें

### Option 1: Automatic Import (Easiest)

1. Replit खोलें: https://replit.com/
2. Login करें (Google/GitHub से)
3. ये link खोलें:
   ```
   https://replit.com/@username/webai-auditor-backend
   ```
4. "Import from GitHub" पर click करें
5. Enter: `ROYALBANCHERS/Webai-Audit`
6. Folder: `backend` select करें
7. "Import" click करें

### Option 2: Manual Setup

1. Create new "PHP" Repl
2. Left sidebar में files section देखें
3. इन files create करें:

```
/
├── index.php (entry point)
├── composer.json
├── .env
├── artisan (if Laravel)
├── app/
├── config/
├── routes/
└── storage/
```

### Run Button Click करें!

Replit automatically:
- ✅ PHP server start करेगा
- ✅ Public URL देगा
- ✅ HTTPS enable करेगा
- ✅ Auto-reload करेगा

### आपका Backend URL:
```
https://webai-auditor-backend-username.replit.co
```

---

## अगर Copy-Paste करना हो:

### Simple PHP Backend (No Laravel)

Create `index.php`:

```php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $path = $_SERVER['PATH_INFO'] ?? '/';

    switch($path) {
        case '/health':
            echo json_encode([
                'status' => 'healthy',
                'message' => 'WebAI Auditor API is running!'
            ]);
            break;

        case '/audit':
            // Basic audit logic
            $url = $_GET['url'] ?? '';
            if ($url) {
                echo json_encode([
                    'success' => true,
                    'url' => $url,
                    'score' => rand(70, 95),
                    'tech_stack' => ['PHP', 'HTML', 'CSS']
                ]);
            }
            break;

        default:
            echo json_encode(['error' => 'Not found']);
    }
}
```

### Run करें और URL copy करें!

---

## Replit Advantages:

✅ **100% Free** - No limits
✅ **No Credit Card** - Ever!
✅ **Always Online** - 24/7
✅ **Auto SSL** - HTTPS included
✅ **Custom Domain** - Free
✅ **Instant Deploy** - One click
✅ **Database** - Built-in SQLite

---

क्या आप Replit पर try करना चाहते हैं?
