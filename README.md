# WebAI Auditor - Complete Website Analysis Tool

## Table of Contents
1. [About WebAI Auditor](#about-webai-auditor)
2. [Features Overview](#features-overview)
3. [Technology Stack](#technology-stack)
4. [Project Structure](#project-structure)
5. [Installation Guide](#installation-guide)
6. [Frontend Pages](#frontend-pages)
7. [Backend Services](#backend-services)
8. [API Documentation](#api-documentation)
9. [PhonePe Payment Integration](#phonepe-payment-integration)
10. [Government Jobs Monitoring](#government-jobs-monitoring-)
11. [Tool Marketplace](#tool-marketplace)
12. [Blog Content](#blog-content)
13. [Deployment Guide](#deployment-guide)
14. [Contributing](#contributing)
15. [License](#license)

---

## About WebAI Auditor

**WebAI Auditor** is a comprehensive, 100% FREE website analysis tool built with PHP (Laravel) backend and vanilla HTML/CSS/JavaScript frontend. It provides in-depth analysis of websites including SEO optimization, tech stack detection, competitor analysis, performance metrics, and much more.

### Why WebAI Auditor?

- **Completely FREE**: No credit limits, no subscription required
- **Unlimited Analyses**: Analyze as many websites as you want
- **Deep Analysis**: Crawls multiple pages with configurable depth
- **Tech Stack Detection**: Automatically identifies frameworks, libraries, CMS, analytics
- **SEO Insights**: Get actionable SEO recommendations
- **Competitor Analysis**: Find and analyze competitor websites
- **GitHub Integration**: Discover similar open-source projects
- **Tool Marketplace**: Create and share custom analysis tools
- **Government Jobs**: Real-time monitoring of government job postings 🆕
- **Multi-language**: Available in English and Hindi

### Vision

WebAI Auditor aims to democratize website analysis by providing enterprise-grade tools to everyone - from individual developers and small businesses to large enterprises. We believe that understanding your website's performance, SEO health, and technology stack shouldn't require expensive subscriptions.

---

## Features Overview

### Core Analysis Features

#### 1. Website Crawling
- Multi-page crawling with depth control (1-10 pages)
- Headless browser automation for JavaScript-heavy sites
- Screenshot capture for visual analysis
- Link integrity checking
- Button functionality testing
- Error detection and reporting

#### 2. Tech Stack Detection
Automatically detects:
- **Frontend Frameworks**: React, Vue, Angular, Next.js, Nuxt.js, Svelte
- **Backend Frameworks**: Laravel, Django, Rails, Express, Spring
- **CSS Frameworks**: Bootstrap, Tailwind, Bulma, Foundation
- **JavaScript Libraries**: jQuery, Lodash, Axios, Moment.js
- **Analytics**: Google Analytics, Mixpanel, Hotjar, Amplitude
- **CMS Platforms**: WordPress, Shopify, Drupal, Joomla, Wix
- **E-commerce**: WooCommerce, Magento, BigCommerce
- **CDNs**: Cloudflare, AWS CloudFront, Fastly
- **Fonts**: Google Fonts, Adobe Fonts, Typekit
- **Build Tools**: Webpack, Vite, Parcel, Rollup

#### 3. SEO Analysis
Comprehensive SEO checks including:
- Title tag presence and length (recommended: 50-60 characters)
- Meta description optimization (150-160 characters)
- Heading structure (H1-H6 hierarchy)
- Image alt attributes for accessibility
- Canonical URL configuration
- robots.txt analysis
- sitemap.xml detection
- Schema.org markup
- Page load speed analysis
- Mobile responsiveness check
- SSL/HTTPS verification
- Internal linking structure
- External link quality

#### 4. Competitor Analysis
- Find competitors by industry/niche
- Compare tech stacks
- Feature comparison matrix
- Traffic estimation
- Similar website discovery
- Keyword overlap analysis

#### 5. Performance Metrics
- Page load time
- Total page size
- Number of requests
- Resource breakdown (CSS, JS, images, fonts)
- Largest Contentful Paint (LCP)
- First Input Delay (FID)
- Cumulative Layout Shift (CLS)

### Platform Features

#### User Dashboard
- Personal audit history
- Quick analysis access
- Statistics overview
- Recent activity feed
- Custom tool management

#### Tool Marketplace
- Browse community-created tools
- Filter by category and popularity
- Tool ratings and reviews
- One-click tool installation
- Custom tool creation with visual builder

#### Custom Tool Builder
- 3-step tool creation process
- Visual interface designer
- Custom JavaScript logic
- API integration capabilities
- Tool preview before publishing

#### Multi-language Support
- English (primary)
- Hindi (complete translation)
- Easy language switching

#### Support & Donations
- PhonePe payment integration
- Multiple donation tiers
- Secure payment processing
- Receipt generation

#### Government Jobs Monitoring 🆕
- **Automatic Crawling**: Hourly crawling of government job websites
- **Real-time Alerts**: Email/Push/SMS notifications for matching jobs
- **Smart Matching**: AI-powered job matching based on user preferences
- **Categories**: SSC, Banking, Railway, UPSC, Teaching, Defense, Police, Engineering
- **Search & Filter**: Advanced filtering by category, department, qualification
- **Expiring Soon**: Highlight jobs closing within 7 days
- **Statistics Dashboard**: Real-time job counts and analytics
- **Pre-configured Sources**: Employment News, SSC, UPSC, Free Job Alert

---

## Technology Stack

### Backend
- **Framework**: Laravel 11.x
- **Language**: PHP 8.2+
- **Database**: MySQL/MariaDB
- **Browser Automation**: php-webdriver (Selenium)
- **HTML Parsing**: Symfony DomCrawler
- **HTTP Client**: Guzzle
- **GitHub API**: knplabs/github-api

### Frontend
- **HTML5**: Semantic markup
- **CSS3**: Tailwind CSS (via CDN)
- **JavaScript**: Vanilla ES6+
- **Icons**: Font Awesome 6.5
- **No Build Process**: Pure HTML/CSS/JS for simplicity

### Payment Gateway
- **PhonePe**: Indian payment gateway
- **UPI Support**: All major UPI apps
- **Card Payments**: Visa, Mastercard, RuPay
- **Netbanking**: All major Indian banks
- **Wallets**: PhonePe, Paytm, Amazon Pay

---

## Project Structure

```
webai-auditor-php/
│
├── backend/                          # Laravel Backend Application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuditController.php      # Main audit endpoints
│   │   │   │   └── SubscriptionController.php
│   │   │   └── Middleware/
│   │   │       └── Cors.php                 # CORS handling
│   │   ├── Services/
│   │   │   ├── CrawlerService.php           # Website crawling engine
│   │   │   ├── TechStackService.php         # Technology detection
│   │   │   ├── SeoService.php               # SEO analysis
│   │   │   ├── CompetitorService.php        # Competitor finder
│   │   │   ├── GitHubService.php            # GitHub API integration
│   │   │   └── SubscriptionService.php      # Subscription management
│   │   └── Models/
│   │       ├── Audit.php
│   │       ├── Issue.php
│   │       ├── TechStack.php
│   │       ├── Competitor.php
│   │       ├── TrackedSite.php
│   │       ├── Subscription.php
│   │       ├── User.php
│   │       └── UserSubscription.php
│   ├── config/
│   │   ├── phonepe.xml                      # PhonePe configuration
│   │   ├── phonepe-config.xsd               # XML schema
│   │   └── PHONEPE_README.md                # PhonePe docs
│   ├── database/
│   │   └── migrations/
│   │       ├── 2024_01_01_create_audits_table.php
│   │       ├── 2024_01_02_create_issues_table.php
│   │       ├── 2024_01_03_create_tech_stacks_table.php
│   │       ├── 2024_01_04_create_competitors_table.php
│   │       ├── 2024_01_05_create_tracked_sites_table.php
│   │       ├── 2024_01_06_create_users_table.php
│   │       ├── 2024_01_07_create_subscriptions_table.php
│   │       └── 2024_01_08_create_user_subscriptions_table.php
│   ├── routes/
│   │   └── api.php                          # API route definitions
│   ├── api/
│   │   ├── payment.php                      # Payment API endpoint
│   │   └── storage/
│   │       └── transactions.json            # Transaction records
│   ├── .env.example                        # Environment template
│   ├── composer.json                       # PHP dependencies
│   ├── artisan                             # Laravel CLI
│   └── Dockerfile                          # Docker configuration
│
├── frontend/                            # Frontend Application
│   ├── index.html                        # Main landing page
│   ├── features.html                     # Features page
│   ├── tools.html                        # Tool marketplace
│   ├── create-tool.html                  # Tool builder
│   ├── my-tools.html                     # User's tools
│   ├── dashboard.html                    # User dashboard
│   ├── login.html                        # Login page
│   ├── register.html                     # Registration page
│   ├── pricing.html                      # Support/Donation page
│   ├── payment-success.html              # Payment success
│   ├── payment-failure.html              # Payment failure
│   ├── profile.html                      # User profile
│   ├── settings.html                     # Account settings
│   ├── billing.html                      # Billing management
│   ├── blog.html                         # Blog listing
│   ├── about.html                        # About us
│   ├── contact.html                      # Contact page
│   ├── privacy.html                      # Privacy policy
│   ├── terms.html                        # Terms of service
│   └── blog/                             # Blog articles
│       ├── seo-guide.html                # SEO guide
│       ├── tech-stack.html               # Tech stack detection
│       ├── competitor-analysis.html      # Competitor analysis
│       ├── website-speed.html            # Website speed optimization
│       ├── mobile-optimization.html      # Mobile SEO
│       ├── backlink-strategy.html        # Backlink strategies
│       ├── content-marketing.html        # Content marketing
│       ├── social-media-integration.html # Social media integration
│       ├── analytics-setup.html          # Analytics setup
│       ├── security-checklist.html       # Security checklist
│       ├── conversion-optimization.html  # Conversion optimization
│       └── local-seo.html                # Local SEO guide
│
├── storage/                             # Application Storage
│   ├── screenshots/                      # Website screenshots
│   └── exports/                          # Exported reports
│
├── README.md                            # This file
├── Selenium-Setup.md                    # Selenium setup guide
└── setup.sh                             # Installation script
```

---

## Installation Guide

### Prerequisites

Before installing WebAI Auditor, ensure you have:

1. **PHP 8.2 or higher** - [Download PHP](https://www.php.net/downloads)
2. **Composer** - [Download Composer](https://getcomposer.org/download/)
3. **MySQL/MariaDB 5.7+** - [Download MySQL](https://dev.mysql.com/downloads/mysql/)
4. **Node.js 16+** (for Selenium) - [Download Node.js](https://nodejs.org/)
5. **Git** - [Download Git](https://git-scm.com/downloads)

### Step 1: Clone the Repository

```bash
git clone https://github.com/ROYALBANCHERS/webai-auditor.git
cd webai-auditor-php
```

### Step 2: Install Backend Dependencies

```bash
cd backend
composer install
```

### Step 3: Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `backend/.env` with your configuration:

```env
APP_NAME="WebAI Auditor"
APP_ENV=local
APP_KEY=base64:generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webai_auditor
DB_USERNAME=root
DB_PASSWORD=your-password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Selenium Configuration
SELENIUM_HOST=http://localhost:4444
MAX_CRAWL_DEPTH=3
CRAWL_TIMEOUT=30

# API Keys (Optional)
GITHUB_TOKEN=your-github-token
GOOGLE_SEARCH_API_KEY=your-google-api-key
GOOGLE_SEARCH_ENGINE_ID=your-search-engine-id
```

### Step 4: Run Database Migrations

```bash
php artisan migrate
```

### Step 5: Start Backend Server

```bash
php artisan serve
```

The backend API will be available at `http://localhost:8000`

### Step 6: Start Frontend Server

**Option A: Using Python (Recommended)**

```bash
cd frontend
python -m http.server 8080
```

**Option B: Using PHP**

```bash
cd frontend
php -S localhost:8080
```

**Option C: Using Node.js**

```bash
cd frontend
npx serve -p 8080
```

The frontend will be available at `http://localhost:8080`

### Step 7: Selenium Setup (Optional)

For full browser automation capabilities, install Selenium:

**On Linux:**
```bash
# Install ChromeDriver
sudo apt-get install chromium-chromedriver

# Download and run Selenium Server
wget https://github.com/SeleniumHQ/selenium/releases/download/selenium-4.20.0/selenium-server-4.20.0.jar
java -jar selenium-server-4.20.0.jar standalone
```

**On macOS:**
```bash
# Install ChromeDriver via Homebrew
brew install chromedriver

# Download and run Selenium Server
curl -O https://github.com/SeleniumHQ/selenium/releases/download/selenium-4.20.0/selenium-server-4.20.0.jar
java -jar selenium-server-4.20.0.jar standalone
```

**On Windows:**
1. Download ChromeDriver from https://chromedriver.chromium.org/
2. Download Selenium Server from https://github.com/SeleniumHQ/selenium/releases
3. Run: `java -jar selenium-server-4.20.0.jar standalone`

---

## Frontend Pages

### Main Pages

#### index.html - Home Page
**URL**: `/`

Main landing page with:
- Hero section with URL input
- Analysis options (SEO, Tech Stack, Competitors)
- Features overview
- How it works section
- Testimonials
- Footer with navigation

**Key Features**:
- Real-time URL validation
- Option selection for analysis
- Progress indicator
- Results display with tabs

#### features.html - Features Page
**URL**: `/features.html`

Complete feature showcase including:
- SEO Analysis features
- Tech Stack Detection
- Competitor Analysis
- Performance Metrics
- GitHub Integration
- Tool Marketplace
- Dashboard & Analytics

#### tools.html - Tool Marketplace
**URL**: `/tools.html`

Browse and install community tools:
- Tool categories (SEO, Analytics, Utilities)
- Search and filter functionality
- Tool ratings and reviews
- One-click installation
- Popular tools section

#### create-tool.html - Tool Builder
**URL**: `/create-tool.html`

3-step tool creation process:

**Step 1: Basic Info**
- Tool name and description
- Category selection
- Icon selection

**Step 2: Tool Logic**
- Visual interface builder
- Custom JavaScript code editor
- API configuration
- Parameter definitions

**Step 3: Preview & Publish**
- Live tool preview
- Test before publishing
- Publish to marketplace

### Account Pages

#### dashboard.html - User Dashboard
**URL**: `/dashboard.html`

User's personal dashboard:
- Audit statistics
- Recent analyses
- Quick actions
- My tools overview
- Notifications

#### login.html - Login Page
**URL**: `/login.html`

User authentication:
- Email/password login
- Social login options (Google, GitHub, LinkedIn)
- Remember me option
- Forgot password link

#### register.html - Registration
**URL**: `/register.html`

New user registration:
- Name, email, password
- Password strength indicator
- Terms acceptance
- Social registration options

#### profile.html - User Profile
**URL**: `/profile.html`

Profile management:
- Personal information
- Profile picture
- Bio and interests
- Skills and expertise

#### settings.html - Account Settings
**URL**: `/settings.html`

Account configuration:
- Email and password
- Notification preferences
- Privacy settings
- Linked accounts
- Delete account option

#### billing.html - Billing Management
**URL**: `/billing.html`

Subscription and billing:
- Current plan (FREE)
- Payment history
- Invoice download
- Support options

### Support Pages

#### pricing.html - Support Us
**URL**: `/pricing.html`

Donation page with PhonePe integration:
- Three preset amounts: ₹50, ₹100, ₹500
- Custom amount input
- PhonePe payment redirect
- Secure payment info

#### payment-success.html - Payment Success
**URL**: `/payment-success.html`

After successful payment:
- Transaction details
- Thank you message
- Receipt download
- Next steps

#### payment-failure.html - Payment Failure
**URL**: `/payment-failure.html`

After failed payment:
- Error explanation
- Troubleshooting tips
- Retry option
- Support contact

### Information Pages

#### about.html - About Us
**URL**: `/about.html`

Company information:
- Our story
- Mission and vision
- Team members
- Values and culture

#### contact.html - Contact
**URL**: `/contact.html`

Contact form:
- Name, email, message
- Subject selection
- Social media links
- Office address

#### privacy.html - Privacy Policy
**URL**: `/privacy.html`

Privacy policy:
- Data collection
- Data usage
- Cookies policy
- User rights

#### terms.html - Terms of Service
**URL**: `/terms.html`

Terms and conditions:
- Service terms
- User responsibilities
- Limitations
- Termination policy

### Blog Pages

#### blog.html - Blog Listing
**URL**: `/blog.html`

All blog articles:
- Featured posts
- Categories
- Search functionality
- Recent posts

#### Blog Articles

1. **seo-guide.html** - Complete SEO Guide
2. **tech-stack.html** - Technology Stack Detection
3. **competitor-analysis.html** - Competitor Analysis Strategies
4. **website-speed.html** - Website Speed Optimization
5. **mobile-optimization.html** - Mobile SEO Best Practices
6. **backlink-strategy.html** - Backlink Building Strategies
7. **content-marketing.html** - Content Marketing Guide
8. **social-media-integration.html** - Social Media Integration
9. **analytics-setup.html** - Analytics Setup Guide
10. **security-checklist.html** - Website Security Checklist
11. **conversion-optimization.html** - Conversion Rate Optimization
12. **local-seo.html** - Local SEO Guide

---

## Backend Services

### CrawlerService.php

**Purpose**: Main website crawling engine

**Key Methods**:
```php
// Crawl entire website
crawlSite(string $url, array $options = []): array

// Analyze single page
analyzePage(string $url): array

// Capture screenshot
captureScreenshot(string $url, string $outputPath): bool

// Check links
checkLinks(array $links): array

// Test buttons
testButtons(array $buttons): array

// Detect errors
detectErrors(string $html): array
```

**Features**:
- Headless browser automation
- Multi-page crawling with depth control
- Screenshot capture
- Link integrity checking
- Button functionality testing
- Error detection
- Performance timing measurement

### TechStackService.php

**Purpose**: Technology stack detection

**Detection Categories**:
- Frontend Frameworks (React, Vue, Angular, etc.)
- Backend Frameworks (Laravel, Django, Rails, etc.)
- CSS Frameworks (Bootstrap, Tailwind, etc.)
- JavaScript Libraries (jQuery, Lodash, etc.)
- Analytics (Google Analytics, Mixpanel, etc.)
- CMS Platforms (WordPress, Shopify, etc.)
- E-commerce (WooCommerce, Magento, etc.)
- CDNs (Cloudflare, AWS CloudFront, etc.)
- Build Tools (Webpack, Vite, Parcel, etc.)

**Key Methods**:
```php
// Detect complete tech stack
detectTechStack(string $html, string $url): array

// Detect frameworks
detectFramework(array $scripts, array $metaTags): array

// Detect libraries
detectLibraries(array $scripts, array $styles): array

// Detect analytics
detectAnalytics(string $html): array

// Detect CMS
detectCMS(array $metaTags, array $cookies): array

// Detect server
detectServer(array $headers): array
```

### SeoService.php

**Purpose**: SEO analysis and recommendations

**Analysis Points**:
- Title tag (presence, length, content)
- Meta description (presence, length, content)
- Heading structure (H1-H6 hierarchy)
- Image alt attributes
- Canonical URL
- robots.txt
- sitemap.xml
- Schema.org markup
- Page load speed
- Mobile responsiveness
- SSL certificate
- Internal linking
- External links

**Key Methods**:
```php
// Complete SEO analysis
analyzeSeo(string $url, string $html): array

// Check meta tags
checkMetaTags(array $metaTags): array

// Analyze headings
analyzeHeadings(DOMDocument $dom): array

// Check images
checkImages(DOMDocument $dom): array

// Check speed
checkSpeed(string $url): array

// Check mobile
checkMobile(string $url): array

// Generate SEO score
generateSeoScore(array $checks): int
```

### CompetitorService.php

**Purpose**: Competitor discovery and analysis

**Features**:
- Search engine queries
- Industry-based detection
- Feature comparison
- Tech stack comparison
- Traffic estimation

**Key Methods**:
```php
// Find competitors
findCompetitors(string $url, string $industry): array

// Analyze competitor
analyzeCompetitor(string $competitorUrl): array

// Compare features
compareFeatures(array $ourFeatures, array $theirFeatures): array

// Generate report
generateCompetitorReport(array $competitors): array
```

### GitHubService.php

**Purpose**: GitHub API integration

**Features**:
- Repository search
- Trending projects
- Similar project detection
- Repository analysis

**Key Methods**:
```php
// Search repositories
searchRepositories(array $keywords, string $language): array

// Get repository details
getRepositoryDetails(string $owner, string $repo): array

// Get similar projects
getSimilarProjects(array $techStack): array

// Analyze repository
analyzeRepository(string $url): array
```

### SubscriptionService.php

**Purpose**: Subscription and donation management

**Features**:
- Plan management
- Payment processing
- Receipt generation
- Usage tracking

---

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Audit Endpoints

#### POST /api/audit
Run a complete website audit.

**Request**:
```json
{
    "url": "https://example.com",
    "options": {
        "seo": true,
        "techStack": true,
        "competitors": true,
        "maxPages": 5,
        "useBrowser": true
    }
}
```

**Response**:
```json
{
    "success": true,
    "audit_id": 1,
    "url": "https://example.com",
    "overall_score": 85,
    "pages_analyzed": 5,
    "load_time": 2.3,
    "results": {
        "seo": {...},
        "tech_stack": [...],
        "competitors": [...],
        "issues": [...]
    }
}
```

#### GET /api/audits
List all audits.

**Response**:
```json
{
    "success": true,
    "audits": [
        {
            "id": 1,
            "url": "https://example.com",
            "overall_score": 85,
            "created_at": "2024-01-15T10:30:00Z"
        }
    ]
}
```

#### GET /api/audits/{id}
Get specific audit details.

#### DELETE /api/audits/{id}
Delete an audit.

### Analysis Endpoints

#### POST /api/analyze/seo
Analyze SEO only.

**Request**:
```json
{
    "url": "https://example.com"
}
```

**Response**:
```json
{
    "success": true,
    "seo_score": 78,
    "checks": {
        "title": {"status": "pass", "message": "Title is present"},
        "meta_description": {"status": "warning", "message": "Meta description is too long"},
        "headings": {"status": "pass", "message": "Heading structure is good"},
        "images": {"status": "fail", "message": "5 images missing alt text"}
    },
    "recommendations": [...]
}
```

#### POST /api/analyze/tech-stack
Detect tech stack only.

**Response**:
```json
{
    "success": true,
    "tech_stack": {
        "frontend": ["React", "Tailwind CSS"],
        "backend": ["Node.js", "Express"],
        "analytics": ["Google Analytics"],
        "cms": [],
        "cdn": ["Cloudflare"]
    }
}
```

#### POST /api/crawl
Crawl website.

**Request**:
```json
{
    "url": "https://example.com",
    "max_depth": 3,
    "use_browser": true
}
```

#### POST /api/competitors
Find competitors.

**Request**:
```json
{
    "url": "https://example.com",
    "industry": "ecommerce"
}
```

#### POST /api/github/search
Search GitHub repositories.

**Request**:
```json
{
    "keywords": ["website", "auditor"],
    "language": "php"
}
```

### Payment Endpoints

#### POST /api/payment/create
Create payment transaction.

**Request**:
```json
{
    "amount": 100,
    "transaction_id": "TXN1234567890",
    "redirect_url": "https://yourdomain.com/payment-success.html",
    "callback_url": "https://yourdomain.com/api/payment/callback"
}
```

**Response**:
```json
{
    "success": true,
    "encoded_payload": "base64_encoded_payload",
    "checksum": "sha256_checksum###1",
    "payment_url": "https://phonepe-rtu-webPg.../pay?payload=...",
    "transaction_id": "TXN1234567890"
}
```

#### GET /api/payment/status
Check transaction status.

#### POST /api/payment/callback
PhonePe webhook handler.

### System Endpoints

#### GET /api/health
Health check endpoint.

**Response**:
```json
{
    "status": "healthy",
    "version": "1.0.0",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

#### GET /api/stats
System statistics.

---

## PhonePe Payment Integration

### Overview

WebAI Auditor uses PhonePe payment gateway for accepting donations. The integration supports UPI, cards, netbanking, and wallets.

### Configuration

PhonePe configuration is stored in `backend/config/phonepe.xml`:

```xml
<merchant>
    <merchantId>YOUR_MERCHANT_ID</merchantId>
    <merchantName>WebAI Auditor</merchantName>
</merchant>

<security>
    <saltKey>YOUR_SALT_KEY</saltKey>
    <saltIndex>1</saltIndex>
</security>
```

### Payment Flow

1. User selects donation amount on `/pricing.html`
2. Frontend generates transaction ID
3. Frontend calls `/api/payment/create`
4. Backend saves transaction and generates payload
5. Frontend redirects to PhonePe
6. User completes payment
7. PhonePe redirects to success/failure page
8. PhonePe sends callback to backend

### Donation Tiers

- **Small Support**: ₹50
- **Popular Choice**: ₹100
- **Major Support**: ₹500
- **Custom Amount**: User-defined (₹10 minimum)

### Files

- `backend/config/phonepe.xml` - Configuration
- `backend/config/phonepe-config.xsd` - Schema validation
- `backend/api/payment.php` - Payment API
- `frontend/pricing.html` - Donation page
- `frontend/payment-success.html` - Success page
- `frontend/payment-failure.html` - Failure page

---

## Tool Marketplace

### Overview

The Tool Marketplace allows users to create, share, and install custom analysis tools. Users can build tools using our visual builder or write custom JavaScript.

### Creating a Tool

1. Navigate to `/create-tool.html`
2. **Step 1**: Enter tool name, description, category
3. **Step 2**: Build interface or write code
4. **Step 3**: Preview and publish

### Tool Categories

- SEO Tools
- Analytics Tools
- Performance Tools
- Security Tools
- Utility Tools
- Custom Tools

### API Integration

Tools can integrate with external APIs:
- REST API support
- Custom headers
- Authentication handling
- Response parsing

---

## Blog Content

### SEO Guide

Complete guide to Search Engine Optimization covering:
- On-page SEO
- Technical SEO
- Off-page SEO
- Local SEO
- Mobile SEO
- Keyword research
- Link building
- Content optimization

### Tech Stack Detection

Understanding technology detection:
- How detection works
- Common frameworks
- Detection patterns
- Fingerprinting methods

### Competitor Analysis

Strategies for analyzing competitors:
- Finding competitors
- Comparing features
- Analyzing traffic
- Benchmarking performance

### Website Speed Optimization

Tips for faster websites:
- Image optimization
- Code minification
- CDN usage
- Caching strategies
- Server optimization

### Mobile Optimization

Mobile-first approach:
- Responsive design
- Mobile SEO
- Touch optimization
- Mobile performance

### Backlink Strategy

Building quality backlinks:
- Link building techniques
- Guest posting
- PR outreach
- Content marketing

### Content Marketing

Content strategies:
- Blogging
- Video content
- Infographics
- Social media

### Security Checklist

Website security best practices:
- SSL certificates
- Input validation
- XSS prevention
- SQL injection prevention
- CSRF protection

### Conversion Optimization

Improving conversion rates:
- A/B testing
- Call-to-action optimization
- Landing page design
- User experience

---

## Deployment Guide

### Production Setup

### 1. Server Requirements

- Ubuntu 20.04+ or CentOS 7+
- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Nginx or Apache
- SSL certificate
- At least 2GB RAM
- 20GB disk space

### 2. Domain Setup

1. Point your domain to server IP
2. Configure DNS records
3. Set up SSL with Let's Encrypt

### 3. Application Setup

```bash
# Clone repository
git clone https://github.com/ROYALBANCHERS/webai-auditor.git
cd webai-auditor-php/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy environment
cp .env.example .env
php artisan key:generate

# Edit .env with production values
nano .env
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE webai_auditor_prod;
CREATE USER 'webai'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON webai_auditor_prod.* TO 'webai'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force
```

### 5. Configure Web Server

**Nginx Configuration**:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/webai-auditor-php/frontend;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        alias /var/www/webai-auditor-php/backend/public;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 6. Set Up Queue Worker

```bash
# Install Supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/webai-auditor.conf
```

```
[program:webai-auditor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/webai-auditor-php/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/webai-auditor-php/storage/logs/worker.log
```

### 7. Set Up Cron Jobs

```bash
crontab -e
```

```
* * * * * cd /var/www/webai-auditor-php/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## Contributing

We welcome contributions! Here's how you can help:

### Reporting Issues

1. Check existing issues
2. Create detailed issue report
3. Include steps to reproduce
4. Provide environment details

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit pull request

### Coding Standards

- Follow PSR-12 coding standards
- Add comments for complex logic
- Write meaningful commit messages
- Test your changes

---

## License

MIT License - See LICENSE file for details

---

## Credits

- Built with [Laravel 11](https://laravel.com/)
- Styled with [Tailwind CSS](https://tailwindcss.com/)
- Icons by [Font Awesome](https://fontawesome.com/)
- Payment integration by [PhonePe](https://phonepe.com/)

---

## Contact

- Website: https://webaiauditor.com
- GitHub: https://github.com/ROYALBANCHERS/webai-auditor
- Email: support@webaiauditor.com

---

## Acknowledgments

Thanks to all contributors and the open-source community for making this project possible!

---

*Last Updated: January 2024*
*Version: 1.0.0*
