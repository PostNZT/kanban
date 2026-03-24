import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { DragDropContext, Droppable, DropResult } from '@hello-pangea/dnd';
import { Board } from '../../types';
import { useBoard } from '../../context/BoardContext';
import { useToast } from '../../context/ToastContext';
import { snapshotBoard } from '../../reducers/boardReducer';
import * as boardsApi from '../../api/boards';
import * as cardsApi from '../../api/cards';
import Column from './Column';
import AddColumnForm from './AddColumnForm';

export default function BoardView() {
    const { id } = useParams<{ id: string }>();
    const location = useLocation();
    const { state, dispatch } = useBoard();
    const [loading, setLoading] = useState<boolean>(true);
    const navigate = useNavigate();
    const { showToast } = useToast();

    useEffect(() => {
        if (!id) return;

        // Use board from route state if available (instant render after create)
        const passedBoard = (location.state as { board?: Board } | null)?.board;
        if (passedBoard && passedBoard.id === id) {
            dispatch({ type: 'SET_BOARD', payload: passedBoard });
            setLoading(false);
            return;
        }

        boardsApi.getBoard(id).then((board) => {
            dispatch({ type: 'SET_BOARD', payload: board });
            setLoading(false);
        }).catch(() => {
            showToast('Board not found.', 'error');
            navigate('/');
        });
    }, [id, dispatch, navigate, showToast]);

    const onDragEnd = (result: DropResult) => {
        const { source, destination } = result;
        if (!destination) return;
        if (
            source.droppableId === destination.droppableId &&
            source.index === destination.index
        ) {
            return;
        }

        const previousState = snapshotBoard(state);

        dispatch({
            type: 'MOVE_CARD',
            payload: { source, destination },
        });

        const sourceCol = state.columns.find(
            (c) => c.id === parseInt(source.droppableId)
        );
        if (!sourceCol) return;
        const card = sourceCol.cards[source.index];

        cardsApi
            .moveCard(card.id, parseInt(destination.droppableId), destination.index)
            .catch(() => {
                dispatch({ type: 'ROLLBACK', payload: previousState });
                showToast('Failed to move card.', 'error');
            });
    };

    if (loading) return <div className="loading">Loading board...</div>;

    return (
        <div className="board-view">
            <div className="board-header">
                <h2>{state.title}</h2>
                <button onClick={() => navigate('/')} className="btn btn-secondary">
                    Back to Boards
                </button>
            </div>
            <DragDropContext onDragEnd={onDragEnd}>
                <div className="columns-container">
                    {state.columns.map((column) => (
                        <Column key={column.id} column={column} />
                    ))}
                    <AddColumnForm boardId={state.id} />
                </div>
            </DragDropContext>
        </div>
    );
}
