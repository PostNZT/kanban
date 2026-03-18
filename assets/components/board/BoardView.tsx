import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { DragDropContext, Droppable, DropResult } from '@hello-pangea/dnd';
import { useBoard } from '../../context/BoardContext';
import { snapshotBoard } from '../../reducers/boardReducer';
import * as boardsApi from '../../api/boards';
import * as cardsApi from '../../api/cards';
import Column from './Column';
import AddColumnForm from './AddColumnForm';

export default function BoardView() {
    const { id } = useParams<{ id: string }>();
    const { state, dispatch } = useBoard();
    const [loading, setLoading] = useState<boolean>(true);
    const navigate = useNavigate();

    useEffect(() => {
        if (!id) return;
        boardsApi.getBoard(id).then((board) => {
            dispatch({ type: 'SET_BOARD', payload: board });
            setLoading(false);
        }).catch(() => {
            navigate('/');
        });
    }, [id, dispatch, navigate]);

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
