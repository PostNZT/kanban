import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { Board } from '../../../types';

let capturedOnDragEnd: ((result: any) => void) | null = null;

jest.mock('@hello-pangea/dnd', () => ({
    DragDropContext: ({ onDragEnd, children }: any) => {
        capturedOnDragEnd = onDragEnd;
        return <>{children}</>;
    },
    Droppable: ({ children }: any) =>
        children(
            { innerRef: jest.fn(), droppableProps: {}, placeholder: null },
            { isDraggingOver: false },
        ),
    Draggable: ({ children }: any) =>
        children(
            { innerRef: jest.fn(), draggableProps: {}, dragHandleProps: {} },
            { isDragging: false },
        ),
}));

jest.mock('../../../api/boards', () => ({
    getBoard: jest.fn(),
}));

jest.mock('../../../api/cards', () => ({
    moveCard: jest.fn().mockResolvedValue(undefined),
}));

jest.mock('../../../api/columns', () => ({
    createColumn: jest.fn(),
    deleteColumn: jest.fn(),
}));

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useNavigate: () => mockNavigate,
}));

import BoardView from '../../../components/board/BoardView';
import { BoardProvider } from '../../../context/BoardContext';
import * as boardsApi from '../../../api/boards';
import * as cardsApi from '../../../api/cards';

const mockBoard: Board = {
    id: 'test-uuid',
    title: 'Test Board',
    columns: [
        {
            id: 1,
            title: 'To Do',
            position: 0,
            cards: [
                { id: 1, title: 'Task 1', description: null, position: 0, createdAt: '2026-01-01' },
            ],
        },
        {
            id: 2,
            title: 'In Progress',
            position: 1,
            cards: [],
        },
    ],
    createdAt: '2026-01-01',
};

function renderBoardView(boardId: string = 'test-uuid') {
    return render(
        <MemoryRouter initialEntries={[`/boards/${boardId}`]}>
            <Routes>
                <Route
                    path="/boards/:id"
                    element={
                        <BoardProvider>
                            <BoardView />
                        </BoardProvider>
                    }
                />
            </Routes>
        </MemoryRouter>,
    );
}

describe('BoardView', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('shows loading state initially', () => {
        (boardsApi.getBoard as jest.Mock).mockReturnValue(new Promise(() => {}));

        renderBoardView();

        expect(screen.getByText(/loading board/i)).toBeInTheDocument();
    });

    test('renders board with columns after loading', async () => {
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);

        renderBoardView();

        await waitFor(() => {
            expect(screen.getByText('Test Board')).toBeInTheDocument();
            expect(screen.getByText('To Do')).toBeInTheDocument();
            expect(screen.getByText('In Progress')).toBeInTheDocument();
            expect(screen.getByText('Task 1')).toBeInTheDocument();
        });
    });

    test('fetches board using URL param id', async () => {
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);

        renderBoardView('test-uuid');

        await waitFor(() => {
            expect(boardsApi.getBoard).toHaveBeenCalledWith('test-uuid');
        });
    });

    test('navigates home when board fetch fails', async () => {
        (boardsApi.getBoard as jest.Mock).mockRejectedValue(new Error('Not found'));

        renderBoardView();

        await waitFor(() => {
            expect(mockNavigate).toHaveBeenCalledWith('/');
        });
    });

    test('renders back to boards button', async () => {
        const user = userEvent.setup();
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);

        renderBoardView();

        await waitFor(() => {
            expect(screen.getByText('Test Board')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('button', { name: /back to boards/i }));

        expect(mockNavigate).toHaveBeenCalledWith('/');
    });

    test('onDragEnd with no destination does nothing', async () => {
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);

        renderBoardView();

        await waitFor(() => {
            expect(screen.getByText('Test Board')).toBeInTheDocument();
        });

        act(() => {
            capturedOnDragEnd!({
                source: { droppableId: '1', index: 0 },
                destination: null,
            });
        });

        expect(cardsApi.moveCard).not.toHaveBeenCalled();
    });

    test('onDragEnd with same position does nothing', async () => {
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);

        renderBoardView();

        await waitFor(() => {
            expect(screen.getByText('Test Board')).toBeInTheDocument();
        });

        act(() => {
            capturedOnDragEnd!({
                source: { droppableId: '1', index: 0 },
                destination: { droppableId: '1', index: 0 },
            });
        });

        expect(cardsApi.moveCard).not.toHaveBeenCalled();
    });

    test('onDragEnd moves card between columns and calls API', async () => {
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);

        renderBoardView();

        await waitFor(() => {
            expect(screen.getByText('Test Board')).toBeInTheDocument();
        });

        act(() => {
            capturedOnDragEnd!({
                source: { droppableId: '1', index: 0 },
                destination: { droppableId: '2', index: 0 },
            });
        });

        expect(cardsApi.moveCard).toHaveBeenCalledWith(1, 2, 0);
    });

    test('onDragEnd rolls back state on API failure', async () => {
        (boardsApi.getBoard as jest.Mock).mockResolvedValue(mockBoard);
        (cardsApi.moveCard as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderBoardView();

        await waitFor(() => {
            expect(screen.getByText('Test Board')).toBeInTheDocument();
        });

        act(() => {
            capturedOnDragEnd!({
                source: { droppableId: '1', index: 0 },
                destination: { droppableId: '2', index: 0 },
            });
        });

        await waitFor(() => {
            // After rollback, Task 1 should still be visible
            expect(screen.getByText('Task 1')).toBeInTheDocument();
        });
    });
});
