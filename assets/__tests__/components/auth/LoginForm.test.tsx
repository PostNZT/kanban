import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { MemoryRouter } from 'react-router-dom';
import LoginForm from '../../../components/auth/LoginForm';
import { AuthProvider } from '../../../context/AuthContext';

// Mock the auth API
jest.mock('../../../api/auth', () => ({
    login: jest.fn(),
    register: jest.fn(),
    logout: jest.fn(),
}));

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useNavigate: () => mockNavigate,
}));

import * as authApi from '../../../api/auth';

function renderLoginForm() {
    return render(
        <MemoryRouter>
            <AuthProvider>
                <LoginForm />
            </AuthProvider>
        </MemoryRouter>,
    );
}

describe('LoginForm', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders email and password fields', () => {
        renderLoginForm();

        expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /login/i })).toBeInTheDocument();
    });

    test('renders link to register page', () => {
        renderLoginForm();

        const link = screen.getByRole('link', { name: /register/i });
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/register');
    });

    test('calls login API and navigates on successful submit', async () => {
        const user = userEvent.setup();
        (authApi.login as jest.Mock).mockResolvedValue({
            user: { id: 1, email: 'test@test.com' },
        });

        renderLoginForm();

        await user.type(screen.getByLabelText(/email/i), 'test@test.com');
        await user.type(screen.getByLabelText(/password/i), 'Password1');
        await user.click(screen.getByRole('button', { name: /login/i }));

        await waitFor(() => {
            expect(authApi.login).toHaveBeenCalledWith('test@test.com', 'Password1');
            expect(mockNavigate).toHaveBeenCalledWith('/');
        });
    });

    test('shows error message on failed login', async () => {
        const user = userEvent.setup();
        (authApi.login as jest.Mock).mockRejectedValue(new Error('Invalid credentials'));

        renderLoginForm();

        await user.type(screen.getByLabelText(/email/i), 'test@test.com');
        await user.type(screen.getByLabelText(/password/i), 'wrong');
        await user.click(screen.getByRole('button', { name: /login/i }));

        await waitFor(() => {
            expect(screen.getByText(/invalid email or password/i)).toBeInTheDocument();
        });
    });

    test('shows loading state while submitting', async () => {
        const user = userEvent.setup();
        let resolveLogin: (value: unknown) => void;
        (authApi.login as jest.Mock).mockReturnValue(
            new Promise((resolve) => { resolveLogin = resolve; }),
        );

        renderLoginForm();

        await user.type(screen.getByLabelText(/email/i), 'test@test.com');
        await user.type(screen.getByLabelText(/password/i), 'Password1');
        await user.click(screen.getByRole('button', { name: /login/i }));

        expect(screen.getByRole('button', { name: /logging in/i })).toBeDisabled();

        resolveLogin!({ user: { id: 1, email: 'test@test.com' } });

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /login/i })).not.toBeDisabled();
        });
    });
});
