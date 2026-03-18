import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { MemoryRouter } from 'react-router-dom';
import Header from '../../../components/layout/Header';

const mockLogout = jest.fn();
const mockLogin = jest.fn();
const mockRegister = jest.fn();

jest.mock('../../../context/AuthContext', () => ({
    useAuth: jest.fn(),
    AuthProvider: ({ children }: any) => <>{children}</>,
}));

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useNavigate: () => mockNavigate,
}));

import { useAuth } from '../../../context/AuthContext';

function renderHeader() {
    return render(
        <MemoryRouter>
            <Header />
        </MemoryRouter>,
    );
}

describe('Header', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders nothing when not authenticated', () => {
        (useAuth as jest.Mock).mockReturnValue({
            user: null,
            isAuthenticated: false,
            login: mockLogin,
            register: mockRegister,
            logout: mockLogout,
        });

        const { container } = renderHeader();

        expect(container.innerHTML).toBe('');
    });

    test('renders header when authenticated', () => {
        (useAuth as jest.Mock).mockReturnValue({
            user: { id: 1, email: 'test@test.com' },
            isAuthenticated: true,
            login: mockLogin,
            register: mockRegister,
            logout: mockLogout,
        });

        renderHeader();

        expect(screen.getByText('Kanban Board')).toBeInTheDocument();
        expect(screen.getByText('test@test.com')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /logout/i })).toBeInTheDocument();
    });

    test('logout button calls logout and navigates to login', async () => {
        const user = userEvent.setup();

        (useAuth as jest.Mock).mockReturnValue({
            user: { id: 1, email: 'test@test.com' },
            isAuthenticated: true,
            login: mockLogin,
            register: mockRegister,
            logout: mockLogout,
        });

        renderHeader();

        await user.click(screen.getByRole('button', { name: /logout/i }));

        expect(mockLogout).toHaveBeenCalledTimes(1);
        expect(mockNavigate).toHaveBeenCalledWith('/login');
    });

    test('clicking title navigates to home', async () => {
        const user = userEvent.setup();

        (useAuth as jest.Mock).mockReturnValue({
            user: { id: 1, email: 'test@test.com' },
            isAuthenticated: true,
            login: mockLogin,
            register: mockRegister,
            logout: mockLogout,
        });

        renderHeader();

        await user.click(screen.getByText('Kanban Board'));

        expect(mockNavigate).toHaveBeenCalledWith('/');
    });
});
