import { createTheme } from '@mui/material/styles';

const theme = createTheme({
    palette: {
        mode: 'light',
        primary: {
            main: '#2563eb',
        },
        secondary: {
            main: '#0f766e',
        },
        background: {
            default: '#f4f6f8',
            paper: '#ffffff',
        },
        text: {
            primary: '#17202a',
            secondary: '#5b6776',
        },
    },
    typography: {
        fontFamily: 'Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        h4: {
            fontWeight: 800,
            letterSpacing: 0,
        },
        h5: {
            fontWeight: 700,
            letterSpacing: 0,
        },
        button: {
            fontWeight: 700,
            textTransform: 'none',
        },
    },
    shape: {
        borderRadius: 8,
    },
});

export default theme;
