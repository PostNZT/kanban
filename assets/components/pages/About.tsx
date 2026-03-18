import React from 'react';

export default function About() {
    return (
        <div className="about-page">
            <h1>About This Project</h1>
            <p className="about-intro">
                A full-stack kanban board application demonstrating modern web development practices
                with Symfony 8 and React 19. Built as a portfolio project to showcase full-stack
                engineering skills across backend API design, frontend SPA development, authentication,
                and deployment infrastructure.
            </p>

            <section className="about-section">
                <h2>Architecture Overview</h2>
                <p>
                    The application follows a decoupled architecture with a RESTful API backend
                    and a single-page application (SPA) frontend. The backend serves both the API
                    endpoints and the initial HTML shell that bootstraps the React application.
                </p>
                <ul>
                    <li><strong>Backend:</strong> Symfony 8.0 serving as the API layer with Doctrine ORM for data persistence</li>
                    <li><strong>Frontend:</strong> React 19 SPA with TypeScript, bundled via Webpack Encore</li>
                    <li><strong>Database:</strong> PostgreSQL hosted on Neon (serverless Postgres)</li>
                    <li><strong>Deployment:</strong> Docker container on Railway with Cloudflare DNS</li>
                </ul>
            </section>

            <section className="about-section">
                <h2>Backend — Symfony 8 / PHP 8.4</h2>
                <ul>
                    <li><strong>RESTful API controllers</strong> for boards, columns, cards, and authentication</li>
                    <li><strong>Doctrine ORM 3.6</strong> with entity relationships (User → Board → Column → Card)</li>
                    <li><strong>JWT authentication</strong> using firebase/php-jwt with cookie-based token transport</li>
                    <li><strong>Custom security voter</strong> (BoardVoter) for resource-level authorization</li>
                    <li><strong>Rate limiting</strong> on authentication endpoints to prevent brute-force attacks</li>
                    <li><strong>Security headers</strong> via event subscribers (CSP, HSTS, X-Frame-Options)</li>
                    <li><strong>Database migrations</strong> managed through Doctrine Migrations</li>
                    <li><strong>CORS configuration</strong> via NelmioCorsBundle for cross-origin requests</li>
                </ul>
            </section>

            <section className="about-section">
                <h2>Frontend — React 19 / TypeScript</h2>
                <ul>
                    <li><strong>React 19</strong> with functional components and hooks</li>
                    <li><strong>TypeScript</strong> for type safety across the entire frontend</li>
                    <li><strong>React Router 7</strong> for client-side routing with protected routes</li>
                    <li><strong>Context API</strong> for global state management (AuthContext, BoardContext, ToastContext)</li>
                    <li><strong>Drag and drop</strong> using @hello-pangea/dnd for card/column reordering</li>
                    <li><strong>Axios</strong> for HTTP client with interceptors for auth token handling</li>
                    <li><strong>Webpack Encore</strong> for asset bundling, code splitting, and hot reload</li>
                </ul>
            </section>

            <section className="about-section">
                <h2>Testing</h2>
                <ul>
                    <li><strong>PHPUnit 12.5</strong> for backend unit and functional tests</li>
                    <li><strong>Jest 30</strong> with React Testing Library for frontend component tests</li>
                    <li><strong>Code coverage</strong> reports generated via PHPUnit HTML reporter</li>
                    <li><strong>Test isolation</strong> using SQLite file-based database for fast test execution</li>
                </ul>
            </section>

            <section className="about-section">
                <h2>DevOps &amp; Infrastructure</h2>
                <ul>
                    <li><strong>Multi-stage Docker build</strong> — Node for frontend assets, PHP-Apache for production</li>
                    <li><strong>Railway</strong> for container hosting with automatic deployments from GitHub</li>
                    <li><strong>Cloudflare</strong> for DNS management, SSL termination, and CDN</li>
                    <li><strong>Neon</strong> serverless PostgreSQL for managed database hosting</li>
                    <li><strong>Environment-based configuration</strong> — no secrets in code, all injected at runtime</li>
                </ul>
            </section>

            <section className="about-section">
                <h2>Security Practices</h2>
                <ul>
                    <li>JWT tokens stored in HTTP-only cookies (not localStorage)</li>
                    <li>CSRF protection through SameSite cookie attribute</li>
                    <li>Rate limiting on login/register endpoints</li>
                    <li>Security audit logging via event subscribers</li>
                    <li>Input validation using Symfony Validator component</li>
                    <li>Resource-level authorization with custom Voters</li>
                </ul>
            </section>
        </div>
    );
}
