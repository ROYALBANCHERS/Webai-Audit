/**
 * Firestore Service
 * Handles all database operations for the monitoring system
 */

import * as admin from 'firebase-admin';
import {
  MonitoredSite,
  SiteChange,
  Notification,
  UserProfile,
  ScrapingStats,
} from '../types';

// ============================================
// INITIALIZE FIREBASE ADMIN
// ============================================
let db: admin.firestore.Firestore;

export function initializeFirestore(): admin.firestore.Firestore {
  if (!db) {
    db = admin.firestore();
    db.settings({
      ignoreUndefinedProperties: true,
    });
  }
  return db;
}

// ============================================
// USER OPERATIONS
// ============================================
export class UserService {
  private db: admin.firestore.Firestore;

  constructor() {
    this.db = initializeFirestore();
  }

  /**
   * Get user profile
   */
  async getUserProfile(userId: string): Promise<UserProfile | null> {
    const doc = await this.db.collection('users').doc(userId).get();

    if (!doc.exists) return null;

    return {
      uid: doc.id,
      ...doc.data(),
    } as UserProfile;
  }

  /**
   * Create user profile
   */
  async createUserProfile(userId: string, data: Partial<UserProfile>): Promise<void> {
    const userProfile: Partial<UserProfile> = {
      uid: userId,
      email: data.email || '',
      displayName: data.displayName,
      photoURL: data.photoURL,
      createdAt: admin.firestore.FieldValue.serverTimestamp() as any,
      updatedAt: admin.firestore.FieldValue.serverTimestamp() as any,
      settings: data.settings || {
        emailNotifications: true,
        pushNotifications: false,
        notificationFrequency: 'instant',
        timezone: 'Asia/Kolkata',
        language: 'en',
      },
      subscription: data.subscription || {
        plan: 'free',
        maxMonitoredSites: 10,
        currentSites: 0,
        isTrial: false,
      },
    };

    await this.db.collection('users').doc(userId).set(userProfile, { merge: true });
  }

  /**
   * Update user's site count
   */
  async updateUserSiteCount(userId: string, increment: number): Promise<void> {
    await this.db.collection('users').doc(userId).update({
      'subscription.currentSites': admin.firestore.FieldValue.increment(increment),
      updatedAt: admin.firestore.FieldValue.serverTimestamp(),
    });
  }

  /**
   * Check if user can add more sites
   */
  async canUserAddSite(userId: string): Promise<boolean> {
    const user = await this.getUserProfile(userId);

    if (!user) return false;

    return user.subscription.currentSites < user.subscription.maxMonitoredSites;
  }
}

// ============================================
// MONITORED SITE OPERATIONS
// ============================================
export class SiteService {
  private db: admin.firestore.Firestore;

  constructor() {
    this.db = initializeFirestore();
  }

  /**
   * Add a new monitored site
   */
  async addSite(userId: string, siteData: Omit<MonitoredSite, 'id' | 'userId' | 'createdAt' | 'updatedAt' | 'totalChangesDetected' | 'monitoringStatus'>): Promise<string> {
    const siteRef = this.db.collection('users').doc(userId).collection('monitored_sites').doc();

    const site: MonitoredSite = {
      id: siteRef.id,
      userId,
      ...siteData,
      monitoringStatus: siteData.isActive ? 'active' : 'paused',
      totalChangesDetected: 0,
      createdAt: admin.firestore.FieldValue.serverTimestamp() as any,
      updatedAt: admin.firestore.FieldValue.serverTimestamp() as any,
    };

    await siteRef.set(site);

    // Update user's site count
    const userService = new UserService();
    await userService.updateUserSiteCount(userId, 1);

    return siteRef.id;
  }

  /**
   * Get a monitored site
   */
  async getSite(userId: string, siteId: string): Promise<MonitoredSite | null> {
    const doc = await this.db
      .collection('users')
      .doc(userId)
      .collection('monitored_sites')
      .doc(siteId)
      .get();

    if (!doc.exists) return null;

    return {
      id: doc.id,
      ...doc.data(),
    } as MonitoredSite;
  }

