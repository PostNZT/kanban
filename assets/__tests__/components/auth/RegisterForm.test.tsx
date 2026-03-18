import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { MemoryRouter } from 'react-router-dom';
import RegisterForm from '../../../components/auth/RegisterForm';
import { AuthProvider } from '../../../context/AuthContext';

jest.mock('../../../api/auth', () => ({
    login: jest.fn(),
    register: jest.fn(),
    logout: jest.fn(),
}));

const mockNavigate = jest.fn();
const mockShowToast = jest.fn();
jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useNavigate: () => mockNavigate,
}));

jest.mock('../../../context/ToastContext', () => ({
    useToast: () => ({
        toasts: [],
        showToast: mockShowToast,
        removeToast: jest.fn(),
    }),
}));

import * as authApi from '../../../api/auth';

function renderRegisterForm() {
    return render(
        <MemoryRouter>
            <AuthProvider>
                <RegisterForm />
            </AuthProvider>
        </MemoryRouter>,
    );
}

describe('RegisterForm', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders email, password, and confirm password fields', () => {
        renderRegisterForm();

        expect(screen.getByLabelText(/^email$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^password$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/confirm password/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /register/i })).toBeInTheDocument();
    });

    test('renders link to login page', () => {
        renderRegisterForm();

        const link = screen.getByRole('link', { name: /login/i });
        expect(link).toBeInTheDocument();
        expect(link).toHaveAttribute('href', '/login');
    });

    test('calls register API and navigates to login on success', async () => {
        const user = userEvent.setup();
        (authApi.register as jest.Mock).mockResolvedValue({
            id: 1,
            email: 'new@test.com',
        });

        renderRegisterForm();

        await user.type(screen.getByLabelText(/^email$/i), 'new@test.com');
        await user.type(screen.getByLabelText(/^password$/i), 'Password1');
        await user.type(screen.getByLabelText(/confirm password/i), 'Password1');
        await user.click(screen.getByRole('button', { name: /register/i }));

        await waitFor(() => {
            expect(authApi.register).toHaveBeenCalledWith('new@test.com', 'Password1');
            expect(mockNavigate).toHaveBeenCalledWith('/login');
        });
    });

    test('shows warning toast when passwords do not match', async () => {
        const user = userEvent.setup();

        renderRegisterForm();

        await user.type(screen.getByLabelText(/^email$/i), 'new@test.com');
        await user.type(screen.getByLabelText(/^password$/i), 'Password1');
        await user.type(screen.getByLabelText(/confirm password/i), 'Different1');
        await user.click(screen.getByRole('button', { name: /register/i }));

        expect(mockShowToast).toHaveBeenCalledWith('Passwords do not match.', 'warning');
        expect(authApi.register).not.toHaveBeenCalled();
    });

    test('shows error toast on API failure', async () => {
        const user = userEvent.setup();
        (authApi.register as jest.Mock).mockRejectedValue(new Error('Email taken'));

        renderRegisterForm();

        await user.type(screen.getByLabelText(/^email$/i), 'taken@test.com');
        await user.type(screen.getByLabelText(/^password$/i), 'Password1');
        await user.type(screen.getByLabelText(/confirm password/i), 'Password1');
        await user.click(screen.getByRole('button', { name: /register/i }));

        await waitFor(() => {
            expect(mockShowToast).toHaveBeenCalledWith('Registration failed. Email may already be in use.', 'error');
        });
    });

    test('shows loading state while submitting', async () => {
        const user = userEvent.setup();
        let resolveRegister: (value: unknown) => void;
        (authApi.register as jest.Mock).mockReturnValue(
            new Promise((resolve) => { resolveRegister = resolve; }),
        );

        renderRegisterForm();

        await user.type(screen.getByLabelText(/^email$/i), 'new@test.com');
        await user.type(screen.getByLabelText(/^password$/i), 'Password1');
        await user.type(screen.getByLabelText(/confirm password/i), 'Password1');
        await user.click(screen.getByRole('button', { name: /register/i }));

        expect(screen.getByRole('button', { name: /registering/i })).toBeDisabled();

        resolveRegister!({ id: 1, email: 'new@test.com' });

        await waitFor(() => {
            expect(screen.getByRole('button', { name: /register/i })).not.toBeDisabled();
        });
    });
});
