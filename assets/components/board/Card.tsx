import React, { useState } from 'react';
import { Draggable } from '@hello-pangea/dnd';
import { Card } from '../../types';
import { useBoard } from '../../context/BoardContext';
import { snapshotBoard } from '../../reducers/boardReducer';
import * as cardsApi from '../../api/cards';

interface CardItemProps {
    card: Card;
    index: number;
    columnId: number;
}

export default function CardItem({ card, index, columnId }: CardItemProps) {
    const { state: board, dispatch } = useBoard();
    const [isEditing, setIsEditing] = useState<boolean>(false);
    const [title, setTitle] = useState<string>(card.title);

    const handleSave = () => {
        if (!title.trim()) return;
        const trimmed = title.trim();
        const previousState = snapshotBoard(board);

        dispatch({
            type: 'UPDATE_CARD',
            payload: { id: card.id, title: trimmed, description: card.description },
        });
        setIsEditing(false);

        cardsApi.updateCard(card.id, { title: trimmed }).catch(() => {
            dispatch({ type: 'ROLLBACK', payload: previousState });
        });
    };

    const handleDelete = () => {
        const previousState = snapshotBoard(board);
        dispatch({ type: 'DELETE_CARD', payload: { columnId, cardId: card.id } });

        if (card.id > 0) {
            cardsApi.deleteCard(card.id).catch(() => {
                dispatch({ type: 'ROLLBACK', payload: previousState });
            });
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') handleSave();
        if (e.key === 'Escape') {
            setTitle(card.title);
            setIsEditing(false);
        }
    };

    return (
        <Draggable draggableId={String(card.id)} index={index}>
            {(provided, snapshot) => (
                <div
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    {...provided.dragHandleProps}
                    className={`card ${snapshot.isDragging ? 'dragging' : ''}`}
                >
                    {isEditing ? (
                        <input
                            className="card-edit-input"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            onBlur={handleSave}
                            onKeyDown={handleKeyDown}
                            autoFocus
                        />
                    ) : (
                        <div className="card-content">
                            <span
                                className="card-title"
                                onDoubleClick={() => setIsEditing(true)}
                            >
                                {card.title}
                            </span>
                            <button
                                onClick={handleDelete}
                                className="card-delete"
                                aria-label="Delete card"
                            >
                                x
                            </button>
                        </div>
                    )}
                    {card.description && (
                        <p className="card-description">{card.description}</p>
                    )}
                </div>
            )}
        </Draggable>
    );
}
