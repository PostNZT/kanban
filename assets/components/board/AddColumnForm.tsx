import React, { useState } from 'react';
import { useBoard } from '../../context/BoardContext';
import { useToast } from '../../context/ToastContext';
import { snapshotBoard } from '../../reducers/boardReducer';
import * as columnsApi from '../../api/columns';

interface AddColumnFormProps {
    boardId: string;
}

export default function AddColumnForm({ boardId }: AddColumnFormProps) {
    const [isOpen, setIsOpen] = useState<boolean>(false);
    const [title, setTitle] = useState<string>('');
    const { state: board, dispatch } = useBoard();
    const { showToast } = useToast();

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!title.trim()) return;

        const trimmed = title.trim();
        const tempId = -Date.now();
        const tempColumn = { id: tempId, title: trimmed, position: board.columns.length, cards: [] };

        const previousState = snapshotBoard(board);
        dispatch({ type: 'ADD_COLUMN', payload: tempColumn });
        setTitle('');
        setIsOpen(false);

        columnsApi.createColumn(boardId, trimmed)
            .then((realColumn) => {
                dispatch({ type: 'REPLACE_COLUMN_ID', payload: { tempId, realColumn: { ...realColumn, cards: [] } } });
            })
            .catch(() => {
                dispatch({ type: 'ROLLBACK', payload: previousState });
                showToast('Failed to add column.', 'error');
            });
    };

    if (!isOpen) {
        return (
            <div className="add-column">
                <button
                    className="btn btn-add-column"
                    onClick={() => setIsOpen(true)}
                >
                    + Add Column
                </button>
            </div>
        );
    }

    return (
        <div className="add-column">
            <form onSubmit={handleSubmit} className="add-column-form">
                <input
                    type="text"
                    placeholder="Column title..."
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    autoFocus
                />
                <div className="add-column-actions">
                    <button type="submit" className="btn btn-primary btn-sm">
                        Add
                    </button>
                    <button
                        type="button"
                        className="btn btn-secondary btn-sm"
                        onClick={() => {
                            setIsOpen(false);
                            setTitle('');
                        }}
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}
