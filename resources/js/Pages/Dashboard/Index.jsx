import React from 'react';
import { usePage } from '@inertiajs/react';
import { Card, CardContent, Stack, Typography } from '@mui/material';
import AppLayout from '../../layouts/AppLayout';

export default function Dashboard() {
    const { user } = usePage().props;

    return (
        <AppLayout>
            <Card elevation={0} sx={{ border: '1px solid #d9e2ec' }}>
                <CardContent>
                    <Stack spacing={1}>
                        <Typography variant="h5">Dashboard</Typography>
                        <Typography color="text.secondary">
                            Your authenticated session is active for {user?.email}. Use the app bar to change your password or log out.
                        </Typography>
                    </Stack>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
