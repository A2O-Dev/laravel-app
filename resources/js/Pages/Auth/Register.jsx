import React, { useState } from 'react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { Box, Button, Stack, TextField } from '@mui/material';
import AuthLayout, { AuthLink } from '../../layouts/AuthLayout';
import PasswordField from '../../components/PasswordField';
import FormAlert from '../../components/FormAlert';
import { apiErrors, firstError, firstResult, persistSession } from '../../lib/auth';

export default function Register() {
    const [form, setForm] = useState({
        name: '',
        email: '',
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
            await axios.post('/api/auth/register', form);
            const loginResponse = await axios.post('/api/auth/login', {
                email: form.email,
                password: form.password,
            });
            const user = firstResult(loginResponse);
            persistSession(user, user.token);
            router.visit('/dashboard');
        } catch (error) {
            setErrors(apiErrors(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthLayout
            title="Register"
            subtitle="Create an account to start using the dashboard."
            footer={<>Already have an account? <AuthLink href="/login">Login</AuthLink></>}
        >
            <Box component="form" onSubmit={submit}>
                <Stack spacing={2.5}>
                    <FormAlert>{firstError(errors, '')}</FormAlert>

                    <TextField
                        fullWidth
                        required
                        name="name"
                        label="Name"
                        value={form.name}
                        onChange={update}
                        error={Boolean(errors.name)}
                        helperText={errors.name?.[0]}
                        autoComplete="name"
                    />
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
                        label="Password"
                        name="password"
                        value={form.password}
                        onChange={update}
                        error={Boolean(errors.password)}
                        helperText={errors.password?.[0]}
                        autoComplete="new-password"
                    />
                    <PasswordField
                        label="Confirm Password"
                        name="password_confirmation"
                        value={form.password_confirmation}
                        onChange={update}
                        error={Boolean(errors.password_confirmation)}
                        helperText={errors.password_confirmation?.[0]}
                        autoComplete="new-password"
                    />

                    <Button type="submit" size="large" variant="contained" disabled={submitting}>
                        {submitting ? 'Creating account...' : 'Register'}
                    </Button>
                </Stack>
            </Box>
        </AuthLayout>
    );
}
