import React from 'react';
import { Link } from 'react-router-dom';

export default function Footer() {
    return (
        <footer className="footer">
            <div className="footer-content">
                <span className="footer-brand">&copy; {new Date().getFullYear()} jhunecarlotrogelio.com</span>
                <nav className="footer-links">
                    <Link to="/about">About</Link>
                    <a href="/api/doc" target="_blank" rel="noopener noreferrer">API Docs</a>
                </nav>
            </div>
        </footer>
    );
}
