/**
 * Firebase Cloud Functions - Web Monitor & AI Auditor
 * Main entry point for all cloud functions
 */

import * as functions from 'firebase-functions';
import * as admin from 'firebase-admin';
import { scrapeUrl, scrapeMultipleUrls } from './services/scraper';
import { createAIService, shouldUseAI } from './services/ai-service';
import { SiteService, ChangeService, UserService } from './services/firestore';
import { generateContentHash, isSignificantChange } from './utils/hash';

// ============================================
// INITIALIZE FIREBASE ADMIN
// ============================================
admin.initializeApp();

// ============================================
// HTTP FUNCTIONS (Called from React App)
// ============================================

/**
 * Add a new URL to monitor
 * POST /api/addSite
 * Body: { url, siteName?, category?, checkInterval? }
 */
export const addSite = functions.https.onCall(async (data, context) => {
  // Authentication check
  if (!context.auth) {
    throw new functions.https.HttpsError(
      'unauthenticated',
      'You must be logged in to add a site'
    );
  }

  const userId = context.auth.uid;
  const { url, siteName, category, checkInterval, selectors } = data;

  // Validate input
  if (!url || typeof url !== 'string') {
    throw new functions.https.HttpsError(
      'invalid-argument',
      'URL is required'
    );
  }

  try {
    // Check if user can add more sites
    const userService = new UserService();
    const canAdd = await userService.canUserAddSite(userId);

    if (!canAdd) {
      throw new functions.https.HttpsError(
        'resource-exhausted',
        'You have reached your maximum limit of monitored sites. Upgrade your plan for more.'
      );
    }

    // Validate URL by attempting to scrape it
    const initialScrape = await scrapeUrl(url, selectors);

    if (!initialScrape.success) {
      throw new functions.https.HttpsError(
        'invalid-argument',
        `Could not fetch URL: ${initialScrape.error?.message || 'Unknown error'}`
      );
    }

    // Add to database
    const siteService = new SiteService();
    const siteId = await siteService.addSite(userId, {
      url: initialScrape.url,
      siteName: siteName || initialScrape.title || new URL(initialScrape.url).hostname,
      category: category || 'other',
      checkInterval: checkInterval || 240, // 4 hours default
      isActive: true,
      lastContentHash: initialScrape.contentHash,
      lastContentSnapshot: initialScrape.contentSnapshot,
      lastCheckedAt: admin.firestore.Timestamp.now(),
      metadata: initialScrape.metadata || { domain: new URL(initialScrape.url).hostname },
      selectors,
    });

    return {
      success: true,
      siteId,
      message: 'Site added successfully',
      initialCheck: {
        status: initialScrape.statusCode,
        contentHash: initialScrape.contentHash,
        title: initialScrape.title,
      },
    };
  } catch (error: any) {
    console.error('Error adding site:', error);

    if (error instanceof functions.https.HttpsError) {
      throw error;
    }

    throw new functions.https.HttpsError(
      'internal',
      error.message || 'Failed to add site'
    );
  }
});

/**
 * Get all monitored sites for current user
 * GET /api/getSites
 */
export const getSites = functions.https.onCall(async (data, context) => {
  if (!context.auth) {
    throw new functions.https.HttpsError(
      'unauthenticated',
      'You must be logged in'
    );
  }

  try {
    const siteService = new SiteService();
    const sites = await siteService.getActiveSites(context.auth.uid);

    return {
      success: true,
      sites,
    };
  } catch (error: any) {
    console.error('Error getting sites:', error);
    throw new functions.https.HttpsError(
      'internal',
      error.message || 'Failed to get sites'
    );
  }
});

/**
 * Remove a monitored site
 * POST /api/removeSite
 * Body: { siteId }
 */
export const removeSite = functions.https.onCall(async (data, context) => {
  if (!context.auth) {
    throw new functions.https.HttpsError(
      'unauthenticated',
      'You must be logged in'
    );
  }

  const { siteId } = data;

  if (!siteId) {
    throw new functions.https.HttpsError(
      'invalid-argument',
      'siteId is required'
    );
  }

  try {
    const siteService = new SiteService();
    await siteService.deleteSite(context.auth.uid, siteId);

    return {
      success: true,
      message: 'Site removed successfully',
    };
  } catch (error: any) {
    console.error('Error removing site:', error);
    throw new functions.https.HttpsError(
      'internal',
      error.message || 'Failed to remove site'
    );
  }
});

