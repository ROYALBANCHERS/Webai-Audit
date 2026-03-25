/**
 * App Module - Main application logic with subscription support
 * Production-ready with configurable API endpoint
 */

// Configure API base URL - supports multiple environments
const API_BASE = window.env?.API_URL ||
                 import.meta.env?.VITE_API_URL ||
                 'https://webai-auditor-backend.abhishek88694.workers.dev/api';

// Subscription state
let currentSubscription = null;
let currentUsage = null;

// Error tracking
const errors = {
    api: [],
    validation: [],
    runtime: []
};

/**
 * Safe error handler that logs errors appropriately
 */
function handleError(error, context = 'general') {
    const errorInfo = {
        message: error.message || String(error),
        context: context,
        timestamp: new Date().toISOString(),
        stack: error.stack,
        url: window.location.href,
        userAgent: navigator.userAgent
    };

    // Add to appropriate error bucket
    if (error instanceof API.APIError) {
        errors.api.push(errorInfo);
    } else {
        errors.runtime.push(errorInfo);
    }

    // Keep only last 50 errors
    Object.keys(errors).forEach(key => {
        if (errors[key].length > 50) {
            errors[key] = errors[key].slice(-50);
        }
    });

    // Log to console in development
    if (window.env?.DEBUG || import.meta.env?.DEV) {
        console.error(`[${context}]`, error, errorInfo);
    }

    // Send to error tracking service if configured
    if (window.env?.ERROR_TRACKING_URL) {
        sendErrorTracking(errorInfo).catch(() => {});
    }

    return errorInfo;
}

/**
 * Send error to tracking service
 */
async function sendErrorTracking(errorInfo) {
    try {
        await fetch(window.env.ERROR_TRACKING_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(errorInfo),
            keepalive: true
        });
    } catch (e) {
        // Silently fail
    }
}

// Global error handlers
window.addEventListener('error', (e) => {
    handleError(e.error || new Error(e.message), 'uncaught');
});

window.addEventListener('unhandledrejection', (e) => {
    handleError(e.reason, 'unhandled-promise');
});

