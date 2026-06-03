import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { clearSession, configureAuthHeader, firstResult, getStoredUser, getToken, persistSession } from '../lib/auth';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(getStoredUser);
    const [loading, setLoading] = useState(Boolean(getToken()));

    useEffect(() => {
        configureAuthHeader();

        if (!getToken()) {
            setLoading(false);
            return;
        }

        axios.get('/api/auth/me')
            .then((response) => {
                const currentUser = firstResult(response);
                if (currentUser) {
                    persistSession(currentUser, getToken());
                    setUser(currentUser);
                } else {
                    clearSession();
                    setUser(null);
                }
            })
            .catch(() => {
                clearSession();
                setUser(null);
            })
            .finally(() => setLoading(false));
    }, []);

    const value = useMemo(() => ({
        user,
        loading,
        isAuthenticated: Boolean(user && getToken()),
        setSession(nextUser, token) {
            persistSession(nextUser, token);
            setUser(nextUser);
        },
        clearAuth() {
            clearSession();
            setUser(null);
        },
    }), [user, loading]);

    return (
        <AuthContext.Provider value={value}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
