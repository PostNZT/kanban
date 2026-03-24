import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Board, BoardSummary } from '../../types';
import { useToast } from '../../context/ToastContext';
import * as boardsApi from '../../api/boards';

export default function BoardList() {
    const [boards, setBoards] = useState<BoardSummary[]>([]);
    const [newTitle, setNewTitle] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(true);
    const navigate = useNavigate();
    const { showToast } = useToast();

    useEffect(() => {
        boardsApi.getBoards().then((data) => {
            setBoards(data);
            setLoading(false);
        });
    }, []);

    const handleCreate = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!newTitle.trim()) return;

        const title = newTitle.trim();
        const uuid = crypto.randomUUID();
        const now = new Date().toISOString();

        // Build optimistic board to pass via route state
        const optimisticBoard: Board = {
            id: uuid,
            title,
            columns: [
                { id: -1, title: 'To Do', position: 0, cards: [] },
                { id: -2, title: 'In Progress', position: 1, cards: [] },
                { id: -3, title: 'Done', position: 2, cards: [] },
            ],
            createdAt: now,
        };

        // Navigate immediately — no waiting for API
        setNewTitle('');
        showToast('Board created!', 'success');
        navigate(`/boards/${uuid}`, { state: { board: optimisticBoard } });

        // Fire-and-forget: persist in background
        boardsApi.createBoard(title, uuid).catch(() => {
            showToast('Board may not have saved. Try refreshing.', 'error');
        });
    };

    const handleDelete = (id: string) => {
        // Optimistic remove — no waiting for API
        setBoards((prev) => prev.filter((b) => b.id !== id));
        showToast('Board deleted.', 'success');

        boardsApi.deleteBoard(id).catch(() => {
            showToast('Failed to delete board. Refreshing list.', 'error');
            boardsApi.getBoards().then(setBoards);
        });
    };

    if (loading) return <div className="loading">Loading boards...</div>;

    return (
        <div className="board-list">
            <h2>My Boards</h2>
            <form onSubmit={handleCreate} className="create-board-form">
                <input
                    type="text"
                    placeholder="New board title..."
                    value={newTitle}
                    onChange={(e) => setNewTitle(e.target.value)}
                />
                <button type="submit" className="btn btn-primary">
                    Create Board
                </button>
            </form>
            <div className="boards-grid">
                {boards.map((board) => (
                    <div key={board.id} className="board-card">
                        <h3 onClick={() => navigate(`/boards/${board.id}`)}>
                            {board.title}
                        </h3>
                        <button
                            onClick={() => handleDelete(board.id)}
                            className="btn btn-danger btn-sm"
                        >
                            Delete
                        </button>
                    </div>
                ))}
                {boards.length === 0 && (
                    <p className="empty-state">
                        No boards yet. Create your first board above!
                    </p>
                )}
            </div>
        </div>
    );
}
