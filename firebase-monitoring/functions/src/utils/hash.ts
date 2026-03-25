/**
 * Content Hashing Utility
 * Generates consistent hashes for content comparison
 * Uses SHA-256 for cryptographic hashing
 */

import { createHash } from 'crypto';

/**
 * Generate a hash from text content
 * Normalizes text to avoid false positives from whitespace/formatting changes
 */
export function generateContentHash(content: string): string {
  // Normalize content to reduce false positives
  const normalized = normalizeContent(content);
  return createHash('sha256').update(normalized).digest('hex');
}

/**
 * Normalize content for consistent hashing
 * Removes/normalizes elements that cause false positives
 */
export function normalizeContent(content: string): string {
  return content
    // Remove extra whitespace
    .replace(/\s+/g, ' ')
    // Remove visitor counters (common false positive)
    .replace(/\d{1,3}(?:,\d{3})*(?:\.\d+)?\s*(?:visits?|views?|followers?)/gi, '')
    // Remove timestamps (unless they're meaningful dates)
    .replace(/Last updated:?\s*\d{1,2}:\d{2}\s*(?:AM|PM|am|pm)/gi, '')
    // Remove random IDs/keys
    .replace(/["'][a-zA-Z0-9]{20,}["']/g, '""')
    // Normalize line endings
    .replace(/\r\n/g, '\n')
    // Remove leading/trailing whitespace
    .trim()
    // Convert to lowercase for domain consistency
    .toLowerCase();
}

/**
 * Calculate the percentage difference between two content strings
 */
export function calculateContentDifference(
  oldContent: string,
  newContent: string
): number {
  const oldLength = oldContent.length;
  const newLength = newContent.length;
  const maxLength = Math.max(oldLength, newLength);

  if (maxLength === 0) return 0;

  // Simple character-based difference
  // For more sophisticated diff, use 'diff' package
  const diff = Math.abs(newLength - oldLength) / maxLength;
  return Math.round(diff * 100);
}

/**
 * Extract meaningful keywords from content
 * Used for categorizing changes
 */
export function extractKeywords(content: string): string[] {
  const keywordPatterns = {
    jobs: /\b(vacancy|recruitment|post| vacancy|job|opening|position|application)\b/gi,
    exam: /\b(exam|result|admit card|schedule|date|notification|cutoff|merit)\b/gi,
    admission: /\b(admission|form|registration|enrollment|course|college|university)\b/gi,
    dates: /\b(january|february|march|april|may|june|july|august|september|october|november|december|\d{1,2}[/-]\d{1,2}[/-]\d{2,4})\b/gi,
    urgent: /\b(urgent|immediate|last date|closing soon|deadline|extended)\b/gi,
  };

  const foundKeywords: string[] = [];
  const contentLower = content.toLowerCase();

  for (const [category, pattern] of Object.entries(keywordPatterns)) {
    const matches = contentLower.match(pattern);
    if (matches && matches.length > 0) {
      // Get unique keywords
      const uniqueKeywords = [...new Set(matches)].slice(0, 5);
      foundKeywords.push(...uniqueKeywords.map(k => `[${category}] ${k}`));
    }
  }

  return foundKeywords;
}

/**
 * Compress content for storage (to reduce Firestore document size)
 * Removes HTML and keeps only meaningful text
 */
export function compressContent(html: string): string {
  // Remove script tags, style tags, comments
  let compressed = html
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/gi, '')
    .replace(/<!--[\s\S]*?-->/g, '')
    // Remove common non-content elements
    .replace(/<nav\b[^<]*(?:(?!<\/nav>)<[^<]*)*<\/nav>/gi, '')
    .replace(/<footer\b[^<]*(?:(?!<\/footer>)<[^<]*)*<\/footer>/gi, '')
    .replace(/<aside\b[^<]*(?:(?!<\/aside>)<[^<]*)*<\/aside>/gi, '')
    // Decode HTML entities
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    // Remove remaining HTML tags but keep text
    .replace(/<[^>]+>/g, ' ')
    // Normalize whitespace
    .replace(/\s+/g, ' ')
    .trim();

  // Limit to 50,000 characters (Firestore document size limit)
  return compressed.substring(0, 50000);
}

/**
 * Generate a unique ID for a change record
 */
export function generateChangeId(siteId: string): string {
  return `${siteId}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`;
}

/**
 * Check if content change is significant enough to warrant AI analysis
 */
export function isSignificantChange(
  oldContent: string,
  newContent: string,
  threshold: number = 5 // 5% change threshold
): boolean {
  const difference = calculateContentDifference(oldContent, newContent);
  return difference >= threshold;
}
