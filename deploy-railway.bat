@echo off
REM WebAI Auditor - Railway Deployment Script
REM This script deploys the backend to Railway

echo ========================================
echo  WebAI Auditor - Railway Deployment
echo ========================================
echo.

echo Step 1: Checking Railway CLI...
railway --version >nul 2>&1
if errorlevel 1 (
    echo [!] Railway CLI not found. Installing...
    npm install -g @railway/cli
)

echo.
echo Step 2: Login to Railway...
echo [!] Please login in the browser that opens...
railway login

echo.
echo Step 3: Creating new project...
railway init --name webai-auditor-backend

echo.
echo Step 4: Deploying to Railway...
railway up

echo.
echo ========================================
echo  Deployment Complete!
echo ========================================
echo.
echo Getting your project URL...
railway domain --json 2>nul
echo.

echo Your backend is now live!
echo.
echo NEXT STEP:
echo 1. Copy the URL above (e.g., https://webai-auditor-backend.up.railway.app)
echo 2. Update gov-jobs.html line ~499:
echo    const railwayUrl = 'https://YOUR-URL.up.railway.app';
echo 3. Commit and push gov-jobs.html
echo.

pause