/**
 * Get recent changes for current user
 * GET /api/getChanges
 */
export const getChanges = functions.https.onCall(async (data, context) => {
  if (!context.auth) {
    throw new functions.https.HttpsError(
      'unauthenticated',
      'You must be logged in'
    );
  }

  try {
    const changeService = new ChangeService();
    const changes = await changeService.getRecentChanges(context.auth.uid, data.limit || 20);

    return {
      success: true,
      changes,
    };
  } catch (error: any) {
    console.error('Error getting changes:', error);
    throw new functions.https.HttpsError(
      'internal',
      error.message || 'Failed to get changes'
    );
  }
});

/**
 * Manual check trigger for a specific site
 * POST /api/checkSite
 * Body: { siteId, forceAI? }
 */
export const checkSite = functions.https.onCall(async (data, context) => {
  if (!context.auth) {
    throw new functions.https.HttpsError(
      'unauthenticated',
      'You must be logged in'
    );
  }

  const { siteId, forceAI = false } = data;

  if (!siteId) {
    throw new functions.https.HttpsError(
      'invalid-argument',
      'siteId is required'
    );
  }

  try {
    const siteService = new SiteService();
    const site = await siteService.getSite(context.auth.uid, siteId);

    if (!site) {
      throw new functions.https.HttpsError(
        'not-found',
        'Site not found'
      );
    }

    // Scrape the site
    const result = await scrapeUrl(site.url, site.selectors);

    if (!result.success) {
      // Update site with error
      await siteService.updateSiteAfterScrape(context.auth.uid, siteId, {
        contentHash: site.lastContentHash || '',
        contentSnapshot: site.lastContentSnapshot || '',
        lastCheckedAt: admin.firestore.Timestamp.now(),
        error: result.error,
      });

      return {
        success: false,
        error: result.error,
      };
    }

    // Check if content changed
    const contentChanged = result.contentHash !== site.lastContentHash;

    if (contentChanged && (forceAI || shouldUseAI(site.lastContentSnapshot || '', result.contentSnapshot))) {
      // Use AI to analyze the change
      const aiService = createAIService();
      const aiSummary = await aiService.detectChanges(
        site.lastContentSnapshot || '',
        result.contentSnapshot,
        { siteName: site.siteName, siteUrl: site.url, category: site.category }
      );

      // Record the change
      const changeService = new ChangeService();
      await changeService.recordChange({
        userId: context.auth.uid,
        siteId: site.id,
        siteName: site.siteName,
        siteUrl: site.url,
        changeType: aiSummary.confidence > 0.6 ? 'content_update' : 'false_positive',
        oldContentHash: site.lastContentHash || '',
        newContentHash: result.contentHash,
        aiSummary,
        metadata: {
          previousSnapshotLength: site.lastContentSnapshot?.length || 0,
          newSnapshotLength: result.contentSnapshot.length,
          characterDifference: result.contentSnapshot.length - (site.lastContentSnapshot?.length || 0),
          percentageChanged: isSignificantChange(site.lastContentSnapshot || '', result.contentSnapshot) ? 10 : 5,
        },
      });

      // Update site
      await siteService.updateSiteAfterScrape(context.auth.uid, siteId, {
        contentHash: result.contentHash,
        contentSnapshot: result.contentSnapshot,
        lastCheckedAt: admin.firestore.Timestamp.now(),
        statusCode: result.statusCode,
        responseTime: result.processingTime,
      });

      return {
        success: true,
        changed: true,
        aiAnalysis: aiSummary,
      };
    }

    // Update site (no change detected)
    await siteService.updateSiteAfterScrape(context.auth.uid, siteId, {
      contentHash: result.contentHash,
      contentSnapshot: result.contentSnapshot,
      lastCheckedAt: admin.firestore.Timestamp.now(),
      statusCode: result.statusCode,
      responseTime: result.processingTime,
    });

    return {
      success: true,
      changed: false,
    };
  } catch (error: any) {
    console.error('Error checking site:', error);
    throw new functions.https.HttpsError(
      'internal',
      error.message || 'Failed to check site'
    );
  }
});

// ============================================
// SCHEDULED FUNCTIONS (Cron Jobs)
// ============================================

/**
 * Scheduled scraper - runs every 4 hours
 * Checks all active sites and detects changes
 */
