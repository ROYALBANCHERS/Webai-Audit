@echo off
echo ================================================
echo   WebAI Auditor - InfinityFree Upload Script
echo ================================================
echo.
echo Your InfinityFree Details:
echo   Domain: 5howdxve.infinityfree.com
echo   FTP Host: ftpupload.net
echo   FTP User: if0_41366078
echo.
echo ================================================
echo.

REM Files to upload
set FILES=api.php index.php .htaccess
set SOURCE= infinityfree-backend

echo [Step 1] Opening FileZilla...
echo.
echo Please connect with:
echo   Host: ftpupload.net
echo   User: if0_41366078
echo   Pass: [Your InfinityFree Password]
echo.
echo Then upload these 3 files to htdocs folder:
echo   - api.php
echo   - index.php
echo   - .htaccess
echo.
pause

REM Open FileZilla if installed
start "" "C:\Program Files\FileZilla FTP Client\filezilla.exe" 2>nul
if errorlevel 1 (
    start "" "C:\Program Files (x86)\FileZilla FTP Client\filezilla.exe" 2>nul
)

REM Open source folder
explorer %CD%\infinityfree-backend

echo.
echo ================================================
echo   Follow these steps:
echo ================================================
echo.
echo 1. FileZilla में connect करें (details upar हैं)
echo 2. Right side में 'htdocs' folder खोलें
echo 3. Left side में से 3 files select करें
echo 4. Right side में drag करें
echo 5. Wait for upload complete
echo.
echo After upload, test this URL:
echo   https://5howdxve.infinityfree.com/api/health
echo.
echo ================================================
pause