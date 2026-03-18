import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { Card } from '../../../types';

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

const mockDispatch = jest.fn();
const mockShowToast = jest.fn();
jest.mock('../../../context/BoardContext', () => ({
    useBoard: () => ({
        state: {
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
        },
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

jest.mock('../../../api/cards', () => ({
    createCard: jest.fn().mockResolvedValue({}),
    updateCard: jest.fn().mockResolvedValue({}),
    deleteCard: jest.fn().mockResolvedValue(undefined),
    moveCard: jest.fn().mockResolvedValue(undefined),
}));

jest.mock('../../../reducers/boardReducer', () => ({
    ...jest.requireActual('../../../reducers/boardReducer'),
    snapshotBoard: jest.fn().mockReturnValue({ id: 'snapshot' }),
}));

import CardItem from '../../../components/board/Card';
import * as cardsApi from '../../../api/cards';

const baseCard: Card = {
    id: 10,
    title: 'Task 1',
    description: 'A description',
    position: 0,
    createdAt: '',
};

function renderCard(card: Card = baseCard, columnId: number = 1, index: number = 0) {
    return render(<CardItem card={card} index={index} columnId={columnId} />);
}

describe('CardItem', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders card title', () => {
        renderCard();
        expect(screen.getByText('Task 1')).toBeInTheDocument();
    });

    test('renders delete button', () => {
        renderCard();
        expect(screen.getByRole('button', { name: /delete card/i })).toBeInTheDocument();
    });

    test('renders description when present', () => {
        renderCard();
        expect(screen.getByText('A description')).toBeInTheDocument();
    });

    test('does not render description when null', () => {
        const cardWithoutDesc: Card = { ...baseCard, description: null };
        const { container } = renderCard(cardWithoutDesc);
        expect(screen.queryByText('A description')).not.toBeInTheDocument();
        expect(container.querySelector('.card-description')).toBeNull();
    });

    test('double-clicking title enters edit mode', async () => {
        const user = userEvent.setup();
        renderCard();

        const titleElement = screen.getByText('Task 1');
        await user.dblClick(titleElement);

        expect(screen.getByDisplayValue('Task 1')).toBeInTheDocument();
    });

    test('pressing Enter in edit mode saves the card', async () => {
        const user = userEvent.setup();
        renderCard();

        await user.dblClick(screen.getByText('Task 1'));

        const input = screen.getByDisplayValue('Task 1');
        await user.clear(input);
        await user.type(input, 'Updated Title{Enter}');

        expect(mockDispatch).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'UPDATE_CARD',
                payload: expect.objectContaining({ title: 'Updated Title' }),
            }),
        );
        expect(cardsApi.updateCard).toHaveBeenCalledWith(10, { title: 'Updated Title' });
    });

    test('pressing Escape in edit mode cancels editing', async () => {
        const user = userEvent.setup();
        renderCard();

        await user.dblClick(screen.getByText('Task 1'));

        const input = screen.getByDisplayValue('Task 1');
        await user.clear(input);
        await user.type(input, 'Something else{Escape}');

        expect(screen.getByText('Task 1')).toBeInTheDocument();
        expect(mockDispatch).not.toHaveBeenCalledWith(
            expect.objectContaining({ type: 'UPDATE_CARD' }),
        );
    });

    test('delete card calls API and dispatches DELETE_CARD', async () => {
        const user = userEvent.setup();
        renderCard();

        await user.click(screen.getByRole('button', { name: /delete card/i }));

        expect(mockDispatch).toHaveBeenCalledWith({
            type: 'DELETE_CARD',
            payload: { columnId: 1, cardId: 10 },
        });
        expect(cardsApi.deleteCard).toHaveBeenCalledWith(10);
    });

    test('delete card skips API call for temp IDs (card.id < 0)', async () => {
        const user = userEvent.setup();
        const tempCard: Card = { ...baseCard, id: -12345 };
        renderCard(tempCard);

        await user.click(screen.getByRole('button', { name: /delete card/i }));

        expect(mockDispatch).toHaveBeenCalledWith({
            type: 'DELETE_CARD',
            payload: { columnId: 1, cardId: -12345 },
        });
        expect(cardsApi.deleteCard).not.toHaveBeenCalled();
    });

    test('blur in edit mode saves the card', async () => {
        const user = userEvent.setup();
        renderCard();

        await user.dblClick(screen.getByText('Task 1'));

        const input = screen.getByDisplayValue('Task 1');
        await user.clear(input);
        await user.type(input, 'Blur Saved');
        await user.tab();

        expect(mockDispatch).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'UPDATE_CARD',
                payload: expect.objectContaining({ title: 'Blur Saved' }),
            }),
        );
        expect(cardsApi.updateCard).toHaveBeenCalledWith(10, { title: 'Blur Saved' });
    });

    test('save with empty title does not dispatch', async () => {
        const user = userEvent.setup();
        renderCard();

        await user.dblClick(screen.getByText('Task 1'));

        const input = screen.getByDisplayValue('Task 1');
        await user.clear(input);
        await user.keyboard('{Enter}');

        expect(mockDispatch).not.toHaveBeenCalledWith(
            expect.objectContaining({ type: 'UPDATE_CARD' }),
        );
    });

    test('update card rolls back on API failure', async () => {
        const user = userEvent.setup();
        (cardsApi.updateCard as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderCard();

        await user.dblClick(screen.getByText('Task 1'));

        const input = screen.getByDisplayValue('Task 1');
        await user.clear(input);
        await user.type(input, 'Fail{Enter}');

        await waitFor(() => {
            expect(mockDispatch).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'ROLLBACK' }),
            );
        });
    });

    test('delete card rolls back on API failure', async () => {
        const user = userEvent.setup();
        (cardsApi.deleteCard as jest.Mock).mockRejectedValueOnce(new Error('API error'));

        renderCard();

        await user.click(screen.getByRole('button', { name: /delete card/i }));

        await waitFor(() => {
            expect(mockDispatch).toHaveBeenCalledWith(
                expect.objectContaining({ type: 'ROLLBACK' }),
            );
        });
    });
});