export const scheduledScraper = functions
  .runWith({
    timeoutSeconds: 540, // Max timeout (9 minutes)
    memory: '2GB',
  })
  .pubsub
  .schedule('0 */4 * * *') // Every 4 hours
  .timeZone('Asia/Kolkata')
  .onRun(async (context: any) => {
    const siteService = new SiteService();
    const changeService = new ChangeService();

    // Get all sites due for checking
    const sites = await siteService.getSitesDueForCheck(240); // 4 hours

    console.log(`Checking ${sites.length} sites...`);

    const results = {
      totalProcessed: 0,
      successful: 0,
      failed: 0,
      changesDetected: 0,
      aiProcessed: 0,
      errors: [] as Array<{ siteId: string; url: string; error: string }>,
    };

    // Process sites in batches to avoid timeout
    const batchSize = 10;
    for (let i = 0; i < sites.length; i += batchSize) {
      const batch = sites.slice(i, i + batchSize);

      await Promise.allSettled(
        batch.map(async (site) => {
          try {
            results.totalProcessed++;

            // Scrape the site
            const scrapeResult = await scrapeUrl(site.url, site.selectors);

            if (!scrapeResult.success) {
              results.failed++;
              results.errors.push({
                siteId: site.id,
                url: site.url,
                error: scrapeResult.error?.message || 'Unknown error',
              });

              // Update with error
              await siteService.updateSiteAfterScrape(site.userId, site.id, {
                contentHash: site.lastContentHash || '',
                contentSnapshot: site.lastContentSnapshot || '',
                lastCheckedAt: admin.firestore.Timestamp.now(),
                error: scrapeResult.error,
              });

              return;
            }

            results.successful++;

            // Check for changes
            const contentChanged = scrapeResult.contentHash !== site.lastContentHash;

            if (contentChanged) {
              // Decide if AI analysis is needed
              const useAI = shouldUseAI(site.lastContentSnapshot || '', scrapeResult.contentSnapshot);

              if (useAI) {
                results.aiProcessed++;

                try {
                  const aiService = createAIService();
                  const aiSummary = await aiService.detectChanges(
                    site.lastContentSnapshot || '',
                    scrapeResult.contentSnapshot,
                    { siteName: site.siteName, siteUrl: site.url, category: site.category }
                  );

                  // Record change if significant
                  if (aiSummary.confidence > 0.5) {
                    results.changesDetected++;

                    await changeService.recordChange({
                      userId: site.userId,
                      siteId: site.id,
                      siteName: site.siteName,
                      siteUrl: site.url,
                      changeType: 'content_update',
                      oldContentHash: site.lastContentHash || '',
                      newContentHash: scrapeResult.contentHash,
                      aiSummary,
                      metadata: {
                        previousSnapshotLength: site.lastContentSnapshot?.length || 0,
                        newSnapshotLength: scrapeResult.contentSnapshot.length,
                        characterDifference: scrapeResult.contentSnapshot.length - (site.lastContentSnapshot?.length || 0),
                        percentageChanged: Math.abs(
                          (scrapeResult.contentSnapshot.length - (site.lastContentSnapshot?.length || 0)) /
                          Math.max(site.lastContentSnapshot?.length || 1, 1)
                        ) * 100,
                      },
                    });
                  }
                } catch (aiError: any) {
                  console.error(`AI analysis failed for ${site.url}:`, aiError);
                }
              }
            }

            // Update site
            await siteService.updateSiteAfterScrape(site.userId, site.id, {
              contentHash: scrapeResult.contentHash,
              contentSnapshot: scrapeResult.contentSnapshot,
              lastCheckedAt: admin.firestore.Timestamp.now(),
              statusCode: scrapeResult.statusCode,
              responseTime: scrapeResult.processingTime,
            });
          } catch (error: any) {
            console.error(`Error processing site ${site.url}:`, error);
            results.failed++;
            results.errors.push({
              siteId: site.id,
              url: site.url,
              error: error.message,
            });
          }
        })
      );
    }

    console.log('Scraper results:', results);

    // Record stats
    const today = new Date().toISOString().split('T')[0];
    // const statsService = new StatsService();
    // await statsService.recordScrapingStats(today, results);

    return null;
  });

/**
 * Health check endpoint
 */
export const health = functions.https.onRequest(async (req, res) => {
  res.json({
    status: 'healthy',
    service: 'Web Monitor & AI Auditor',
    version: '1.0.0',
    timestamp: new Date().toISOString(),
    region: process.env.GCP_REGION || 'unknown',
  });
});
