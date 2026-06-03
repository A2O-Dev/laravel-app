import React from 'react';
import PropTypes from 'prop-types';
import { Alert } from '@mui/material';

FormAlert.propTypes = {
    severity: PropTypes.oneOf(['error', 'warning', 'info', 'success']),
    children: PropTypes.node,
};

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
