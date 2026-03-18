import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { User } from '../types';
import * as authApi from '../api/auth';

interface AuthState {
    user: User | null;
    isAuthenticated: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (email: string, password: string) => Promise<void>;
    logout: () => void;
}

const AuthContext = createContext<AuthState | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<User | null>(() => {
        const stored = sessionStorage.getItem('user');
        return stored ? (JSON.parse(stored) as User) : null;
    });

    const login = useCallback(async (email: string, password: string) => {
        const data = await authApi.login(email, password);
        sessionStorage.setItem('user', JSON.stringify(data.user));
        setUser(data.user);
    }, []);

    const register = useCallback(async (email: string, password: string) => {
        await authApi.register(email, password);
    }, []);

    const logout = useCallback(async () => {
        await authApi.logout();
        sessionStorage.removeItem('user');
        setUser(null);
    }, []);

    return (
        <AuthContext.Provider
            value={{
                user,
                isAuthenticated: !!user,
                login,
                register,
                logout,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth(): AuthState {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
}
