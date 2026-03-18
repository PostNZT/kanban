import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '../context/AuthContext';
import { BoardProvider } from '../context/BoardContext';
import { ToastProvider } from '../context/ToastContext';
import Header from './layout/Header';
import Footer from './layout/Footer';
import ProtectedRoute from './layout/ProtectedRoute';
import ToastContainer from './ui/ToastContainer';
import LoginForm from './auth/LoginForm';
import RegisterForm from './auth/RegisterForm';
import BoardList from './board/BoardList';
import BoardView from './board/BoardView';
import About from './pages/About';

export default function App() {
    return (
        <ToastProvider>
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
                            <Route path="/about" element={<About />} />
                            <Route path="*" element={<Navigate to="/" replace />} />
                        </Routes>
                    </main>
                    <Footer />
                    <ToastContainer />
                </BrowserRouter>
            </AuthProvider>
        </ToastProvider>
    );
}
