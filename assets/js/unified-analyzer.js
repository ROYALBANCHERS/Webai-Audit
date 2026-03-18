/**
 * Unified Website Analyzer
 * Works with ALL websites - Government & Private
 */

// Government website API configurations
const govAPIs = {
    'ssc.gov.in': {
        name: 'SSC - Staff Selection Commission',
        apis: [
            '/api/general-website/portal/notice-boards',
            '/api/general-website/portal/records?contentType=browse-exam',
            '/api/admin/5.1/allExams'
        ]
    },
    'upsc.gov.in': { name: 'UPSC', apis: [] },
    'ibps.in': { name: 'IBPS', apis: [] },
    'pib.gov.in': { name: 'PIB', apis: [] }
};

// Detect if website is a government site
function isGovWebsite(url) {
    const govDomains = ['.gov.in', '.gov', '.nic.in', 'ssc.gov.in', 'upsc.gov.in', 'ibps.in', 'pib.gov.in'];
    return govDomains.some(domain => url.includes(domain));
}

// Fetch government website data using APIs
async function fetchGovWebsiteData(url) {
    const results = {
        isGov: true,
        notices: [],
        exams: [],
        announcements: [],
        calendar: []
    };

    try {
        // SSC Special handling
        if (url.includes('ssc.gov.in')) {
            // Fetch notices
            const noticeParams = 'page=1&limit=50&contentType=notice-boards&key=createdAt&order=DESC&isAttachment=true&language=english&attributes=id,headline,createdAt';
            const noticeRes = await fetch('https://ssc.gov.in/api/general-website/portal/notice-boards?' + noticeParams);
            if (noticeRes.ok) {
                const noticeData = await noticeRes.json();
                if (noticeData.statusCode === '200') {
                    results.notices = noticeData.data || [];
                }
            }

            // Fetch exams
            const examParams = 'page=1&limit=50&contentType=browse-exam&key=createdAt&order=DESC&isPaginationRequired=false&isAttachment=true&language=english';
            const examRes = await fetch('https://ssc.gov.in/api/general-website/portal/records?' + examParams);
            if (examRes.ok) {
                const examData = await examRes.json();
                if (examData.statusCode === '200') {
                    results.exams = examData.data || [];
                }
            }

            // Fetch announcements
            const ribbonRes = await fetch('https://ssc.gov.in/api/general-website/portal/records?page=1&limit=10&contentType=ribbons&key=createdAt&order=DESC&isAttachment=false&language=english');
            if (ribbonRes.ok) {
                const ribbonData = await ribbonRes.json();
                if (ribbonData.statusCode === '200') {
                    results.announcements = ribbonData.data || [];
                }
            }

            // Fetch calendar
            const calRes = await fetch('https://ssc.gov.in/api/general-website/portal/ssc-calendar?page=1&limit=50&contentType=ssc-calendar&key=startDate&order=ASC&year=2026');
            if (calRes.ok) {
                const calData = await calRes.json();
                if (calData.statusCode === '200') {
                    results.calendar = calData.data || [];
                }
            }
        }

        return results;
    } catch (error) {
        console.error('Gov API Error:', error);
        return results;
    }
}

