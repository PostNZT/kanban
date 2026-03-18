import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { Board, BoardColumn as BoardColumnType } from '../../../types';

jest.mock('@hello-pangea/dnd', () => ({
    Draggable: ({ children }: any) =>
        children(
            { innerRef: jest.fn(), draggableProps: {}, dragHandleProps: {} },
            { isDragging: false },
        ),
    Droppable: ({ children }: any) =>
        children(
            { innerRef: jest.fn(), droppableProps: {}, placeholder: null },
            { isDraggingOver: false },
        ),
    DragDropContext: ({ children }: any) => <>{children}</>,
}));

const mockBoard: Board = {
    id: 'test-uuid',
    title: 'Test Board',
    columns: [
        {
            id: 1,
            title: 'To Do',
            position: 0,
            cards: [
                { id: 10, title: 'Task 1', description: 'desc', position: 0, createdAt: '' },
                { id: 11, title: 'Task 2', description: null, position: 1, createdAt: '' },
            ],
        },
        { id: 2, title: 'Done', position: 1, cards: [] },
    ],
    createdAt: '',
};

const mockDispatch = jest.fn();
jest.mock('../../../context/BoardContext', () => ({
    useBoard: () => ({
        state: mockBoard,
        dispatch: mockDispatch,
    }),
    BoardProvider: ({ children }: any) => <>{children}</>,
}));

jest.mock('../../../api/cards', () => ({
    createCard: jest.fn().mockResolvedValue({}),
    updateCard: jest.fn().mockResolvedValue({}),
    deleteCard: jest.fn().mockResolvedValue(undefined),
    moveCard: jest.fn().mockResolvedValue(undefined),
}));

jest.mock('../../../api/columns', () => ({
    createColumn: jest.fn().mockResolvedValue({}),
    updateColumn: jest.fn().mockResolvedValue({}),
    deleteColumn: jest.fn().mockResolvedValue(undefined),
    reorderColumns: jest.fn().mockResolvedValue(undefined),
}));

jest.mock('../../../reducers/boardReducer', () => ({
    ...jest.requireActual('../../../reducers/boardReducer'),
    snapshotBoard: jest.fn().mockReturnValue({ id: 'snapshot' }),
}));

import Column from '../../../components/board/Column';
import * as columnsApi from '../../../api/columns';

const testColumn: BoardColumnType = {
    id: 1,
    title: 'To Do',
    position: 0,
    cards: [
        { id: 10, title: 'Task 1', description: 'desc', position: 0, createdAt: '' },
        { id: 11, title: 'Task 2', description: null, position: 1, createdAt: '' },
    ],
};

function renderColumn(column: BoardColumnType = testColumn) {
    return render(<Column column={column} />);
}

describe('Column', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders column title', () => {
        renderColumn();
        expect(screen.getByText('To Do')).toBeInTheDocument();
    });

    test('renders card count', () => {
        renderColumn();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    test('renders cards', () => {
        renderColumn();
        expect(screen.getByText('Task 1')).toBeInTheDocument();
        expect(screen.getByText('Task 2')).toBeInTheDocument();
    });

    test('renders delete button', () => {
        renderColumn();
        expect(screen.getByRole('button', { name: /delete column/i })).toBeInTheDocument();
    });

    test('double-clicking title enters edit mode', async () => {
        const user = userEvent.setup();
        renderColumn();

        const title = screen.getByText('To Do');
        await user.dblClick(title);

        expect(screen.getByDisplayValue('To Do')).toBeInTheDocument();
    });

    test('delete column dispatches when confirmed', async () => {
        const user = userEvent.setup();
        jest.spyOn(window, 'confirm').mockReturnValue(true);

        renderColumn();

        await user.click(screen.getByRole('button', { name: /delete column/i }));

        expect(window.confirm).toHaveBeenCalledWith('Delete "To Do" and all its cards?');
        expect(mockDispatch).toHaveBeenCalledWith({
            type: 'DELETE_COLUMN',
            payload: 1,
        });
        expect(columnsApi.deleteColumn).toHaveBeenCalledWith(1);
    });

    test('delete column cancelled on deny', async () => {
        const user = userEvent.setup();
        jest.spyOn(window, 'confirm').mockReturnValue(false);

        renderColumn();

        await user.click(screen.getByRole('button', { name: /delete column/i }));

        expect(window.confirm).toHaveBeenCalled();
        expect(mockDispatch).not.toHaveBeenCalled();
        expect(columnsApi.deleteColumn).not.toHaveBeenCalled();
    });

    test('edit save dispatches UPDATE_COLUMN and calls API', async () => {
        const user = userEvent.setup();
        renderColumn();

        await user.dblClick(screen.getByText('To Do'));

        const input = screen.getByDisplayValue('To Do');
        await user.clear(input);
        await user.type(input, 'Backlog');
        await user.keyboard('{Enter}');

        expect(mockDispatch).toHaveBeenCalledWith({
            type: 'UPDATE_COLUMN',
            payload: { id: 1, title: 'Backlog' },
        });
        expect(columnsApi.updateColumn).toHaveBeenCalledWith(1, 'Backlog');
    });

    test('edit cancel on Escape restores original title', async () => {
        const user = userEvent.setup();
        renderColumn();

        await user.dblClick(screen.getByText('To Do'));

        const input = screen.getByDisplayValue('To Do');
        await user.clear(input);
        await user.type(input, 'Changed');
        await user.keyboard('{Escape}');

        expect(screen.getByText('To Do')).toBeInTheDocument();
        expect(mockDispatch).not.toHaveBeenCalled();
    });

    test('edit save with unchanged title just closes edit mode', async () => {
        const user = userEvent.setup();
        renderColumn();

        await user.dblClick(screen.getByText('To Do'));
        await user.keyboard('{Enter}');

        expect(screen.getByText('To Do')).toBeInTheDocument();
        expect(mockDispatch).not.toHaveBeenCalled();
        expect(columnsApi.updateColumn).not.toHaveBeenCalled();
    });

    test('edit save with empty title does not dispatch', async () => {
        const user = userEvent.setup();
        renderColumn();

        await user.dblClick(screen.getByText('To Do'));

        const input = screen.getByDisplayValue('To Do');
        await user.clear(input);
        await user.keyboard('{Enter}');

        expect(mockDispatch).not.toHaveBeenCalled();
    });

    test('edit save on blur dispatches UPDATE_COLUMN', async () => {
        const user = userEvent.setup();
        renderColumn();

        await user.dblClick(screen.getByText('To Do'));

        const input = screen.getByDisplayValue('To Do');
        await user.clear(input);
        await user.type(input, 'Updated');
        await user.tab();

        expect(mockDispatch).toHaveBeenCalledWith({
            type: 'UPDATE_COLUMN',
            payload: { id: 1, title: 'Updated' },
        });
    });

    test('update column rolls back on API failure', async () => {
        const user = userEvent.setup();
        (columnsApi.updateColumn as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderColumn();

        await user.dblClick(screen.getByText('To Do'));

        const input = screen.getByDisplayValue('To Do');
        await user.clear(input);
        await user.type(input, 'Failing');
        await user.keyboard('{Enter}');

        await waitFor(() => {
            expect(mockDispatch).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'ROLLBACK' }),
            );
        });
    });

    test('delete column rolls back on API failure', async () => {
        const user = userEvent.setup();
        jest.spyOn(window, 'confirm').mockReturnValue(true);
        (columnsApi.deleteColumn as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderColumn();

        await user.click(screen.getByRole('button', { name: /delete column/i }));

        await waitFor(() => {
            expect(mockDispatch).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'ROLLBACK' }),
            );
        });
    });
});
