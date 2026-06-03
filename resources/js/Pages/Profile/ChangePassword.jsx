import React, { useState } from 'react';
import axios from 'axios';
import { Box, Button, Card, CardContent, Stack, Typography } from '@mui/material';
import AppLayout from '../../layouts/AppLayout';
import PasswordField from '../../components/PasswordField';
import FormAlert from '../../components/FormAlert';
import { usePage } from '@inertiajs/react';
import { apiErrors, firstError, firstResult, persistSession } from '../../lib/auth';

export default function ChangePassword() {
    const { user } = usePage().props;
    const [form, setForm] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [errors, setErrors] = useState({});
    const [success, setSuccess] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const update = (event) => {
        setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});
        setSuccess('');

        try {
            await axios.post('/api/auth/change-password', form);
            const loginResponse = await axios.post('/api/auth/login', {
                email: user.email,
                password: form.password,
            });
            const refreshedUser = firstResult(loginResponse);
            persistSession(refreshedUser, refreshedUser.token);
            setSuccess('Password changed successfully.');
            setForm({
                current_password: '',
                password: '',
                password_confirmation: '',
            });
        } catch (error) {
            setErrors(apiErrors(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Card elevation={0} sx={{ maxWidth: 560, border: '1px solid #d9e2ec' }}>
                <CardContent>
                    <Stack spacing={3}>
                        <Box>
                            <Typography variant="h5">Change Password</Typography>
                            <Typography color="text.secondary" sx={{ mt: 0.5 }}>
                                Update the password for your authenticated account.
                            </Typography>
                        </Box>

                        <Box component="form" onSubmit={submit}>
                            <Stack spacing={2.5}>
                                <FormAlert severity="success">{success}</FormAlert>
                                <FormAlert>{firstError(errors, '')}</FormAlert>

                                <PasswordField
                                    label="Current Password"
                                    name="current_password"
                                    value={form.current_password}
                                    onChange={update}
                                    error={Boolean(errors.current_password)}
                                    helperText={errors.current_password?.[0]}
                                    autoComplete="current-password"
                                />
                                <PasswordField
                                    label="New Password"
                                    name="password"
                                    value={form.password}
                                    onChange={update}
                                    error={Boolean(errors.password)}
                                    helperText={errors.password?.[0]}
                                    autoComplete="new-password"
                                />
                                <PasswordField
                                    label="Confirm New Password"
                                    name="password_confirmation"
                                    value={form.password_confirmation}
                                    onChange={update}
                                    error={Boolean(errors.password_confirmation)}
                                    helperText={errors.password_confirmation?.[0]}
                                    autoComplete="new-password"
                                />

                                <Button type="submit" size="large" variant="contained" disabled={submitting}>
                                    {submitting ? 'Saving...' : 'Change password'}
                                </Button>
                            </Stack>
                        </Box>
                    </Stack>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
