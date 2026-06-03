import React, { useState } from 'react';
import axios from 'axios';
import { Link, router } from '@inertiajs/react';
import { Alert, Box, Button, Stack, TextField, Typography } from '@mui/material';
import AuthLayout, { AuthLink } from '../../layouts/AuthLayout';
import PasswordField from '../../components/PasswordField';
import FormAlert from '../../components/FormAlert';
import { apiErrors, firstError, firstResult } from '../../lib/auth';
import { useAuth } from '../../contexts/AuthContext';

export default function Login() {
    const { setSession } = useAuth();
    const [form, setForm] = useState({ email: '', password: '' });
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
            const response = await axios.post('/api/auth/login', form);
            const user = firstResult(response);
            setSession(user, user.token);
            router.visit('/dashboard');
        } catch (error) {
            setErrors(apiErrors(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthLayout
            title="Login"
            subtitle="Access your Laravel Demo dashboard."
            footer={<>No account yet? <AuthLink href="/register">Create one</AuthLink></>}
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

                    <Box>
                        <PasswordField
                            label="Password"
                            name="password"
                            value={form.password}
                            onChange={update}
                            error={Boolean(errors.password)}
                            helperText={errors.password?.[0]}
                            autoComplete="current-password"
                        />
                        <Typography variant="body2" sx={{ mt: 1, textAlign: 'right' }}>
                            <Link href="/forgot-password" style={{ color: '#2563eb', fontWeight: 700, textDecoration: 'none' }}>
                                Forgot Password?
                            </Link>
                        </Typography>
                    </Box>

                    <Button type="submit" size="large" variant="contained" disabled={submitting}>
                        {submitting ? 'Signing in...' : 'Login'}
                    </Button>
                </Stack>
            </Box>
        </AuthLayout>
    );
}
