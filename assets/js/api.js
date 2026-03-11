/**
 * API Module - Handles all backend communication
 */

const API = (() => {
    const API_BASE = 'http://localhost:8000/api';

    /**
     * Make API request
     */
    async function request(endpoint, options = {}) {
        const url = `${API_BASE}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
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
    };
})();
