import './bootstrap';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { CssBaseline, ThemeProvider } from '@mui/material';
import { AuthProvider } from './contexts/AuthContext';
import theme from './theme';

const pages = {
    'Auth/Login': () => import('./Pages/Auth/Login'),
    'Auth/Register': () => import('./Pages/Auth/Register'),
    'Auth/ForgotPassword': () => import('./Pages/Auth/ForgotPassword'),
    'Auth/ResetPassword': () => import('./Pages/Auth/ResetPassword'),
    'Dashboard/Index': () => import('./Pages/Dashboard/Index'),
    'Profile/ChangePassword': () => import('./Pages/Profile/ChangePassword'),
};

createInertiaApp({
    resolve: (name) => {
        const page = pages[name];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page().then((module) => module.default);
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <React.StrictMode>
                <ThemeProvider theme={theme}>
                    <CssBaseline />
                    <AuthProvider>
                        <App {...props} />
                    </AuthProvider>
                </ThemeProvider>
            </React.StrictMode>,
        );
    },
});
