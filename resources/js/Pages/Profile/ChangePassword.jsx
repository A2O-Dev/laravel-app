import React, { useState } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { Box, Button, Card, CardContent, Stack, Typography } from '@mui/material';
import AppLayout from '../../layouts/AppLayout';
import { FormAlert, PasswordConfirmFields, PasswordField } from '../../components';
import useForm from '../../hooks/useForm';
import { apiErrors, firstError, firstResult, persistSession } from '../../lib/auth';

const INITIAL_FORM = { current_password: '', password: '', password_confirmation: '' };

export default function ChangePassword() {
    const { user } = usePage().props;
    const { form, errors, setErrors, submitting, setSubmitting, update, reset } = useForm(INITIAL_FORM);
    const [success, setSuccess] = useState('');

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
            reset();
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

                                <PasswordConfirmFields form={form} errors={errors} onChange={update} />

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
