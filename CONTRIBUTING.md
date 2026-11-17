# Contributing to WorkBoard API

Thank you for considering contributing to the WorkBoard API project. As a portfolio showcase demonstrating enterprise Laravel practices, we hold our code quality, architecture, and testing standards to a high bar.

## Code Standards & Style

1. **PSR-12 Compliance**: All PHP files must strictly conform to PSR-12 coding guidelines.
2. **Strict Typing**: Declare `declare(strict_types=1);` at the top of every PHP file.
3. **Architecture Boundaries**:
   - Keep controllers thin; handle HTTP concerns only.
   - Delegate complex status transitions and multi-step writes to domain services (`TaskService`).
   - Use Form Requests for request validation and authorization where appropriate.
   - Use API Resources for JSON serialization.
   - Use Eloquent Policies for user authorization.
   - Prevent IDOR by verifying ownership on every resource interaction.
4. **Testing**:
   - Write comprehensive Feature tests for every new endpoint.
   - Write Unit tests for domain services and enums.
   - Ensure all tests pass with `php artisan test`.

## Local Development Workflow

1. Fork and clone the repository.
2. Set up your environment variables:
   ```bash
   cp .env.example .env
   ```
3. Start the Docker environment:
   ```bash
   docker compose up -d --build
   ```
4. Run migrations and seed data:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```
5. Run the test suite:
   ```bash
   docker compose exec app php artisan test
   ```

## Pull Request Guidelines

- Create a feature branch with a descriptive name (`feature/task-reordering`, `fix/status-transition`).
- Keep commits atomic and messages descriptive following conventional commit patterns.
- Ensure all tests pass in Docker and CI before requesting a review.
