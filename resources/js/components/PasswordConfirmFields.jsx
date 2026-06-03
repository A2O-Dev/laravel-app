import React from 'react';
import PasswordField from './PasswordField';

export default function PasswordConfirmFields({ form, errors, onChange }) {
    return (
        <>
            <PasswordField
                label="New Password"
                name="password"
                value={form.password}
                onChange={onChange}
                error={Boolean(errors.password)}
                helperText={errors.password?.[0]}
                autoComplete="new-password"
            />
            <PasswordField
                label="Confirm New Password"
                name="password_confirmation"
                value={form.password_confirmation}
                onChange={onChange}
                error={Boolean(errors.password_confirmation)}
                helperText={errors.password_confirmation?.[0]}
                autoComplete="new-password"
            />
        </>
    );
}
