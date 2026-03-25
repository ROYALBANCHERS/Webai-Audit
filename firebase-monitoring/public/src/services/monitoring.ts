/**
 * Monitoring Service - Frontend API
 * Handles all communication with Firebase Cloud Functions
 */

import { callFunction } from './firebase';
import { MonitoredSite, SiteChange } from '../types';

/**
 * Get all monitored sites for current user
 */
export async function getSites(): Promise<MonitoredSite[]> {
  try {
    const result = await callFunction<{ success: boolean; sites: MonitoredSite[] }>('getSites');
    return result.sites || [];
  } catch (error: any) {
    console.error('Error fetching sites:', error);
    throw new Error(error.message || 'Failed to fetch sites');
  }
}

/**
 * Add a new site to monitor
 */
export async function addSite(data: {
  url: string;
  siteName?: string;
  category?: string;
  checkInterval?: number;
}): Promise<{ siteId: string; message: string }> {
  try {
    const result = await callFunction<{
      success: boolean;
      siteId: string;
      message: string;
    }>('addSite', data);

    if (!result.success) {
      throw new Error(result.message || 'Failed to add site');
    }

    return { siteId: result.siteId, message: result.message };
  } catch (error: any) {
    console.error('Error adding site:', error);
    throw new Error(error.message || 'Failed to add site');
  }
}

/**
 * Remove a monitored site
 */
export async function removeSite(siteId: string): Promise<void> {
  try {
    const result = await callFunction<{ success: boolean; message: string }>('removeSite', { siteId });

    if (!result.success) {
      throw new Error(result.message || 'Failed to remove site');
    }
  } catch (error: any) {
    console.error('Error removing site:', error);
    throw new Error(error.message || 'Failed to remove site');
  }
}

/**
 * Get recent changes
 */
export async function getChanges(limit: number = 20): Promise<SiteChange[]> {
  try {
    const result = await callFunction<{ success: boolean; changes: SiteChange[] }>('getChanges', { limit });
    return result.changes || [];
  } catch (error: any) {
    console.error('Error fetching changes:', error);
    throw new Error(error.message || 'Failed to fetch changes');
  }
}

/**
 * Manually check a site for changes
 */
export async function checkSite(siteId: string, forceAI: boolean = false): Promise<{
  success: boolean;
  changed?: boolean;
  aiAnalysis?: any;
}> {
  try {
    const result = await callFunction('checkSite', { siteId, forceAI });
    return result;
  } catch (error: any) {
    console.error('Error checking site:', error);
    throw new Error(error.message || 'Failed to check site');
  }
}

/**
 * Mark a change as read
 */
export async function markChangeAsRead(changeId: string): Promise<void> {
  try {
    await callFunction('markChangeRead', { changeId });
  } catch (error: any) {
    console.error('Error marking change as read:', error);
    throw new Error(error.message || 'Failed to mark as read');
  }
}

/**
 * Mark all changes as read
 */
export async function markAllChangesAsRead(): Promise<void> {
  try {
    await callFunction('markAllChangesRead');
  } catch (error: any) {
    console.error('Error marking all as read:', error);
    throw new Error(error.message || 'Failed to mark all as read');
  }
}

// Export as a service object for convenience
export const monitoredSitesService = {
  getSites,
  addSite,
  removeSite,
  getChanges,
  checkSite,
  markChangeAsRead,
  markAllChangesAsRead,
};
