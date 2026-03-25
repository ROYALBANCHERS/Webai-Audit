<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

class TechStackService
{
    protected array $patterns;
    protected array $detectedTech = [];

    public function __construct()
    {
        $this->patterns = config('auditor.tech_patterns') ?? [];
    }

    /**
     * Main method to detect the complete tech stack
     */
    public function detectTechStack(string $html, string $url, array $headers = []): array
    {
        $this->detectedTech = [];

        $crawler = new SymfonyCrawler($html, $url);

        // Extract scripts and meta tags
        $scripts = $this->extractScripts($crawler);
        $metaTags = $this->extractMetaTags($crawler);
        $stylesheets = $this->extractStylesheets($crawler);

        // Detect various categories
        $this->detectFramework($scripts, $metaTags, $html);
        $this->detectLibraries($scripts, $stylesheets, $html);
        $this->detectAnalytics($html);
        $this->detectCMS($metaTags, $html);
        $this->detectServer($headers);
        $this->detectCDN($headers, $html);
        $this->detectBuildTools($scripts, $html);
        $this->detectFonts($html);
        $this->detectPaymentGateways($html);
        $this->detectTrackingTools($html);
        $this->detectJavaScriptFramework($html, $scripts);
        $this->detectCSSFramework($stylesheets, $html);

        return $this->formatResults();
    }

    /**
     * Extract all script sources and inline scripts
     */
    protected function extractScripts(SymfonyCrawler $crawler): array
    {
        $scripts = [
            'external' => [],
            'inline' => [],
        ];

        $crawler->filter('script[src]')->each(function (SymfonyCrawler $node) use (&$scripts) {
            $scripts['external'][] = $node->attr('src');
        });

        $crawler->filter('script:not([src])')->each(function (SymfonyCrawler $node) use (&$scripts) {
            $scripts['inline'][] = $node->text();
        });

        return $scripts;
    }

    /**
     * Extract all meta tags
     */
    protected function extractMetaTags(SymfonyCrawler $crawler): array
    {
        $metaTags = [];

        $crawler->filter('meta')->each(function (SymfonyCrawler $node) use (&$metaTags) {
            $name = $node->attr('name') ?? $node->attr('property') ?? '';
            $content = $node->attr('content') ?? '';

            if ($name) {
                $metaTags[$name] = $content;
            }
        });

        return $metaTags;
    }

    /**
     * Extract all stylesheets
     */
    protected function extractStylesheets(SymfonyCrawler $crawler): array
    {
        $stylesheets = [];

        $crawler->filter('link[rel="stylesheet"]')->each(function (SymfonyCrawler $node) use (&$stylesheets) {
            $stylesheets[] = $node->attr('href');
        });

        return $stylesheets;
    }

