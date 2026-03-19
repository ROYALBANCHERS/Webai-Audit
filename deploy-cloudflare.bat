@echo off
REM ========================================
REM Cloudflare Pages Auto-Deploy Script
REM WebAI Auditor - Government Jobs
REM ========================================

echo.
echo ============================================
echo    Cloudflare Pages Deployment Tool
echo ============================================
echo.

REM Check if Node.js is installed
where node >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Node.js not installed!
    echo Please install Node.js from: https://nodejs.org/
    pause
    exit /b 1
)

echo [1/4] Checking Wrangler CLI...

REM Check if Wrangler is installed
where wrangler >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Wrangler not found. Installing...
    npm install -g wrangler
)

echo [2/4] Logging into Cloudflare...
echo.
echo Browser will open for authentication.
echo Please login to your Cloudflare account and authorize Wrangler.
echo.
wrangler login

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Login failed! Please try again.
    pause
    exit /b 1
)

echo.
echo [3/4] Deploying to Cloudflare Pages...
echo.

REM Deploy to Cloudflare Pages
wrangler pages deploy . --project-name=webai-audit

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Deployment failed!
    echo Please check the errors above.
    pause
    exit /b 1
)

echo.
echo [4/4] Deployment Successful!
echo.
echo ============================================
echo    Your website is now LIVE!
echo ============================================
echo.
echo Your website URL: https://webai-audit.pages.dev
echo.
echo To add custom domain:
echo 1. Go to: https://dash.cloudflare.com
echo 2. Workers & Pages -> webai-audit
echo 3. Custom Domains -> Add Domain
echo.
echo Press any key to open your website...
pause >nul

REM Open the deployed website
start https://webai-audit.pages.dev/gov-jobs.html

echo.
echo Thank you for using WebAI Auditor!
echo.
