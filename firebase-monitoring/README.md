# Dynamic Multi-URL Web Monitor & AI Auditor

A complete, production-ready system for monitoring multiple websites with AI-powered change detection. Built with React, Firebase, and Gemini AI.

## 🚀 Features

- **Multi-URL Monitoring**: Add ANY number of URLs (government job sites, news portals, etc.)
- **Smart Change Detection**: AI-powered analysis using Gemini 2.0 Flash
- **Hash-Based Comparison**: Only processes actual changes (saves API costs)
- **Automatic Scraping**: Runs every 4 hours via Firebase Cloud Functions
- **Real-Time Notifications**: Get instant alerts when content changes
- **Categorization**: Organize monitored sites by category
- **Change History**: View all detected changes with AI summaries

## 📋 Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   React App     │────▶│  Firebase Auth   │────▶│  User Profile   │
│   (Frontend)    │     │  Functions       │     │  (Firestore)    │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                │
                                ▼
                         ┌──────────────────┐
                         │  Scraper Service │
                         │  (Axios/Cheerio) │
                         └──────────────────┘
                                │
                                ▼
                         ┌──────────────────┐
                         │   Hash Compare   │
                         │  (SHA-256)       │
                         └──────────────────┘
                                │
                        Changed? (Yes)
                                ▼
                         ┌──────────────────┐
                         │  Gemini AI       │
                         │  (Change Detect) │
                         └──────────────────┘
                                │
                                ▼
                         ┌──────────────────┐
                         │  Notification    │
                         │  (Email/Web)     │
                         └──────────────────┘
```

## 🔧 Tech Stack

| Component | Technology |
|-----------|------------|
| Frontend | React 18 + TypeScript + Material-UI |
| Backend | Firebase Cloud Functions (Node.js 18) |
| Database | Firestore |
| Scraping | Axios + Cheerio |
| AI | Gemini 2.0 Flash API |
| Auth | Firebase Authentication |
| Scheduling | Pub/Sub (every 4 hours) |

## 📦 Installation

### Prerequisites

- Node.js 18+
- Firebase CLI (`npm install -g firebase-tools`)
- Gemini API Key ([Get one here](https://ai.google.dev/))

### 1. Clone and Install

```bash
cd firebase-monitoring/functions
npm install
```

### 2. Environment Setup

Create `functions/.env`:

```bash
# Gemini AI API Key
GEMINI_API_KEY=your_gemini_api_key_here

# Firebase region
GCP_REGION=asia-south1
```

### 3. Firebase Configuration

Update `.firebaserc` with your project ID:

```json
{
  "projects": {
    "default": "your-firebase-project-id"
  }
}
```

Update `public/src/services/firebase.ts` with your Firebase config.

### 4. Deploy

```bash
# Login to Firebase
firebase login

# Initialize project (first time only)
firebase init

# Deploy everything
firebase deploy

# Deploy only functions
firebase deploy --only functions

# Deploy only hosting
firebase deploy --only hosting
```

## 📁 Database Schema

### Users Collection
```typescript
{
  uid: string,
  email: string,
  settings: { emailNotifications, frequency, ... },
  subscription: { plan, maxSites, currentSites }
}
```

### Monitored Sites (Sub-collection)
```typescript
{
  url: string,
  siteName: string,
  category: string,
  isActive: boolean,
  lastContentHash: string,
  lastContentSnapshot: string,
  monitoringStatus: 'active' | 'error',
  totalChangesDetected: number
}
```

### Site Changes (Sub-collection)
```typescript
{
  siteId: string,
  changeType: string,
  aiSummary: { title, keyChanges, confidence },
  detectedAt: Timestamp,
  isRead: boolean
}
```

## ⚙️ Configuration

### Change Detection Threshold

Edit `functions/src/utils/hash.ts`:

```typescript
export const SIGNIFICANT_CHANGE_THRESHOLD = 5; // 5%
export const MIN_TIME_BETWEEN_AI_CHECKS = 60 * 60 * 1000; // 1 hour
```

### Cron Schedule

Edit `functions/src/index.ts`:

```typescript
export const scheduledScraper = functions.pubsub
  .schedule('0 */4 * * *') // Every 4 hours
  .timeZone('Asia/Kolkata')
```

### Memory & Timeout

```typescript
.runWith({
  timeoutSeconds: 540, // Max 9 minutes
  memory: '2GB',      // Use 2GB for better performance
})
```

## 🐛 Bug Prevention

### 1. Memory Limits
- Batch processing limited to 10 sites at a time
- Content snapshots limited to 50,000 characters
- Use `Promise.allSettled` instead of `Promise.all`

### 2. CORS Handling
- All functions use `onCall` (auto-handles CORS)
- For HTTP functions, add CORS headers

### 3. Website Down Errors
```typescript
// Scraper returns structured errors
{
  success: false,
  error: {
    code: 'HTTP_404',
    message: 'Website not found',
    isPermanent: true  // Stop retrying if permanent
  }
}
```

### 4. Rate Limiting
- User-Agent rotation (5 different browsers)
- 1-second delay between batches
- Exponential backoff on retries

## 📊 API Reference

### Client Functions

```typescript
// Add a site to monitor
addSite({ url, siteName?, category? })

// Get all monitored sites
getSites()

// Remove a site
removeSite(siteId)

// Get recent changes
getChanges(limit?)

// Manual check
checkSite(siteId, forceAI?)
```

### Cloud Functions

| Function | Trigger | Description |
|----------|---------|-------------|
| `addSite` | Callable | Add new monitored site |
| `getSites` | Callable | Get user's sites |
| `removeSite` | Callable | Delete monitored site |
| `checkSite` | Callable | Manual scrape trigger |
| `scheduledScraper` | Pub/Sub | Auto-check every 4 hours |
| `health` | HTTP | Health check endpoint |

## 🔍 Testing

### Local Emulator

```bash
# Start all emulators
firebase emulators:start

# In another terminal, test functions
firebase functions:shell
```

### Test Scraper

```bash
cd functions
npm run test
```

## 📈 Monitoring

### View Logs

```bash
# All logs
firebase functions:log

# Specific function
firebase functions:log --only scheduledScraper

# Real-time
firebase functions:log --only scheduledScraper
```

### Firestore Stats

Check `scraping_stats` collection for daily statistics.

## 🔐 Security Rules

- Users can only read/write their own data
- Server-only creation for changes and notifications
- Input validation on all callable functions

## 🚀 Deployment Checklist

- [ ] Set Gemini API key in environment
- [ ] Update Firebase config in frontend
- [ ] Create Firestore indexes (use `firebase deploy --only firestore:rules`)
- [ ] Set up Firebase Authentication (Email/Google)
- [ ] Configure email notifications (optional)
- [ ] Test with emulator first

## 📝 License

MIT License - Contributed by **Rishabh**

## 🆘 Support

For issues and questions, check the documentation in `/docs` folder.

---

**Built with ❤️ using Firebase + Gemini AI**
