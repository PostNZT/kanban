import React, { createContext, useContext, useState, useCallback, useRef, useEffect } from 'react';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

interface Toast {
    id: number;
    message: string;
    type: ToastType;
}

interface ToastContextValue {
    toasts: Toast[];
    showToast: (message: string, type?: ToastType) => void;
    removeToast: (id: number) => void;
}

const ToastContext = createContext<ToastContextValue | null>(null);

export function useToast(): ToastContextValue {
    const context = useContext(ToastContext);
    if (!context) throw new Error('useToast must be used within ToastProvider');
    return context;
}

export function ToastProvider({ children }: { children: React.ReactNode }) {
    const [toasts, setToasts] = useState<Toast[]>([]);
    const nextId = useRef(0);
    const timers = useRef<Map<number, ReturnType<typeof setTimeout>>>(new Map());

    const removeToast = useCallback((id: number) => {
        const timer = timers.current.get(id);
        if (timer) {
            clearTimeout(timer);
            timers.current.delete(id);
        }
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    const showToast = useCallback((message: string, type: ToastType = 'info') => {
        const id = nextId.current++;
        setToasts((prev) => [...prev, { id, message, type }]);
        const timer = setTimeout(() => {
            timers.current.delete(id);
            setToasts((prev) => prev.filter((t) => t.id !== id));
        }, 4000);
        timers.current.set(id, timer);
    }, []);

    useEffect(() => {
        return () => {
            timers.current.forEach((timer) => clearTimeout(timer));
            timers.current.clear();
        };
    }, []);

    return (
        <ToastContext.Provider value={{ toasts, showToast, removeToast }}>
            {children}
        </ToastContext.Provider>
    );
}