    /**
     * Detect web frameworks
     */
    protected function detectFramework(array $scripts, array $metaTags, string $html): void
    {
        $frameworks = [
            'React' => ['patterns' => ['react', 'react-dom', 'reactjs'], 'confidence' => 'high'],
            'Vue.js' => ['patterns' => ['vue', 'vuejs', 'nuxt.js'], 'confidence' => 'high'],
            'Angular' => ['patterns' => ['angular', 'ng-app', 'ng-controller'], 'confidence' => 'high'],
            'Next.js' => ['patterns' => ['next.js', '__next', '/_next/'], 'confidence' => 'high'],
            'Nuxt.js' => ['patterns' => ['nuxt.js', '__nuxt', '/_nuxt/'], 'confidence' => 'high'],
            'Svelte' => ['patterns' => ['svelte'], 'confidence' => 'high'],
            'SolidJS' => ['patterns' => ['solid-js', 'solidjs'], 'confidence' => 'high'],
            'Astro' => ['patterns' => ['astro'], 'confidence' => 'high'],
            'Remix' => ['patterns' => ['remix', '__remixContext'], 'confidence' => 'high'],
            'Laravel' => ['patterns' => ['laravel', 'csrf-token', 'x-csrf-token'], 'confidence' => 'high'],
            'WordPress' => ['patterns' => ['wp-content', 'wordpress', 'wp-includes'], 'confidence' => 'high'],
            'Shopify' => ['patterns' => ['shopify', 'shopify-theme', 'cdn.shopify.com'], 'confidence' => 'high'],
            'Django' => ['patterns' => ['csrfmiddlewaretoken'], 'confidence' => 'high'],
            'Ruby on Rails' => ['patterns' => ['rails', 'ruby_on_rails', 'csrf-token'], 'confidence' => 'high'],
            'Express' => ['patterns' => ['express'], 'confidence' => 'medium'],
            'Spring' => ['patterns' => ['spring-framework'], 'confidence' => 'medium'],
            'Flask' => ['patterns' => ['flask'], 'confidence' => 'medium'],
            'FastAPI' => ['patterns' => ['fastapi'], 'confidence' => 'medium'],
            'ASP.NET' => ['patterns' => ['asp.net', '__viewstate'], 'confidence' => 'high'],
            'Drupal' => ['patterns' => ['drupal'], 'confidence' => 'high'],
            'Joomla' => ['patterns' => ['joomla'], 'confidence' => 'high'],
            'Magento' => ['patterns' => ['magento'], 'confidence' => 'high'],
            'Wix' => ['patterns' => ['wix', 'static.wixstatic.com'], 'confidence' => 'high'],
            'Squarespace' => ['patterns' => ['squarespace'], 'confidence' => 'high'],
            'Webflow' => ['patterns' => ['webflow'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($frameworks, $scripts['external'], $scripts['inline'], $html, 'framework');
    }

    /**
     * Detect JavaScript libraries
     */
    protected function detectLibraries(array $scripts, array $stylesheets, string $html): void
    {
        $libraries = [
            'jQuery' => ['patterns' => ['jquery'], 'confidence' => 'high'],
            'Bootstrap' => ['patterns' => ['bootstrap'], 'confidence' => 'high'],
            'Tailwind CSS' => ['patterns' => ['tailwind'], 'confidence' => 'high'],
            'Three.js' => ['patterns' => ['three', 'three.js', 'three.min.js'], 'confidence' => 'high'],
            'GSAP' => ['patterns' => ['gsap', 'greensock'], 'confidence' => 'high'],
            'D3.js' => ['patterns' => ['d3.js', 'd3js'], 'confidence' => 'high'],
            'Chart.js' => ['patterns' => ['chart.js', 'chartjs'], 'confidence' => 'high'],
            'Moment.js' => ['patterns' => ['moment.js', 'moment.min.js'], 'confidence' => 'high'],
            'Lodash' => ['patterns' => ['lodash'], 'confidence' => 'high'],
            'Axios' => ['patterns' => ['axios'], 'confidence' => 'high'],
            'Underscore.js' => ['patterns' => ['underscore'], 'confidence' => 'high'],
            'Anime.js' => ['patterns' => ['anime'], 'confidence' => 'high'],
            'AOS' => ['patterns' => ['aos'], 'confidence' => 'high'],
            'Swiper' => ['patterns' => ['swiper'], 'confidence' => 'high'],
            'Slick' => ['patterns' => ['slick'], 'confidence' => 'high'],
            'Fancybox' => ['patterns' => ['fancybox'], 'confidence' => 'high'],
            'Lightbox' => ['patterns' => ['lightbox'], 'confidence' => 'high'],
            'Select2' => ['patterns' => ['select2'], 'confidence' => 'high'],
            'DataTables' => ['patterns' => ['datatables'], 'confidence' => 'high'],
            'FullCalendar' => ['patterns' => ['fullcalendar'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($libraries, $scripts['external'], $scripts['inline'], $html, 'library');
    }

    /**
     * Detect analytics tools
     */
    protected function detectAnalytics(string $html): void
    {
        $analytics = [
            'Google Analytics' => ['patterns' => ['googletagmanager.com', 'google-analytics.com', 'ga(', 'gtag('], 'confidence' => 'high'],
            'Google Tag Manager' => ['patterns' => ['googletagmanager.com/gtag/js', 'GTM-'], 'confidence' => 'high'],
            'Mixpanel' => ['patterns' => ['mixpanel'], 'confidence' => 'high'],
            'Hotjar' => ['patterns' => ['hotjar'], 'confidence' => 'high'],
            'Segment' => ['patterns' => ['segment.com', 'analytics.js'], 'confidence' => 'high'],
            'Amplitude' => ['patterns' => ['amplitude'], 'confidence' => 'high'],
            'Heap' => ['patterns' => ['heapanalytics'], 'confidence' => 'high'],
            'FullStory' => ['patterns' => ['fullstory'], 'confidence' => 'high'],
            'LogRocket' => ['patterns' => ['logrocket'], 'confidence' => 'high'],
            'Mouseflow' => ['patterns' => ['mouseflow'], 'confidence' => 'high'],
            'Crazy Egg' => ['patterns' => ['crazyegg'], 'confidence' => 'high'],
            'Facebook Pixel' => ['patterns' => ['connect.facebook.net', 'fbq('], 'confidence' => 'high'],
            'LinkedIn Insight Tag' => ['patterns' => ['linkedin.com/li.insight'], 'confidence' => 'high'],
            'Twitter Pixel' => ['patterns' => ['twitter.com/i/jot', 'twq('], 'confidence' => 'high'],
            'Pinterest Tag' => ['patterns' => ['pinimg.com'], 'confidence' => 'high'],
            'TikTok Pixel' => ['patterns' => ['tiktok.com'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($analytics, [], [], $html, 'analytics');
    }

    /**
     * Detect CMS
     */
    protected function detectCMS(array $metaTags, string $html): void
    {
        $cms = [
            'WordPress' => ['patterns' => ['wp-content', 'wordpress', 'wp-json'], 'confidence' => 'high'],
            'Shopify' => ['patterns' => ['shopify', 'cdn.shopify.com'], 'confidence' => 'high'],
            'Wix' => ['patterns' => ['wix-static.com', 'wix-code'], 'confidence' => 'high'],
            'Squarespace' => ['patterns' => ['squarespace'], 'confidence' => 'high'],
            'Drupal' => ['patterns' => ['drupal'], 'confidence' => 'high'],
            'Joomla' => ['patterns' => ['joomla'], 'confidence' => 'high'],
            'Magento' => ['patterns' => ['magento'], 'confidence' => 'high'],
            'BigCommerce' => ['patterns' => ['bigcommerce'], 'confidence' => 'high'],
            'WooCommerce' => ['patterns' => ['woocommerce'], 'confidence' => 'high'],
            'Ghost' => ['patterns' => ['ghost'], 'confidence' => 'high'],
            'HubSpot CMS' => ['patterns' => ['hubspot'], 'confidence' => 'high'],
            'Contentful' => ['patterns' => ['contentful'], 'confidence' => 'high'],
            'Sanity' => ['patterns' => ['sanity.io'], 'confidence' => 'high'],
            'Strapi' => ['patterns' => ['strapi.io'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($cms, [], [], $html, 'cms');
    }

    /**
     * Detect server technology from headers
     */
    protected function detectServer(array $headers): void
    {
        // Handle Laravel HTTP client header format
        $serverHeader = '';

        // Headers from Laravel HTTP client may be arrays or strings
        foreach (['server', 'Server', 'x-server', 'x-powered-by'] as $key) {
            if (isset($headers[$key])) {
                $value = $headers[$key];
                if (is_array($value)) {
                    $serverHeader = implode(' ', $value);
                } else {
                    $serverHeader = (string) $value;
                }
                break;
            }
        }

        if (empty($serverHeader)) {
            return;
        }

        // Ensure $serverHeader is a string
        if (is_array($serverHeader)) {
            $serverHeader = implode(' ', $serverHeader);
        }

        $servers = [
            'nginx' => 'Nginx',
            'apache' => 'Apache',
            'cloudflare' => 'Cloudflare',
            'litespeed' => 'LiteSpeed',
            'iis' => 'IIS',
            'microsoft-iis' => 'IIS',
            'gunicorn' => 'Gunicorn',
            'uwsgi' => 'uWSGI',
            'passenger' => 'Phusion Passenger',
            'vercel' => 'Vercel',
            'netlify' => 'Netlify',
            'heroku' => 'Heroku',
        ];

        foreach ($servers as $pattern => $name) {
            if (stripos($serverHeader, $pattern) !== false) {
                $this->addDetected('server', $name, 'high');
            }
        }

        // Try to extract version
        if (preg_match('/([a-z]+\/[\d\.]+)/i', $serverHeader, $matches)) {
            $this->addDetected('server', $matches[1], 'high');
        }
    }

    /**
     * Detect CDN usage
     */
    protected function detectCDN(array $headers, string $html): void
    {
        $cdn = [
            'Cloudflare' => ['patterns' => ['cloudflare', 'cf-ray'], 'confidence' => 'high'],
            'AWS CloudFront' => ['patterns' => ['cloudfront'], 'confidence' => 'high'],
            'Akamai' => ['patterns' => ['akamai'], 'confidence' => 'high'],
            'Fastly' => ['patterns' => ['fastly'], 'confidence' => 'high'],
            'Azure CDN' => ['patterns' => ['azureedge.net', 'azurecdn.net'], 'confidence' => 'high'],
            'Google CDN' => ['patterns' => ['googleusercontent.com', 'gstatic.com'], 'confidence' => 'high'],
            'BunnyCDN' => ['patterns' => ['bunnycdn'], 'confidence' => 'high'],
            'KeyCDN' => ['patterns' => ['keycdn'], 'confidence' => 'high'],
            'StackPath' => ['patterns' => ['stackpath'], 'confidence' => 'high'],
            'CDN77' => ['patterns' => ['cdn77'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($cdn, [], [], $html, 'cdn');

        // Check headers for CDN
        foreach ($headers as $key => $value) {
            // Convert value to string if it's an array
            $headerValue = is_array($value) ? implode(' ', $value) : (string) $value;

            foreach ($cdn as $name => $config) {
                foreach ($config['patterns'] as $pattern) {
                    if (stripos($headerValue, $pattern) !== false) {
                        $this->addDetected('cdn', $name, $config['confidence']);
                    }
                }
            }
        }
    }

    /**
     * Detect build tools
     */
    protected function detectBuildTools(array $scripts, string $html): void
    {
        $buildTools = [
            'Webpack' => ['patterns' => ['webpack', '__webpack_require__'], 'confidence' => 'high'],
            'Vite' => ['patterns' => ['vite', '/@vite/'], 'confidence' => 'high'],
            'Parcel' => ['patterns' => ['parcel'], 'confidence' => 'high'],
            'Rollup' => ['patterns' => ['rollup'], 'confidence' => 'high'],
            'esbuild' => ['patterns' => ['esbuild'], 'confidence' => 'high'],
            'Browserify' => ['patterns' => ['browserify'], 'confidence' => 'high'],
            'Gulp' => ['patterns' => ['gulp'], 'confidence' => 'medium'],
            'Grunt' => ['patterns' => ['grunt'], 'confidence' => 'medium'],
        ];

        $this->checkPatterns($buildTools, $scripts['external'], $scripts['inline'], $html, 'build_tool');
    }

    /**
     * Detect font libraries
     */
    protected function detectFonts(string $html): void
    {
        $fonts = [
            'Google Fonts' => ['patterns' => ['fonts.googleapis.com', 'fonts.gstatic.com'], 'confidence' => 'high'],
            'Adobe Fonts' => ['patterns' => ['use.typekit.net', 'fonts.adobe.com'], 'confidence' => 'high'],
            'Font Awesome' => ['patterns' => ['fontawesome', 'fa-solid', 'fa-brands'], 'confidence' => 'high'],
            'Bootstrap Icons' => ['patterns' => ['bootstrap-icons'], 'confidence' => 'high'],
            'Material Icons' => ['patterns' => ['material-icons', 'materialdesignicons'], 'confidence' => 'high'],
            'Ionicons' => ['patterns' => ['ionicons'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($fonts, [], [], $html, 'font');
    }

    /**
     * Detect payment gateways
     */
    protected function detectPaymentGateways(string $html): void
    {
        $gateways = [
            'Stripe' => ['patterns' => ['js.stripe.com', 'stripe.js'], 'confidence' => 'high'],
            'PayPal' => ['patterns' => ['paypal.com', 'paypalobjects.com'], 'confidence' => 'high'],
            'Square' => ['patterns' => ['squareup.com'], 'confidence' => 'high'],
            'Braintree' => ['patterns' => ['braintree'], 'confidence' => 'high'],
            'Adyen' => ['patterns' => ['adyen.com'], 'confidence' => 'high'],
            'Razorpay' => ['patterns' => ['razorpay.com'], 'confidence' => 'high'],
            'Authorize.net' => ['patterns' => ['authorize.net'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($gateways, [], [], $html, 'payment');
    }

    /**
     * Detect tracking and marketing tools
     */
    protected function detectTrackingTools(string $html): void
    {
        $tools = [
            'Intercom' => ['patterns' => ['intercom'], 'confidence' => 'high'],
            'Drift' => ['patterns' => ['drift'], 'confidence' => 'high'],
            'Zendesk' => ['patterns' => ['zendesk'], 'confidence' => 'high'],
            'Freshdesk' => ['patterns' => ['freshdesk'], 'confidence' => 'high'],
            'Salesforce' => ['patterns' => ['salesforce'], 'confidence' => 'high'],
            'HubSpot' => ['patterns' => ['hubspot'], 'confidence' => 'high'],
            'Mailchimp' => ['patterns' => ['mailchimp', 'mc.us'], 'confidence' => 'high'],
            'SendGrid' => ['patterns' => ['sendgrid'], 'confidence' => 'high'],
        ];

        $this->checkPatterns($tools, [], [], $html, 'tracking');
    }

    /**
     * Detect JavaScript framework
     */
    protected function detectJavaScriptFramework(string $html, array $scripts): void
    {
        // Check for specific framework indicators
        if (preg_match('/data-react-root/i', $html) || preg_match('/reactid/i', $html)) {
            $this->addDetected('framework', 'React', 'high');
        }

        if (preg_match('/data-v-[a-f0-9]+/i', $html) || preg_match('/vue-router/i', $html)) {
            $this->addDetected('framework', 'Vue.js', 'high');
        }

        if (preg_match('/ng-version/i', $html) || preg_match('/ng-app/i', $html)) {
            $this->addDetected('framework', 'Angular', 'high');
        }

        if (preg_match('/__NEXT_DATA__/i', $html)) {
            $this->addDetected('framework', 'Next.js', 'high');
        }

        if (preg_match('/__NUXT__/i', $html)) {
            $this->addDetected('framework', 'Nuxt.js', 'high');
        }
    }

    /**
     * Detect CSS framework
     */
    protected function detectCSSFramework(array $stylesheets, string $html): void
    {
        $cssFrameworks = [
            'Bootstrap' => ['bootstrap', 'cdn.jsdelivr.net/npm/bootstrap'],
            'Tailwind CSS' => ['tailwind'],
            'Bulma' => ['bulma'],
            'Foundation' => ['foundation', 'cdn.foundation'],
            'Materialize' => ['materialize'],
            'Semantic UI' => ['semantic'],
            'Pure CSS' => ['purecss'],
            'Skeleton' => ['skeleton'],
            'Milligram' => ['milligram'],
            'Picnic CSS' => ['picnic'],
        ];

        foreach ($cssFrameworks as $name => $patterns) {
            foreach ($patterns as $pattern) {
                foreach ($stylesheets as $sheet) {
                    if (stripos($sheet, $pattern) !== false) {
                        $this->addDetected('css_framework', $name, 'high');
                        break 2;
                    }
                }

                if (stripos($html, $pattern) !== false) {
                    $this->addDetected('css_framework', $name, 'high');
                    break;
                }
            }
        }
    }

    /**
     * Check patterns against content
     */
    protected function checkPatterns(array $techList, array $externalScripts, array $inlineScripts, string $html, string $category): void
    {
        $allContent = implode(' ', $externalScripts) . ' ' . implode(' ', $inlineScripts) . ' ' . $html;

        foreach ($techList as $name => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (stripos($allContent, $pattern) !== false) {
                    $this->addDetected($category, $name, $config['confidence']);
                    break;
                }
            }
        }
    }

    /**
     * Add detected technology
     */
    protected function addDetected(string $category, string $name, string $confidence = 'medium', ?string $version = null): void
    {
        $key = $category . ':' . $name;

        if (!isset($this->detectedTech[$key])) {
            $this->detectedTech[$key] = [
                'category' => $category,
                'name' => $name,
                'confidence' => $confidence,
                'version' => $version,
            ];
        } else {
            // Update confidence if higher
            $levels = ['low' => 1, 'medium' => 2, 'high' => 3];
            if ($levels[$confidence] > $levels[$this->detectedTech[$key]['confidence']]) {
                $this->detectedTech[$key]['confidence'] = $confidence;
            }
        }
    }

    /**
     * Format results for output
     */
    protected function formatResults(): array
    {
        $results = [
            'frameworks' => [],
            'libraries' => [],
            'analytics' => [],
            'cms' => [],
            'servers' => [],
            'cdn' => [],
            'fonts' => [],
            'payments' => [],
            'tracking' => [],
            'build_tools' => [],
            'css_frameworks' => [],
            'all' => array_values($this->detectedTech),
        ];

        foreach ($this->detectedTech as $tech) {
            $category = $tech['category'];

            // Map category to results key
            $keyMap = [
                'framework' => 'frameworks',
                'library' => 'libraries',
                'analytics' => 'analytics',
                'cms' => 'cms',
                'server' => 'servers',
                'cdn' => 'cdn',
                'font' => 'fonts',
                'payment' => 'payments',
                'tracking' => 'tracking',
                'build_tool' => 'build_tools',
                'css_framework' => 'css_frameworks',
            ];

            $key = $keyMap[$category] ?? 'all';

            if (!isset($results[$key])) {
                $results[$key] = [];
            }

            $results[$key][] = [
                'name' => $tech['name'],
                'confidence' => $tech['confidence'],
                'version' => $tech['version'],
            ];
        }

        return $results;
    }

    /**
     * Get tech stack summary
     */
    public function getTechStackSummary(array $techStack): array
    {
        return [
            'total' => count($techStack['all']),
            'by_category' => [
                'frameworks' => count($techStack['frameworks']),
                'libraries' => count($techStack['libraries']),
                'analytics' => count($techStack['analytics']),
                'cms' => count($techStack['cms']),
            ],
            'primary_framework' => !empty($techStack['frameworks']) ? $techStack['frameworks'][0]['name'] : null,
            'primary_cms' => !empty($techStack['cms']) ? $techStack['cms'][0]['name'] : null,
        ];
    }
}