  /**
   * Get all active sites for a user
   */
  async getActiveSites(userId: string): Promise<MonitoredSite[]> {
    const snapshot = await this.db
      .collection('users')
      .doc(userId)
      .collection('monitored_sites')
      .where('isActive', '==', true)
      .where('monitoringStatus', '==', 'active')
      .get();

    return snapshot.docs.map(doc => ({
      id: doc.id,
      ...doc.data(),
    } as MonitoredSite));
  }

  /**
   * Get all active sites across all users (for cron job)
   */
  async getAllActiveSites(limit: number = 100): Promise<MonitoredSite[]> {
    // Note: This requires a collection group query
    const snapshot = await this.db
      .collectionGroup('monitored_sites')
      .where('isActive', '==', true)
      .where('monitoringStatus', '==', 'active')
      .limit(limit)
      .get();

    return snapshot.docs.map(doc => ({
      id: doc.id,
      userId: doc.ref.parent.parent?.id || '',
      ...doc.data(),
    } as MonitoredSite));
  }

  /**
   * Update site after scraping
   */
  async updateSiteAfterScrape(
    userId: string,
    siteId: string,
    data: {
      contentHash: string;
      contentSnapshot: string;
      lastCheckedAt: admin.firestore.Timestamp;
      statusCode?: number;
      responseTime?: number;
      error?: any;
    }
  ): Promise<void> {
    const updateData: any = {
      lastContentHash: data.contentHash,
      lastContentSnapshot: data.contentSnapshot,
      lastCheckedAt: data.lastCheckedAt,
      updatedAt: admin.firestore.FieldValue.serverTimestamp(),
    };

    if (data.statusCode) {
      updateData['metadata.lastStatusCode'] = data.statusCode;
    }

    if (data.responseTime) {
      updateData['metadata.responseTime'] = data.responseTime;
    }

    if (data.error) {
      updateData.lastError = {
        code: data.error.code,
        message: data.error.message,
        isPermanent: data.error.isPermanent,
        occurredAt: admin.firestore.FieldValue.serverTimestamp(),
        retryCount: admin.firestore.FieldValue.increment(1),
      };

      // Mark as error if permanent
      if (data.error.isPermanent) {
        updateData.monitoringStatus = 'error';
      }
    } else {
      // Clear error if successful
      updateData.lastError = admin.firestore.FieldValue.delete();
      updateData.monitoringStatus = 'active';
    }

    await this.db
      .collection('users')
      .doc(userId)
      .collection('monitored_sites')
      .doc(siteId)
      .update(updateData);
  }

  /**
   * Increment change count for a site
   */
  async incrementChangeCount(userId: string, siteId: string): Promise<void> {
    await this.db
      .collection('users')
      .doc(userId)
      .collection('monitored_sites')
      .doc(siteId)
      .update({
        totalChangesDetected: admin.firestore.FieldValue.increment(1),
        lastChangeDetectedAt: admin.firestore.FieldValue.serverTimestamp(),
        updatedAt: admin.firestore.FieldValue.serverTimestamp(),
      });
  }

  /**
   * Update site status
   */
  async updateSiteStatus(
    userId: string,
    siteId: string,
    status: 'active' | 'paused' | 'error' | 'disabled'
  ): Promise<void> {
    await this.db
      .collection('users')
      .doc(userId)
      .collection('monitored_sites')
      .doc(siteId)
      .update({
        monitoringStatus: status,
        updatedAt: admin.firestore.FieldValue.serverTimestamp(),
      });
  }

  /**
   * Delete a site
   */
  async deleteSite(userId: string, siteId: string): Promise<void> {
    await this.db
      .collection('users')
      .doc(userId)
      .collection('monitored_sites')
      .doc(siteId)
      .delete();

    // Update user's site count
    const userService = new UserService();
    await userService.updateUserSiteCount(userId, -1);
  }

