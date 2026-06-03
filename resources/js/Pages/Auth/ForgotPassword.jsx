import { useState } from 'react';
import axios from 'axios';
import { Link } from '@inertiajs/react';
import { Box, Button, Stack } from '@mui/material';
import { AuthCard, EmailField, FormAlert } from '../../components';
import { apiErrors, firstError } from '../../lib/auth';

export default function ForgotPassword() {
    const [email, setEmail] = useState('');
    const [errors, setErrors] = useState({});
    const [success, setSuccess] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true);
        setErrors({});
        setSuccess('');

        try {
            const response = await axios.post('/api/auth/forgot-password', { email });
            setSuccess(response.data?.message || 'Password reset instructions have been sent.');
        } catch (error) {
            setErrors(apiErrors(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthCard
            title="Forgot Password"
            subtitle="Enter your email to receive reset instructions."
            footer={<Link href="/login" style={{ color: '#2563eb', fontWeight: 700, textDecoration: 'none' }}>Back to login</Link>}
        >
            <Box component="form" onSubmit={submit}>
                <Stack spacing={2.5}>
                    <FormAlert severity="success">{success}</FormAlert>
                    <FormAlert>{firstError(errors, '')}</FormAlert>

                    <EmailField
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        error={Boolean(errors.email)}
                        helperText={errors.email?.[0]}
                    />

                    <Button type="submit" size="large" variant="contained" disabled={submitting}>
                        {submitting ? 'Sending...' : 'Send reset instructions'}
                    </Button>
                </Stack>
            </Box>
        </AuthCard>
    );
}
