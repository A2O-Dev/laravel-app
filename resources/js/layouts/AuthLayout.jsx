import React, { useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import { Box, Paper, Stack, Typography } from '@mui/material';
import { useAuth } from '../contexts/AuthContext';

export default function AuthLayout({ title, subtitle, footer, children }) {
    const { isAuthenticated, loading } = useAuth();

    useEffect(() => {
        if (!loading && isAuthenticated) {
            router.visit('/dashboard');
        }
    }, [isAuthenticated, loading]);

    return (
        <Box
            sx={{
                minHeight: '100vh',
                display: 'grid',
                placeItems: 'center',
                px: 2,
                py: 5,
                background: 'linear-gradient(135deg, #f8fafc 0%, #eef2f7 50%, #ecfeff 100%)',
            }}
        >
            <Paper
                elevation={0}
                sx={{
                    width: '100%',
                    maxWidth: 460,
                    p: { xs: 3, sm: 4 },
                    border: '1px solid #d9e2ec',
                }}
            >
                <Stack spacing={3}>
                    <Box>
                        <Typography variant="h4" component="h1">
                            {title}
                        </Typography>
                        {subtitle && (
                            <Typography color="text.secondary" sx={{ mt: 1 }}>
                                {subtitle}
                            </Typography>
                        )}
                    </Box>

                    {children}

                    {footer && (
                        <Typography variant="body2" color="text.secondary" textAlign="center">
                            {footer}
                        </Typography>
                    )}
                </Stack>
            </Paper>
        </Box>
    );
}

export function AuthLink({ href, children }) {
    return (
        <Link href={href} style={{ color: '#2563eb', fontWeight: 700, textDecoration: 'none' }}>
            {children}
        </Link>
    );
}
