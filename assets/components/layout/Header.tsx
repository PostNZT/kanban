import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { useNavigate } from 'react-router-dom';

export default function Header() {
    const { user, logout, isAuthenticated } = useAuth();
    const navigate = useNavigate();

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    if (!isAuthenticated) return null;

    return (
        <header className="header">
            <h1 onClick={() => navigate('/')}>Kanban Board</h1>
            <div className="header-right">
                <span>{user?.email}</span>
                <button onClick={handleLogout} className="btn btn-secondary">
                    Logout
                </button>
            </div>
        </header>
    );
}
