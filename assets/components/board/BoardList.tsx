import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { BoardSummary } from '../../types';
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

    const [creating, setCreating] = useState<boolean>(false);

    const handleCreate = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!newTitle.trim() || creating) return;

        const title = newTitle.trim();
        const uuid = crypto.randomUUID();

        setCreating(true);
        boardsApi.createBoard(title, uuid).then((board) => {
            setNewTitle('');
            showToast('Board created!', 'success');
            navigate(`/boards/${uuid}`, { state: { board } });
        }).catch(() => {
            showToast('Failed to create board.', 'error');
        }).finally(() => {
            setCreating(false);
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
                <button type="submit" className="btn btn-primary" disabled={creating}>
                    {creating ? 'Creating...' : 'Create Board'}
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
