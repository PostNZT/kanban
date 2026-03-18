import React from 'react';
import { useToast } from '../../context/ToastContext';

export default function ToastContainer() {
    const { toasts, removeToast } = useToast();

    if (toasts.length === 0) return null;

    return (
        <div className="toast-container">
            {toasts.map((toast) => (
                <div key={toast.id} className={`toast toast-${toast.type}`}>
                    <span className="toast-message">{toast.message}</span>
                    <button
                        className="toast-close"
                        onClick={() => removeToast(toast.id)}
                        aria-label="Dismiss"
                    >
                        &times;
                    </button>
                </div>
            ))}
        </div>
    );
}
