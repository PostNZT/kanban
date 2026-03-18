import React, { useState, useRef, useEffect } from 'react';
import { Droppable } from '@hello-pangea/dnd';
import { BoardColumn } from '../../types';
import { useBoard } from '../../context/BoardContext';
import { useToast } from '../../context/ToastContext';
import { snapshotBoard } from '../../reducers/boardReducer';
import * as columnsApi from '../../api/columns';
import CardItem from './Card';
import AddCardForm from './AddCardForm';

interface ColumnProps {
    column: BoardColumn;
}

export default function Column({ column }: ColumnProps) {
    const { state: board, dispatch } = useBoard();
    const { showToast } = useToast();
    const [isEditing, setIsEditing] = useState<boolean>(false);
    const [title, setTitle] = useState<string>(column.title);
    const [confirmingDelete, setConfirmingDelete] = useState<boolean>(false);
    const confirmTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (confirmTimer.current) clearTimeout(confirmTimer.current);
        };
    }, []);

    const handleSave = () => {
        if (!title.trim()) return;
        const trimmed = title.trim();
        if (trimmed === column.title) {
            setIsEditing(false);
            return;
        }

        const previousState = snapshotBoard(board);
        dispatch({ type: 'UPDATE_COLUMN', payload: { id: column.id, title: trimmed } });
        setIsEditing(false);

        columnsApi.updateColumn(column.id, trimmed).catch(() => {
            dispatch({ type: 'ROLLBACK', payload: previousState });
            setTitle(column.title);
            showToast('Failed to update column.', 'error');
        });
    };

    const handleDelete = () => {
        if (!confirmingDelete) {
            setConfirmingDelete(true);
            confirmTimer.current = setTimeout(() => {
                setConfirmingDelete(false);
                confirmTimer.current = null;
            }, 3000);
            return;
        }

        if (confirmTimer.current) {
            clearTimeout(confirmTimer.current);
            confirmTimer.current = null;
        }
        setConfirmingDelete(false);
        const previousState = snapshotBoard(board);
        dispatch({ type: 'DELETE_COLUMN', payload: column.id });

        if (column.id > 0) {
            columnsApi.deleteColumn(column.id).catch(() => {
                dispatch({ type: 'ROLLBACK', payload: previousState });
                showToast('Failed to delete column.', 'error');
            });
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') handleSave();
        if (e.key === 'Escape') {
            setTitle(column.title);
            setIsEditing(false);
        }
    };

    return (
        <div className="column">
            <div className="column-header">
                {isEditing ? (
                    <input
                        className="column-edit-input"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                        onBlur={handleSave}
                        onKeyDown={handleKeyDown}
                        autoFocus
                    />
                ) : (
                    <h3 onDoubleClick={() => setIsEditing(true)}>{column.title}</h3>
                )}
                <div className="column-header-actions">
                    <span className="card-count">{column.cards.length}</span>
                    <button
                        onClick={handleDelete}
                        className={`column-delete ${confirmingDelete ? 'confirming' : ''}`}
                        aria-label="Delete column"
                        title={confirmingDelete ? 'Click again to confirm' : 'Delete column'}
                    >
                        {confirmingDelete ? '?' : '\u00d7'}
                    </button>
                </div>
            </div>
            <Droppable droppableId={String(column.id)}>
                {(provided, snapshot) => (
                    <div
                        ref={provided.innerRef}
                        {...provided.droppableProps}
                        className={`card-list ${snapshot.isDraggingOver ? 'dragging-over' : ''}`}
                    >
                        {column.cards.map((card, index) => (
                            <CardItem
                                key={card.id}
                                card={card}
                                index={index}
                                columnId={column.id}
                            />
                        ))}
                        {provided.placeholder}
                    </div>
                )}
            </Droppable>
            <AddCardForm columnId={column.id} />
        </div>
    );
}
