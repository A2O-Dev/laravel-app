import React from 'react';
import PropTypes from 'prop-types';
import axios from 'axios';
import { Link, router, usePage } from '@inertiajs/react';
import { AppBar, Box, Button, Container, Stack, Toolbar, Typography } from '@mui/material';
import { LockReset, Logout, SpaceDashboard } from '@mui/icons-material';
import { clearSession } from '../lib/auth';

AppLayout.propTypes = {
    children: PropTypes.node.isRequired,
};

export default function AppLayout({ children }) {
    const { user } = usePage().props;

    const logout = async () => {
        try {
            await axios.post('/api/auth/logout');
        } finally {
            clearSession();
            router.visit('/login');
        }
    };

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
