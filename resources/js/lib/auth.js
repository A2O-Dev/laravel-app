import axios from 'axios';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 30;

function writeTokenCookie(token) {
    document.cookie = `${TOKEN_KEY}=${token}; path=/; max-age=${COOKIE_MAX_AGE}; SameSite=Lax`;
}

function clearTokenCookie() {
    document.cookie = `${TOKEN_KEY}=; path=/; max-age=0; SameSite=Lax`;
}

export function getToken() {
    return window.localStorage.getItem(TOKEN_KEY);
}

export function getStoredUser() {
    const user = window.localStorage.getItem(USER_KEY);

    if (!user) {
        return null;
    }

    try {
        return JSON.parse(user);
    } catch (error) {
        window.localStorage.removeItem(USER_KEY);
        return null;
    }
}

export function persistSession(user, token) {
    window.localStorage.setItem(TOKEN_KEY, token);
    window.localStorage.setItem(USER_KEY, JSON.stringify(user));
    writeTokenCookie(token);
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
}

export function clearSession() {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(USER_KEY);
    clearTokenCookie();
    delete axios.defaults.headers.common.Authorization;
}

export function configureAuthHeader() {
    const token = getToken();

    if (token) {
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    }
}

export function firstResult(response) {
    return response?.data?.results?.[0] || null;
}

export function apiErrors(error) {
    const payload = error?.response?.data || {};
    const errors = payload.errors || {};

    if (typeof errors === 'string') {
        return { general: [errors] };
    }

    return Object.keys(errors).reduce((carry, key) => {
        const value = errors[key];
        carry[key] = Array.isArray(value) ? value : [value];
        return carry;
    }, {});
}

export function firstError(errors, fallback = 'Something went wrong. Please try again.') {
    const key = Object.keys(errors || {})[0];

    if (!key) {
        return fallback;
    }

    return errors[key]?.[0] || fallback;
}
