/**
 * Type definitions for the frontend
 * These mirror the backend types but with client-side friendly structures
 */

export interface MonitoredSite {
  id: string;
  userId: string;
  url: string;
  siteName: string;
  category: SiteCategory;
  isActive: boolean;
  checkInterval: number;
  lastCheckedAt?: {
    toDate: () => Date;
  };
  lastContentHash?: string;
  lastContentSnapshot?: string;
  lastChangeDetectedAt?: {
    toDate: () => Date;
  };
  totalChangesDetected: number;
  monitoringStatus: 'active' | 'paused' | 'error' | 'disabled';
  lastError?: {
    code: string;
    message: string;
    isPermanent: boolean;
    occurredAt: {
      toDate: () => Date;
    };
    retryCount: number;
  };
  selectors?: ContentSelectors;
  metadata: SiteMetadata;
  createdAt: {
    toDate: () => Date;
  };
  updatedAt: {
    toDate: () => Date;
  };
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
  mainContent?: string;
  excludeSelectors?: string[];
  titleSelector?: string;
  dateSelector?: string;
  linkSelector?: string;
}

export interface SiteMetadata {
  domain: string;
  favicon?: string;
  pageTitle?: string;
  description?: string;
  lastStatusCode?: number;
  responseTime?: number;
  contentType?: string;
  size?: number;
}

export interface SiteChange {
  id: string;
  userId: string;
  siteId: string;
  siteName: string;
  siteUrl: string;
  changeType: ChangeType;
  detectedAt: {
    toDate: () => Date;
  };
  oldContentHash: string;
  newContentHash: string;
  aiSummary: AISummary;
  isRead: boolean;
  notifiedAt?: {
    toDate: () => Date;
  };
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
  title: string;
  description: string;
  confidence: number;
  keyChanges: string[];
  ignoredChanges: string[];
  model: string;
  processedAt: {
    toDate: () => Date;
  };
  processingTime: number;
}

export interface ChangeMetadata {
  previousSnapshotLength: number;
  newSnapshotLength: number;
  characterDifference: number;
  percentageChanged: number;
  detectedKeywords?: string[];
}

export interface AddSiteRequest {
  url: string;
  siteName?: string;
  category?: SiteCategory;
  checkInterval?: number;
  selectors?: ContentSelectors;
}
