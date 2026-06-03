import React from 'react';
import PropTypes from 'prop-types';
import { Box, Paper, Stack, Typography } from '@mui/material';

AuthCard.propTypes = {
    title: PropTypes.string.isRequired,
    subtitle: PropTypes.string,
    footer: PropTypes.node,
    children: PropTypes.node.isRequired,
};

export default function AuthCard({ title, subtitle, footer, children }) {
    return (
        <Box sx={{ minHeight: '100vh', display: 'grid', placeItems: 'center', px: 2, py: 5, background: 'linear-gradient(135deg, #f8fafc 0%, #eef2f7 50%, #ecfeff 100%)' }}>
            <Paper elevation={0} sx={{ width: '100%', maxWidth: 460, p: { xs: 3, sm: 4 }, border: '1px solid #d9e2ec' }}>
                <Stack spacing={3}>
                    <Box>
                        <Typography variant="h4" component="h1">{title}</Typography>
                        {subtitle && (
                            <Typography color="text.secondary" sx={{ mt: 1 }}>{subtitle}</Typography>
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
