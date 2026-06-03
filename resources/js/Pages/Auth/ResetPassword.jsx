import axios from 'axios';
import { Link, router, usePage } from '@inertiajs/react';
import { Box, Button, Stack } from '@mui/material';
import { AuthCard, EmailField, FormAlert, PasswordConfirmFields } from '../../components';
import useForm from '../../hooks/useForm';
import { apiErrors, firstError } from '../../lib/auth';

export default function ResetPassword() {
    const { token, email: initialEmail } = usePage().props;
    const { form, errors, setErrors, submitting, setSubmitting, update } = useForm({
        token,
        email: initialEmail || '',
        password: '',
        password_confirmation: '',
    });

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
        <AuthCard
            title="Reset Password"
            subtitle="Choose a new password for your account."
            footer={<Link href="/login" style={{ color: '#2563eb', fontWeight: 700, textDecoration: 'none' }}>Back to login</Link>}
        >
            <Box component="form" onSubmit={submit}>
                <Stack spacing={2.5}>
                    <FormAlert>{firstError(errors, '')}</FormAlert>

                    <EmailField
                        value={form.email}
                        onChange={update}
                        error={Boolean(errors.email)}
                        helperText={errors.email?.[0]}
                    />

                    <PasswordConfirmFields form={form} errors={errors} onChange={update} />

                    <Button type="submit" size="large" variant="contained" disabled={submitting}>
                        {submitting ? 'Updating...' : 'Reset password'}
                    </Button>
                </Stack>
            </Box>
        </AuthCard>
    );
}
