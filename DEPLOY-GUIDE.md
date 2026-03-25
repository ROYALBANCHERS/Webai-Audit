# WebAI Auditor - Quick Deploy Guide

## Deploy to Cloudflare Pages (Manual)

### Step 1: Make Changes
Edit your files and commit:
```bash
git add .
git commit -m "your message"
git push origin main
```

### Step 2: Deploy to Cloudflare
```bash
cd C:\Users\Shamiksha\repo_temp
wrangler pages deploy . --project-name=webai-audit
```

### Done! Your site is live at:
- https://webai-audit.pages.dev
- https://webai-audit.pages.dev/gov-jobs.html

---

## Note:
- No API token needed for manual deployment
- Wrangler is already authenticated on your machine
- GitHub Actions auto-deploy has been removed
