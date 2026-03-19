// WebAI Auditor - Z.AI Powered Scraper
// Uses Z.AI API for intelligent content extraction

const https = require('https');

// Z.AI Configuration
const Z_AI_API_KEY = process.env.Z_AI_API_KEY || 'bb19354bebd5450ca6374b3b015b74c9.0YkKlRVUs9UdxG55';
const Z_AI_BASE_URL = 'api.z.ai';

/**
 * Fetch website content
 */
function fetchWebsite(url) {
    return new Promise((resolve, reject) => {
        // Use AllOrigins CORS proxy
        const proxyUrl = `https://api.allorigins.win/raw?url=${encodeURIComponent(url)}`;

        https.get(proxyUrl, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve({ success: true, content: data, statusCode: res.statusCode }));
        }).on('error', reject);
    });
}

/**
 * Call Z.AI API for content analysis
 */
function callZaiAI(prompt, systemPrompt = '') {
    return new Promise((resolve, reject) => {
        const data = JSON.stringify({
            model: 'zai-gpt-4o',
            messages: [
                { role: 'system', content: systemPrompt || 'You are a web content extraction expert. Extract structured data from HTML content.' },
                { role: 'user', content: prompt }
            ],
            temperature: 0.3,
            response_format: { type: 'json_object' }
        });

        const options = {
            hostname: Z_AI_BASE_URL,
            path: '/chat/completions',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${Z_AI_API_KEY}`
            }
        };

        const req = https.request(options, (res) => {
            let responseData = '';
            res.on('data', chunk => responseData += chunk);
            res.on('end', () => {
                try {
                    const result = JSON.parse(responseData);
                    if (result.choices && result.choices[0]) {
                        resolve(JSON.parse(result.choices[0].message.content));
                    } else {
                        resolve({ success: false, error: 'No response from AI' });
                    }
                } catch (e) {
                    resolve({ success: false, error: e.message });
                }
            });
        });

        req.on('error', reject);
        req.write(data);
        req.end();
    });
}

/**
 * Extract government job data using Z.AI
 */
async function extractGovDataWithAI(html, url) {
    const systemPrompt = `You are a government website content extractor. Extract the following information in JSON format:
- Latest notices/news/updates (title, link, date)
- Upcoming exams/recruitments (name, posts, last date, link)
- Job vacancies (title, vacancies, last date, link)
- Quick links (important sections)

Return valid JSON only.`;

    const prompt = `Extract government job information from this website:
URL: ${url}

HTML Content (truncated to 5000 chars):
${html.substring(0, 5000)}

Return JSON with this structure:
{
  "name": "Organization Name",
  "notices": [{"title": "", "link": "", "date": ""}],
  "exams": [{"name": "", "posts": "", "date": "", "link": ""}],
  "jobs": [{"title": "", "vacancies": "", "lastDate": "", "link": ""}],
  "quickLinks": [{"text": "", "url": ""}]
}`;

    try {
        return await callZaiAI(prompt, systemPrompt);
    } catch (e) {
        // Fallback to regex extraction
        return extractGovDataRegex(html, url);
    }
}

/**
 * Fallback regex-based extraction
 */
function extractGovDataRegex(html, url) {
    const notices = [];
    const exams = [];
    const jobs = [];

    // Extract notices
    const noticeRegex = /<a[^>]*href="([^"]*)"[^>]*>([^<]*(?:notices?|notifications?|news|updates?|latest)[^<]*)<\/a>/gi;
    let match;
    let count = 0;

    while ((match = noticeRegex.exec(html)) !== null && count < 8) {
        let link = match[1];
        if (link.startsWith('/')) {
            try {
                const urlObj = new URL(url);
                link = `${urlObj.protocol}//${urlObj.host}${link}`;
            } catch (e) {}
        }
        notices.push({
            title: match[2].replace(/<[^>]*>/g, '').trim(),
            link: link,
            date: new Date().toLocaleDateString('en-IN')
        });
        count++;
    }

    // Extract exams
    const examRegex = /<a[^>]*href="([^"]*)"[^>]*>([^<]*(?:recruitment|vacancy|exam|cgl|chsl)[^<]*)<\/a>/gi;
    count = 0;

    while ((match = examRegex.exec(html)) !== null && count < 8) {
        let link = match[1];
        if (link.startsWith('/')) {
            try {
                const urlObj = new URL(url);
                link = `${urlObj.protocol}//${urlObj.host}${link}`;
            } catch (e) {}
        }
        exams.push({
            name: match[2].replace(/<[^>]*>/g, '').trim(),
            posts: 'Various Posts',
            date: 'Check Official Site',
            link: link
        });
        count++;
    }

    // Get title
    const titleMatch = html.match(/<title[^>]*>([^<]*)<\/title>/i);
    const name = titleMatch ? titleMatch[1].split('|')[0].split('-')[0].trim() : new URL(url).hostname;

    return {
        name,
        notices: notices.length > 0 ? notices : [{ title: 'Visit official website for latest updates', link: url, date: new Date().toLocaleDateString('en-IN') }],
        exams: exams.length > 0 ? exams : [{ name: 'Latest Recruitment', posts: 'Various Posts', date: 'Check Official Site', link: url }],
        jobs: [],
        quickLinks: []
    };
}

/**
 * Main scrape function with AI enhancement
 */
async function scrapeWithAI(url) {
    try {
        // Fetch website content
        const websiteData = await fetchWebsite(url);

        if (!websiteData.success) {
            return { success: false, error: 'Failed to fetch website' };
        }

        // Extract data using Z.AI
        const aiData = await extractGovDataWithAI(websiteData.content, url);

        // Calculate score
        const totalItems = (aiData.notices?.length || 0) + (aiData.exams?.length || 0) + (aiData.jobs?.length || 0);
        const score = Math.min(95, 50 + totalItems * 5);

        return {
            success: true,
            url,
            name: aiData.name || new URL(url).hostname.toUpperCase(),
            score,
            notices: aiData.notices || [],
            exams: aiData.exams || [],
            jobs: aiData.jobs || [],
            quickLinks: aiData.quickLinks || [],
            stats: {
                notices: aiData.notices?.length || 0,
                exams: aiData.exams?.length || 0,
                jobs: aiData.jobs?.length || 0
            },
            timestamp: new Date().toISOString(),
            source: 'Z.AI Powered Scraper'
        };
    } catch (error) {
        return {
            success: false,
            url,
            error: error.message
        };
    }
}

/**
 * Search jobs across government websites
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
            const data = await scrapeWithAI(source.url);
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

// Export for Vercel/Netlify
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
                const result = await scrapeWithAI(url);
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
                    message: 'WebAI Auditor API - Z.AI Powered',
                    version: '2.0.0',
                    endpoints: ['scrape', 'search', 'jobs'],
                    ai: 'Z.AI GPT-4o',
                    usage: '?action=scrape&url=https://ssc.gov.in'
                });
        }
    } catch (error) {
        return res.status(500).json({ success: false, error: error.message });
    }
}
