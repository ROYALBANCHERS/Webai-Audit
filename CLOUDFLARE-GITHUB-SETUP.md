# Cloudflare Pages + GitHub Auto-Deploy Setup

## ✅ Already Done:

1. ✅ Cloudflare Pages project created
2. ✅ First deployment complete
3. ✅ Website is LIVE

## 🔗 Your Website URL:

```
https://webai-audit.pages.dev
```

Direct gov-jobs page:
```
https://webai-audit.pages.dev/gov-jobs.html
```

---

## 📋 Step-by-Step: GitHub Auto-Deploy Setup

### Step 1: Create Cloudflare API Token

1. **Go to Cloudflare Dashboard:**
   https://dash.cloudflare.com/profile/api-tokens

2. **Click "Create Token"**

3. **Use "Cloudflare Pages" template or create custom:**
   - Permissions: `Account - Cloudflare Pages - Edit`
   - Account Resources: `Include - All accounts`
   - OR: Specific Account: `cf3ed02320ca71c06d27930d2dfdec0e`

4. **Click "Continue to summary" → "Create Token"**

5. **COPY the token** (looks like: `abc123def456...`)

---

### Step 2: Add Secrets to GitHub

1. **Go to your GitHub repository:**
   https://github.com/ROYALBANCHERS/Webai-Audit/settings/secrets

2. **Add "New repository secret"**

3. **Add these TWO secrets:**

   | Name | Value |
   |------|-------|
   | `CLOUDFLARE_API_TOKEN` | Paste token from Step 1 |
   | `CLOUDFLARE_ACCOUNT_ID` | `cf3ed02320ca71c06d27930d2dfdec0e` |

4. **Click "Add secret" for each**

---

### Step 3: Test Auto-Deploy

**That's it!** Now whenever you push to `main` branch:

```
git push origin main
```

GitHub Actions will automatically deploy to Cloudflare Pages! ✅

---

## 🌐 Quick Access Links

| Purpose | Link |
|---------|------|
| Your Website | https://webai-audit.pages.dev |
| Gov Jobs Page | https://webai-audit.pages.dev/gov-jobs.html |
| Cloudflare Dashboard | https://dash.cloudflare.com/cf3ed02320ca71c06d27930d2dfdec0e/pages/view/webai-audit |
| GitHub Secrets | https://github.com/ROYALBANCHERS/Webai-Audit/settings/secrets |
| GitHub Actions | https://github.com/ROYALBANCHERS/Webai-Audit/actions |

---

## 🔧 Manual Deploy (Anytime)

If you want to deploy manually from your computer:

```bash
cd C:\Users\Shamiksha\repo_temp
wrangler pages deploy . --project-name=webai-audit
```

---

## 📊 Current Status

| Item | Status |
|------|--------|
| Cloudflare Project | ✅ Created |
| First Deployment | ✅ Complete |
| Website Live | ✅ https://webai-audit.pages.dev |
| GitHub Workflow | ✅ Created |
| API Token | ⏳ Need to add to GitHub |
| Account ID | ✅ cf3ed02320ca71c06d27930d2dfdec0e |

---

## 🚀 After Setup

Every time you update code:
1. `git push origin main`
2. GitHub Actions runs automatically
3. Cloudflare Pages deploys
4. Website updates in ~30 seconds

---

## 📝 Notes:

- **Custom Domain:** If you add a custom domain later, go to Cloudflare Dashboard → Pages → Custom Domains
- **Branch Preview:** Each branch gets its own preview URL
- **Rollback:** You can rollback to any previous deployment in Cloudflare Dashboard

---

Created: 2026-03-19
Account ID: cf3ed02320ca71c06d27930d2dfdec0e
Project: webai-audit
