# My Project — Kanban Board

A full-stack kanban board application built with Symfony 8 and React 19. Users can register, log in, create boards, and manage tasks with drag-and-drop columns and cards.

## Tech Stack

- **Backend:** Symfony 8.0, PHP 8.4+, Doctrine ORM
- **Frontend:** React 19, TypeScript, Webpack Encore
- **Database:** PostgreSQL (local Docker or Neon cloud)
- **Auth:** JWT (firebase/php-jwt), cookie-based token extraction
- **Testing:** PHPUnit 12.5 (backend), Jest 30 (frontend)

## Prerequisites

- PHP 8.4+
- Composer
- Node.js & npm
- PostgreSQL (or Docker)

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd my_project
```

### 2. Configure environment variables

```bash
cp .env.example .env
```

Edit `.env` and set your values:
- `APP_SECRET` — a random string for Symfony's internal security
- `DATABASE_URL` — your PostgreSQL connection string
- `JWT_SECRET` — a random string (min 32 characters) for signing JWT tokens

### 3. Install dependencies

```bash
composer install
npm install
```

### 4. Set up the database

Using Docker:
```bash
docker compose up -d
```

Run migrations:
```bash
php bin/console doctrine:migrations:migrate
```

Optionally load fixtures:
```bash
php bin/console doctrine:fixtures:load
```

### 5. Build frontend assets

```bash
npm run dev
```

Or start the dev server with hot reload:
```bash
npm run dev-server
```

### 6. Start the Symfony server

```bash
symfony server:start
```

The app will be available at `http://localhost:8000`.

## Running Tests

### Backend (PHPUnit)

```bash
php bin/phpunit
```

### Frontend (Jest)

```bash
npx jest
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | No | Register a new user |
| POST | `/api/login` | No | Log in and receive JWT |
| POST | `/api/logout` | No | Log out |
| GET | `/api/boards` | Yes | List user's boards |
| POST | `/api/boards` | Yes | Create a board |
| GET | `/api/boards/{id}` | Yes | Get board details |
| — | `/api/doc` | No | API documentation |

All `/api/*` endpoints (except those listed as public) require a valid JWT token.

## Project Structure

```
src/
├── Controller/       # API controllers (Auth, Board, Column, Card, Spa)
├── Entity/           # Doctrine entities (User, Board, BoardColumn, Card)
├── Repository/       # Doctrine repositories
├── Security/         # JWT handler, cookie extractor, board voter
├── Service/          # Business logic services
├── EventSubscriber/  # Security headers & audit logging
└── DataFixtures/     # Test/dev data fixtures

assets/               # React/TypeScript frontend source
tests/                # PHPUnit test suites
migrations/           # Doctrine database migrations
config/               # Symfony configuration
```

## License

Proprietary
