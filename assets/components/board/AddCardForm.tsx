import React, { useState } from 'react';
import { useBoard } from '../../context/BoardContext';
import { snapshotBoard } from '../../reducers/boardReducer';
import * as cardsApi from '../../api/cards';

interface AddCardFormProps {
    columnId: number;
}

export default function AddCardForm({ columnId }: AddCardFormProps) {
    const [isOpen, setIsOpen] = useState<boolean>(false);
    const [title, setTitle] = useState<string>('');
    const { state: board, dispatch } = useBoard();

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!title.trim()) return;

        const trimmed = title.trim();
        const tempId = -Date.now();
        const tempCard = { id: tempId, title: trimmed, description: null, position: 999, createdAt: '' };

        const previousState = snapshotBoard(board);
        dispatch({ type: 'ADD_CARD', payload: { columnId, card: tempCard } });
        setTitle('');
        setIsOpen(false);

        cardsApi.createCard(columnId, trimmed)
            .then((realCard) => {
                dispatch({ type: 'REPLACE_CARD_ID', payload: { tempId, realCard } });
            })
            .catch(() => {
                dispatch({ type: 'ROLLBACK', payload: previousState });
            });
    };

    if (!isOpen) {
        return (
            <button
                className="btn btn-add-card"
                onClick={() => setIsOpen(true)}
            >
                + Add Card
            </button>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="add-card-form">
            <input
                type="text"
                placeholder="Enter card title..."
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                autoFocus
            />
            <div className="add-card-actions">
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
    );
}
