# Selenium Setup for WebAI Auditor

This guide explains how to set up Selenium for browser automation in the WebAI Auditor.

## Why Selenium?

Selenium enables the crawler to:
- Execute JavaScript
- Capture screenshots
- Test interactive elements
- Measure page load times
- Access dynamically loaded content

## Option 1: Selenium Standalone Server

### Installation

1. **Install Java Runtime (JRE)**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install default-jre

   # macOS
   brew install openjdk

   # Windows
   # Download from java.com
   ```

2. **Download Selenium Server**
   ```bash
   wget https://github.com/SeleniumHQ/selenium/releases/download/selenium-4.20.0/selenium-server-4.20.0.jar
   ```

3. **Download ChromeDriver**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install chromium-chromedriver

   # macOS
   brew install chromedriver

   # Windows
   # Download from https://chromedriver.chromium.org/
   ```

4. **Start Selenium Server**
   ```bash
   java -jar selenium-server-4.20.0.jar standalone
   ```

   The server will start on `http://localhost:4444`

### Configuration

Update `backend/.env`:
```env
SELENIUM_HOST=http://localhost:4444
```

## Option 2: Docker Selenium (Recommended for Development)

### Using Docker Compose

Create `docker-compose.yml`:
```yaml
version: '3.8'
services:
  selenium:
    image: selenium/standalone-chrome:latest
    ports:
      - "4444:4444"
    environment:
      - SE_NODE_MAX_SESSIONS=5
    volumes:
      - /dev/shm:/dev/shm
```

Start Selenium:
```bash
docker-compose up -d
```

## Option 3: ChromeDriver Direct (No Selenium Server)

For simpler setups, you can use ChromeDriver directly without Selenium Server.

### Installation

```bash
# Ubuntu/Debian
sudo apt-get install chromium-chromedriver

# macOS
brew install chromedriver

# Windows
# Download and add to PATH
```

### Configuration

The CrawlerService will automatically detect and use ChromeDriver if available.

## Testing Selenium Connection

```bash
curl http://localhost:4444/status
```

Expected response:
```json
{
  "value": {
    "ready": true,
    "message": "Selenium 4.x is ready"
  }
}
```

## Troubleshooting

### Port Already in Use

```bash
# Find process using port 4444
lsof -i :4444

# Kill the process
kill -9 <PID>
```

### ChromeDriver Version Mismatch

Ensure ChromeDriver version matches your Chrome browser version:
```bash
google-chrome --version
chromedriver --version
```

### Permission Denied (Linux)

```bash
chmod +x /usr/bin/chromedriver
```

### Headless Mode Issues

For servers without display, ensure headless mode is enabled in `backend/config/auditor.php`:
```php
'headless' => env('BROWSER_HEADLESS', true),
```

## Production Considerations

1. **Use Docker** for consistent environments
2. **Limit concurrent sessions** to avoid memory issues
3. **Set timeouts** appropriately for your use case
4. **Monitor resources** - Selenium can be memory-intensive
5. **Consider alternatives** like Puppeteer for lighter weight needs

## Alternative: Puppeteer

If Selenium is too heavy, consider using Puppeteer with the `chrome-php/chrome` package:

```bash
composer require chrome-php/chrome
```

This provides similar functionality with less overhead.
