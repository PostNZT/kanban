import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { BoardSummary } from '../../types';
import * as boardsApi from '../../api/boards';

export default function BoardList() {
    const [boards, setBoards] = useState<BoardSummary[]>([]);
    const [newTitle, setNewTitle] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(true);
    const navigate = useNavigate();

    useEffect(() => {
        boardsApi.getBoards().then((data) => {
            setBoards(data);
            setLoading(false);
        });
    }, []);

    const handleCreate = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!newTitle.trim()) return;

        const board = await boardsApi.createBoard(newTitle.trim());
        navigate(`/boards/${board.id}`);
    };

    const handleDelete = async (id: string) => {
        await boardsApi.deleteBoard(id);
        setBoards(boards.filter((b) => b.id !== id));
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
