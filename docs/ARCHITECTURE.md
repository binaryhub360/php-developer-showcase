# WorkBoard API – Architectural Design Record (ADR)

## 1. Executive Overview

**WorkBoard API** is an enterprise-grade RESTful project and task management system built with **PHP 8.2+** and **Laravel 12**. The architectural objective is to balance high maintainability, strict type safety, scalable database patterns, and robust security without introducing unnecessary architectural overhead (such as premature Repository wrappers or overly complex event brokers).

---

## 2. Layered MVC Architecture

WorkBoard API adheres to modern Laravel architecture boundaries:

```
┌─────────────────────────────────────────────────────────────┐
│                       HTTP Layer                            │
│  - Form Requests (Validation, Type Casting)                 │
│  - Route Middlewares (auth:sanctum, throttling)             │
│  - Policies (Fine-grained User Authorization)               │
└──────────────────────────────┬──────────────────────────────┘
                               │ Validated DTO / Models
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    Controller Layer                         │
│  - Thin controllers handling HTTP request / response        │
│  - Calls Policies for resource permission checks            │
│  - Invokes Eloquent queries for basic CRUD                  │
│  - Delegates state machine & multi-write logic to Services  │
└──────────────────────────────┬──────────────────────────────┘
                               │ Domain Operations
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                     Service Layer                           │
│  - TaskService: status transition rules & timestamp audit   │
│  - Encapsulates DB::transaction blocks                      │
└──────────────────────────────┬──────────────────────────────┘
                               │ Persistence & Relations
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                     Data Access Layer                       │
│  - Eloquent Models with strict casts and relationships      │
│  - Native PHP 8.2+ Backed Enums                             │
│  - Database Migrations, Indexes & Soft Deletes              │
└──────────────────────────────┬──────────────────────────────┘
                               │ Serialized Arrays
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                   Presentation Layer                        │
│  - API Resources (JsonResource) formatting unified output   │
│  - Consistent envelope: success, message, data, meta        │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Database Design & Referential Integrity

### Schema Definition
1. **`users`**:
   - `id`, `name`, `email` (unique index), `password` (Argon2id/Bcrypt), `email_verified_at`, `timestamps`.
2. **`projects`**:
   - `id`, `user_id` (foreign key to `users.id`), `name` (indexed), `description` (nullable), `status` (indexed), `timestamps`, `deleted_at` (soft deletes).
   - Composite Index: `['user_id', 'status']`.
3. **`tasks`**:
   - `id`, `project_id` (foreign key to `projects.id`), `title` (indexed), `description` (nullable), `status` (indexed), `priority` (indexed), `due_date` (nullable, indexed), `completed_at` (nullable), `timestamps`, `deleted_at` (soft deletes).
   - Composite Indexes: `['project_id', 'status']`, `['project_id', 'priority']`, `['project_id', 'due_date']`.

### Referential Integrity & Deletion Strategy
- **Foreign Key Constraints**: Foreign keys are established with `cascadeOnDelete()`. If an account or project is hard-deleted from the database, all child entities are automatically pruned, avoiding orphaned records.
- **Soft Deletion**: For routine application operations, models use `SoftDeletes`. When a user deletes a project or task, it is marked with `deleted_at`, preserving audit history and allowing data recovery if needed.
- **Transactions**: Multi-table updates or operations requiring state synchronization (such as marking a task completed and stamping `completed_at`) are wrapped inside `DB::transaction(...)` to guarantee atomicity.

---

## 4. Avoiding N+1 Query Problems

N+1 queries degrade API throughput under scale. The API employs two direct prevention techniques:
1. **Eager Loading & `withCount`**:
   - In `ProjectController::index` and `show`, task counts are loaded using `$query->withCount('tasks')`, performing an efficient single subquery rather than querying `tasks` for every returned project row.
2. **Strict Foreign Key Scoping**:
   - Queries query through relationships (`$request->user()->projects()`, `$project->tasks()`), ensuring that the database query planner resolves indexes directly on `user_id` and `project_id`.

---

## 5. Security Architecture

1. **Authentication (Sanctum Tokens)**:
   - Stateless, cryptographically secure bearer tokens generated via Laravel Sanctum.
   - Tokens are hashed in the database (`personal_access_tokens.token`).
   - Logout endpoint explicitly deletes the currently active token: `$request->user()->currentAccessToken()->delete()`.
2. **IDOR (Insecure Direct Object Reference) Protection**:
   - Every single-resource endpoint authorizes the action through Laravel Policies (`ProjectPolicy` and `TaskPolicy`).
   - A user attempting to read, update, or delete a project or task belonging to another user receives a `403 Forbidden` response.
3. **Mass-Assignment Protection**:
   - All models define explicit `$fillable` arrays. Sensitive fields (`id`, `user_id`, `created_at`, `updated_at`) cannot be mass-assigned from raw request payloads.
4. **Data Sanitization & Strict Typing**:
   - Strict typing (`declare(strict_types=1);`) is enforced across the codebase.
   - Validation is handled in dedicated `FormRequest` classes with type-checked rules.
   - Native PHP Enums (`ProjectStatus`, `TaskStatus`, `TaskPriority`) prevent invalid string parameters from reaching database queries.

---

## 6. Architectural Trade-offs & Decisions

### Decision: Direct Eloquent in Controllers vs. Repository Pattern
- **Choice**: Thin controllers directly utilizing Eloquent queries for basic CRUD, delegating complex multi-entity or transactional mutations to Domain Services (`TaskService`).
- **Rationale**: The Repository pattern in Laravel often degrades into an anemic pass-through layer over Eloquent without providing real swapability. Eloquent is already an active-record ORM with powerful query builder capabilities. Bypassing repetitive repository wrappers improves developer velocity and readability without sacrificing testability.

### Decision: Soft Deletes vs. Hard Deletes
- **Choice**: Soft deletes enabled on `projects` and `tasks`.
- **Rationale**: In collaborative project management systems, accidental deletions happen frequently. Soft deletes allow non-destructive deletions for users while retaining relational data consistency.
