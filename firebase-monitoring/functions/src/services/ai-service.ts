/**
 * Gemini AI Service for Change Detection
 * Analyzes content changes and provides intelligent summaries
 */

import { GoogleGenerativeAI, HarmCategory, HarmBlockThreshold } from '@google/generative-ai';
import { AISummary, ChangeType } from '../types';

// ============================================
// CONFIGURATION
// ============================================
const AI_CONFIG = {
  model: 'gemini-2.0-flash-exp', // Fast, cost-effective model
  fallbackModel: 'gemini-1.5-flash',
  maxRetries: 2,
  timeout: 25000, // 25 seconds
  temperature: 0.3, // Low temperature for consistent results
  maxOutputTokens: 500,
};

// Safety settings (reduce false positives)
const SAFETY_SETTINGS = [
  {
    category: HarmCategory.HARM_CATEGORY_HARASSMENT,
    threshold: HarmBlockThreshold.BLOCK_NONE,
  },
  {
    category: HarmCategory.HARM_CATEGORY_HATE_SPEECH,
    threshold: HarmBlockThreshold.BLOCK_NONE,
  },
  {
    category: HarmCategory.HARM_CATEGORY_SEXUALLY_EXPLICIT,
    threshold: HarmBlockThreshold.BLOCK_NONE,
  },
  {
    category: HarmCategory.HARM_CATEGORY_DANGEROUS_CONTENT,
    threshold: HarmBlockThreshold.BLOCK_NONE,
  },
];

// ============================================
// ANALYSIS PROMPTS
// ============================================
const CHANGE_DETECTION_PROMPT = `You are an expert web content analyzer specialized in detecting meaningful changes on government and news websites.

Your task is to compare two versions of website content and identify significant updates.

ANALYSIS RULES:
1. **FOCUS ON**: New job posts, exam notifications, date changes, admission announcements, results
2. **IGNORE**: Layout changes, ads, visitor counts, timestamps, social media links, formatting changes
3. **BE SPECIFIC**: Mention exactly what changed (e.g., "New vacancy for SSC CGL 2025 announced")
4. **BE CONCISE**: Keep your summary to 2 lines maximum

RESPONSE FORMAT (JSON only):
{
  "changeType": "new_job_post" | "job_update" | "date_change" | "new_notification" | "content_update" | "false_positive",
  "title": "Brief 2-line summary of what changed",
  "keyChanges": ["specific change 1", "specific change 2"],
  "ignoredChanges": ["layout", "visitor count"],
  "confidence": 0.95,
  "urgency": "low" | "medium" | "high"
}`;

const GOVERNMENT_JOB_KEYWORDS = [
  'vacancy', 'recruitment', 'notification', 'apply', 'application',
  'exam', 'result', 'admit card', 'schedule', 'date', 'cutoff',
  'admission', 'form', 'registration', 'posts', 'position',
];

// ============================================
// MAIN AI SERVICE CLASS
// ============================================
export class AIChangeDetector {
  private genAI: GoogleGenerativeAI;
  private model: any;

  constructor(apiKey: string) {
    this.genAI = new GoogleGenerativeAI(apiKey);
    this.model = this.genAI.getGenerativeModel({
      model: AI_CONFIG.model,
      safetySettings: SAFETY_SETTINGS,
      generationConfig: {
        temperature: AI_CONFIG.temperature,
        maxOutputTokens: AI_CONFIG.maxOutputTokens,
        responseMimeType: 'application/json',
      },
    });
  }

  /**
   * Main method: Detect and summarize changes
   */
  async detectChanges(
    oldContent: string,
    newContent: string,
    context?: {
      siteName?: string;
      siteUrl?: string;
      category?: string;
    }
  ): Promise<AISummary> {
    const startTime = Date.now();

    try {
      // Pre-process content to focus on relevant parts
      const processedOld = this.preprocessContent(oldContent);
      const processedNew = this.preprocessContent(newContent);

      // Build the prompt
      const prompt = this.buildPrompt(processedOld, processedNew, context);

      // Call Gemini API with retry logic
      const response = await this.callWithRetry(prompt);

      // Parse response
      const result = this.parseResponse(response, context);

      // Add processing metadata
      result.processedAt = { toDate: () => new Date() } as any;
      result.processingTime = Date.now() - startTime;
      result.model = AI_CONFIG.model;

      return result;
    } catch (error) {
      console.error('AI detection error:', error);
      // Return fallback summary
      return this.getFallbackSummary(oldContent, newContent, startTime);
    }
  }

  /**
   * Pre-process content to focus on relevant text
   */
  private preprocessContent(content: string): string {
    // Limit content size to stay within token limits
    const maxLength = 8000; // characters

    // If content is too large, try to extract relevant sections
    if (content.length > maxLength) {
      // Split by paragraphs and keep those with keywords
      const paragraphs = content.split(/\n\n+/);

      const relevantParagraphs = paragraphs
        .filter(para => {
          const lower = para.toLowerCase();
          return GOVERNMENT_JOB_KEYWORDS.some(keyword => lower.includes(keyword));
        })
        .slice(0, 10); // Max 10 relevant paragraphs

      if (relevantParagraphs.length > 0) {
        return relevantParagraphs.join('\n\n');
      }

      // Fallback: return first portion
      return content.substring(0, maxLength);
    }

    return content;
  }

  /**
   * Build the analysis prompt
   */
  private buildPrompt(oldContent: string, newContent: string, context?: any): string {
    return `${CHANGE_DETECTION_PROMPT}

CONTEXT:
${context?.siteName ? `Site: ${context.siteName}` : ''}
${context?.siteUrl ? `URL: ${context.siteUrl}` : ''}
${context?.category ? `Category: ${context.category}` : ''}

OLD CONTENT (Previous Version):
"""
${oldContent}
"""

NEW CONTENT (Current Version):
"""
${newContent}
"""

Analyze the changes and respond in the specified JSON format.`;
  }

