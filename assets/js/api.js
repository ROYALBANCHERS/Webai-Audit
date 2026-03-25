/**
 * API Module - Handles all backend communication
 * Production-ready with configurable API endpoint
 */

const API = (() => {
    // Configure API base URL from environment or fallback
    // Supports local development, staging, and production environments
    const API_BASE = window.env?.API_URL ||
                     import.meta.env?.VITE_API_URL ||
                     'https://webai-auditor-backend.abhishek88694.workers.dev/api';

    // Request timeout in milliseconds
    const REQUEST_TIMEOUT = 30000;

    /**
     * Make API request with timeout and error handling
     */
    async function request(endpoint, options = {}) {
        const url = `${API_BASE}${endpoint}`;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), REQUEST_TIMEOUT);

        const config = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers,
            },
            signal: controller.signal,
            ...options,
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            clearTimeout(timeoutId);

            // Handle non-JSON responses
            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                data = { message: text };
            }

            if (!response.ok) {
                throw new APIError(
                    data.error || data.message || `HTTP ${response.status}: Request failed`,
                    response.status,
                    data
                );
            }

            return data;
        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === 'AbortError') {
                throw new APIError('Request timeout - please try again', 408);
            }

            if (error instanceof APIError) {
                throw error;
            }

            // Network errors
            if (error instanceof TypeError) {
                throw new APIError('Network error - please check your connection', 0);
            }

            console.error('API Error:', error);
            throw new APIError('An unexpected error occurred', 500);
        }
    }

    /**
     * Custom API Error class
     */
    class APIError extends Error {
        constructor(message, status, details = null) {
            super(message);
            this.name = 'APIError';
            this.status = status;
            this.details = details;
        }
    }

    /**
     * Run website audit
     */
    async function runAudit(url, options = {}) {
        return request('/audit', {
            method: 'POST',
            body: { url, ...options },
        });
    }

    /**
     * Get audit by ID
     */
    async function getAudit(id) {
        return request(`/audits/${id}`);
    }

    /**
     * List all audits
     */
    async function listAudits(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return request(`/audits${queryString ? `?${queryString}` : ''}`);
    }

    /**
     * Crawl website
     */
    async function crawl(url, options = {}) {
        return request('/crawl', {
            method: 'POST',
            body: { url, ...options },
        });
    }

    /**
     * Analyze tech stack
     */
    async function analyzeTechStack(url) {
        return request('/analyze/tech-stack', {
            method: 'POST',
            body: { url },
        });
    }

    /**
     * Analyze SEO
     */
    async function analyzeSeo(url) {
        return request('/analyze/seo', {
            method: 'POST',
            body: { url },
        });
    }

    /**
     * Find competitors
     */
    async function findCompetitors(url, industry) {
        return request('/competitors', {
            method: 'POST',
            body: { url, industry },
        });
    }

    /**
     * Search GitHub
     */
    async function searchGitHub(query, language) {
        return request('/github/search', {
            method: 'POST',
            body: { query, language },
        });
    }

    /**
     * Get trending repositories
     */
    async function getTrending(language, period) {
        const params = new URLSearchParams({ language, period }).toString();
        return request(`/github/trending?${params}`);
    }

    /**
     * Get statistics
     */
    async function getStats() {
        return request('/stats');
    }

    /**
     * Health check
     */
    async function healthCheck() {
        return request('/health');
    }

    return {
        runAudit,
        getAudit,
        listAudits,
        crawl,
        analyzeTechStack,
        analyzeSeo,
        findCompetitors,
        searchGitHub,
        getTrending,
        getStats,
        healthCheck,
        APIError,
        API_BASE,
    };
})();
