/**
 * Universal Web Scraper Service
 * Handles fetching and parsing HTML from any URL
 * Optimized to avoid blocks and memory issues
 */

import axios, { AxiosError } from 'axios';
import * as cheerio from 'cheerio';
import { compressContent, generateContentHash, normalizeContent } from '../utils/hash';
import { ContentSelectors, SiteMetadata, ScrapingResult } from '../types';

// ============================================
// CONFIGURATION
// ============================================
const SCRAPER_CONFIG = {
  timeout: 30000, // 30 seconds (Cloud Functions max is much higher, but we want quick fails)
  maxRetries: 2,
  retryDelay: 1000,
  maxContentSize: 10 * 1024 * 1024, // 10MB max response size
  maxRedirects: 3,
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
};

// ============================================
// CUSTOM USER AGENTS (ROTATION)
// ============================================
const USER_AGENTS = [
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
];

// ============================================
// ERROR TYPES
// ============================================
export class ScrapingError extends Error {
  constructor(
    public code: string,
    message: string,
    public isPermanent: boolean = false
  ) {
    super(message);
    this.name = 'ScrapingError';
  }
}

// ============================================
// MAIN SCRAPER CLASS
// ============================================
export class WebScraper {
  private selectors?: ContentSelectors;

  constructor(selectors?: ContentSelectors) {
    this.selectors = selectors;
  }

  /**
   * Main scraping method - fetches and processes a URL
   */
  async scrape(url: string): Promise<ScrapingResult> {
    const startTime = Date.now();

    try {
      // Validate URL
      const validatedUrl = this.validateAndFormatUrl(url);

      // Fetch HTML
      const response = await this.fetchWithRetry(validatedUrl);

      // Check content size
      const contentSize = Buffer.byteLength(response.data, 'utf8');
      if (contentSize > SCRAPER_CONFIG.maxContentSize) {
        throw new ScrapingError(
          'CONTENT_TOO_LARGE',
          `Response size (${contentSize} bytes) exceeds maximum allowed`,
          true
        );
      }

      // Parse HTML
      const $ = cheerio.load(response.data);

      // Extract visible text content
      const visibleContent = this.extractVisibleContent($, validatedUrl);

      // Generate metadata
      const metadata = this.extractMetadata($, response, validatedUrl, contentSize, startTime);

      // Generate hash
      const contentHash = generateContentHash(visibleContent);

      return {
        success: true,
        url: validatedUrl,
        statusCode: response.status,
        contentHash,
        contentSnapshot: compressContent(visibleContent),
        title: metadata.title,
        metadata,
        processingTime: Date.now() - startTime,
      };
    } catch (error) {
      return this.handleError(error, url, startTime);
    }
  }

  /**
   * Validate and format URL
   */
  private validateAndFormatUrl(url: string): string {
    try {
      let formattedUrl = url.trim();

      // Add protocol if missing
      if (!formattedUrl.startsWith('http://') && !formattedUrl.startsWith('https://')) {
        formattedUrl = `https://${formattedUrl}`;
      }

      const urlObj = new URL(formattedUrl);

      // Ensure protocol is https for security
      if (urlObj.protocol === 'http:') {
        urlObj.protocol = 'https:';
      }

      return urlObj.toString();
    } catch (error) {
      throw new ScrapingError('INVALID_URL', `Invalid URL: ${url}`, true);
    }
  }

  /**
   * Fetch HTML with retry logic
   */
  private async fetchWithRetry(url: string, attempt: number = 1): Promise<any> {
    try {
      const randomUserAgent = USER_AGENTS[Math.floor(Math.random() * USER_AGENTS.length)];

      const response = await axios.get(url, {
        timeout: SCRAPER_CONFIG.timeout,
        maxRedirects: SCRAPER_CONFIG.maxRedirects,
        headers: {
          'User-Agent': randomUserAgent,
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
          'Accept-Language': 'en-US,en;q=0.9,hi;q=0.8',
          'Accept-Encoding': 'gzip, deflate, br',
          'DNT': '1',
          'Connection': 'keep-alive',
          'Upgrade-Insecure-Requests': '1',
          'Cache-Control': 'max-age=0',
        },
        // Decompress automatically
        decompress: true,
        // Validate SSL certificates (set to false only for testing)
        httpsAgent: undefined, // Use default
      });

      return response;
    } catch (error) {
      if (attempt < SCRAPER_CONFIG.maxRetries && this.isRetryableError(error)) {
        // Exponential backoff
        await this.delay(SCRAPER_CONFIG.retryDelay * attempt);
        return this.fetchWithRetry(url, attempt + 1);
      }
      throw error;
    }
  }

  /**
   * Check if error is retryable
   */
  private isRetryableError(error: unknown): boolean {
    if (error instanceof AxiosError) {
      const statusCode = error.response?.status;
      // Retry on: 429 (rate limit), 500, 502, 503, 504, network errors
      return (
        statusCode === 429 ||
        (statusCode && statusCode >= 500) ||
        error.code === 'ECONNRESET' ||
        error.code === 'ETIMEDOUT' ||
        error.code === 'ENOTFOUND'
      );
    }
    return false;
  }

