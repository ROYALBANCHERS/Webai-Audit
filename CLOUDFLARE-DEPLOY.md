# Cloudflare Pages Deployment Guide
## WebAI Auditor - Custom Domain Setup

---

## Step 1: Connect GitHub to Cloudflare Pages

1. **Login to Cloudflare Dashboard:**
   https://dash.cloudflare.com/

2. **Go to "Workers & Pages"**
   - Click on "Pages" tab

3. **Click "Create a project"**
   - Select "Connect to Git"
   - Choose GitHub
   - Authorize Cloudflare (if needed)

4. **Select Repository:**
   - Find: `ROYALBANCHERS/Webai-Audit`
   - Click "Begin setup"

---

## Step 2: Configure Build Settings

| Setting | Value |
|---------|-------|
| Framework preset | **None** |
| Build command | `echo "No build needed"` |
| Build output directory | `.` (root directory) |
| Root directory | `/` (leave empty) |

---

## Step 3: Environment Variables (Optional)

Add these if needed:

| Variable | Value |
|----------|-------|
| NODE_VERSION | 18 |

---

## Step 4: Deploy

Click **"Deploy"** and wait for deployment to complete.

Your site will be available at:
```
https://webai-audit.pages.dev
```

---

## Step 5: Add Custom Domain

1. **After deployment, go to:**
   Workers & Pages → Your Project → Custom Domains

2. **Click "Set up a custom domain"**

3. **Enter your domain:**
   - Example: `jobs.yourdomain.com`
   - Or root: `yourdomain.com`

4. **Click "Continue"**

---

## Step 6: Update DNS (Required for Custom Domain)

### Option A: If domain is on Cloudflare

DNS will be **automatically configured** ✅

### Option B: If domain is elsewhere (GoDaddy, Namecheap, etc.)

Add these DNS records:

| Type | Name | Content | Proxy |
|------|------|---------|-------|
| CNAME | jobs (or subdomain) | webai-audit.pages.dev | ✅ Proxied |

---

## Custom Domain Examples

| Your Domain | Access URL |
|-------------|------------|
| `jobs.yourdomain.com` | https://jobs.yourdomain.com/gov-jobs.html |
| `yourdomain.com` | https://yourdomain.com/gov-jobs.html |
| `webai.yourdomain.com` | https://webai.yourdomain.com/gov-jobs.html |

---

## Step 7: SSL Certificate (Automatic)

Cloudflare **automatically** provides:
✅ SSL Certificate
✅ HTTPS redirection
✅ DDoS Protection
✅ CDN (Global)

---

## Quick Deploy Commands (Optional)

If you prefer CLI deployment:

```bash
# Install Wrangler CLI
npm install -g wrangler

# Login to Cloudflare
wrangler login

# Deploy
wrangler pages deploy . --project-name=webai-auditor
```

---

## Post-Deployment Checklist

- [ ] Site loads at `*.pages.dev`
- [ ] Custom domain added
- [ ] DNS records configured
- [ ] HTTPS working
- [ ] gov-jobs.html loads jobs
- [ ] Search/Analyze works
- [ ] Apply buttons redirect correctly

---

## Your Links After Deployment

| Page | Cloudflare URL |
|------|----------------|
| Home | `https://webai-audit.pages.dev/` |
| Gov Jobs | `https://webai-audit.pages.dev/gov-jobs.html` |
| Custom Domain | `https://your-domain.com/gov-jobs.html` |

---

## Troubleshooting

**Site not loading?**
- Check DNS propagation (can take 24-48 hours)
- Clear browser cache
- Check Cloudflare SSL/TLS mode (Full recommended)

**API not working?**
- Check _headers file is uploaded
- Verify CORS settings

**Jobs not loading?**
- Check api/gov-jobs.json path
- Verify file exists in deployment

---

## Benefits of Cloudflare Pages

| Feature | Benefit |
|---------|---------|
| **FREE** | No cost for basic hosting |
| **Global CDN** | Fast loading worldwide |
| **SSL** | Free HTTPS certificates |
| **DDoS Protection** | Built-in security |
| **Instant Rollback** | Easy deployment history |
| **Preview Deployments** | Test before production |
| **Custom Domains** | Unlimited domains |

---

Created: 2026-03-19
