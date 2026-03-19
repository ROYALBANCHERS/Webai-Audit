// WebAI Auditor - Real Website Scraper API
// Uses web reader MCP for actual content extraction

const https = require('https');

/**
 * Scrape government website and extract real data
 */
async function scrapeGovernmentWebsite(url) {
    try {
        // First try to fetch with web reader
        const data = await fetchWithWebReader(url);

        if (data.success) {
            return {
                success: true,
                url,
                ...extractGovData(data.content, url)
            };
        }

        return { success: false, error: 'Failed to scrape' };
    } catch (error) {
        return { success: false, error: error.message };
    }
}

/**
 * Fetch website content using web reader
 */
function fetchWithWebReader(url) {
    return new Promise((resolve) => {
        const options = {
            hostname: 'api.allorigins.win',
            path: `/raw?url=${encodeURIComponent(url)}`,
            method: 'GET',
            headers: { 'User-Agent': 'Mozilla/5.0' }
        };

        https.get(options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                resolve({
                    success: res.statusCode === 200,
                    content: data,
                    statusCode: res.statusCode
                });
            });
        }).on('error', () => resolve({ success: false, content: '' }));
    });
}

/**
 * Extract government job data from HTML
 */
function extractGovData(html, url) {
    const notices = [];
    const exams = [];
    const jobs = [];

    // Extract notices
    const noticeRegex = /<a[^>]*href="([^"]*)"[^>]*>([^<]*(?:notices?|notifications?|news|updates?|latest)[^<]*)<\/a>/gi;
    let match;
    let noticeCount = 0;

    while ((match = noticeRegex.exec(html)) !== null && noticeCount < 10) {
        let link = match[1];
        if (link.startsWith('/')) {
            const urlObj = new URL(url);
            link = `${urlObj.protocol}//${urlObj.host}${link}`;
        }
        notices.push({
            title: match[2].replace(/<[^>]*>/g, '').trim(),
            link: link,
            date: new Date().toLocaleDateString('en-IN')
        });
        noticeCount++;
    }

    // Extract exam/recruitment info
    const examRegex = /<a[^>]*href="([^"]*)"[^>]*>([^<]*(?:recruitment|exam|vacancy|cgl|chsl|civil services|notification)[^<]*)<\/a>/gi;
    let examCount = 0;

    while ((match = examRegex.exec(html)) !== null && examCount < 10) {
        let link = match[1];
        if (link.startsWith('/')) {
            const urlObj = new URL(url);
            link = `${urlObj.protocol}//${urlObj.host}${link}`;
        }
        exams.push({
            name: match[2].replace(/<[^>]*>/g, '').trim(),
            link: link,
            posts: 'Various Posts',
            date: 'Check Official Site'
        });
        examCount++;
    }

    // Extract page title
    const titleMatch = html.match(/<title[^>]*>([^<]*)<\/title>/i);
    const title = titleMatch ? titleMatch[1] : new URL(url).hostname;

    // Calculate score based on content
    const score = Math.min(95, 50 + (notices.length + exams.length) * 5);

    return {
        name: title.replace(/\|.*/g, '').trim().substring(0, 50),
        score,
        notices: notices.length > 0 ? notices : [{ title: 'Visit official website for latest updates', link: url, date: new Date().toLocaleDateString('en-IN') }],
        exams: exams.length > 0 ? exams : [{ name: 'Latest Recruitment', link: url, posts: 'Various Posts', date: 'Check Official Site' }],
        jobs,
        stats: {
            notices: notices.length,
            exams: exams.length,
            jobs: jobs.length
        },
        timestamp: new Date().toISOString()
    };
}

/**
 * Search for jobs across all sources
 */
async function searchJobs(query) {
    const sources = [
        { name: 'SSC', url: 'https://ssc.gov.in' },
        { name: 'UPSC', url: 'https://upsc.gov.in' },
        { name: 'IBPS', url: 'https://ibps.in' },
        { name: 'Railway', url: 'https://rrb.gov.in' }
    ];

    const results = [];

    for (const source of sources) {
        try {
            const data = await scrapeGovernmentWebsite(source.url);
            if (data.success) {
                // Filter by query if provided
                if (!query || data.name.toLowerCase().includes(query.toLowerCase())) {
                    results.push(data);
                }
            }
        } catch (e) {
            // Continue with next source
        }
    }

    return results;
}

// Vercel/Netlify handler
export default async function handler(req, res) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') return res.status(200).end();

    const { action, url, query, token } = req.query;

    try {
        switch (action) {
            case 'scrape':
                if (!url) return res.json({ success: false, error: 'URL required' });
                const result = await scrapeGovernmentWebsite(url);
                return res.json(result);

            case 'search':
                const searchResults = await searchJobs(query);
                return res.json({ success: true, results: searchResults });

            case 'jobs':
                // Return local jobs file
                const fs = require('fs');
                const path = require('path');
                const jobsPath = path.join(process.cwd(), 'api', 'gov-jobs.json');
                if (fs.existsSync(jobsPath)) {
                    const jobsData = JSON.parse(fs.readFileSync(jobsPath, 'utf8'));
                    // Filter by query if provided
                    if (query) {
                        jobsData.jobs = jobsData.jobs.filter(j =>
                            j.title.toLowerCase().includes(query.toLowerCase()) ||
                            j.organization.toLowerCase().includes(query.toLowerCase())
                        );
                    }
                    return res.json(jobsData);
                }
                return res.json({ success: false, error: 'Jobs data not found' });

            default:
                return res.json({
                    success: true,
                    message: 'WebAI Auditor API v2',
                    endpoints: ['scrape', 'search', 'jobs'],
                    usage: '?action=scrape&url=https://ssc.gov.in'
                });
        }
    } catch (error) {
        return res.status(500).json({ success: false, error: error.message });
    }
}
