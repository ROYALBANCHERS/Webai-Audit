# Government Jobs MCP Server - Setup Guide

## Overview

This MCP (Model Context Protocol) server automatically scrapes and analyzes government job websites, providing real-time updates to your WebAI Auditor application.

## Features

- **Automatic Scraping**: Scrapes government websites for latest job notifications
- **User Custom Websites**: Users can add their own trusted government websites
- **Auto-Update**: Built-in cron job/webhook support for automatic updates
- **Real-time API**: Vercel serverless function API endpoints
- **LocalStorage**: Saves user preferences locally

## Architecture

```
┌─────────────────┐     ┌──────────────┐     ┌──────────────────┐
│   User Browser  │────▶│  Vercel API  │────▶│  Gov Websites    │
│  (gov-jobs.html)│     │  (MCP Server)│     │  (SSC, UPSC...)  │
└─────────────────┘     └──────────────┘     └──────────────────┘
        │                        │
        │                        ▼
        │                 ┌──────────────┐
        └────────────────▶│   JSON Data  │
                         │  (gov-jobs)  │
                         └──────────────┘
                                ▲
                                │
                        ┌──────────────┐
                        │ GitHub Actions│
                        │  (Cron Job)  │
                        └──────────────┘
```

## Setup Instructions

### 1. Deploy to Vercel

```bash
# Install Vercel CLI
npm install -g vercel

# Deploy
cd vercel-backend
vercel deploy
```

Update the `API_BASE_URL` in `gov-jobs.html` with your Vercel URL.

### 2. Configure GitHub Actions (Auto-Update)

The workflow automatically runs every 6 hours. You can:

1. **Manual Trigger**: Go to Actions → Government Jobs Auto-Update → Run workflow
2. **Schedule**: Already configured (every 6 hours)
3. **Custom Domains**: Add comma-separated domains when triggering manually

### 3. Set Up Secrets (Optional)

In your GitHub repository settings → Secrets and variables → Actions, add:

- `WEBHOOK_SECRET`: Your webhook secret key
- `WEBHOOK_URL`: Your webhook endpoint URL (for external notifications)
- `DISCORD_WEBHOOK`: Discord webhook for notifications

### 4. Environment Variables for Vercel

```bash
vercel env add WEBHOOK_SECRET
```

## API Endpoints

### Get All Jobs
```http
GET /api/gov-jobs
```

### Get Jobs by Domain
```http
GET /api/gov-jobs?domain=ssc.gov.in
```

### Get Jobs by Category
```http
GET /api/gov-jobs?category=ssc
```

### Add Custom Website
```http
POST /api/gov-jobs
Content-Type: application/json

{
  "url": "https://example.gov.in",
  "name": "Organization Name",
  "category": "general"
}
```

### Scrape Websites
```http
POST /api/gov-jobs/scrape
Content-Type: application/json

{
  "domains": ["ssc.gov.in", "upsc.gov.in"],
  "customUrls": ["https://custom.gov.in"]
}
```

### Get User Websites
```http
GET /api/gov-jobs/websites
```

### Remove Website
```http
DELETE /api/gov-jobs/websites?id=site-123
```

### Trigger Webhook (for cron jobs)
```http
POST /api/gov-jobs/webhook
Content-Type: application/json

{
  "secret": "YOUR_WEBHOOK_SECRET",
  "domains": ["ssc.gov.in", "upsc.gov.in"]
}
```

### MCP Server Info
```http
GET /api/gov-jobs/mcp
```

### Get Categories
```http
GET /api/gov-jobs/categories
```

## User Features

### Add Trusted Website

1. Click "Add Your Trusted Website" button
2. Enter the government website URL
3. (Optional) Add a custom name
4. Select a category
5. Enable "Auto-update via MCP Server" for automatic updates

### Live Job Feed

- Shows all jobs from all sources
- Filter by category
- Auto-refreshes every 30 minutes
- New jobs highlighted with green badge

## Cron Job Setup

### Option 1: GitHub Actions (Recommended)

Already configured in `.github/workflows/gov-jobs-auto-update.yml`

To modify schedule, edit the cron expression:
```yaml
schedule:
  - cron: '0 */6 * * *'  # Every 6 hours
```

### Option 2: External Cron Service

Use cron-job.org, EasyCron, or similar:

```bash
# Trigger webhook every hour
curl -X POST "https://your-vercel-app.vercel.app/api/gov-jobs/webhook" \
  -H "Content-Type: application/json" \
  -d '{"secret":"YOUR_SECRET","domains":["ssc.gov.in","upsc.gov.in"]}'
```

### Option 3: GitHub Actions as Webhook

Use this URL for external triggers:
```
https://api.github.com/repos/royalbanchers/Webai-Audit/actions/workflows/gov-jobs-auto-update.yml/dispatches
```

With headers:
```json
{
  "ref": "main",
  "inputs": { "domains": "ssc.gov.in,upsc.gov.in" }
}
```

## Local Development

### Run MCP Server Locally

```bash
cd vercel-backend
npm install
node api/gov-jobs.js
```

Or use the included test server:
```bash
node test-server.js
```

## Troubleshooting

### Jobs Not Updating

1. Check if GitHub Actions is running
2. Verify API_BASE_URL in gov-jobs.html
3. Check browser console for errors
4. Clear localStorage and refresh

### Auto-Update Not Working

1. Verify WEBHOOK_SECRET is set
2. Check MCP server is deployed
3. Test webhook endpoint manually
4. Check Vercel function logs

### Custom Website Not Scraping

1. Verify URL is valid and accessible
2. Some government sites may block scraping
3. Check CORS settings
4. Use manual analysis as fallback

## Supported Websites

Default supported domains:
- `ssc.gov.in` - Staff Selection Commission
- `upsc.gov.in` - Union Public Service Commission
- `ibps.in` - Institute of Banking Personnel Selection
- `rrb.gov.in` - Railway Recruitment Board
- `bank.sbi` - State Bank of India
- `licindia.in` - Life Insurance Corporation

Users can add any government website (`.gov.in`, `.gov`, `.nic.in` domains).

## License

MIT License - Part of WebAI Auditor Project

## Contributing

Contributions by **Rishabh** and the WebAI Auditor team.
