import React from 'react';
import axios from 'axios';
import { Link, router } from '@inertiajs/react';
import { Box, Button, Stack, TextField } from '@mui/material';
import { AuthCard, EmailField, FormAlert, PasswordConfirmFields } from '../../components';
import useForm from '../../hooks/useForm';
import { apiErrors, firstError, firstResult, persistSession } from '../../lib/auth';

export default function Register() {
    const { form, errors, setErrors, submitting, setSubmitting, update } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

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
        <AuthCard
            title="Register"
            subtitle="Create an account to start using the dashboard."
            footer={<>Already have an account? <Link href="/login" style={{ color: '#2563eb', fontWeight: 700, textDecoration: 'none' }}>Login</Link></>}
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
                    <EmailField
                        value={form.email}
                        onChange={update}
                        error={Boolean(errors.email)}
                        helperText={errors.email?.[0]}
                    />

                    <PasswordConfirmFields form={form} errors={errors} onChange={update} />

                    <Button type="submit" size="large" variant="contained" disabled={submitting}>
                        {submitting ? 'Creating account...' : 'Register'}
                    </Button>
                </Stack>
            </Box>
        </AuthCard>
    );
}
