# Firebase Deployment Guide

## 🚀 Quick Start Deployment

### Step 1: Login to Firebase

Open your terminal and run:

```bash
firebase login
```

This will open a browser window. Sign in with your Google account and authorize Firebase CLI.

### Step 2: Install Dependencies

```bash
cd firebase-monitoring/functions
npm install
```

### Step 3: Set Environment Variables

```bash
# Copy the example file
cp functions/.env.example functions/.env

# Edit and add your GEMINI_API_KEY
# Get your API key from: https://ai.google.dev/
```

### Step 4: Deploy

#### Option A: Use the Deployment Script (Recommended)

**Windows:**
```bash
cd firebase-monitoring
deploy.bat
```

**Linux/Mac:**
```bash
cd firebase-monitoring
chmod +x deploy.sh
./deploy.sh
```

#### Option B: Manual Deployment

```bash
# Deploy everything
firebase deploy

# Or deploy specific components
firebase deploy --only functions     # Cloud Functions only
firebase deploy --only hosting        # Hosting only
firebase deploy --only firestore:rules  # Firestore rules only
```

## 📋 What Gets Deployed

### 1. Cloud Functions
- `addSite` - Add a new monitored site
- `getSites` - Get user's monitored sites
- `removeSite` - Remove a monitored site
- `checkSite` - Manual site check
- `getChanges` - Get recent changes
- `scheduledScraper` - Auto-scraper (runs every 4 hours)
- `health` - Health check endpoint

### 2. Hosting
- React frontend application
- Available at: `https://webai-audit.web.app`

### 3. Firestore
- Database rules and indexes

## 🔧 After Deployment

### 1. Set Up Firestore Indexes

Go to Firebase Console → Firestore → Indexes and create the indexes defined in `firestore.indexes.json`

Or run:
```bash
firebase deploy --only firestore:indexes
```

### 2. Set Environment Variables in Firebase Console

Go to Firebase Console → Functions → Config and add:
```json
{
  "GEMINI_API_KEY": "your_api_key_here",
  "GCP_REGION": "asia-south1"
}
```

### 3. Enable Required APIs

Make sure these APIs are enabled in Google Cloud Console:
- Cloud Functions API
- Cloud Firestore API
- Cloud Pub/Sub API (for scheduled functions)

## 🌐 Live URLs

After deployment, your app will be available at:

| Service | URL |
|---------|-----|
| **Hosting** | https://webai-audit.web.app |
| **Functions** | asia-south1-webai-audit.cloudfunctions.net |

## 🐛 Troubleshooting

### Error: "Failed to authenticate"
```bash
firebase login
```

### Error: "Not authorized"
Make sure you're using the correct Firebase account and have the necessary permissions.

### Error: "Quota exceeded"
Check your Firebase pricing plan and usage limits.

### Functions not triggering
- Check Cloud Functions logs: `firebase functions:log`
- Verify Pub/Sub scheduler is enabled
- Check region settings (asia-south1)

### Build errors
```bash
cd functions
npm run build
```

## 📊 Monitoring

### View Logs
```bash
# All logs
firebase functions:log

# Specific function
firebase functions:log --only scheduledScraper

# Real-time
firebase functions:log --only scheduledScraper
```

### Check Usage
Go to Firebase Console → Functions → Usage

## 🔐 Security

- Firestore rules are automatically deployed
- Only authenticated users can access data
- Users can only read/write their own data

## 📝 Notes

- The scheduled scraper runs every 4 hours (Asia/Kolkata timezone)
- Functions timeout: 540 seconds (9 minutes max)
- Memory allocation: 2GB
- Region: asia-south1

## Support

For issues, check:
- Firebase Console → Functions → Logs
- Firestore → Indexes
- Google Cloud Console → Cloud Functions
