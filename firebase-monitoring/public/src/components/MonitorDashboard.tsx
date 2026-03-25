/**
 * React Component: Monitor Dashboard
 * Main UI for managing monitored websites and viewing changes
 */

import React, { useState, useEffect } from 'react';
import {
  Box,
  Container,
  Grid,
  Card,
  CardContent,
  Typography,
  Button,
  TextField,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Chip,
  IconButton,
  List,
  ListItem,
  ListItemText,
  ListItemSecondaryAction,
  Divider,
  Alert,
  CircularProgress,
  Tab,
  Tabs,
  Badge,
  Tooltip,
} from '@mui/material';
import {
  Add as AddIcon,
  Delete as DeleteIcon,
  Refresh as RefreshIcon,
  CheckCircle as CheckIcon,
  Error as ErrorIcon,
  Schedule as ScheduleIcon,
  Notifications as NotificationsIcon,
  Visibility as VisibilityIcon,
} from '@mui/icons-material';
import { functions } from '../services/firebase';
import { monitoredSitesService } from '../services/monitoring';
import { MonitoredSite, SiteChange } from '../types';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

function TabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props;
  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`monitor-tabpanel-${index}`}
      aria-labelledby={`monitor-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ py: 3 }}>{children}</Box>}
    </div>
  );
}

export const MonitorDashboard: React.FC = () => {
  const [tabValue, setTabValue] = useState(0);
  const [sites, setSites] = useState<MonitoredSite[]>([]);
  const [changes, setChanges] = useState<SiteChange[]>([]);
  const [loading, setLoading] = useState(true);
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  const [newUrl, setNewUrl] = useState('');
  const [newSiteName, setNewSiteName] = useState('');
  const [newCategory, setNewCategory] = useState('government_jobs');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [unreadCount, setUnreadCount] = useState(0);

  // Load data on mount
  useEffect(() => {
    loadData();
    // Refresh every 30 seconds
    const interval = setInterval(loadData, 30000);
    return () => clearInterval(interval);
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [sitesData, changesData] = await Promise.all([
        monitoredSitesService.getSites(),
        monitoredSitesService.getChanges(),
      ]);
      setSites(sitesData);
      setChanges(changesData);
      setUnreadCount(changesData.filter((c) => !c.isRead).length);
    } catch (err: any) {
      console.error('Error loading data:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleAddSite = async () => {
    if (!newUrl.trim()) {
      setError('Please enter a URL');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      await monitoredSitesService.addSite({
        url: newUrl,
        siteName: newSiteName || undefined,
        category: newCategory as any,
      });

      setSuccess('Site added successfully!');
      setAddDialogOpen(false);
      setNewUrl('');
      setNewSiteName('');
      setNewCategory('government_jobs');

      // Reload data
      await loadData();
    } catch (err: any) {
      setError(err.message || 'Failed to add site');
    } finally {
      setSubmitting(false);
    }
  };

  const handleRemoveSite = async (siteId: string) => {
    if (!confirm('Are you sure you want to stop monitoring this site?')) {
      return;
    }

    try {
      setError(null);
      await monitoredSitesService.removeSite(siteId);
      setSuccess('Site removed');
      await loadData();
    } catch (err: any) {
      setError(err.message || 'Failed to remove site');
    }
  };

  const handleCheckNow = async (siteId: string) => {
    try {
      setError(null);
      await monitoredSitesService.checkSite(siteId);
      setSuccess('Site checked successfully!');
      await loadData();
    } catch (err: any) {
      setError(err.message || 'Failed to check site');
    }
  };

  const handleMarkAsRead = async (changeId: string) => {
    try {
      await monitoredSitesService.markChangeAsRead(changeId);
      await loadData();
    } catch (err: any) {
      console.error('Error marking as read:', err);
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await monitoredSitesService.markAllChangesAsRead();
      await loadData();
    } catch (err: any) {
      console.error('Error marking all as read:', err);
    }
  };

  const getCategoryColor = (category: string): string => {
    const colors: Record<string, string> = {
      government_jobs: 'error',
      news: 'info',
      exam_results: 'success',
      admissions: 'warning',
      sports: 'secondary',
      other: 'default',
    };
    return colors[category] || 'default';
  };

  const getCategoryLabel = (category: string): string => {
    return category.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
  };

  if (loading && sites.length === 0) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="50vh">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      {/* Header */}
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h4" component="h1">
          <Badge badgeContent={unreadCount} color="error">
            <NotificationsIcon fontSize="large" />
          </Badge>
          {' Web Monitor'}
        </Typography>
        <Box display="flex" gap={2}>
          <Button
            startIcon={<RefreshIcon />}
            onClick={loadData}
            disabled={loading}
            variant="outlined"
          >
            Refresh
          </Button>
          <Button
            startIcon={<AddIcon />}
            onClick={() => setAddDialogOpen(true)}
            variant="contained"
            color="primary"
          >
            Add Website
          </Button>
        </Box>
      </Box>

      {/* Error/Success Messages */}
      {error && (
        <Alert severity="error" onClose={() => setError(null)} sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}
      {success && (
        <Alert severity="success" onClose={() => setSuccess(null)} sx={{ mb: 2 }}>
          {success}
        </Alert>
      )}

      {/* Tabs */}
      <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
        <Tabs value={tabValue} onChange={(_, v) => setTabValue(v)}>
          <Tab label={`Monitored Sites (${sites.length})`} />
          <Tab
            label={
              <Box display="flex" alignItems="center" gap={1}>
                Recent Changes
                {unreadCount > 0 && (
                  <Chip size="small" label={unreadCount} color="error" />
                )}
              </Box>
            }
          />
        </Tabs>
      </Box>

      {/* Monitored Sites Tab */}
      <TabPanel value={tabValue} index={0}>
        {sites.length === 0 ? (
          <Card>
            <CardContent sx={{ textAlign: 'center', py: 6 }}>
              <ScheduleIcon sx={{ fontSize: 60, color: 'text.secondary', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                No websites monitored yet
              </Typography>
              <Typography color="textSecondary" paragraph>
                Add any government job site, news portal, or other website to get instant
                notifications when content changes.
              </Typography>
              <Button
                variant="contained"
                startIcon={<AddIcon />}
                onClick={() => setAddDialogOpen(true)}
              >
                Add Your First Website
              </Button>
            </CardContent>
          </Card>
        ) : (
          <Grid container spacing={2}>
            {sites.map((site) => (
              <Grid item xs={12} md={6} key={site.id}>
                <Card>
                  <CardContent>
                    <Box display="flex" justifyContent="space-between" alignItems="flex-start">
                      <Box>
                        <Typography variant="h6" gutterBottom>
                          {site.siteName}
                        </Typography>
                        <Typography variant="body2" color="textSecondary" gutterBottom>
                          {new URL(site.url).hostname}
                        </Typography>
                        <Chip
                          size="small"
                          label={getCategoryLabel(site.category)}
                          color={getCategoryColor(site.category) as any}
                          sx={{ mr: 1 }}
                        />
                        <Chip
                          size="small"
                          icon={site.monitoringStatus === 'active' ? <CheckIcon /> : <ErrorIcon />}
                          label={site.monitoringStatus}
                          color={site.monitoringStatus === 'active' ? 'success' : 'error'}
                        />
                      </Box>
                      <Box>
                        <Tooltip title="Check now">
                          <IconButton
                            size="small"
                            onClick={() => handleCheckNow(site.id)}
                            disabled={loading}
                          >
                            <VisibilityIcon />
                          </IconButton>
                        </Tooltip>
                        <Tooltip title="Remove">
                          <IconButton
                            size="small"
                            color="error"
                            onClick={() => handleRemoveSite(site.id)}
                          >
                            <DeleteIcon />
                          </IconButton>
                        </Tooltip>
                      </Box>
                    </Box>

                    <Divider sx={{ my: 2 }} />

                    <Grid container spacing={2} sx={{ fontSize: '0.875rem' }}>
                      <Grid item xs={6}>
                        <Typography color="textSecondary">Total Changes:</Typography>
                        <Typography fontWeight="bold">{site.totalChangesDetected}</Typography>
                      </Grid>
                      <Grid item xs={6}>
                        <Typography color="textSecondary">Last Checked:</Typography>
                        <Typography>
                          {site.lastCheckedAt
                            ? new Date(site.lastCheckedAt.toDate()).toLocaleString()
                            : 'Never'}
                        </Typography>
                      </Grid>
                      {site.lastChangeDetectedAt && (
                        <Grid item xs={12}>
                          <Typography color="textSecondary">Last Change:</Typography>
                          <Typography variant="body2">
                            {new Date(site.lastChangeDetectedAt.toDate()).toLocaleString()}
                          </Typography>
                        </Grid>
                      )}
                    </Grid>
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        )}
      </TabPanel>

      {/* Recent Changes Tab */}
      <TabPanel value={tabValue} index={1}>
        <Box display="flex" justifyContent="flex-end" mb={2}>
          {unreadCount > 0 && (
            <Button size="small" onClick={handleMarkAllAsRead}>
              Mark All as Read
            </Button>
          )}
        </Box>

        {changes.length === 0 ? (
          <Card>
            <CardContent sx={{ textAlign: 'center', py: 6 }}>
              <Typography color="textSecondary">No changes detected yet</Typography>
            </CardContent>
          </Card>
        ) : (
          <List>
            {changes.map((change, index) => (
              <React.Fragment key={change.id}>
                <ListItem
                  alignItems="flex-start"
                  sx={{
                    bgcolor: change.isRead ? 'inherit' : 'action.hover',
                    borderRadius: 1,
                  }}
                >
                  <ListItemText
                    primary={
                      <Box display="flex" alignItems="center" gap={1}>
                        {!change.isRead && <Chip size="small" label="NEW" color="primary" />}
                        <Typography variant="subtitle1" fontWeight="bold">
                          {change.siteName}
                        </Typography>
                        <Typography variant="caption" color="textSecondary">
                          {new Date(change.detectedAt.toDate()).toLocaleString()}
                        </Typography>
                      </Box>
                    }
                    secondary={
                      <Box>
                        <Typography variant="body2" color="textPrimary">
                          {change.aiSummary.title}
                        </Typography>
                        {change.aiSummary.keyChanges.length > 0 && (
                          <Box mt={1}>
                            <Typography variant="caption" color="textSecondary">
                              Key changes:
                            </Typography>
                            {change.aiSummary.keyChanges.map((keyChange, i) => (
                              <Chip
                                key={i}
                                size="small"
                                label={keyChange}
                                sx={{ mr: 0.5, mt: 0.5 }}
                              />
                            ))}
                          </Box>
                        )}
                      </Box>
                    }
                  />
                  <ListItemSecondaryAction>
                    <Tooltip title="Open website">
                      <IconButton
                        edge="end"
                        component="a"
                        href={change.siteUrl}
                        target="_blank"
                        rel="noopener"
                      >
                        <VisibilityIcon />
                      </IconButton>
                    </Tooltip>
                    {!change.isRead && (
                      <Tooltip title="Mark as read">
                        <IconButton edge="end" onClick={() => handleMarkAsRead(change.id)}>
                          <CheckIcon />
                        </IconButton>
                      </Tooltip>
                    )}
                  </ListItemSecondaryAction>
                </ListItem>
                {index < changes.length - 1 && <Divider />}
              </React.Fragment>
            ))}
          </List>
        )}
      </TabPanel>

      {/* Add Site Dialog */}
      <Dialog open={addDialogOpen} onClose={() => setAddDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Add Website to Monitor</DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            margin="dense"
            label="Website URL"
            type="url"
            fullWidth
            variant="outlined"
            value={newUrl}
            onChange={(e) => setNewUrl(e.target.value)}
            placeholder="https://ssc.gov.in"
            sx={{ mb: 2 }}
          />
          <TextField
            margin="dense"
            label="Site Name (Optional)"
            fullWidth
            variant="outlined"
            value={newSiteName}
            onChange={(e) => setNewSiteName(e.target.value)}
            placeholder="SSC Official"
            sx={{ mb: 2 }}
          />
          <TextField
            select
            margin="dense"
            label="Category"
            fullWidth
            variant="outlined"
            value={newCategory}
            onChange={(e) => setNewCategory(e.target.value)}
          >
            <MenuItem value="government_jobs">Government Jobs</MenuItem>
            <MenuItem value="exam_results">Exam Results</MenuItem>
            <MenuItem value="admissions">Admissions</MenuItem>
            <MenuItem value="news">News</MenuItem>
            <MenuItem value="sports">Sports</MenuItem>
            <MenuItem value="technology">Technology</MenuItem>
            <MenuItem value="business">Business</MenuItem>
            <MenuItem value="other">Other</MenuItem>
          </TextField>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setAddDialogOpen(false)}>Cancel</Button>
          <Button onClick={handleAddSite} variant="contained" disabled={submitting}>
            {submitting ? <CircularProgress size={20} /> : 'Add Site'}
          </Button>
        </DialogActions>
      </Dialog>
    </Container>
  );
};

export default MonitorDashboard;