  /**
   * Call Gemini API with retry logic
   */
  private async callWithRetry(prompt: string, attempt: number = 1): Promise<string> {
    try {
      const result = await this.model.generateContent(prompt);
      const response = await result.response;
      return response.text();
    } catch (error: any) {
      if (attempt < AI_CONFIG.maxRetries && this.isRetryableError(error)) {
        // Exponential backoff
        await this.delay(1000 * Math.pow(2, attempt - 1));
        return this.callWithRetry(prompt, attempt + 1);
      }
      throw error;
    }
  }

  /**
   * Check if error is retryable
   */
  private isRetryableError(error: any): boolean {
    const retryableCodes = ['QUOTA_EXCEEDED', 'RESOURCE_EXHAUSTED', 'UNAVAILABLE'];
    return retryableCodes.includes(error.code) || error.status === 429;
  }

  /**
   * Parse AI response into AISummary
   */
  private parseResponse(response: string, context?: any): AISummary {
    try {
      // Extract JSON from response (sometimes it's wrapped in markdown)
      const jsonMatch = response.match(/```json\s*([\s\S]*?)\s*```/) ||
                       response.match(/\{[\s\S]*\}/);

      if (!jsonMatch) {
        throw new Error('No JSON found in response');
      }

      const parsed = JSON.parse(jsonMatch[1] || jsonMatch[0]);

      return {
        title: parsed.title || 'Content updated',
        description: parsed.title || '',
        confidence: parsed.confidence || 0.7,
        keyChanges: parsed.keyChanges || [],
        ignoredChanges: parsed.ignoredChanges || [],
        model: AI_CONFIG.model,
        processedAt: { toDate: () => new Date() } as any,
        processingTime: 0,
      };
    } catch (error) {
      console.error('Failed to parse AI response:', error);
      // Return basic summary
      return {
        title: 'Website content has been updated',
        description: 'Please check the website for latest changes.',
        confidence: 0.5,
        keyChanges: [],
        ignoredChanges: [],
        model: AI_CONFIG.model,
        processedAt: { toDate: () => new Date() } as any,
        processingTime: 0,
      };
    }
  }

  /**
   * Get fallback summary when AI fails
   */
  private getFallbackSummary(oldContent: string, newContent: string, startTime: number): AISummary {
    // Simple keyword-based fallback
    const keywords = this.extractChangedKeywords(oldContent, newContent);

    return {
      title: keywords.length > 0
        ? `Updates detected: ${keywords.slice(0, 3).join(', ')}`
        : 'Website content updated',
      description: 'Manual review recommended',
      confidence: 0.6,
      keyChanges: keywords,
      ignoredChanges: ['layout', 'formatting'],
      model: 'fallback',
      processedAt: { toDate: () => new Date() } as any,
      processingTime: Date.now() - startTime,
    };
  }

  /**
   * Extract keywords that changed between versions
   */
  private extractChangedKeywords(oldContent: string, newContent: string): string[] {
    const oldWords = new Set(oldContent.toLowerCase().split(/\s+/));
    const newWords = new Set(newContent.toLowerCase().split(/\s+/));

    const added: string[] = [];

    for (const word of newWords) {
      if (!oldWords.has(word) && GOVERNMENT_JOB_KEYWORDS.includes(word)) {
        added.push(word);
      }
    }

    return [...new Set(added)];
  }

  /**
   * Delay helper
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Create AI service instance with API key from environment
 */
export function createAIService(): AIChangeDetector {
  const apiKey = process.env.GEMINI_API_KEY || process.env.GOOGLE_AI_API_KEY;

  if (!apiKey) {
    throw new Error('GEMINI_API_KEY or GOOGLE_AI_API_KEY environment variable is required');
  }

  return new AIChangeDetector(apiKey);
}

/**
 * Quick check if change warrants AI analysis
 */
export function shouldUseAI(
  oldContent: string,
  newContent: string,
  lastAIAnalysis?: Date
): boolean {
  // Don't analyze if less than 1 hour since last analysis
  if (lastAIAnalysis && Date.now() - lastAIAnalysis.getTime() < 60 * 60 * 1000) {
    return false;
  }

  // Check if content is significantly different
  const oldLength = oldContent.length;
  const newLength = newContent.length;
  const diffPercent = Math.abs(newLength - oldLength) / Math.max(oldLength, newLength);

  // Use AI if change is more than 5% but less than 80%
  // (too small = noise, too large = likely page redesign)
  return diffPercent > 0.05 && diffPercent < 0.8;
}

/**
 * Batch analyze multiple changes
 */
export async function batchAnalyzeChanges(
  changes: Array<{ oldContent: string; newContent: string; context?: any }>
): Promise<AISummary[]> {
  const aiService = createAIService();

  // Process sequentially to avoid rate limits
  const results: AISummary[] = [];

  for (const change of changes) {
    try {
      const result = await aiService.detectChanges(change.oldContent, change.newContent, change.context);
      results.push(result);
    } catch (error) {
      console.error('Batch analysis error:', error);
      // Add fallback summary
      results.push({
        title: 'Analysis failed',
        description: 'Could not analyze changes',
        confidence: 0,
        keyChanges: [],
        ignoredChanges: [],
        model: 'error',
        processedAt: { toDate: () => new Date() } as any,
        processingTime: 0,
      });
    }

    // Small delay between requests
    await new Promise(resolve => setTimeout(resolve, 500));
  }

  return results;
}
