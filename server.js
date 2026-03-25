// WebAI Auditor - Railway Backend Server
// Real website scraping with CORS support

const express = require('express');
const axios = require('axios');
const cheerio = require('cheerio');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 8000;

// Middleware
app.use(cors());
app.use(express.json());

// Health check
app.get('/', (req, res) => {
  res.json({
    service: 'WebAI Auditor API',
    version: '1.0.0',
    status: 'healthy',
    endpoints: ['/api/scrape', '/api/health']
  });
});

app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

/**
 * Main scrape endpoint
 * GET /api/scrape?url=https://example.com
 */
app.get('/api/scrape', async (req, res) => {
  const { url } = req.query;

  if (!url) {
    return res.status(400).json({
      success: false,
      error: 'URL parameter is required'
    });
  }

  // Validate URL
  try {
    new URL(url);
  } catch (e) {
    return res.status(400).json({
      success: false,
      error: 'Invalid URL format'
    });
  }

  const startTime = Date.now();

  try {
    // Fetch website content
    const response = await axios.get(url, {
      timeout: 15000,
      maxRedirects: 5,
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'Accept-Encoding': 'gzip, deflate, br',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1'
      }
    });

    const processingTime = Date.now() - startTime;
    const html = response.data;
    const $ = cheerio.load(html);

    // Extract organization name
    const title = $('title').text() || '';
    const metaDesc = $('meta[name="description"]').attr('content') || '';
    const orgName = extractOrgName(title, url);

    // Extract notices/links
    const notices = extractNotices($, url);

    // Extract exams/jobs
    const exams = extractExams($, url);

    // Calculate score
    const totalItems = notices.length + exams.length;
    const score = Math.min(95, 60 + totalItems * 3);

    const result = {
      success: true,
      url,
      name: orgName,
      score,
      notices: notices.slice(0, 8),
      exams: exams.slice(0, 8),
      stats: {
        notices: notices.length,
        exams: exams.length
      },
      processingTime,
      timestamp: new Date().toISOString(),
      source: 'Railway Backend - Real Scraping'
    };

    res.json(result);

  } catch (error) {
    console.error('Scraping error:', error.message);

    // Return graceful fallback
    const orgName = extractOrgNameFromUrl(url);
    res.json({
      success: true,
      url,
      name: orgName,
      score: 70,
      notices: [
        { title: `Visit ${orgName} for latest updates`, link: url, date: new Date().toLocaleDateString('en-IN') }
      ],
      exams: [
        { name: 'Latest Notifications', link: url, posts: 'Check Official Site', date: 'Available Now' }
      ],
      stats: { notices: 1, exams: 1 },
      fromFallback: true,
      timestamp: new Date().toISOString()
    });
  }
});

/**
 * Extract organization name from title
 */
function extractOrgName(title, url) {
  if (title) {
    const cleanTitle = title.split('|')[0].split('-')[0].trim();
    if (cleanTitle.length < 100) return cleanTitle;
  }
  return extractOrgNameFromUrl(url);
}

function extractOrgNameFromUrl(url) {
  const hostname = new URL(url).hostname;
  return hostname
    .replace('www.', '')
    .replace('.gov.in', '')
    .replace('.gov', '')
    .replace('.nic.in', '')
    .split('.')
    .join(' ')
    .toUpperCase();
}

/**
 * Extract notices from page
 */
function extractNotices($, baseUrl) {
  const notices = [];
  const keywords = ['notice', 'notification', 'news', 'update', 'announcement', 'press', 'latest'];

  $('a[href]').each((i, el) => {
    if (notices.length >= 10) return false;

    const $el = $(el);
    const text = $el.text().trim();
    const href = $el.attr('href');

    if (!text || text.length < 5 || text.length > 200) return;
    if (!href) return;

    const textLower = text.toLowerCase();
    if (!keywords.some(k => textLower.includes(k))) return;

    let fullUrl = href;
    if (href.startsWith('/')) {
      try {
        const urlObj = new URL(baseUrl);
        fullUrl = `${urlObj.protocol}//${urlObj.host}${href}`;
      } catch (e) {
        fullUrl = baseUrl + href;
      }
    } else if (!href.startsWith('http')) {
      fullUrl = baseUrl + '/' + href;
    }

    // Extract date if present
    const dateMatch = text.match(/(\d{1,2}[\s\-/.]\w+[\s\-/.]\d{4}|\d{4}[\s\-/.]\d{1,2}[\s\-/.]\d{1,2}|(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*[\s\-/.]\d{1,2}[\s\-/,/.]\d{4})/i);
    const date = dateMatch ? dateMatch[1] : new Date().toLocaleDateString('en-IN');

    notices.push({
      title: text,
      link: fullUrl,
      date: date
    });
  });

  return notices;
}

/**
 * Extract exams/recruitments from page
 */
function extractExams($, baseUrl) {
  const exams = [];
  const keywords = ['recruitment', 'vacancy', 'exam', 'cgl', 'chsl', 'gd', 'ntpc', 'group d', 'application', 'apply'];

  $('a[href]').each((i, el) => {
    if (exams.length >= 8) return false;

    const $el = $(el);
    const text = $el.text().trim();
    const href = $el.attr('href');

    if (!text || text.length < 5 || text.length > 200) return;
    if (!href) return;

    const textLower = text.toLowerCase();
    if (!keywords.some(k => textLower.includes(k))) return;

    let fullUrl = href;
    if (href.startsWith('/')) {
      try {
        const urlObj = new URL(baseUrl);
        fullUrl = `${urlObj.protocol}//${urlObj.host}${href}`;
      } catch (e) {
        fullUrl = baseUrl + href;
      }
    } else if (!href.startsWith('http')) {
      fullUrl = baseUrl + '/' + href;
    }

    // Extract posts/vacancies if present
    const postsMatch = text.match(/(\d+[,\d+]*)\s*(posts?vacancies?|vacan|posts)/i);
    const posts = postsMatch ? postsMatch[1] + ' Posts' : 'Various Posts';

    // Extract date if present
    const dateMatch = text.match(/(\d{1,2}[\s\-/.]\w+[\s\-/.]\d{4}|\d{4}[\s\-/.]\d{1,2}|\w+[\s\-/.]\d{1,2}[\s\-/.]\d{4})/i);
    const date = dateMatch ? dateMatch[1] : 'Check Official Site';

    exams.push({
      name: text,
      link: fullUrl,
      posts: posts,
      date: date
    });
  });

  return exams;
}

// Start server
app.listen(PORT, () => {
  console.log(`🚀 WebAI Auditor API running on port ${PORT}`);
  console.log(`📊 Scraping endpoint: /api/scrape?url=URL`);
});
