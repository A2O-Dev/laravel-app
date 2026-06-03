import React from 'react';
import { Alert } from '@mui/material';

export default function FormAlert({ severity = 'error', children }) {
    if (!children) {
        return null;
    }

    return (
        <Alert severity={severity} variant="outlined">
            {children}
        </Alert>
    );
}
