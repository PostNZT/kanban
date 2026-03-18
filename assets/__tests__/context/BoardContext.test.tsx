import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { BoardProvider, useBoard } from '../../context/BoardContext';

function TestConsumer() {
    const { state } = useBoard();
    return <span data-testid="board-title">{state.title || 'empty'}</span>;
}

describe('BoardContext', () => {
    test('useBoard throws when used outside BoardProvider', () => {
        const spy = jest.spyOn(console, 'error').mockImplementation(() => {});
        expect(() => render(<TestConsumer />)).toThrow(
            'useBoard must be used within a BoardProvider',
        );
        spy.mockRestore();
    });

    test('BoardProvider renders with empty initial state', () => {
        render(
            <BoardProvider>
                <TestConsumer />
            </BoardProvider>,
        );
        expect(screen.getByTestId('board-title')).toHaveTextContent('empty');
    });
});
