import React, { useState } from 'react';
import axios from 'axios';
import { router, usePage } from '@inertiajs/react';
import { Box, Button, Stack, TextField } from '@mui/material';
import AuthLayout, { AuthLink } from '../../layouts/AuthLayout';
import PasswordField from '../../components/PasswordField';
import FormAlert from '../../components/FormAlert';
import { apiErrors, firstError } from '../../lib/auth';

export default function ResetPassword() {
    const { token, email: initialEmail } = usePage().props;
    const [form, setForm] = useState({
        token,
        email: initialEmail || '',
        password: '',
        password_confirmation: '',
    });
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const update = (event) => {
        setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await axios.post('/api/auth/reset-password', form);
            router.visit('/login');
        } catch (error) {
            setErrors(apiErrors(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthLayout
            title="Reset Password"
            subtitle="Choose a new password for your account."
            footer={<AuthLink href="/login">Back to login</AuthLink>}
        >
            <Box component="form" onSubmit={submit}>
                <Stack spacing={2.5}>
                    <FormAlert>{firstError(errors, '')}</FormAlert>

                    <TextField
                        fullWidth
                        required
                        name="email"
                        type="email"
                        label="Email"
                        value={form.email}
                        onChange={update}
                        error={Boolean(errors.email)}
                        helperText={errors.email?.[0]}
                        autoComplete="email"
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
                        {submitting ? 'Updating...' : 'Reset password'}
                    </Button>
                </Stack>
            </Box>
        </AuthLayout>
    );
}
