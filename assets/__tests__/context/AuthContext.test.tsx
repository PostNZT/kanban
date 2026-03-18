import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

jest.mock('../../api/auth', () => ({
    login: jest.fn(),
    register: jest.fn(),
    logout: jest.fn(),
}));

import { AuthProvider, useAuth } from '../../context/AuthContext';
import * as authApi from '../../api/auth';

function TestConsumer() {
    const { user, isAuthenticated, login, register, logout } = useAuth();
    return (
        <div>
            <span data-testid="auth-status">
                {isAuthenticated ? 'authenticated' : 'unauthenticated'}
            </span>
            <span data-testid="user-email">{user?.email ?? 'none'}</span>
            <button onClick={() => login('test@test.com', 'Password1')}>Login</button>
            <button onClick={() => register('new@test.com', 'Password1')}>Register</button>
            <button onClick={() => logout()}>Logout</button>
        </div>
    );
}

describe('AuthContext', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        sessionStorage.clear();
    });

    test('useAuth throws when used outside AuthProvider', () => {
        const spy = jest.spyOn(console, 'error').mockImplementation(() => {});
        expect(() => render(<TestConsumer />)).toThrow(
            'useAuth must be used within an AuthProvider',
        );
        spy.mockRestore();
    });

    test('initially unauthenticated when no session stored', () => {
        render(
            <AuthProvider>
                <TestConsumer />
            </AuthProvider>,
        );
        expect(screen.getByTestId('auth-status')).toHaveTextContent('unauthenticated');
        expect(screen.getByTestId('user-email')).toHaveTextContent('none');
    });

    test('restores user from sessionStorage on mount', () => {
        sessionStorage.setItem('user', JSON.stringify({ id: 1, email: 'stored@test.com' }));
        render(
            <AuthProvider>
                <TestConsumer />
            </AuthProvider>,
        );
        expect(screen.getByTestId('auth-status')).toHaveTextContent('authenticated');
        expect(screen.getByTestId('user-email')).toHaveTextContent('stored@test.com');
    });

    test('login stores user and becomes authenticated', async () => {
        const user = userEvent.setup();
        (authApi.login as jest.Mock).mockResolvedValue({
            user: { id: 1, email: 'test@test.com' },
        });

        render(
            <AuthProvider>
                <TestConsumer />
            </AuthProvider>,
        );

        await user.click(screen.getByText('Login'));

        await waitFor(() => {
            expect(screen.getByTestId('auth-status')).toHaveTextContent('authenticated');
            expect(screen.getByTestId('user-email')).toHaveTextContent('test@test.com');
        });

        expect(authApi.login).toHaveBeenCalledWith('test@test.com', 'Password1');
        expect(sessionStorage.getItem('user')).toBeTruthy();
    });

    test('register calls API without updating auth state', async () => {
        const user = userEvent.setup();
        (authApi.register as jest.Mock).mockResolvedValue({
            id: 1,
            email: 'new@test.com',
        });

        render(
            <AuthProvider>
                <TestConsumer />
            </AuthProvider>,
        );

        await user.click(screen.getByText('Register'));

        await waitFor(() => {
            expect(authApi.register).toHaveBeenCalledWith('new@test.com', 'Password1');
        });

        // Register does NOT auto-login the user
        expect(screen.getByTestId('auth-status')).toHaveTextContent('unauthenticated');
    });

    test('logout clears user and session', async () => {
        const user = userEvent.setup();
        (authApi.login as jest.Mock).mockResolvedValue({
            user: { id: 1, email: 'test@test.com' },
        });
        (authApi.logout as jest.Mock).mockResolvedValue(undefined);

        render(
            <AuthProvider>
                <TestConsumer />
            </AuthProvider>,
        );

        // Login first
        await user.click(screen.getByText('Login'));
        await waitFor(() => {
            expect(screen.getByTestId('auth-status')).toHaveTextContent('authenticated');
        });

        // Then logout
        await user.click(screen.getByText('Logout'));
        await waitFor(() => {
            expect(screen.getByTestId('auth-status')).toHaveTextContent('unauthenticated');
        });

        expect(authApi.logout).toHaveBeenCalled();
        expect(sessionStorage.getItem('user')).toBeNull();
    });
});
