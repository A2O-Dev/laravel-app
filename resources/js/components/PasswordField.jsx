import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { IconButton, InputAdornment, TextField } from '@mui/material';
import { Visibility, VisibilityOff } from '@mui/icons-material';

PasswordField.propTypes = {
    label: PropTypes.string.isRequired,
    value: PropTypes.string.isRequired,
    onChange: PropTypes.func.isRequired,
    error: PropTypes.bool,
    helperText: PropTypes.string,
    name: PropTypes.string,
    autoComplete: PropTypes.string,
};

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
