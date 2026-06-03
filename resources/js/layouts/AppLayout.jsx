import React, { useEffect } from 'react';
import axios from 'axios';
import { Link, router } from '@inertiajs/react';
import { AppBar, Box, Button, Container, Stack, Toolbar, Typography } from '@mui/material';
import { LockReset, Logout, SpaceDashboard } from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';

export default function AppLayout({ children }) {
    const { user, loading, isAuthenticated, clearAuth } = useAuth();

    useEffect(() => {
        if (!loading && !isAuthenticated) {
            router.visit('/login');
        }
    }, [isAuthenticated, loading]);

    const logout = async () => {
        try {
            await axios.post('/api/auth/logout');
        } catch (error) {
            // The local token is still removed if the API token has already expired.
        } finally {
            clearAuth();
            router.visit('/login');
        }
    };

    if (loading || !isAuthenticated) {
        return null;
    }

    return (
        <Box sx={{ minHeight: '100vh', bgcolor: 'background.default' }}>
            <AppBar position="static" elevation={0} color="inherit" sx={{ borderBottom: '1px solid #d9e2ec' }}>
                <Toolbar sx={{ gap: 2, flexWrap: 'wrap' }}>
                    <SpaceDashboard color="primary" />
                    <Typography variant="h6" component="div" sx={{ fontWeight: 800, flexGrow: 1 }}>
                        Laravel Demo
                    </Typography>
                    <Button component={Link} href="/dashboard" color="inherit">
                        Dashboard
                    </Button>
                    <Button component={Link} href="/change-password" color="inherit" startIcon={<LockReset />}>
                        Change password
                    </Button>
                    <Button color="primary" variant="contained" startIcon={<Logout />} onClick={logout}>
                        Logout
                    </Button>
                </Toolbar>
            </AppBar>

            <Container maxWidth="lg" sx={{ py: 5 }}>
                <Stack spacing={3}>
                    <Box>
                        <Typography variant="h5">Welcome, {user?.name || 'User'}</Typography>
                        <Typography color="text.secondary">{user?.email}</Typography>
                    </Box>
                    {children}
                </Stack>
            </Container>
        </Box>
    );
}
