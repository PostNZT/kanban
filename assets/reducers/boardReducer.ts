import { Board, BoardColumn, Card } from '../types';

export function snapshotBoard(board: Board): Board {
    return { ...board, columns: board.columns.map(column => ({ ...column, cards: [...column.cards] })) };
}

export type BoardAction =
    | { type: 'SET_BOARD'; payload: Board }
    | { type: 'ADD_COLUMN'; payload: BoardColumn }
    | { type: 'UPDATE_COLUMN'; payload: { id: number; title: string } }
    | { type: 'DELETE_COLUMN'; payload: number }
    | { type: 'ADD_CARD'; payload: { columnId: number; card: Card } }
    | { type: 'UPDATE_CARD'; payload: { id: number; title: string; description: string | null } }
    | { type: 'DELETE_CARD'; payload: { columnId: number; cardId: number } }
    | { type: 'REPLACE_CARD_ID'; payload: { tempId: number; realCard: Card } }
    | { type: 'REPLACE_COLUMN_ID'; payload: { tempId: number; realColumn: BoardColumn } }
    | {
        type: 'MOVE_CARD';
        payload: {
            source: { droppableId: string; index: number };
            destination: { droppableId: string; index: number };
        };
    }
    | { type: 'ROLLBACK'; payload: Board };

export function boardReducer(state: Board, action: BoardAction): Board {
    switch (action.type) {
        case 'SET_BOARD':
            return action.payload;

        case 'ADD_COLUMN':
            return {
                ...state,
                columns: [...state.columns, action.payload],
            };

        case 'UPDATE_COLUMN':
            return {
                ...state,
                columns: state.columns.map((col) =>
                    col.id === action.payload.id
                        ? { ...col, title: action.payload.title }
                        : col
                ),
            };

        case 'DELETE_COLUMN':
            return {
                ...state,
                columns: state.columns.filter((col) => col.id !== action.payload),
            };

        case 'ADD_CARD': {
            return {
                ...state,
                columns: state.columns.map((col) =>
                    col.id === action.payload.columnId
                        ? { ...col, cards: [...col.cards, action.payload.card] }
                        : col
                ),
            };
        }

        case 'UPDATE_CARD':
            return {
                ...state,
                columns: state.columns.map((col) => ({
                    ...col,
                    cards: col.cards.map((card) =>
                        card.id === action.payload.id
                            ? {
                                ...card,
                                title: action.payload.title,
                                description: action.payload.description,
                            }
                            : card
                    ),
                })),
            };

        case 'DELETE_CARD':
            return {
                ...state,
                columns: state.columns.map((col) =>
                    col.id === action.payload.columnId
                        ? {
                            ...col,
                            cards: col.cards.filter(
                                (card) => card.id !== action.payload.cardId
                            ),
                        }
                        : col
                ),
            };

        case 'MOVE_CARD': {
            const { source, destination } = action.payload;
            const sourceColId = parseInt(source.droppableId);
            const destColId = parseInt(destination.droppableId);

            const newColumns = state.columns.map((col) => ({
                ...col,
                cards: [...col.cards],
            }));

            const sourceCol = newColumns.find((c) => c.id === sourceColId)!;
            const [movedCard] = sourceCol.cards.splice(source.index, 1);

            if (sourceColId === destColId) {
                sourceCol.cards.splice(destination.index, 0, movedCard);
            } else {
                const destCol = newColumns.find((c) => c.id === destColId)!;
                destCol.cards.splice(destination.index, 0, movedCard);
            }

            return { ...state, columns: newColumns };
        }

        case 'REPLACE_CARD_ID': {
            const { tempId, realCard } = action.payload;
            return {
                ...state,
                columns: state.columns.map((col) => ({
                    ...col,
                    cards: col.cards.map((card) =>
                        card.id === tempId ? { ...card, id: realCard.id, createdAt: realCard.createdAt } : card
                    ),
                })),
            };
        }

        case 'REPLACE_COLUMN_ID': {
            const { tempId, realColumn } = action.payload;
            return {
                ...state,
                columns: state.columns.map((col) =>
                    col.id === tempId
                        ? { ...col, id: realColumn.id, position: realColumn.position }
                        : col
                ),
            };
        }

        case 'ROLLBACK':
            return action.payload;

        default:
            return state;
    }
}
