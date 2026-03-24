import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { MemoryRouter } from 'react-router-dom';
import BoardList from '../../../components/board/BoardList';

jest.mock('../../../api/boards', () => ({
    getBoards: jest.fn(),
    createBoard: jest.fn(),
    deleteBoard: jest.fn(),
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

import * as boardsApi from '../../../api/boards';

function renderBoardList() {
    return render(
        <MemoryRouter>
            <BoardList />
        </MemoryRouter>,
    );
}

describe('BoardList', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('shows loading state initially', () => {
        (boardsApi.getBoards as jest.Mock).mockReturnValue(new Promise(() => {}));

        renderBoardList();

        expect(screen.getByText(/loading boards/i)).toBeInTheDocument();
    });

    test('renders boards after loading', async () => {
        (boardsApi.getBoards as jest.Mock).mockResolvedValue([
            { id: 'uuid-1', title: 'Board One', createdAt: '2026-01-01' },
            { id: 'uuid-2', title: 'Board Two', createdAt: '2026-01-02' },
        ]);

        renderBoardList();

        await waitFor(() => {
            expect(screen.getByText('Board One')).toBeInTheDocument();
            expect(screen.getByText('Board Two')).toBeInTheDocument();
        });
    });

    test('shows empty state when no boards exist', async () => {
        (boardsApi.getBoards as jest.Mock).mockResolvedValue([]);

        renderBoardList();

        await waitFor(() => {
            expect(screen.getByText(/no boards yet/i)).toBeInTheDocument();
        });
    });

    test('creates a board and navigates to it', async () => {
        const user = userEvent.setup();
        const createdBoard = {
            id: 'new-uuid',
            title: 'New Board',
            columns: [],
            createdAt: '2026-01-01',
        };
        (boardsApi.getBoards as jest.Mock).mockResolvedValue([]);
        (boardsApi.createBoard as jest.Mock).mockResolvedValue(createdBoard);

        renderBoardList();

        await waitFor(() => {
            expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
        });

        await user.type(screen.getByPlaceholderText(/new board title/i), 'New Board');
        await user.click(screen.getByRole('button', { name: /create board/i }));

        await waitFor(() => {
            expect(boardsApi.createBoard).toHaveBeenCalledWith('New Board', expect.any(String));
            expect(mockNavigate).toHaveBeenCalledWith(
                expect.stringMatching(/^\/boards\//),
                { state: { board: createdBoard } },
            );
        });
    });

    test('deletes a board and removes it from list', async () => {
        const user = userEvent.setup();
        (boardsApi.getBoards as jest.Mock).mockResolvedValue([
            { id: 'uuid-1', title: 'Board One', createdAt: '2026-01-01' },
        ]);
        (boardsApi.deleteBoard as jest.Mock).mockResolvedValue(undefined);

        renderBoardList();

        await waitFor(() => {
            expect(screen.getByText('Board One')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /delete/i }));

        await waitFor(() => {
            expect(boardsApi.deleteBoard).toHaveBeenCalledWith('uuid-1');
            expect(screen.queryByText('Board One')).not.toBeInTheDocument();
        });
    });

    test('navigates to board when board title is clicked', async () => {
        const user = userEvent.setup();
        (boardsApi.getBoards as jest.Mock).mockResolvedValue([
            { id: 'uuid-1', title: 'Board One', createdAt: '2026-01-01' },
        ]);

        renderBoardList();

        await waitFor(() => {
            expect(screen.getByText('Board One')).toBeInTheDocument();
        });

        await user.click(screen.getByText('Board One'));

        expect(mockNavigate).toHaveBeenCalledWith('/boards/uuid-1');
    });

    test('empty title does not create board', async () => {
        const user = userEvent.setup();
        (boardsApi.getBoards as jest.Mock).mockResolvedValue([]);

        renderBoardList();

        await waitFor(() => {
            expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /create board/i }));

        expect(boardsApi.createBoard).not.toHaveBeenCalled();
    });
});
