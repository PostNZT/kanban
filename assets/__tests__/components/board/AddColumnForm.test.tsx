import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { Board } from '../../../types';

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
const mockShowToast = jest.fn();
jest.mock('../../../context/BoardContext', () => ({
    useBoard: () => ({
        state: mockBoard,
        dispatch: mockDispatch,
    }),
    BoardProvider: ({ children }: any) => <>{children}</>,
}));

jest.mock('../../../context/ToastContext', () => ({
    useToast: () => ({
        toasts: [],
        showToast: mockShowToast,
        removeToast: jest.fn(),
    }),
}));

jest.mock('../../../api/columns', () => ({
    createColumn: jest.fn().mockResolvedValue({ id: 50, title: 'New Column', position: 2, cards: [] }),
    updateColumn: jest.fn().mockResolvedValue({}),
    deleteColumn: jest.fn().mockResolvedValue(undefined),
    reorderColumns: jest.fn().mockResolvedValue(undefined),
}));

jest.mock('../../../reducers/boardReducer', () => ({
    ...jest.requireActual('../../../reducers/boardReducer'),
    snapshotBoard: jest.fn().mockReturnValue({ id: 'snapshot' }),
}));

import AddColumnForm from '../../../components/board/AddColumnForm';
import * as columnsApi from '../../../api/columns';

function renderAddColumnForm(boardId: string = 'test-uuid') {
    return render(<AddColumnForm boardId={boardId} />);
}

describe('AddColumnForm', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders "+ Add Column" button', () => {
        renderAddColumnForm();
        expect(screen.getByText('+ Add Column')).toBeInTheDocument();
    });

    test('clicking button opens the form', async () => {
        const user = userEvent.setup();
        renderAddColumnForm();

        await user.click(screen.getByText('+ Add Column'));

        expect(screen.getByPlaceholderText('Column title...')).toBeInTheDocument();
        expect(screen.getByText('Add')).toBeInTheDocument();
        expect(screen.getByText('Cancel')).toBeInTheDocument();
    });

    test('submit creates column with dispatch and API call', async () => {
        const user = userEvent.setup();
        renderAddColumnForm();

        await user.click(screen.getByText('+ Add Column'));

        const input = screen.getByPlaceholderText('Column title...');
        await user.type(input, 'New Column');
        await user.click(screen.getByText('Add'));

        expect(mockDispatch).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'ADD_COLUMN',
                payload: expect.objectContaining({
                    title: 'New Column',
                    position: 2,
                    cards: [],
                }),
            }),
        );

        expect(columnsApi.createColumn).toHaveBeenCalledWith('test-uuid', 'New Column');

        // Form should close after submit
        expect(screen.getByText('+ Add Column')).toBeInTheDocument();
    });

    test('cancel closes the form', async () => {
        const user = userEvent.setup();
        renderAddColumnForm();

        await user.click(screen.getByText('+ Add Column'));
        expect(screen.getByPlaceholderText('Column title...')).toBeInTheDocument();

        await user.click(screen.getByText('Cancel'));

        expect(screen.getByText('+ Add Column')).toBeInTheDocument();
        expect(screen.queryByPlaceholderText('Column title...')).not.toBeInTheDocument();
    });

    test('empty title does not submit', async () => {
        const user = userEvent.setup();
        renderAddColumnForm();

        await user.click(screen.getByText('+ Add Column'));
        await user.click(screen.getByText('Add'));

        expect(mockDispatch).not.toHaveBeenCalled();
        expect(columnsApi.createColumn).not.toHaveBeenCalled();

        // Form should remain open
        expect(screen.getByPlaceholderText('Column title...')).toBeInTheDocument();
    });

    test('rolls back on API failure', async () => {
        const user = userEvent.setup();
        (columnsApi.createColumn as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderAddColumnForm();

        await user.click(screen.getByText('+ Add Column'));

        const input = screen.getByPlaceholderText('Column title...');
        await user.type(input, 'Failing Column');
        await user.click(screen.getByText('Add'));

        expect(mockDispatch).toHaveBeenCalledWith(
            expect.objectContaining({ type: 'ADD_COLUMN' }),
        );

        await waitFor(() => {
            expect(mockDispatch).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'ROLLBACK' }),
            );
        });
    });
});
