import React, { useState } from 'react';
import { IconButton, InputAdornment, TextField } from '@mui/material';
import { Visibility, VisibilityOff } from '@mui/icons-material';

export default function PasswordField({ label, value, onChange, error, helperText, name = 'password', autoComplete }) {
    const [visible, setVisible] = useState(false);

    return (
        <TextField
            fullWidth
            required
            name={name}
            label={label}
            type={visible ? 'text' : 'password'}
            value={value}
            onChange={onChange}
            error={Boolean(error)}
            helperText={helperText}
            autoComplete={autoComplete}
            slotProps={{
                input: {
                    endAdornment: (
                        <InputAdornment position="end">
                            <IconButton
                                aria-label={visible ? 'Hide password' : 'Show password'}
                                edge="end"
                                onClick={() => setVisible((current) => !current)}
                            >
                                {visible ? <VisibilityOff /> : <Visibility />}
                            </IconButton>
                        </InputAdornment>
                    ),
                },
            }}
        />
    );
}
