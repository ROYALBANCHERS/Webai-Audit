// WebAI Auditor - Serverless API Function
// For Vercel/Netlify - FREE hosting

const axios = require('axios');
const cheerio = require('cheerio');

// Government websites config
const GOV_SOURCES = [
  { name: 'SSC', url: 'https://ssc.gov.in', category: 'ssc', icon: 'purple' },
  { name: 'UPSC', url: 'https://upsc.gov.in', category: 'upsc', icon: 'green' },
  { name: 'IBPS', url: 'https://ibps.in', category: 'ibps', icon: 'blue' },
  { name: 'Railways', url: 'https://rrb.gov.in', category: 'railway', icon: 'orange' },
  { name: 'SBI', url: 'https://sbi.co.in', category: 'banking', icon: 'red' },
  { name: 'LIC', url: 'https://licindia.in', category: 'insurance', icon: 'teal' },
];

/**
 * Main handler - Vercel/Netlify compatible
 */
export default async function handler(req, res) {
  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  const { action, url, domain } = req.query;

  try {
    switch (action) {
      case 'scrape':
        return await scrapeWebsite(req, res);
      case 'check':
        return await checkChanges(req, res);
      case 'sources':
        return res.json({ success: true, sources: GOV_SOURCES });
      case 'jobs':
        return await getLatestJobs(req, res);
      case 'monitor':
        return await monitorMultiple(req, res);
      default:
        return res.json({
          success: true,
          message: 'WebAI Auditor API',
          version: '1.0.0',
          endpoints: ['scrape', 'check', 'sources', 'jobs', 'monitor'],
          usage: '?action=scrape&url=https://example.com'
        });
    }
  } catch (error) {
    return res.status(500).json({
      success: false,
      error: error.message
    });
  }
}

/**
 * Scrape a website and return analysis
 */
async function scrapeWebsite(req, res) {
  const { url } = req.query;
  if (!url) {
    return res.status(400).json({ success: false, error: 'URL required' });
  }

  try {
    const startTime = Date.now();
    const response = await axios.get(url, {
      timeout: 15000,
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
      }
    });

    const $ = cheerio.load(response.data);
    const processingTime = Date.now() - startTime;

    // Extract data
    const result = {
      success: true,
      url,
      statusCode: response.status,
      processingTime,
      timestamp: new Date().toISOString(),
      analysis: {
        title: $('title').text() || '',
        description: $('meta[name="description"]').attr('content') || '',
        links: $('a').length,
        images: $('img').length,
        headings: {
          h1: $('h1').length,
          h2: $('h2').length,
          h3: $('h3').length
        },
        hasMeta: {
          viewport: $('meta[name="viewport"]').length > 0,
          description: $('meta[name="description"]').length > 0,
          ogTitle: $('meta[property="og:title"]').length > 0
        },
        contentSize: Buffer.byteLength(response.data, 'utf8')
      }
    };

    return res.json(result);
  } catch (error) {
    return res.json({
      success: false,
      url,
      error: error.message,
      statusCode: error.response?.status || 0
    });
  }
}

/**
 * Get latest government jobs
 */
async function getLatestJobs(req, res) {
  // Return jobs from local file
  try {
    const fs = require('fs');
    const path = require('path');
    const jobsPath = path.join(process.cwd(), 'api', 'gov-jobs.json');

    if (fs.existsSync(jobsPath)) {
      const jobsData = JSON.parse(fs.readFileSync(jobsPath, 'utf8'));
      return res.json(jobsData);
    }

    return res.json({ success: false, error: 'Jobs data not found' });
  } catch (error) {
    return res.json({ success: false, error: error.message });
  }
}

/**
 * Monitor multiple websites
 */
async function monitorMultiple(req, res) {
  const results = [];

  for (const source of GOV_SOURCES.slice(0, 5)) { // Limit to 5 for speed
    try {
      const start = Date.now();
      const response = await axios.get(source.url, {
        timeout: 10000,
        headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' }
      });

      results.push({
        name: source.name,
        url: source.url,
        status: 'online',
        statusCode: response.status,
        responseTime: Date.now() - start,
        timestamp: new Date().toISOString()
      });
    } catch (error) {
      results.push({
        name: source.name,
        url: source.url,
        status: 'offline',
        error: error.message,
        timestamp: new Date().toISOString()
      });
    }
  }

  return res.json({
    success: true,
    timestamp: new Date().toISOString(),
    total: results.length,
    online: results.filter(r => r.status === 'online').length,
    results
  });
}

/**
 * Check for changes (basic hash comparison)
 */
async function checkChanges(req, res) {
  const { url } = req.query;
  if (!url) {
    return res.status(400).json({ success: false, error: 'URL required' });
  }

  try {
    const response = await axios.get(url, {
      timeout: 15000,
      headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' }
    });

    const crypto = require('crypto');
    const hash = crypto.createHash('sha256').update(response.data).digest('hex');

    return res.json({
      success: true,
      url,
      contentHash: hash,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    return res.json({
      success: false,
      error: error.message
    });
  }
}
