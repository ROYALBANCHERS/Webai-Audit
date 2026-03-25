/**
 * Firestore Database Schema Types
 * Complete type definitions for the Web Monitor & AI Auditor System
 */

// ============================================
// COLLECTION: users
// ============================================
export interface UserProfile {
  uid: string;
  email: string;
  displayName?: string;
  photoURL?: string;
  createdAt: FirebaseFirestore.Timestamp;
  updatedAt: FirebaseFirestore.Timestamp;
  settings: UserSettings;
  subscription: SubscriptionInfo;
}

export interface UserSettings {
  emailNotifications: boolean;
  pushNotifications: boolean;
  notificationFrequency: 'instant' | 'hourly' | 'daily' | 'weekly';
  timezone: string;
  language: 'en' | 'hi';
}

export interface SubscriptionInfo {
  plan: 'free' | 'pro' | 'enterprise';
  maxMonitoredSites: number;
  currentSites: number;
  expiresAt?: FirebaseFirestore.Timestamp;
  isTrial: boolean;
}

// ============================================
// SUB-COLLECTION: users/{userId}/monitored_sites
// ============================================
export interface MonitoredSite {
  id: string; // Auto-generated document ID
  userId: string;
  url: string;
  siteName: string;
  category: SiteCategory;
  isActive: boolean;
  checkInterval: number; // minutes (default: 240 = 4 hours)
  lastCheckedAt?: FirebaseFirestore.Timestamp;
  lastContentHash?: string;
  lastContentSnapshot?: string; // Compressed text content
  lastChangeDetectedAt?: FirebaseFirestore.Timestamp;
  totalChangesDetected: number;
  monitoringStatus: 'active' | 'paused' | 'error' | 'disabled';
  lastError?: SiteError;
  selectors?: ContentSelectors; // Custom CSS selectors for content extraction
  metadata: SiteMetadata;
  createdAt: FirebaseFirestore.Timestamp;
  updatedAt: FirebaseFirestore.Timestamp;
}

export type SiteCategory =
  | 'government_jobs'
  | 'news'
  | 'exam_results'
  | 'admissions'
  | 'sports'
  | 'entertainment'
  | 'technology'
  | 'business'
  | 'other';

export interface ContentSelectors {
  // Custom selectors for specific websites
  mainContent?: string; // e.g., '.job-listings', '#content'
  excludeSelectors?: string[]; // e.g., ['.ads', '.sidebar', 'nav']
  titleSelector?: string;
  dateSelector?: string;
  linkSelector?: string;
}

export interface SiteMetadata {
  domain: string;
  favicon?: string;
  pageTitle?: string;
  title?: string; // Page title (alias for pageTitle)
  description?: string;
  lastStatusCode?: number;
  responseTime?: number; // milliseconds
  contentType?: string;
  size?: number; // bytes
}

export interface SiteError {
  code: string;
  message: string;
  occurredAt: FirebaseFirestore.Timestamp;
  isPermanent: boolean; // If true, stop retrying
  retryCount: number;
}

// ============================================
// SUB-COLLECTION: users/{userId}/site_changes
// ============================================
export interface SiteChange {
  id: string;
  userId: string;
  siteId: string; // Reference to monitored_sites document
  siteName: string;
  siteUrl: string;
  changeType: ChangeType;
  detectedAt: FirebaseFirestore.Timestamp;
  oldContentHash: string;
  newContentHash: string;
  aiSummary: AISummary;
  rawDiff?: string; // Optional diff output
  isRead: boolean;
  notifiedAt?: FirebaseFirestore.Timestamp;
  metadata: ChangeMetadata;
}

export type ChangeType =
  | 'new_job_post'
  | 'job_update'
  | 'date_change'
  | 'new_notification'
  | 'content_update'
  | 'site_down'
  | 'site_up'
  | 'false_positive';

export interface AISummary {
  title: string; // 2-line summary as requested
  description: string;
  confidence: number; // 0-1
  keyChanges: string[]; // Array of specific changes detected
  ignoredChanges: string[]; // Changes that were ignored (ads, layout, etc.)
  model: string; // e.g., 'gemini-2.0-flash'
  processedAt: FirebaseFirestore.Timestamp;
  processingTime: number; // milliseconds
}