  /**
   * Get sites that need checking (for batch processing)
   */
  async getSitesDueForCheck(minutes: number): Promise<MonitoredSite[]> {
    const cutoffTime = admin.firestore.Timestamp.fromDate(
      new Date(Date.now() - minutes * 60 * 1000)
    );

    // Get sites where lastCheckedAt is before cutoff or doesn't exist
    const snapshot = await this.db
      .collectionGroup('monitored_sites')
      .where('isActive', '==', true)
      .where('monitoringStatus', '==', 'active')
      .get();

    const sites: MonitoredSite[] = [];

    for (const doc of snapshot.docs) {
      const site = doc.data() as MonitoredSite;

      if (!site.lastCheckedAt || site.lastCheckedAt.toDate() < cutoffTime.toDate()) {
        sites.push({
          id: doc.id,
          userId: doc.ref.parent.parent?.id || '',
          url: site.url,
          siteName: site.siteName,
          category: site.category,
          isActive: site.isActive,
          checkInterval: site.checkInterval,
          lastCheckedAt: site.lastCheckedAt,
          lastContentHash: site.lastContentHash,
          lastContentSnapshot: site.lastContentSnapshot,
          lastChangeDetectedAt: site.lastChangeDetectedAt,
          totalChangesDetected: site.totalChangesDetected,
          monitoringStatus: site.monitoringStatus,
          lastError: site.lastError,
          selectors: site.selectors,
          metadata: site.metadata,
          createdAt: site.createdAt,
          updatedAt: site.updatedAt,
        });
      }

      if (sites.length >= 50) break; // Limit per batch
    }

    return sites;
  }
}

// ============================================
// CHANGE TRACKING OPERATIONS
// ============================================
export class ChangeService {
  private db: admin.firestore.Firestore;

  constructor() {
    this.db = initializeFirestore();
  }

  /**
   * Record a detected change
   */
  async recordChange(change: Omit<SiteChange, 'id' | 'detectedAt' | 'isRead'>): Promise<string> {
    const changeRef = this.db
      .collection('users')
      .doc(change.userId)
      .collection('site_changes')
      .doc();

    const siteChange: SiteChange = {
      id: changeRef.id,
      ...change,
      detectedAt: admin.firestore.FieldValue.serverTimestamp() as any,
      isRead: false,
    };

    await changeRef.set(siteChange);

    // Also create notification
    const notificationService = new NotificationService();
    await notificationService.createNotification({
      userId: change.userId,
      type: 'site_change',
      title: `Update detected: ${change.siteName}`,
      message: change.aiSummary.title,
      changeId: changeRef.id,
      siteId: change.siteId,
      isRead: false,
      priority: change.aiSummary.confidence > 0.8 ? 'high' : 'normal',
      channels: change.userId ? [{ type: 'email', status: 'pending' }] : [],
    } as any);

    // Increment site's change count
    const siteService = new SiteService();
    await siteService.incrementChangeCount(change.userId, change.siteId);

    return changeRef.id;
  }

  /**
   * Get recent changes for a user
   */
  async getRecentChanges(userId: string, limit: number = 20): Promise<SiteChange[]> {
    const snapshot = await this.db
      .collection('users')
      .doc(userId)
      .collection('site_changes')
      .orderBy('detectedAt', 'desc')
      .limit(limit)
      .get();

    return snapshot.docs.map(doc => ({
      id: doc.id,
      ...doc.data(),
    } as SiteChange));
  }

  /**
   * Mark change as read
   */
  async markAsRead(userId: string, changeId: string): Promise<void> {
    await this.db
      .collection('users')
      .doc(userId)
      .collection('site_changes')
      .doc(changeId)
      .update({
        isRead: true,
      });
  }

  /**
   * Get unread change count
   */
  async getUnreadCount(userId: string): Promise<number> {
    const snapshot = await this.db
      .collection('users')
      .doc(userId)
      .collection('site_changes')
      .where('isRead', '==', false)
      .count()
      .get();

    return snapshot.data().count;
  }
}

// ============================================
// NOTIFICATION OPERATIONS
// ============================================
export class NotificationService {
  private db: admin.firestore.Firestore;

  constructor() {
    this.db = initializeFirestore();
  }

