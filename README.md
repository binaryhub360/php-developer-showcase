# WorkBoard API – Project & Task Management REST Service

[![CI Pipeline](https://github.com/your-username/php-developer-showcase/actions/workflows/ci.yml/badge.svg)](https://github.com/your-username/php-developer-showcase/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg?logo=php)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-ff2d20.svg?logo=laravel)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A production-quality RESTful API for project and task management engineered with **PHP 8.2+**, **Laravel 12**, **MySQL 8**, and **Laravel Sanctum**. Built to demonstrate modern PHP fundamentals, clean Laravel MVC architecture, database optimization, robust security controls, automated test suites, and containerized Docker environments.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Key Features](#2-key-features)
3. [Technology Stack](#3-technology-stack)
4. [Architecture & Directory Structure](#4-architecture--directory-structure)
5. [Database Relationships & Schema](#5-database-relationships--schema)
6. [Installation Instructions](#6-installation-instructions)
7. [Docker Setup Instructions](#7-docker-setup-instructions)
8. [Environment Configuration](#8-environment-configuration)
9. [Database Migration & Seeding](#9-database-migration--seeding)
10. [API Endpoint Documentation](#10-api-endpoint-documentation)
11. [Authentication Examples](#11-authentication-examples)
12. [Example API Requests & Responses](#12-example-api-requests--responses)
13. [Testing Instructions](#13-testing-instructions)
14. [Security Considerations](#14-security-considerations)
15. [Design Decisions & Trade-offs](#15-design-decisions--trade-offs)
16. [Future Improvements](#16-future-improvements)

---

## 1. Project Overview

**WorkBoard API** powers collaborative team execution by organizing work into Projects and Tasks. The application enforces strong user isolation (preventing IDOR vulnerabilities), offers status transition management via domain services, provides filtering, searching, and sorting, and returns structured API responses with consistent error and pagination envelopes.

---

## 2. Key Features

- **Strict Type Safety & PSR-12**: Every file employs `declare(strict_types=1);` and conforms strictly to PSR-12.
- **Stateless Bearer Authentication**: Secure token generation, inspection, and explicit revocation powered by Laravel Sanctum.
- **Fine-Grained Authorization (Policies)**: `ProjectPolicy` and `TaskPolicy` verify user ownership on every resource interaction.
- **Dedicated Request Validation**: Form Requests (`RegisterRequest`, `StoreProjectRequest`, `StoreTaskRequest`, etc.) decouple validation from controllers.
- **Consistent Response Serialization**: API Resources (`UserResource`, `ProjectResource`, `TaskResource`) format outputs consistently.
- **Domain Service Pattern**: `TaskService` handles status transition state logic and automates `completed_at` timestamps inside database transactions.
- **Database Query Optimization**: Eager loading and `withCount` prevent N+1 queries. Composite indexes accelerate common filter patterns.
- **Soft Deletion**: Preserves historical data integrity and audit trails while allowing soft pruning.
- **Dockerized Environment**: Multi-container setup with PHP 8.3 FPM, Nginx, MySQL 8.0, and health checks.
- **Comprehensive Automated Tests**: Feature and Unit test suites verifying authentication, ownership authorization, state transitions, validation, and filters.

---

## 3. Technology Stack

- **Language**: PHP 8.2+ (PHP 8.3 in Docker container)
- **Framework**: Laravel 12
- **Database**: MySQL 8.0 (Local / Production), SQLite (in-memory for automated tests)
- **Web Server**: Nginx (Alpine) reverse-proxying PHP-FPM
- **Authentication**: Laravel Sanctum (Personal Access Tokens)
- **Testing**: PHPUnit 11 with Laravel test utilities
- **Containerization**: Docker Compose (v2)
- **CI/CD**: GitHub Actions

---

## 4. Architecture & Directory Structure

WorkBoard API follows a thin-controller, domain-service-enabled architecture:

```
php-developer-showcase/
├── app/
│   ├── Enums/                     # Native PHP 8.2 backed enums
│   │   ├── ProjectStatus.php      # active, completed, archived
│   │   ├── TaskPriority.php       # low, medium, high
│   │   └── TaskStatus.php         # pending, in_progress, completed
│   ├── Http/
│   │   ├── Controllers/Api/V1/    # Thin API controllers
│   │   │   ├── AuthController.php
│   │   │   ├── ProjectController.php
│   │   │   └── TaskController.php
│   │   ├── Requests/              # Form Request validation classes
│   │   │   ├── Auth/              # RegisterRequest, LoginRequest
│   │   │   ├── Project/           # StoreProjectRequest, UpdateProjectRequest
│   │   │   └── Task/              # StoreTaskRequest, UpdateTaskRequest, UpdateTaskStatusRequest
│   │   └── Resources/             # Eloquent API JSON resources
│   │       ├── ProjectResource.php
│   │       ├── TaskResource.php
│   │       └── UserResource.php
│   ├── Models/                    # Eloquent models with typed casts & relations
│   │   ├── Project.php
│   │   ├── Task.php
│   │   └── User.php
│   ├── Policies/                  # Resource authorization policies (IDOR defense)
│   │   ├── ProjectPolicy.php
│   │   └── TaskPolicy.php
│   ├── Services/                  # Domain business logic
│   │   └── TaskService.php        # State machine & transaction management
│   └── Traits/
│       └── ApiResponse.php        # Unified JSON response helper
├── config/                        # Laravel framework configurations
├── database/
│   ├── factories/                 # ProjectFactory, TaskFactory, UserFactory
│   ├── migrations/                # Schema definitions with indexes & FKs
│   └── seeders/                   # Realistic test and demo dataset
├── docker/
│   ├── nginx/default.conf         # Nginx server block configuration
│   └── php/
│       ├── Dockerfile             # PHP 8.3 FPM image build
│       └── local.ini              # Custom PHP settings
├── docs/
│   ├── API.md                     # Full REST endpoint reference & payload examples
│   └── ARCHITECTURE.md            # Architectural Decision Records (ADR)
├── routes/
│   ├── api.php                    # Versioned REST API routes (/api/v1/...)
│   └── web.php
├── tests/
│   ├── Feature/                   # AuthTest, ProjectTest, TaskTest
│   └── Unit/                      # TaskServiceTest, EnumTest
├── .env.example                   # Baseline environment configuration
├── compose.yaml                   # Docker Compose specification
└── phpunit.xml                    # Automated test configuration
```

---

## 5. Database Relationships & Schema

```
┌──────────────────┐          1:N          ┌───────────────────┐          1:N          ┌───────────────────┐
│      users       │ ────────────────────> │     projects      │ ────────────────────> │       tasks       │
├──────────────────┤                       ├───────────────────┤                       ├───────────────────┤
│ id (PK)          │                       │ id (PK)           │                       │ id (PK)           │
│ name             │                       │ user_id (FK)      │                       │ project_id (FK)   │
│ email (UQ)       │                       │ name              │                       │ title             │
│ password         │                       │ description       │                       │ description       │
│ timestamps       │                       │ status (enum)     │                       │ status (enum)     │
└──────────────────┘                       │ timestamps        │                       │ priority (enum)   │
                                           │ deleted_at        │                       │ due_date          │
                                           └───────────────────┘                       │ completed_at      │
                                                                                       │ timestamps        │
                                                                                       │ deleted_at        │
                                                                                       └───────────────────┘
```

### Referential Integrity Decision
- **Foreign Key Constraints**: Foreign keys (`projects.user_id` -> `users.id`, `tasks.project_id` -> `projects.id`) use `cascadeOnDelete()`. If an entity is purged at the database level, dependent rows are removed automatically.
- **Application Soft Deletes**: Deletions triggered via the API apply soft deletes (`deleted_at`), protecting against accidental loss and allowing audit retrieval.
- **Composite Indexes**:
  - `projects`: `(user_id, status)` for rapid filtering of a user's active/completed projects.
  - `tasks`: `(project_id, status)`, `(project_id, priority)`, `(project_id, due_date)` for fast filtered task lookups.

---

## 6. Installation Instructions

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running.
- Git.

### Setup Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/php-developer-showcase.git
   cd php-developer-showcase
   ```
2. Copy environment file:
   ```bash
   cp .env.example .env
   ```
3. Start containerized stack:
   ```bash
   docker compose up -d --build
   ```
4. Install dependencies & initialize application key:
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   ```
5. Run migrations and database seeding:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```
6. The API is now live at: `http://localhost:8000/api/v1`

---

## 7. Docker Setup Instructions

The project provides a self-contained multi-container environment via `compose.yaml`:

| Container | Image / Service | Host Port | Internal Port | Description |
|---|---|---|---|---|
| `workboard_web` | `nginx:alpine` | `8000` | `80` | High-performance reverse proxy |
| `workboard_app` | `php:8.3-fpm-alpine` | - | `9000` | Application server with opcache, pdo_mysql, gd |
| `workboard_db` | `mysql:8.0` | `33060` | `3306` | MySQL database with persistent volume |

### Essential Docker Commands
```bash
# Start all containers in the background
docker compose up -d

# View container logs
docker compose logs -f app

# Stop containers
docker compose down

# Destroy containers and database volume
docker compose down -v
```

---

## 8. Environment Configuration

Key configuration parameters inside `.env`:

```env
APP_NAME="WorkBoard API"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=workboard
DB_USERNAME=workboard_user
DB_PASSWORD=secret

APP_PORT=8000
DB_PORT_FORWARD=33060
```

---

## 9. Database Migration & Seeding

```bash
# Run fresh migrations and seed with demo dataset
docker compose exec app php artisan migrate:fresh --seed
```

### Pre-seeded Demo Credentials
- **Email**: `demo@workboard.test`
- **Password**: `Password123!`

The seeder populates projects across different statuses (`active`, `completed`) with tasks demonstrating varied priorities (`high`, `medium`, `low`) and auto-calculated completion timestamps. A second user (`sarah@workboard.test`) is also seeded to test cross-user isolation.

---

## 10. API Endpoint Documentation

| Verb | Path | Description | Auth Required |
|---|---|---|---|
| `POST` | `/api/v1/register` | Register a new user | No |
| `POST` | `/api/v1/login` | Authenticate and get Sanctum token | No |
| `POST` | `/api/v1/logout` | Revoke active access token | Yes |
| `GET` | `/api/v1/me` | Fetch authenticated user profile | Yes |
| `GET` | `/api/v1/projects` | List projects (filtering, search, paginated) | Yes |
| `POST` | `/api/v1/projects` | Create a new project | Yes |
| `GET` | `/api/v1/projects/{project}` | Retrieve a project by ID | Yes (Policy) |
| `PUT` | `/api/v1/projects/{project}` | Update project attributes | Yes (Policy) |
| `DELETE` | `/api/v1/projects/{project}` | Soft delete project | Yes (Policy) |
| `GET` | `/api/v1/projects/{project}/tasks` | List tasks in project (filters, sort, pagination) | Yes (Policy) |
| `POST` | `/api/v1/projects/{project}/tasks` | Create task inside project | Yes (Policy) |
| `GET` | `/api/v1/tasks/{task}` | Get task details | Yes (Policy) |
| `PUT` | `/api/v1/tasks/{task}` | Update task details | Yes (Policy) |
| `PATCH` | `/api/v1/tasks/{task}/status` | Change task status (auto completed_at) | Yes (Policy) |
| `DELETE` | `/api/v1/tasks/{task}` | Soft delete task | Yes (Policy) |

*For full request/response schemas, see [docs/API.md](docs/API.md).*

---

## 11. Authentication Examples

### 1. Register a New User
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Developer",
    "email": "jane@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "demo@workboard.test",
    "password": "Password123!"
  }'
```

---

## 12. Example API Requests & Responses

### List Projects (Status Filter & Search)
```bash
curl -X GET "http://localhost:8000/api/v1/projects?status=active&search=Portal" \
  -H "Authorization: Bearer <YOUR_TOKEN>" \
  -H "Accept: application/json"
```

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Projects retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Customer Portal Redesign",
      "description": "Overhaul the customer portal frontend with modern Tailwind UI.",
      "status": "active",
      "tasks_count": 3,
      "created_at": "2026-11-16T08:00:00Z",
      "updated_at": "2026-11-16T08:00:00Z",
      "deleted_at": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

### Transition Task Status
```bash
curl -X PATCH http://localhost:8000/api/v1/tasks/1/status \
  -H "Authorization: Bearer <YOUR_TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "completed"}'
```

**Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Task status updated successfully",
  "data": {
    "id": 1,
    "project_id": 1,
    "title": "Wireframe user authentication flow",
    "description": "Design high-fidelity Figma mockups.",
    "status": "completed",
    "priority": "high",
    "due_date": "2026-11-14",
    "completed_at": "2026-11-16T09:05:00Z",
    "created_at": "2026-11-10T08:00:00Z",
    "updated_at": "2026-11-16T09:05:00Z",
    "deleted_at": null
  }
}
```

---

## 13. Testing Instructions

The test suite runs against an isolated in-memory SQLite database for maximum speed and zero dependencies on host database state.

### Run All Tests via Docker
```bash
docker compose exec app php artisan test
```

### Run Tests with Coverage / Specific Suites
```bash
# Run only Feature tests
docker compose exec app php artisan test --testsuite=Feature

# Run only Unit tests
docker compose exec app php artisan test --testsuite=Unit

# Filter by test method or class
docker compose exec app php artisan test --filter=TaskTest
```

---

## 14. Security Considerations

1. **IDOR Mitigation**: All queries on resources check ownership via Laravel Policies. Attempts to access other users' projects or tasks return `403 Forbidden`.
2. **Mass Assignment Prevention**: Strict `$fillable` model definitions prevent malicious column tampering.
3. **No Sensitive Leaks**: Password hashes and reset tokens are excluded using `$hidden` arrays and API Resources. Internal exceptions and SQL traces are silenced via custom exception renderers in `bootstrap/app.php`.
4. **Token Security**: Tokens generated by Sanctum are sha-256 hashed at rest. Explicit revocation on `/logout` removes the active token from the database.

---

## 15. Design Decisions & Trade-offs

- **Why Form Requests over Controller Validation?**: Form Requests encapsulate validation rules, keep controllers readable and thin, and provide type-safe access to `$request->validated()`.
- **Why Domain Service for Tasks?**: Simple CRUD operations (creating or updating a project name) do not need boilerplate services. However, task state management involves side effects (setting or resetting `completed_at`, transactional safety). Encapsulating this in `TaskService` cleanly separates business logic from HTTP transport concerns.
- **Why Eloquent directly over the Repository Pattern?**: Eloquent already functions as a robust Active Record ORM. Introducing generic repositories creates pass-through overhead without practical benefits.

---

## 16. Future Improvements

- [ ] Add Redis-backed task queuing for email notifications on overdue tasks.
- [ ] Implement team workspace sharing with multi-role access control (Admin, Member, Viewer).
- [ ] Add WebSocket broadcast notifications using Laravel Reverb for real-time Kanban board updates.
- [ ] Add automated Swagger / OpenAPI interactive schema generation.

---

## License

This project is open-sourced under the [MIT License](LICENSE).