  /**
   * Extract visible text content from HTML
   * Ignores scripts, styles, navbars, ads, etc.
   */
  private extractVisibleContent($: cheerio.CheerioAPI, baseUrl: string): string {
    let $content = $('body');

    // Use custom main content selector if provided
    if (this.selectors?.mainContent) {
      const customContent = $(this.selectors.mainContent);
      if (customContent.length > 0) {
        $content = customContent.first();
      }
    }

    // Clone to avoid modifying original
    $content = $content.clone();

    // Remove elements that shouldn't be compared
    const removeSelectors = [
      'script',
      'style',
      'noscript',
      'iframe',
      'nav',
      'header', // Usually contains navigation
      'footer',
      'aside', // Sidebars
      '.ad',
      '.ads',
      '.advertisement',
      '.social-share',
      '.related-posts',
      '.comments',
      '.cookie-banner',
      '.popup',
      '.modal',
      '.newsletter',
      '.visitor-counter',
      '.share-buttons',
      '[role="complementary"]',
      // Custom exclude selectors
      ...(this.selectors?.excludeSelectors || []),
    ];

    removeSelectors.forEach(selector => {
      $content.find(selector).remove();
    });

    // Get text content
    let text = $content.text();

    // Clean up text
    text = text
      .replace(/\s+/g, ' ')
      .replace(/\n\s*\n/g, '\n')
      .trim();

    return text;
  }

  /**
   * Extract metadata from the page
   */
  private extractMetadata(
    $: cheerio.CheerioAPI,
    response: any,
    url: string,
    contentSize: number,
    startTime: number
  ): SiteMetadata {
    const urlObj = new URL(url);

    return {
      domain: urlObj.hostname,
      favicon: $('link[rel="icon"]').attr('href') || $('link[rel="shortcut icon"]').attr('href'),
      pageTitle: $('title').text() || $('meta[property="og:title"]').attr('content') || '',
      description: $('meta[name="description"]').attr('content') || $('meta[property="og:description"]').attr('content') || '',
      lastStatusCode: response.status,
      responseTime: Date.now() - startTime,
      contentType: response.headers['content-type'] || 'text/html',
      size: contentSize,
    };
  }

  /**
   * Handle errors and return consistent error response
   */
  private handleError(error: unknown, url: string, startTime: number): ScrapingResult {
    const processingTime = Date.now() - startTime;

    if (error instanceof ScrapingError) {
      return {
        success: false,
        url,
        error: {
          code: error.code,
          message: error.message,
          isPermanent: error.isPermanent,
        },
        processingTime,
      };
    }

    if (error instanceof AxiosError) {
      const statusCode = error.response?.status;
      const isPermanent = statusCode === 404 || statusCode === 403 || statusCode === 410;

      return {
        success: false,
        url,
        error: {
          code: `HTTP_${statusCode || 'NETWORK_ERROR'}`,
          message: statusCode
            ? `HTTP ${statusCode}: ${error.response?.statusText || 'Error'}`
            : error.code === 'ENOTFOUND'
            ? 'Website not found'
            : error.code === 'ETIMEDOUT'
            ? 'Connection timeout'
            : 'Network error',
          isPermanent,
        },
        processingTime,
      };
    }

    return {
      success: false,
      url,
      error: {
        code: 'UNKNOWN_ERROR',
        message: error instanceof Error ? error.message : 'Unknown error occurred',
        isPermanent: false,
      },
      processingTime,
    };
  }

  /**
   * Delay helper for retry logic
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// ============================================
// BATCH SCRAPING (For Multiple URLs)
// ============================================
export class BatchScraper {
  private readonly maxConcurrent: number;

  constructor(maxConcurrent: number = 5) {
    this.maxConcurrent = maxConcurrent;
  }

  /**
   * Scrape multiple URLs concurrently with rate limiting
   */
  async scrapeMultiple(urls: string[]): Promise<ScrapingResult[]> {
    const results: ScrapingResult[] = [];

    // Process in batches to avoid overwhelming servers
    for (let i = 0; i < urls.length; i += this.maxConcurrent) {
      const batch = urls.slice(i, i + this.maxConcurrent);
      const batchResults = await Promise.allSettled(
        batch.map(url => new WebScraper().scrape(url))
      );

      batchResults.forEach(result => {
        if (result.status === 'fulfilled') {
          results.push(result.value);
        } else {
          results.push({
            success: false,
            url: '',
            error: {
              code: 'PROMISE_REJECTED',
              message: result.reason?.message || 'Promise rejected',
              isPermanent: false,
            },
            processingTime: 0,
          });
        }
      });

      // Small delay between batches to be polite
      if (i + this.maxConcurrent < urls.length) {
        await new Promise(resolve => setTimeout(resolve, 1000));
      }
    }

    return results;
  }
}

// ============================================
// EXPORT HELPER FUNCTIONS
// ============================================
export async function scrapeUrl(url: string, selectors?: ContentSelectors): Promise<ScrapingResult> {
  const scraper = new WebScraper(selectors);
  return scraper.scrape(url);
}

export async function scrapeMultipleUrls(urls: string[]): Promise<ScrapingResult[]> {
  const batchScraper = new BatchScraper(5); // 5 concurrent requests
  return batchScraper.scrapeMultiple(urls);
}
