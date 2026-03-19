/**
 * Main App Component
 */

import React, { useEffect, useState } from 'react';
import { AppBar, Toolbar, Typography, Button, Container, Box, CircularProgress } from '@mui/material';
import { SignIn as SignInIcon, AccountCircle } from '@mui/icons-material';
import { auth } from './services/firebase';
import { useAuth } from './hooks/useAuth';
import MonitorDashboard from './components/MonitorDashboard';

export default function App() {
  const { user, loading } = useAuth();
  const [isInitialized, setIsInitialized] = useState(false);

  useEffect(() => {
    // Mark as initialized after auth check completes
    if (!loading) {
      setIsInitialized(true);
    }
  }, [loading]);

  const handleLogin = async () => {
    const { GoogleAuthProvider, signInWithPopup } = await import('firebase/auth');
    const provider = new GoogleAuthProvider();
    try {
      await signInWithPopup(auth, provider);
    } catch (error: any) {
      console.error('Login error:', error);
      alert(error.message || 'Failed to login');
    }
  };

  const handleLogout = async () => {
    try {
      await auth.signOut();
    } catch (error: any) {
      console.error('Logout error:', error);
    }
  };

  // Show loading spinner during initialization
  if (!isInitialized) {
    return (
      <Box
        display="flex"
        justifyContent="center"
        alignItems="center"
        minHeight="100vh"
      >
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box sx={{ flexGrow: 1, bgcolor: 'background.default', minHeight: '100vh' }}>
      {/* App Bar */}
      <AppBar position="static" elevation={0}>
        <Toolbar>
          <Box
            component="div"
            sx={{
              width: 32,
              height: 32,
              background: 'linear-gradient(135deg, #fff 0%, #f0f0f0 100%)',
              borderRadius: 1,
              mr: 2,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontWeight: 'bold',
              color: 'primary.main',
            }}
          >
            WM
          </Box>
          <Typography variant="h6" component="div" sx={{ flexGrow: 1 }}>
            Web Monitor & AI Auditor
          </Typography>
          {user ? (
            <Box display="flex" alignItems="center" gap={2}>
              <Box display="flex" alignItems="center" gap={1}>
                {user.photoURL ? (
                  <Box
                    component="img"
                    src={user.photoURL}
                    alt={user.displayName || 'User'}
                    sx={{ width: 32, height: 32, borderRadius: '50%' }}
                  />
                ) : (
                  <AccountCircle />
                )}
                <Typography variant="body2" sx={{ display: { xs: 'none', sm: 'block' } }}>
                  {user.displayName || user.email}
                </Typography>
              </Box>
              <Button color="inherit" onClick={handleLogout}>
                Logout
              </Button>
            </Box>
          ) : (
            <Button
              color="inherit"
              variant="outlined"
              startIcon={<SignInIcon />}
              onClick={handleLogin}
            >
              Sign in with Google
            </Button>
          )}
        </Toolbar>
      </AppBar>

      {/* Main Content */}
      {user ? (
        <MonitorDashboard />
      ) : (
        <Container maxWidth="md" sx={{ py: 8, textAlign: 'center' }}>
          <Box mb={4}>
            <Typography variant="h3" gutterBottom fontWeight="bold">
              Monitor Any Website for Changes
            </Typography>
            <Typography variant="h6" color="textSecondary">
              AI-powered change detection for government jobs, news, and more
            </Typography>
          </Box>

          <Box mb={6}>
            <Typography variant="body1" paragraph>
              Add any URL and get instant notifications when content changes.
              Our AI analyzes changes to filter out noise and only notify you
              about important updates.
            </Typography>
          </Box>

          <Box display="flex" justifyContent="center" gap={4} mb={6}>
            <FeatureCard
              icon="🔍"
              title="Smart Detection"
              description="AI analyzes changes to filter out ads, layout updates, and noise"
            />
            <FeatureCard
              icon="⚡"
              title="Real-Time Alerts"
              description="Get notified instantly when important changes are detected"
            />
            <FeatureCard
              icon="📊"
              title="Change History"
              description="View complete history with AI summaries of what changed"
            />
          </Box>

          <Button
            variant="contained"
            size="large"
            startIcon={<SignInIcon />}
            onClick={handleLogin}
            sx={{ px: 4, py: 1.5 }}
          >
            Get Started Free
          </Button>

          <Box mt={4}>
            <Typography variant="caption" color="textSecondary">
              Free plan: Up to 10 monitored sites • No credit card required
            </Typography>
          </Box>
        </Container>
      )}
    </Box>
  );
}

interface FeatureCardProps {
  icon: string;
  title: string;
  description: string;
}

function FeatureCard({ icon, title, description }: FeatureCardProps) {
  return (
    <Box sx={{ maxWidth: 200 }}>
      <Typography variant="h3" gutterBottom>
        {icon}
      </Typography>
      <Typography variant="subtitle1" fontWeight="bold" gutterBottom>
        {title}
      </Typography>
      <Typography variant="body2" color="textSecondary">
        {description}
      </Typography>
    </Box>
  );
}
