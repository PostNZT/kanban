import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '../context/AuthContext';
import { BoardProvider } from '../context/BoardContext';
import Header from './layout/Header';
import ProtectedRoute from './layout/ProtectedRoute';
import LoginForm from './auth/LoginForm';
import RegisterForm from './auth/RegisterForm';
import BoardList from './board/BoardList';
import BoardView from './board/BoardView';

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Header />
                <main className="main-content">
                    <Routes>
                        <Route path="/login" element={<LoginForm />} />
                        <Route path="/register" element={<RegisterForm />} />
                        <Route
                            path="/"
                            element={
                                <ProtectedRoute>
                                    <BoardList />
                                </ProtectedRoute>
                            }
                        />
                        <Route
                            path="/boards/:id"
                            element={
                                <ProtectedRoute>
                                    <BoardProvider>
                                        <BoardView />
                                    </BoardProvider>
                                </ProtectedRoute>
                            }
                        />
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Routes>
                </main>
            </BrowserRouter>
        </AuthProvider>
    );
}