document.addEventListener('DOMContentLoaded', async () => {
    // Initialize modules
    UI.init();
    i18n.init();

    // Get DOM elements
    const auditForm = document.getElementById('auditForm');
    const urlInput = document.getElementById('urlInput');
    const submitBtn = document.getElementById('submitBtn');

    // Load subscription info on startup
    loadSubscriptionInfo();

    // Check health on load
    checkHealth();

    // Form submission handler
    auditForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const url = urlInput.value.trim();
        if (!url) {
            UI.showToast('Please enter a valid URL', 'error');
            return;
        }

        await runAudit(url);
    });

    /**
     * Load subscription and usage info
     */
    async function loadSubscriptionInfo() {
        try {
            // Load current subscription with timeout
            const subResponse = await fetchWithTimeout(`${API_BASE}/subscription/current`, 5000);
            const subData = await subResponse.json();

            if (subData.success) {
                currentSubscription = subData.data;
                updateCreditsDisplay();
            } else if (subResponse.status !== 401) {
                // Only log if not an auth error (expected for non-logged users)
                console.warn('Subscription API returned:', subData.message);
            }

            // Load usage stats with timeout
            const usageResponse = await fetchWithTimeout(`${API_BASE}/subscription/usage`, 5000);
            const usageData = await usageResponse.json();

            if (usageData.success) {
                currentUsage = usageData.data;
                updateCreditsInfo();
            }
        } catch (error) {
            // Distinguish between different error types
            if (error.name === 'AbortError') {
                console.warn('Subscription info request timed out');
            } else if (error instanceof TypeError) {
                console.warn('Network error loading subscription info - backend may be unavailable');
            } else {
                handleError(error, 'loadSubscriptionInfo');
            }
            // Set defaults so app can function
            currentSubscription = { credits_remaining: 5 };
            currentUsage = { max_pages: 5, max_websites: 1 };
        }
    }

    /**
     * Fetch with timeout
     */
    async function fetchWithTimeout(url, timeout = 10000, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                const timeoutError = new Error(`Request timed out after ${timeout}ms`);
                timeoutError.name = 'AbortError';
                throw timeoutError;
            }
            throw error;
        }
    }

    /**
     * Update credits display in navbar
     */
    function updateCreditsDisplay() {
        const creditsDisplay = document.getElementById('creditsDisplay');
        if (!creditsDisplay) return;

        const credits = currentSubscription?.credits_remaining ?? 0;
        const plan = currentSubscription?.plan?.name ?? 'Free';

        creditsDisplay.innerHTML = `<span class="text-yellow-400">${credits}</span> <span class="text-gray-400">credits</span>`;
    }

    /**
     * Update credits info in options panel
     */
    function updateCreditsInfo() {
        const creditsInfo = document.getElementById('creditsInfo');
        if (!creditsInfo || !currentUsage) return;

        const maxPages = currentUsage.max_pages ?? 5;
        const maxWebsites = currentUsage.max_websites ?? 1;
        const hasCompetitors = currentUsage.can_analyze_competitors ?? false;
        const hasGithub = currentUsage.can_use_github ?? false;

        creditsInfo.textContent = `Your plan allows up to ${maxPages} pages per audit, ${maxWebsites} websites. ` +
            (hasCompetitors ? 'Competitor analysis available. ' : 'Upgrade for competitor analysis. ') +
            (hasGithub ? 'GitHub search available.' : 'Upgrade for GitHub search.');
    }

    /**
     * Check API health
     */
    async function checkHealth() {
        try {
            await API.healthCheck();
        } catch (error) {
            UI.showToast('Backend not connected. Make sure Laravel server is running.', 'warning');
        }
    }

    /**
     * Run website audit
     */
    async function runAudit(url) {
        // Get options
        const checkSeo = document.getElementById('optSeo')?.checked ?? true;
        const checkTechStack = document.getElementById('optTechStack')?.checked ?? true;
        const checkCompetitors = document.getElementById('optCompetitors')?.checked ?? false;
        const checkGithub = document.getElementById('optGithub')?.checked ?? false;
        const maxDepth = parseInt(document.getElementById('optDepth')?.value ?? 3);
        const maxPages = parseInt(document.getElementById('optMaxPages')?.value ?? 10);

        // Check if user has permission for premium features
        if (checkCompetitors && !currentUsage?.can_analyze_competitors) {
            UI.showToast('Competitor analysis requires Professional plan or higher', 'error');
            return;
        }

        if (checkGithub && !currentUsage?.can_use_github) {
            UI.showToast('GitHub search requires Business plan or higher', 'error');
            return;
        }

        // Check credits
        const estimatedCredits = maxPages; // 1 credit per page
        if (currentSubscription?.credits_remaining < estimatedCredits) {
            UI.showToast(`Insufficient credits. You need ${estimatedCredits} credits but only have ${currentSubscription.credits_remaining}. <a href="pricing.html" class="underline">Upgrade now</a>`, 'error');
            return;
        }

        // Show progress
        UI.showSection('progress');
        setFormLoading(true);

        // Simulate progress
        simulateProgress(maxPages);

        try {
            // Validate URL before processing
            if (!isValidUrl(url)) {
                throw new Error('Please enter a valid URL (e.g., https://example.com)');
            }

            // For now, run individual analyses
            const results = {
                url: url,
                overall_score: 0,
                seo_score: 0,
                pages_count: 0,
                tech_stack: { all: [] },
                issues: [],
                crawler: { pages_crawled: 0, total_time: 0 },
            };

            // Run SEO analysis if requested
            if (checkSeo) {
                updateProgressStatus('Running SEO analysis...');
                try {
                    const seoResult = await API.analyzeSeo(url);
                    if (seoResult.success) {
                        results.seo = seoResult.data;
                        results.seo_score = seoResult.data.score || 0;
                    }
                } catch (e) {
                    console.warn('SEO analysis failed, continuing without it:', e.message);
                    results.issues.push({
                        type: 'warning',
                        title: 'SEO Analysis Unavailable',
                        message: 'Could not complete SEO analysis'
                    });
                }
            }

            // Run tech stack analysis if requested
            if (checkTechStack) {
                updateProgressStatus('Detecting technologies...');
                try {
                    const techResult = await API.analyzeTechStack(url);
                    if (techResult.success) {
                        results.tech_stack = techResult.data;
                    }
                } catch (e) {
                    console.warn('Tech stack analysis failed, continuing without it:', e.message);
                }
            }

            // Run crawl for multi-page analysis
            updateProgressStatus(`Crawling up to ${maxPages} pages...`);
            try {
                const crawlResult = await API.crawl(url, { max_depth: maxDepth, max_pages: maxPages });

                if (crawlResult.success) {
                    const crawlData = crawlResult.data;
                    results.crawler = crawlData;
                    results.pages_count = crawlData.pages_crawled || 1;

                    // Extract issues from crawl
                    if (crawlData.issues) {
                        results.issues = [...results.issues, ...crawlData.issues];
                    }

                    // Calculate overall score
                    const seoScore = results.seo_score || 75;
                    const techScore = 100; // Good tech stack
                    const issuePenalty = Math.min(results.issues.length * 5, 50);
                    results.overall_score = Math.round((seoScore + techScore) / 2 - issuePenalty);
                    results.overall_score = Math.max(0, Math.min(100, results.overall_score));
                }
            } catch (e) {
                handleError(e, 'crawl');
                results.issues.push({
                    type: 'error',
                    title: 'Crawl Failed',
                    message: 'Could not crawl website: ' + e.message
                });
            }

            // Competitor analysis (if allowed)
            if (checkCompetitors && currentUsage?.can_analyze_competitors) {
                updateProgressStatus('Finding competitors...');
                try {
                    const competitorResult = await API.findCompetitors(url);
                    if (competitorResult.success) {
                        results.competitors = competitorResult.data;
                    }
                } catch (e) {
                    console.warn('Competitor analysis failed:', e.message);
                    // Don't fail the entire analysis for optional features
                }
            }

            // GitHub search (if allowed)
            if (checkGithub && currentUsage?.can_use_github) {
                updateProgressStatus('Searching GitHub...');
                try {
                    const techStack = results.tech_stack?.all || [];
                    const language = detectLanguage(techStack);
                    const githubResult = await API.searchGitHub(language || 'web', 'en');
                    if (githubResult.success) {
                        results.github = githubResult.data;
                    }
                } catch (e) {
                    console.warn('GitHub search failed:', e.message);
                    // Don't fail the entire analysis for optional features
                }
            }

            UI.updateProgress(100, 'Analysis complete!');
            await delay(500);

            // Display results
            Analyzer.displayResults(results);
            UI.showToast('Analysis completed successfully!', 'success');

            // Reload subscription info (credits were deducted)
            loadSubscriptionInfo();

        } catch (error) {
            handleError(error, 'runAudit');
            UI.showSection('hero');

            // Show user-friendly error message
            let errorMessage = 'Analysis failed. Please try again.';
            if (error instanceof API.APIError) {
                if (error.status === 408) {
                    errorMessage = 'Request timed out. The server is taking too long to respond.';
                } else if (error.status === 429) {
                    errorMessage = 'Too many requests. Please wait a moment and try again.';
                } else if (error.status >= 500) {
                    errorMessage = 'Server error. Please try again later.';
                } else if (error.status === 0) {
                    errorMessage = 'Network error. Please check your connection.';
                } else {
                    errorMessage = error.message;
                }
            } else if (error.message) {
                errorMessage = error.message;
            }

            UI.showToast(errorMessage, 'error');
        } finally {
            setFormLoading(false);
        }
    }

    /**
     * Validate URL format
     */
    function isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    /**
     * Detect primary programming language from tech stack
     */
    function detectLanguage(techStack) {
        const techMap = {
            'JavaScript': 'javascript',
            'React': 'javascript',
            'Vue.js': 'javascript',
            'Angular': 'javascript',
            'Node.js': 'javascript',
            'TypeScript': 'typescript',
            'PHP': 'php',
            'Laravel': 'php',
            'WordPress': 'php',
            'Python': 'python',
            'Django': 'python',
            'Flask': 'python',
            'Ruby': 'ruby',
            'Ruby on Rails': 'ruby',
            'Java': 'java',
            'Spring': 'java',
            'Go': 'go',
        };

        for (const tech of techStack) {
            const name = tech.name || '';
            for (const [key, lang] of Object.entries(techMap)) {
                if (name.includes(key)) {
                    return lang;
                }
            }
        }
        return 'web';
    }

    /**
     * Simulate progress updates
     */
    function simulateProgress(maxPages = 10) {
        const baseSteps = [
            { progress: 10, status: 'Initializing crawler...' },
            { progress: 20, status: 'Fetching main page...' },
            { progress: 30, status: 'Analyzing HTML structure...' },
            { progress: 40, status: 'Detecting technologies...' },
            { progress: 50, status: 'Running SEO checks...' },
            { progress: 60, status: 'Analyzing performance...' },
        ];

        const crawlSteps = [
            { progress: 70, status: 'Crawling internal pages...' },
            { progress: 80, status: 'Checking links and resources...' },
            { progress: 90, status: 'Analyzing sub-pages...' },
        ];

        const finalSteps = [
            { progress: 95, status: 'Generating report...' },
        ];

        const allSteps = [...baseSteps, ...crawlSteps, ...finalSteps];

        let index = 0;
        const interval = setInterval(() => {
            if (index < allSteps.length && document.getElementById('progressSection').classList.contains('hidden') === false) {
                const step = allSteps[index];
                UI.updateProgress(step.progress, step.status);
                index++;
            } else {
                clearInterval(interval);
            }
        }, 600);
    }

    /**
     * Update progress status directly
     */
    function updateProgressStatus(status) {
        const statusEl = document.getElementById('progressStatus');
        if (statusEl) {
            statusEl.textContent = status;
        }
    }

    /**
     * Set form loading state
     */
    function setFormLoading(loading) {
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner w-5 h-5 border-2"></div>';
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<i class="fas fa-search"></i><span>Analyze</span>`;
        }
    }

    /**
     * Delay helper
     */
    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // Add quick demo URLs
    const demoUrls = [
        'https://example.com',
        'https://github.com',
        'https://wordpress.org',
        'https://shopify.com',
    ];

    // Add URL suggestions on focus
    urlInput.addEventListener('focus', () => {
        if (!urlInput.value) {
            urlInput.placeholder = `e.g., ${demoUrls[Math.floor(Math.random() * demoUrls.length)]}`;
        }
    });
});
