# Repository Guidelines

## Project Structure & Module Organization
Symfony application code sits in `src/` with domain models, controllers, and services following PSR-4 namespaces. Environment and service wiring live in `config/`. HTTP entrypoints and assets served via `public/`. Twig email templates in `templates/`. Tests and fixtures under `tests/`. Docker orchestration and TLS utilities live in `docker/` and `scripts/`. Performance artefacts are tracked in `load-tests/` and `performance-results/`. Legacy guidance remains in `.kiro/steering/` and `.kiro/specs/`; AI agents should reference these documents when planning work even though formal Kiro workflows are retired.

## Build, Test, and Development Commands
`make setup` provisions directories, SSL certs, and the shared Docker network. `make build` builds the PHP, Traefik, PostgreSQL, and Redis containers; rerun whenever Dockerfiles change. `make start` boots the traditional stack at `https://traditional.ecommerce.localhost`. Run `make install` for composer deps, and `make migrate` or `make db-create` to manage schema. Use `make stop` and `make clean` to tear down environments.

## Coding Style & Naming Conventions
PHP code follows Symfony + PHP 8.4 rules enforced by `make cs-check`/`make cs-fix` (PHP-CS-Fixer). Keep strict types enabled, prefer constructor property promotion, and order imports alphabetically by class/function/const. Use 4-space indentation, PascalCase classes, camelCase methods, and descriptive service IDs. Configuration files should mirror feature-based directory names.

## Testing Guidelines
Unit and feature tests belong in `tests/` with filenames ending in `Test.php` and PHPUnit namespaces matching their directory. Execute the suite with `make test`, which runs `vendor/bin/phpunit` inside the PHP-FPM container. Include regression coverage for new endpoints and update fixtures as needed. For performance phases, trigger Artillery scenarios with `make load-test` and capture results in `performance-results/`.

## Commit & Pull Request Guidelines
Follow conventional commit prefixes observed in history (`feat:`, `fix:`, `chore:`) and keep subjects under 72 characters. Describe context, intent, and any spec links in the body. Pull requests should summarize changes, reference steering documents or issues, note how tests were executed, and attach relevant API responses or screenshots when behaviour shifts. Keep PRs scoped to a single feature or fix to ease agent review.