  /**
   * Create a notification
   */
  async createNotification(notification: Omit<Notification, 'id' | 'createdAt' | 'deliveryStatus'>): Promise<string> {
    const notificationRef = this.db.collection('users').doc(notification.userId).collection('notifications').doc();

    const newNotification: Notification = {
      id: notificationRef.id,
      ...notification,
      createdAt: admin.firestore.FieldValue.serverTimestamp() as any,
      deliveryStatus: 'pending',
    };

    await notificationRef.set(newNotification);

    return notificationRef.id;
  }

  /**
   * Get unread notifications
   */
  async getUnreadNotifications(userId: string): Promise<Notification[]> {
    const snapshot = await this.db
      .collection('users')
      .doc(userId)
      .collection('notifications')
      .where('isRead', '==', false)
      .orderBy('createdAt', 'desc')
      .limit(20)
      .get();

    return snapshot.docs.map(doc => ({
      id: doc.id,
      ...doc.data(),
    } as Notification));
  }

  /**
   * Mark notification as read
   */
  async markAsRead(userId: string, notificationId: string): Promise<void> {
    await this.db
      .collection('users')
      .doc(userId)
      .collection('notifications')
      .doc(notificationId)
      .update({ isRead: true });
  }

  /**
   * Mark all notifications as read
   */
  async markAllAsRead(userId: string): Promise<void> {
    const snapshot = await this.db
      .collection('users')
      .doc(userId)
      .collection('notifications')
      .where('isRead', '==', false)
      .get();

    const batch = this.db.batch();

    snapshot.docs.forEach(doc => {
      batch.update(doc.ref, { isRead: true });
    });

    await batch.commit();
  }
}

// ============================================
// STATS OPERATIONS
// ============================================
export class StatsService {
  private db: admin.firestore.Firestore;

  constructor() {
    this.db = initializeFirestore();
  }

  /**
   * Record scraping statistics
   */
  async recordScrapingStats(date: string, stats: Partial<ScrapingStats>): Promise<void> {
    const docRef = this.db.collection('scraping_stats').doc(date);

    await this.db.runTransaction(async (transaction) => {
      const doc = await transaction.get(docRef);

      const existing = doc.exists ? (doc.data() as ScrapingStats) : {
        id: date,
        date,
        totalScrapes: 0,
        successfulScrapes: 0,
        failedScrapes: 0,
        changesDetected: 0,
        falsePositives: 0,
        avgResponseTime: 0,
        totalDataProcessed: 0,
        aiApiCalls: 0,
        aiProcessingTime: 0,
        errorsByType: {},
        topDomains: [],
      };

      const updated: ScrapingStats = {
        ...existing,
        totalScrapes: (existing.totalScrapes || 0) + (stats.totalScrapes || 0),
        successfulScrapes: (existing.successfulScrapes || 0) + (stats.successfulScrapes || 0),
        failedScrapes: (existing.failedScrapes || 0) + (stats.failedScrapes || 0),
        changesDetected: (existing.changesDetected || 0) + (stats.changesDetected || 0),
        falsePositives: (existing.falsePositives || 0) + (stats.falsePositives || 0),
        totalDataProcessed: (existing.totalDataProcessed || 0) + (stats.totalDataProcessed || 0),
        aiApiCalls: (existing.aiApiCalls || 0) + (stats.aiApiCalls || 0),
        aiProcessingTime: (existing.aiProcessingTime || 0) + (stats.aiProcessingTime || 0),
      };

      // Update average response time
      if (stats.avgResponseTime) {
        const totalResponseTime = (existing.avgResponseTime || 0) * (existing.totalScrapes || 0);
        updated.avgResponseTime = (totalResponseTime + stats.avgResponseTime) / (updated.totalScrapes || 1);
      }

      // Merge error types
      if (stats.errorsByType) {
        updated.errorsByType = {
          ...existing.errorsByType,
          ...stats.errorsByType,
        };
      }

      transaction.set(docRef, updated);
    });
  }

  /**
   * Get stats for a date range
   */
  async getStatsForRange(startDate: string, endDate: string): Promise<ScrapingStats[]> {
    const snapshot = await this.db
      .collection('scraping_stats')
      .where('date', '>=', startDate)
      .where('date', '<=', endDate)
      .orderBy('date', 'asc')
      .get();

    return snapshot.docs.map(doc => doc.data() as ScrapingStats);
  }
}
