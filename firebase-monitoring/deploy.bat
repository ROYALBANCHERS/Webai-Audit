@echo off
REM Firebase Deployment Script for WebAI Audit
REM Run this script after logging in with: firebase login

echo ====================================
echo WebAI Audit - Firebase Deployment
echo ====================================
echo.

REM Check if user is logged in
echo Checking Firebase login status...
firebase projects:list >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Not logged in to Firebase!
    echo.
    echo Please run: firebase login
    echo Then run this script again.
    pause
    exit /b 1
)

echo [SUCCESS] Logged in to Firebase
echo.

REM Navigate to project directory
cd /d "%~dp0"

REM Show current project
echo Current Firebase project:
firebase projects:list
echo.

echo ====================================
echo Deployment Options:
echo ====================================
echo.
echo 1. Deploy Everything (Functions + Hosting + Firestore)
echo 2. Deploy Functions Only
echo 3. Deploy Hosting Only
echo 4. Deploy Firestore Rules Only
echo 5. Cancel
echo.

set /p choice="Select option (1-5): "

if "%choice%"=="1" (
    echo.
    echo Deploying Everything...
    firebase deploy
) else if "%choice%"=="2" (
    echo.
    echo Deploying Functions...
    firebase deploy --only functions
) else if "%choice%"=="3" (
    echo.
    echo Deploying Hosting...
    firebase deploy --only hosting
) else if "%choice%"=="4" (
    echo.
    echo Deploying Firestore Rules...
    firebase deploy --only firestore:rules
) else if "%choice%"=="5" (
    echo Deployment cancelled.
    pause
    exit /b 0
) else (
    echo Invalid option!
    pause
    exit /b 1
)

echo.
echo ====================================
echo Deployment Complete!
echo ====================================
echo.
echo Your app is live at:
echo - Hosting: https://webai-audit.web.app
echo - Functions: asia-south1-webai-audit.cloudfunctions.net
echo.
pause