export interface ChangeMetadata {
  previousSnapshotLength: number;
  newSnapshotLength: number;
  characterDifference: number;
  percentageChanged: number;
  detectedKeywords?: string[]; // e.g., ['vacancy', 'recruitment', 'apply']
}

// ============================================
// SUB-COLLECTION: users/{userId}/notifications
// ============================================
export interface Notification {
  id: string;
  userId: string;
  type: NotificationType;
  title: string;
  message: string;
  changeId?: string; // Link to site_change
  siteId?: string;
  isRead: boolean;
  createdAt: FirebaseFirestore.Timestamp;
  sentAt?: FirebaseFirestore.Timestamp;
  deliveryStatus: 'pending' | 'sent' | 'failed';
  channels: NotificationChannel[];
  priority: 'low' | 'normal' | 'high' | 'urgent';
  expiresAt?: FirebaseFirestore.Timestamp;
}

export type NotificationType = 'site_change' | 'site_down' | 'site_up' | 'system' | 'subscription';

export interface NotificationChannel {
  type: 'email' | 'push' | 'webhook';
  status: 'pending' | 'sent' | 'failed';
  sentAt?: FirebaseFirestore.Timestamp;
  error?: string;
  webhookUrl?: string;
}

// ============================================
// COLLECTION: system_logs
// ============================================
export interface SystemLog {
  id: string;
  level: 'info' | 'warning' | 'error' | 'debug';
  service: string; // 'scraper', 'ai', 'scheduler', 'notifier'
  message: string;
  metadata?: Record<string, unknown>;
  timestamp: FirebaseFirestore.Timestamp;
  userId?: string;
  siteId?: string;
}

// ============================================
// COLLECTION: scraping_stats
// ============================================
export interface ScrapingStats {
  id: string; // Date-based: YYYY-MM-DD
  date: string;
  totalScrapes: number;
  successfulScrapes: number;
  failedScrapes: number;
  changesDetected: number;
  falsePositives: number;
  avgResponseTime: number;
  totalDataProcessed: number; // bytes
  aiApiCalls: number;
  aiProcessingTime: number; // milliseconds
  errorsByType: Record<string, number>;
  topDomains: Array<{ domain: string; count: number }>;
}

// ============================================
// REQUEST/RESPONSE TYPES FOR API
// ============================================
export interface ScrapingResult {
  success: boolean;
  url: string;
  statusCode?: number;
  contentHash?: string;
  contentSnapshot?: string;
  title?: string;
  metadata?: SiteMetadata;
  processingTime: number;
  error?: {
    code: string;
    message: string;
    isPermanent: boolean;
  };
}

export interface AddSiteRequest {
  url: string;
  siteName?: string;
  category?: SiteCategory;
  checkInterval?: number;
  selectors?: ContentSelectors;
}

export interface AddSiteResponse {
  success: boolean;
  siteId?: string;
  message: string;
  initialCheck?: {
    status: number;
    contentHash: string;
    title?: string;
  };
}

export interface BatchScrapeRequest {
  userIds?: string[]; // For admin use
  siteIds?: string[]; // Specific sites to scrape
  forceAI?: boolean; // Skip hash check and run AI
}

export interface BatchScrapeResult {
  totalProcessed: number;
  successful: number;
  failed: number;
  changesDetected: number;
  aiProcessed: number;
  errors: Array<{ siteId: string; url: string; error: string }>;
  processingTime: number;
  timestamp: string;
}

// ============================================
// FIRESTORE INDEX DEFINITIONS
// ============================================
// Create these indexes via Firebase Console or CLI:
/*
users/{userId}/monitored_sites:
- Fields: [isActive, lastCheckedAt]
- Fields: [userId, isActive]

users/{userId}/site_changes:
- Fields: [siteId, detectedAt]
- Fields: [userId, isRead, detectedAt]

users/{userId}/notifications:
- Fields: [userId, isRead, createdAt]

scraping_stats:
- Fields: [date]
*/
