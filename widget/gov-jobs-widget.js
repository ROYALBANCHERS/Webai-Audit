/**
 * Government Jobs Widget
 * Embed on any website to show latest government jobs
 *
 * Usage:
 * <script src="gov-jobs-widget.js"
 *         data-categories="ssc,upsc,ibps,railway,defence,teaching"
 *         data-style="modern"
 *         data-theme="purple"
 *         data-count="10"
 *         data-auto-refresh="true"></script>
 * <div id="gov-jobs-widget"></div>
 */

(function() {
    'use strict';

    // Configuration from script attributes
    const script = document.currentScript || document.querySelector('script[src*="gov-jobs-widget.js"]');
    const config = {
        categories: script.getAttribute('data-categories') || 'ssc,upsc,ibps,railway,defence,teaching',
        style: script.getAttribute('data-style') || 'modern',
        theme: script.getAttribute('data-theme') || 'purple',
        count: parseInt(script.getAttribute('data-count')) || 10,
        autoRefresh: script.getAttribute('data-auto-refresh') !== 'false',
        refreshInterval: 60 * 60 * 1000 // 1 hour
    };

    // Theme colors
    const themes = {
        purple: {
            primary: '#8b5cf6',
            secondary: '#6366f1',
            bg: 'rgba(139, 92, 246, 0.1)',
            text: '#1f2937',
            lightText: '#6b7280'
        },
        blue: {
            primary: '#3b82f6',
            secondary: '#06b6d4',
            bg: 'rgba(59, 130, 246, 0.1)',
            text: '#1f2937',
            lightText: '#6b7280'
        },
        green: {
            primary: '#10b981',
            secondary: '#059669',
            bg: 'rgba(16, 185, 129, 0.1)',
            text: '#1f2937',
            lightText: '#6b7280'
        },
        red: {
            primary: '#ef4444',
            secondary: '#ec4899',
            bg: 'rgba(239, 68, 68, 0.1)',
            text: '#1f2937',
            lightText: '#6b7280'
        },
        dark: {
            primary: '#374151',
            secondary: '#1f2937',
            bg: '#1f2937',
            text: '#f9fafb',
            lightText: '#d1d5db'
        }
    };

    // Current job data
    let jobData = null;
    let refreshTimer = null;

    // API endpoint
    const API_URL = 'https://royalbanchers.github.io/Webai-Audit/api/gov-jobs.json';

    // Fallback static data (in case API fails)
    const fallbackJobs = [
        {
            id: 'ssc-cgl-2025',
            title: 'SSC CGL 2025 Notification',
            organization: 'SSC',
            category: 'ssc',
            posts: 'Group B & C Posts',
            vacancies: '17727',
            lastDate: '31 July 2025',
            applyLink: 'https://ssc.gov.in',
            new: true
        },
        {
            id: 'upsc-cse-2025',
            title: 'UPSC Civil Services 2025',
            organization: 'UPSC',
            category: 'upsc',
            posts: 'IAS, IPS, IFS & Others',
            vacancies: '1056',
            lastDate: '18 February 2025',
            applyLink: 'https://upsc.gov.in',
            new: true
        },
        {
            id: 'ibps-po-xv',
            title: 'IBPS PO XV 2025-26',
            organization: 'IBPS',
            category: 'ibps',
            posts: 'Probationary Officers',
            vacancies: '4234',
            lastDate: '28 August 2025',
            applyLink: 'https://ibps.in',
            new: false
        },
        {
            id: 'railway-ntpc',
            title: 'Railway RRB NTPC 2025',
            organization: 'Railways',
            category: 'railway',
            posts: 'Non-Technical Popular Categories',
            vacancies: '3445',
            lastDate: '30 September 2025',
            applyLink: 'https://rrbcdg.gov.in',
            new: true
        },
        {
            id: 'army-agniveer',
            title: 'Agniveer Rally 2025',
            organization: 'Indian Army',
            category: 'defence',
            posts: 'Agniveer General Duty',
            vacancies: '25000+',
            lastDate: '22 March 2025',
            applyLink: 'https://joinindianarmy.nic.in',
            new: true
        },
        {
            id: 'ssc-chsl-2025',
            title: 'SSC CHSL 2025',
            organization: 'SSC',
            category: 'ssc',
            posts: 'LDC, DEO, PA/SA',
            vacancies: '5402',
            lastDate: '15 July 2025',
            applyLink: 'https://ssc.gov.in',
            new: false
        },
        {
            id: 'ssc-gd-2025',
            title: 'SSC GD Constable 2025',
            organization: 'SSC',
            category: 'ssc',
            posts: 'Constable (GD) in CAPFs',
            vacancies: '39481',
            lastDate: '28 September 2025',
            applyLink: 'https://ssc.gov.in',
            new: true
        },
        {
            id: 'ibps-clerk-xv',
            title: 'IBPS Clerk XV 2025-26',
            organization: 'IBPS',
            category: 'ibps',
            posts: 'Clerical Cadre',
            vacancies: '6128',
            lastDate: '21 July 2025',
            applyLink: 'https://ibps.in',
            new: false
        },
        {
            id: 'kvs-teacher',
            title: 'KVS Recruitment 2025',
            organization: 'KVS',
            category: 'teaching',
            posts: 'PGT, TGT, PRT & Others',
            vacancies: '17954',
            lastDate: '22 February 2025',
            applyLink: 'https://kvsangathan.nic.in',
            new: true
        },
        {
            id: 'upsc-cds-2025',
            title: 'UPSC CDS (I) 2025',
            organization: 'UPSC',
            category: 'upsc',
            posts: 'Officers in Army, Navy, Air Force',
            vacancies: '457',
            lastDate: '31 December 2025',
            applyLink: 'https://upsc.gov.in',
            new: false
        },
        {
            id: 'navy-agniveer',
            title: 'Indian Navy Agniveer 2025',
            organization: 'Indian Navy',
            category: 'defence',
            posts: 'Agniveer SSR, MR',
            vacancies: '3500+',
            lastDate: '15 May 2025',
            applyLink: 'https://joinindiannavy.gov.in',
            new: true
        },
        {
            id: 'dsssb-tgt',
            title: 'DSSSB TGT/PRT 2025',
            organization: 'DSSSB',
            category: 'teaching',
            posts: 'Trained Graduate Teacher',
            vacancies: '5218',
            lastDate: '20 March 2025',
            applyLink: 'https://dsssb.delhi.gov.in',
            new: false
        }
    ];

    // Fetch jobs from API
    async function fetchJobs() {
        try {
            const response = await fetch(API_URL + '?t=' + Date.now());
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.jobs) {
                    jobData = data.jobs;
                    return filterJobs(data.jobs);
                }
            }
        } catch (error) {
            console.warn('Widget: API fetch failed, using fallback data');
        }
        return filterJobs(fallbackJobs);
    }

    // Filter jobs by categories
    function filterJobs(jobs) {
        const selectedCategories = config.categories.split(',').map(c => c.trim().toLowerCase());
        return jobs.filter(job =>
            selectedCategories.includes(job.category.toLowerCase())
        ).slice(0, config.count);
    }

    // Render widget based on style
    function renderWidget(jobs) {
        const container = document.getElementById('gov-jobs-widget');
        if (!container) return;

        const theme = themes[config.theme] || themes.purple;
        let html = '';

        switch (config.style) {
            case 'compact':
                html = renderCompactStyle(jobs, theme);
                break;
            case 'ticker':
                html = renderTickerStyle(jobs, theme);
                break;
            case 'minimal':
                html = renderMinimalStyle(jobs, theme);
                break;
            default:
                html = renderModernStyle(jobs, theme);
        }

        container.innerHTML = html;
    }

    // Modern card style
    function renderModernStyle(jobs, theme) {
        let html = `
            <style>
                #gov-jobs-widget .gjw-container {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    max-width: 100%;
                    margin: 0 auto;
                }
                #gov-jobs-widget .gjw-header {
                    background: linear-gradient(135deg, ${theme.primary}, ${theme.secondary});
                    color: white;
                    padding: 12px 16px;
                    border-radius: 8px 8px 0 0;
                    font-weight: 600;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                #gov-jobs-widget .gjw-refresh {
                    font-size: 12px;
                    opacity: 0.8;
                }
                #gov-jobs-widget .gjw-job-card {
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-top: none;
                    padding: 12px 16px;
                    transition: all 0.2s;
                }
                #gov-jobs-widget .gjw-job-card:last-child {
                    border-radius: 0 0 8px 8px;
                    border-bottom: 1px solid #e5e7eb;
                }
                #gov-jobs-widget .gjw-job-card:hover {
                    background: ${theme.bg};
                }
                #gov-jobs-widget .gjw-job-title {
                    font-weight: 600;
                    color: ${theme.text};
                    margin-bottom: 4px;
                }
                #gov-jobs-widget .gjw-job-meta {
                    display: flex;
                    gap: 12px;
                    font-size: 12px;
                    color: ${theme.lightText};
                }
                #gov-jobs-widget .gjw-new-badge {
                    background: ${theme.primary};
                    color: white;
                    font-size: 10px;
                    padding: 2px 6px;
                    border-radius: 4px;
                    margin-left: 6px;
                }
                #gov-jobs-widget .gjw-apply-link {
                    display: inline-block;
                    margin-top: 8px;
                    font-size: 12px;
                    color: ${theme.primary};
                    text-decoration: none;
                    font-weight: 500;
                }
                #gov-jobs-widget .gjw-apply-link:hover {
                    text-decoration: underline;
                }
                ${config.theme === 'dark' ? `
                #gov-jobs-widget .gjw-job-card {
                    background: ${theme.secondary};
                    border-color: #374151;
                }
                ` : ''}
            </style>
            <div class="gjw-container">
                <div class="gjw-header">
                    <span>🔍 Latest Government Jobs</span>
                    <span class="gjw-refresh">Updated just now</span>
                </div>`;

        jobs.forEach(job => {
            html += `
                <div class="gjw-job-card">
                    <div class="gjw-job-title">
                        ${job.title}
                        ${job.new ? '<span class="gjw-new-badge">NEW</span>' : ''}
                    </div>
                    <div class="gjw-job-meta">
                        <span>📢 ${job.organization}</span>
                        ${job.vacancies ? `<span>👥 ${job.vacancies} Vacancies</span>` : ''}
                        <span>📅 ${job.lastDate}</span>
                    </div>
                    <a href="${job.applyLink}" target="_blank" class="gjw-apply-link">Apply Now →</a>
                </div>`;
        });

        html += `</div>`;
        return html;
    }

    // Compact list style
    function renderCompactStyle(jobs, theme) {
        let html = `
            <style>
                #gov-jobs-widget .gjw-compact {
                    font-family: sans-serif;
                }
                #gov-jobs-widget .gjw-compact-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                #gov-jobs-widget .gjw-compact-item {
                    padding: 8px 12px;
                    border-bottom: 1px solid #e5e7eb;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 13px;
                }
                #gov-jobs-widget .gjw-compact-item:hover {
                    background: ${theme.bg};
                }
                #gov-jobs-widget .gjw-compact-title {
                    color: ${theme.text};
                    font-weight: 500;
                }
                #gov-jobs-widget .gjw-compact-date {
                    color: ${theme.lightText};
                    font-size: 11px;
                    white-space: nowrap;
                }
            </style>
            <div class="gjw-compact">
                <div style="padding: 8px 12px; background: ${theme.primary}; color: white; font-weight: 600; font-size: 14px;">
                    🔍 Gov Jobs
                </div>
                <ul class="gjw-compact-list">`;

        jobs.forEach(job => {
            html += `
                <li class="gjw-compact-item">
                    <span class="gjw-compact-title">${job.title}</span>
                    <span class="gjw-compact-date">${job.lastDate}</span>
                </li>`;
        });

        html += `</ul></div>`;
        return html;
    }

    // News ticker style
    function renderTickerStyle(jobs, theme) {
        let html = `
            <style>
                #gov-jobs-widget .gjw-ticker-wrapper {
                    background: ${theme.primary};
                    padding: 10px;
                    border-radius: 6px;
                    overflow: hidden;
                }
                #gov-jobs-widget .gjw-ticker {
                    display: flex;
                    animation: ticker 30s linear infinite;
                }
                #gov-jobs-widget .gjw-ticker-item {
                    flex-shrink: 0;
                    padding: 0 20px;
                    color: white;
                    font-size: 13px;
                    white-space: nowrap;
                }
                #gov-jobs-widget .gjw-ticker-item a {
                    color: white;
                    text-decoration: none;
                }
                @keyframes ticker {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                #gov-jobs-widget .gjw-ticker:hover {
                    animation-play-state: paused;
                }
            </style>
            <div class="gjw-ticker-wrapper">
                <div class="gjw-ticker">`;

        // Duplicate items for seamless loop
        const tickerJobs = [...jobs, ...jobs];
        tickerJobs.forEach(job => {
            html += `
                <div class="gjw-ticker-item">
                    <a href="${job.applyLink}" target="_blank">
                        📌 ${job.title} | Last Date: ${job.lastDate}
                    </a>
                </div>`;
        });

        html += `</div></div>`;
        return html;
    }

    // Minimal style
    function renderMinimalStyle(jobs, theme) {
        let html = `
            <style>
                #gov-jobs-widget .gjw-minimal {
                    font-family: sans-serif;
                }
                #gov-jobs-widget .gjw-minimal-item {
                    padding: 6px 0;
                    font-size: 13px;
                }
                #gov-jobs-widget .gjw-minimal-link {
                    color: ${theme.text};
                    text-decoration: none;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                #gov-jobs-widget .gjw-minimal-link:hover {
                    color: ${theme.primary};
                }
                #gov-jobs-widget .gjw-dot {
                    width: 6px;
                    height: 6px;
                    background: ${theme.primary};
                    border-radius: 50%;
                }
            </style>
            <div class="gjw-minimal">`;

        jobs.forEach(job => {
            html += `
                <div class="gjw-minimal-item">
                    <a href="${job.applyLink}" target="_blank" class="gjw-minimal-link">
                        <span class="gjw-dot"></span>
                        <span>${job.title}</span>
                    </a>
                </div>`;
        });

        html += `</div>`;
        return html;
    }

    // Initialize widget
    async function init() {
        const jobs = await fetchJobs();
        renderWidget(jobs);

        // Set up auto-refresh
        if (config.autoRefresh) {
            refreshTimer = setInterval(async () => {
                const updatedJobs = await fetchJobs();
                renderWidget(updatedJobs);
            }, config.refreshInterval);
        }
    }

    // Start the widget
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
