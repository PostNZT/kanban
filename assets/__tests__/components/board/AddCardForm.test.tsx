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
jest.mock('../../../context/BoardContext', () => ({
    useBoard: () => ({
        state: mockBoard,
        dispatch: mockDispatch,
    }),
    BoardProvider: ({ children }: any) => <>{children}</>,
}));

jest.mock('../../../api/cards', () => ({
    createCard: jest.fn().mockResolvedValue({ id: 100, title: 'New Card', description: null, position: 0, createdAt: '2026-01-01' }),
    updateCard: jest.fn().mockResolvedValue({}),
    deleteCard: jest.fn().mockResolvedValue(undefined),
    moveCard: jest.fn().mockResolvedValue(undefined),
}));

jest.mock('../../../reducers/boardReducer', () => ({
    ...jest.requireActual('../../../reducers/boardReducer'),
    snapshotBoard: jest.fn().mockReturnValue({ id: 'snapshot' }),
}));

import AddCardForm from '../../../components/board/AddCardForm';
import * as cardsApi from '../../../api/cards';

function renderAddCardForm(columnId: number = 1) {
    return render(<AddCardForm columnId={columnId} />);
}

describe('AddCardForm', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders "+ Add Card" button', () => {
        renderAddCardForm();
        expect(screen.getByText('+ Add Card')).toBeInTheDocument();
    });

    test('clicking button opens the form', async () => {
        const user = userEvent.setup();
        renderAddCardForm();

        await user.click(screen.getByText('+ Add Card'));

        expect(screen.getByPlaceholderText('Enter card title...')).toBeInTheDocument();
        expect(screen.getByText('Add')).toBeInTheDocument();
        expect(screen.getByText('Cancel')).toBeInTheDocument();
    });

    test('submit creates card with dispatch and API call', async () => {
        const user = userEvent.setup();
        renderAddCardForm();

        await user.click(screen.getByText('+ Add Card'));

        const input = screen.getByPlaceholderText('Enter card title...');
        await user.type(input, 'New Card');
        await user.click(screen.getByText('Add'));

        expect(mockDispatch).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'ADD_CARD',
                payload: expect.objectContaining({
                    columnId: 1,
                    card: expect.objectContaining({
                        title: 'New Card',
                        description: null,
                    }),
                }),
            }),
        );

        expect(cardsApi.createCard).toHaveBeenCalledWith(1, 'New Card');

        // Form should close after submit
        expect(screen.getByText('+ Add Card')).toBeInTheDocument();
    });

    test('cancel closes the form', async () => {
        const user = userEvent.setup();
        renderAddCardForm();

        await user.click(screen.getByText('+ Add Card'));
        expect(screen.getByPlaceholderText('Enter card title...')).toBeInTheDocument();

        await user.click(screen.getByText('Cancel'));

        expect(screen.getByText('+ Add Card')).toBeInTheDocument();
        expect(screen.queryByPlaceholderText('Enter card title...')).not.toBeInTheDocument();
    });

    test('empty title does not submit', async () => {
        const user = userEvent.setup();
        renderAddCardForm();

        await user.click(screen.getByText('+ Add Card'));

        // Submit with empty input
        await user.click(screen.getByText('Add'));

        expect(mockDispatch).not.toHaveBeenCalled();
        expect(cardsApi.createCard).not.toHaveBeenCalled();

        // Form should remain open
        expect(screen.getByPlaceholderText('Enter card title...')).toBeInTheDocument();
    });

    test('rolls back on API failure', async () => {
        const user = userEvent.setup();
        (cardsApi.createCard as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderAddCardForm();

        await user.click(screen.getByText('+ Add Card'));

        const input = screen.getByPlaceholderText('Enter card title...');
        await user.type(input, 'Failing Card');
        await user.click(screen.getByText('Add'));

        expect(mockDispatch).toHaveBeenCalledWith(
            expect.objectContaining({ type: 'ADD_CARD' }),
        );

        await waitFor(() => {
            expect(mockDispatch).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'ROLLBACK' }),
            );
        });
    });
});
