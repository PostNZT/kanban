import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import ProtectedRoute from '../../../components/layout/ProtectedRoute';

const mockLogout = jest.fn();
const mockLogin = jest.fn();
const mockRegister = jest.fn();

jest.mock('../../../context/AuthContext', () => ({
    useAuth: jest.fn(),
    AuthProvider: ({ children }: any) => <>{children}</>,
}));

import { useAuth } from '../../../context/AuthContext';

describe('ProtectedRoute', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('redirects to login when not authenticated', () => {
        (useAuth as jest.Mock).mockReturnValue({
            user: null,
            isAuthenticated: false,
            login: mockLogin,
            register: mockRegister,
            logout: mockLogout,
        });

        render(
            <MemoryRouter initialEntries={['/protected']}>
                <Routes>
                    <Route
                        path="/protected"
                        element={
                            <ProtectedRoute>
                                <div>Secret Content</div>
                            </ProtectedRoute>
                        }
                    />
                    <Route path="/login" element={<div>Login Page</div>} />
                </Routes>
            </MemoryRouter>,
        );

        expect(screen.queryByText('Secret Content')).not.toBeInTheDocument();
        expect(screen.getByText('Login Page')).toBeInTheDocument();
    });

    test('renders children when authenticated', () => {
        (useAuth as jest.Mock).mockReturnValue({
            user: { id: 1, email: 'test@test.com' },
            isAuthenticated: true,
            login: mockLogin,
            register: mockRegister,
            logout: mockLogout,
        });

        render(
            <MemoryRouter initialEntries={['/protected']}>
                <Routes>
                    <Route
                        path="/protected"
                        element={
                            <ProtectedRoute>
                                <div>Secret Content</div>
                            </ProtectedRoute>
                        }
                    />
                    <Route path="/login" element={<div>Login Page</div>} />
                </Routes>
            </MemoryRouter>,
        );

        expect(screen.getByText('Secret Content')).toBeInTheDocument();
        expect(screen.queryByText('Login Page')).not.toBeInTheDocument();
    });
});
