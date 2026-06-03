import React from 'react';
import PropTypes from 'prop-types';
import { TextField } from '@mui/material';

EmailField.propTypes = {
    value: PropTypes.string.isRequired,
    onChange: PropTypes.func.isRequired,
    error: PropTypes.bool,
    helperText: PropTypes.string,
};

export default function EmailField({ value, onChange, error, helperText }) {
    return (
        <TextField
            fullWidth
            required
            name="email"
            type="email"
            label="Email"
            value={value}
            onChange={onChange}
            error={Boolean(error)}
            helperText={helperText}
            autoComplete="email"
        />
    );
}
