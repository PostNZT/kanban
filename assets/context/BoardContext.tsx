import React, { createContext, useContext, useReducer, ReactNode } from 'react';
import { Board } from '../types';
import { boardReducer, BoardAction } from '../reducers/boardReducer';

interface BoardContextType {
    state: Board;
    dispatch: React.Dispatch<BoardAction>;
}

const BoardContext = createContext<BoardContextType | undefined>(undefined);

const emptyBoard: Board = {
    id: '',
    title: '',
    columns: [],
    createdAt: '',
};

export function BoardProvider({ children }: { children: ReactNode }) {
    const [state, dispatch] = useReducer(boardReducer, emptyBoard);

    return (
        <BoardContext.Provider value={{ state, dispatch }}>
            {children}
        </BoardContext.Provider>
    );
}

export function useBoard(): BoardContextType {
    const context = useContext(BoardContext);
    if (!context) {
        throw new Error('useBoard must be used within a BoardProvider');
    }
    return context;
}
