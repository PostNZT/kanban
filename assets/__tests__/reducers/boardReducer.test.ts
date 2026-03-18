import { boardReducer, BoardAction, snapshotBoard } from '../../reducers/boardReducer';
import { Board, Card } from '../../types';

const cardA: Card = { id: 1, title: 'Card A', description: null, position: 0, createdAt: '' };
const cardB: Card = { id: 2, title: 'Card B', description: null, position: 1, createdAt: '' };
const cardC: Card = { id: 3, title: 'Card C', description: null, position: 2, createdAt: '' };

const makeBoard = (): Board => ({
    id: 'test-uuid-1234',
    title: 'Test Board',
    columns: [
        { id: 10, title: 'To Do', position: 0, cards: [cardA, cardB, cardC] },
        { id: 20, title: 'In Progress', position: 1, cards: [] },
        { id: 30, title: 'Done', position: 2, cards: [] },
    ],
    createdAt: '',
});

describe('boardReducer', () => {
    test('SET_BOARD replaces entire state', () => {
        const newBoard = makeBoard();
        const result = boardReducer({ id: '', title: '', columns: [], createdAt: '' }, {
            type: 'SET_BOARD',
            payload: newBoard,
        });
        expect(result).toEqual(newBoard);
    });

    test('ADD_COLUMN appends a column', () => {
        const board = makeBoard();
        const result = boardReducer(board, {
            type: 'ADD_COLUMN',
            payload: { id: 40, title: 'Review', position: 3, cards: [] },
        });
        expect(result.columns).toHaveLength(4);
        expect(result.columns[3].title).toBe('Review');
    });

    test('UPDATE_COLUMN changes column title', () => {
        const board = makeBoard();
        const result = boardReducer(board, {
            type: 'UPDATE_COLUMN',
            payload: { id: 10, title: 'Backlog' },
        });
        expect(result.columns[0].title).toBe('Backlog');
    });

    test('DELETE_COLUMN removes a column', () => {
        const board = makeBoard();
        const result = boardReducer(board, { type: 'DELETE_COLUMN', payload: 20 });
        expect(result.columns).toHaveLength(2);
        expect(result.columns.find((c) => c.id === 20)).toBeUndefined();
    });

    test('ADD_CARD appends card to column', () => {
        const board = makeBoard();
        const newCard: Card = { id: 99, title: 'New', description: null, position: 3, createdAt: '' };
        const result = boardReducer(board, {
            type: 'ADD_CARD',
            payload: { columnId: 10, card: newCard },
        });
        expect(result.columns[0].cards).toHaveLength(4);
        expect(result.columns[0].cards[3].title).toBe('New');
    });

    test('UPDATE_CARD changes card properties', () => {
        const board = makeBoard();
        const result = boardReducer(board, {
            type: 'UPDATE_CARD',
            payload: { id: 1, title: 'Updated', description: 'desc' },
        });
        const card = result.columns[0].cards[0];
        expect(card.title).toBe('Updated');
        expect(card.description).toBe('desc');
    });

    test('DELETE_CARD removes card from column', () => {
        const board = makeBoard();
        const result = boardReducer(board, {
            type: 'DELETE_CARD',
            payload: { columnId: 10, cardId: 2 },
        });
        expect(result.columns[0].cards).toHaveLength(2);
        expect(result.columns[0].cards.find((c) => c.id === 2)).toBeUndefined();
    });

    test('MOVE_CARD within same column reorders cards', () => {
        const board = makeBoard();
        const result = boardReducer(board, {
            type: 'MOVE_CARD',
            payload: {
                source: { droppableId: '10', index: 0 },
                destination: { droppableId: '10', index: 2 },
            },
        });
        const cards = result.columns[0].cards;
        expect(cards[0].id).toBe(2); // cardB
        expect(cards[1].id).toBe(3); // cardC
        expect(cards[2].id).toBe(1); // cardA (moved)
    });

    test('MOVE_CARD between columns transfers card', () => {
        const board = makeBoard();
        const result = boardReducer(board, {
            type: 'MOVE_CARD',
            payload: {
                source: { droppableId: '10', index: 0 },
                destination: { droppableId: '20', index: 0 },
            },
        });
        expect(result.columns[0].cards).toHaveLength(2); // source lost 1
        expect(result.columns[1].cards).toHaveLength(1); // target gained 1
        expect(result.columns[1].cards[0].id).toBe(1); // cardA moved
    });

    test('REPLACE_CARD_ID swaps temp id with real id', () => {
        const board = makeBoard();
        const tempCard: Card = { id: -100, title: 'Temp', description: null, position: 3, createdAt: '' };
        const withTemp = boardReducer(board, { type: 'ADD_CARD', payload: { columnId: 10, card: tempCard } });
        const result = boardReducer(withTemp, {
            type: 'REPLACE_CARD_ID',
            payload: { tempId: -100, realCard: { id: 50, title: 'Temp', description: null, position: 3, createdAt: '2026-01-01' } },
        });
        const cards = result.columns[0].cards;
        expect(cards.find(c => c.id === -100)).toBeUndefined();
        expect(cards.find(c => c.id === 50)).toBeDefined();
        expect(cards.find(c => c.id === 50)!.createdAt).toBe('2026-01-01');
    });

    test('REPLACE_COLUMN_ID swaps temp id with real id', () => {
        const board = makeBoard();
        const tempCol = { id: -200, title: 'Review', position: 3, cards: [] };
        const withTemp = boardReducer(board, { type: 'ADD_COLUMN', payload: tempCol });
        const result = boardReducer(withTemp, {
            type: 'REPLACE_COLUMN_ID',
            payload: { tempId: -200, realColumn: { id: 40, title: 'Review', position: 3, cards: [] } },
        });
        expect(result.columns.find(c => c.id === -200)).toBeUndefined();
        expect(result.columns.find(c => c.id === 40)).toBeDefined();
    });

    test('ROLLBACK restores previous state', () => {
        const board = makeBoard();
        const modified = boardReducer(board, { type: 'DELETE_COLUMN', payload: 10 });
        const rolled = boardReducer(modified, { type: 'ROLLBACK', payload: board });
        expect(rolled.columns).toHaveLength(3);
    });

    test('unknown action returns state unchanged', () => {
        const board = makeBoard();
        const result = boardReducer(board, { type: 'UNKNOWN_ACTION' } as any);
        expect(result).toEqual(board);
    });

    test('snapshotBoard creates a deep copy', () => {
        const board = makeBoard();
        const snapshot = snapshotBoard(board);

        // Modify original
        board.columns[0].cards.push({
            id: 99,
            title: 'Extra',
            description: null,
            position: 99,
            createdAt: '',
        });

        // Snapshot should not be affected
        expect(snapshot.columns[0].cards).toHaveLength(3);
        expect(board.columns[0].cards).toHaveLength(4);
    });
});
