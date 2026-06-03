import './bootstrap';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { CssBaseline, ThemeProvider } from '@mui/material';
import theme from './theme';

createInertiaApp({
    resolve: (name) => import(`./Pages/${name}.jsx`).then((m) => m.default),
    setup({ el, App, props }) {
        createRoot(el).render(
            <React.StrictMode>
                <ThemeProvider theme={theme}>
                    <CssBaseline />
                    <App {...props} />
                </ThemeProvider>
            </React.StrictMode>,
        );
    },
});
