# PHP Installation Script for Windows

Write-Host "=== PHP & Composer Installation Script ===" -ForegroundColor Cyan
Write-Host ""

# Create directories
Write-Host "Creating directories..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "C:\php" | Out-Null
New-Item -ItemType Directory -Force -Path "C:\temp" | Out-Null

# Download PHP
Write-Host "Downloading PHP 8.2..." -ForegroundColor Yellow
$phpUrl = "https://windows.php.net/downloads/releases/archives/php-8.2.18-nts-Win32-vs16-x64.zip"
$phpZip = "C:\temp\php.zip"

try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip -UseBasicParsing
    Write-Host "PHP downloaded successfully" -ForegroundColor Green
} catch {
    Write-Host "Failed to download PHP: $_" -ForegroundColor Red
    Write-Host "Please download manually from: https://windows.php.net/download/"
    exit 1
}

# Extract PHP
Write-Host "Extracting PHP..." -ForegroundColor Yellow
try {
    Expand-Archive -Path $phpZip -DestinationPath "C:\php" -Force
    Write-Host "PHP extracted successfully" -ForegroundColor Green
} catch {
    Write-Host "Failed to extract PHP: $_" -ForegroundColor Red
    exit 1
}

# Create php.ini
Write-Host "Configuring PHP..." -ForegroundColor Yellow
$phpIni = @"
extension_dir = "ext"
extension=curl
extension=fileinfo
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
date.timezone = "UTC"
"@
Set-Content -Path "C:\php\php.ini" -Value $phpIni

# Download Composer
Write-Host "Downloading Composer..." -ForegroundColor Yellow
$composerUrl = "https://getcomposer.org/installer"
$composerSetup = "C:\temp\composer-setup.php"

try {
    Invoke-WebRequest -Uri $composerUrl -OutFile $composerSetup -UseBasicParsing

    # Install Composer using PHP
    & "C:\php\php.exe" $composerSetup --quiet --install-dir="C:\php"

    # Create composer.bat
    Set-Content -Path "C:\php\composer.bat" -Value "@php C:\php\composer.phar %*"
    Write-Host "Composer installed successfully" -ForegroundColor Green
} catch {
    Write-Host "Failed to install Composer: $_" -ForegroundColor Red
}

# Add to PATH (requires manual action or admin rights)
Write-Host ""
Write-Host "=== Installation Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "PHP installed to: C:\php" -ForegroundColor Cyan
Write-Host "Composer installed to: C:\php\composer.phar" -ForegroundColor Cyan
Write-Host ""
Write-Host "IMPORTANT: Add C:\php to your system PATH:" -ForegroundColor Yellow
Write-Host "1. Search for 'Environment Variables' in Windows" -ForegroundColor White
Write-Host "2. Click 'Edit the system environment variables'" -ForegroundColor White
Write-Host "3. Click 'Environment Variables'" -ForegroundColor White
Write-Host "4. Under 'System variables', find 'Path' and click 'Edit'" -ForegroundColor White
Write-Host "5. Add 'C:\php' and click OK" -ForegroundColor White
Write-Host ""
Write-Host "After adding to PATH, restart your terminal and run setup.bat again" -ForegroundColor Cyan

# Test installation
Write-Host ""
Write-Host "Testing PHP installation..." -ForegroundColor Yellow
& "C:\php\php.exe" -v

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