// Main analyzer function - works for ALL websites
async function analyzeWebsiteClientSide(url) {
    const results = {
        success: false,
        url: url,
        analyzed_at: new Date().toISOString(),
        isGov: isGovWebsite(url),
        pages: [],
        blogs: [],
        pdfs: [],
        notices: [],
        exams: [],
        announcements: [],
        calendar: [],
        summary: '',
        error: null
    };

    try {
        // Check if it's a government website with known APIs
        if (results.isGov) {
            console.log('Government website detected, using specialized APIs...');
            const govData = await fetchGovWebsiteData(url);
            results.notices = govData.notices || [];
            results.exams = govData.exams || [];
            results.announcements = govData.announcements || [];
            results.calendar = govData.calendar || [];
        }

        // Use CORS proxy to fetch the website
        const corsProxies = [
            'https://api.allorigins.win/raw?url=',
            'https://corsproxy.io/?',
            'https://api.codetabs.com/v1/proxy?quest='
        ];

        let html = null;

        // Try different proxies
        for (const proxy of corsProxies) {
            try {
                const proxyUrl = proxy + encodeURIComponent(url);
                const response = await fetch(proxyUrl);
                if (response.ok) {
                    html = await response.text();
                    break;
                }
            } catch (e) {
                continue;
            }
        }

        // Parse HTML if we got it
        if (html) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Extract basic info
            const title = doc.querySelector('title')?.textContent || '';
            const description = doc.querySelector('meta[name="description"]')?.content || '';
            results.title = title;
            results.description = description;

            // Extract all links
            const links = Array.from(doc.querySelectorAll('a[href]'))
                .map(a => {
                    const href = a.getAttribute('href');
                    if (href.startsWith('http')) return href;
                    if (href.startsWith('/')) return new URL(href, url).href;
                    return null;
                })
                .filter(l => l && l.startsWith(new URL(url).origin));

            results.pages = [...new Set(links)].slice(0, 50);

            // Extract blogs/articles
            const blogSelectors = [
                'article', '[class*="blog"]', '[class*="post"]',
                '[id*="blog"]', '[class*="news"]', '[class*="notice"]'
            ];

            for (const selector of blogSelectors) {
                const found = doc.querySelectorAll(selector);
                if (found.length > 0) {
                    found.forEach(el => {
                        const titleEl = el.querySelector('h1, h2, h3, h4, h5, h6');
                        const linkEl = el.querySelector('a[href]');
                        if (linkEl && linkEl.href.startsWith(new URL(url).origin)) {
                            results.blogs.push({
                                title: titleEl?.textContent?.trim() || linkEl.textContent?.trim() || '',
                                url: linkEl.href
                            });
                        }
                    });
                    break;
                }
            }

            // Extract PDFs
            results.pdfs = Array.from(doc.querySelectorAll('a[href$=".pdf"]'))
                .map(a => ({
                    title: a.textContent?.trim() || a.href.split('/').pop(),
                    url: a.href.startsWith('http') ? a.href : new URL(a.href, url).href
                }));
        }

        // Check if we have any data
        const hasData = results.notices.length > 0 || results.exams.length > 0 ||
                       results.blogs.length > 0 || results.pages.length > 0;

        if (!hasData && !html) {
            results.error = 'Could not fetch website. The site may be blocking requests.';
            return results;
        }

        // Generate summary
        if (results.isGov) {
            results.summary = generateGovSummary(url, results);
        } else {
            results.summary = generateBasicSummary(url, results.title || '', results.description || '', results.blogs, results.pdfs, results.pages.length);
        }

        results.success = true;
        return results;

    } catch (error) {
        results.error = error.message;
        return results;
    }
}

// Generate summary for government websites
function generateGovSummary(url, results) {
    let summary = `# Government Website Analysis\n\n`;
    summary += `**URL:** ${url}\n`;
    summary += `**Analysis Date:** ${new Date().toLocaleDateString()}\n\n`;

    if (results.notices.length > 0) {
        summary += `## Latest Notices (${results.notices.length})\n\n`;
        results.notices.slice(0, 10).forEach((notice, i) => {
            const date = notice.createdAt ? notice.createdAt.substring(0, 10) : 'N/A';
            summary += `${i + 1}. **${notice.headline}**\n`;
            summary += `   - Date: ${date}\n\n`;
        });
    }

    if (results.exams.length > 0) {
        summary += `## Exam Updates (${results.exams.length})\n\n`;
        results.exams.slice(0, 5).forEach((exam, i) => {
            summary += `${i + 1}. **${exam.headline}**\n\n`;
        });
    }

    if (results.announcements.length > 0) {
        summary += `## Important Announcements\n\n`;
        results.announcements.forEach((ann, i) => {
            summary += `${i + 1}. ${ann.headline}\n\n`;
        });
    }

    if (results.calendar && results.calendar.length > 0) {
        summary += `## Upcoming Exam Dates\n\n`;
        results.calendar.slice(0, 10).forEach((cal, i) => {
            summary += `${i + 1}. **${cal.headline}**\n`;
            summary += `   - Date: ${cal.startDate ? cal.startDate.substring(0, 10) : 'TBA'}\n\n`;
        });
    }

    return summary;
}

// Generate basic summary for non-government websites
function generateBasicSummary(url, title, description, blogs, pdfs, pageCount) {
    let summary = `# Website Analysis Report\n\n`;
    summary += `**URL:** ${url}\n`;
    summary += `**Title:** ${title || 'N/A'}\n\n`;
    summary += `## Statistics\n\n`;
    summary += `- Total Pages: ${pageCount}\n`;
    summary += `- Blogs/Articles: ${blogs.length}\n`;
    summary += `- PDFs: ${pdfs.length}\n\n`;
    return summary;
}
