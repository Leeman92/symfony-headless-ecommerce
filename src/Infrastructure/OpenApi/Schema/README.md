# OpenAPI Schema Catalogue

This folder contains dedicated schema classes annotated with `#[OpenApi\Attributes\Schema]`. They exist solely for documentation and client generation purposes.

## Guidelines

- Keep one schema per file and name it after the payload it represents (e.g. `OrderResponseSchema`).
- Reuse schemas via `$ref` instead of repeating inline definitions—this keeps the Swagger UI concise and SDKs type-safe.
- Prefer nullable fields for optional data rather than omitting properties. The transformers always include keys, so the schema should mirror that behaviour.
- When documenting framework-provided routes (e.g. Symfony JSON login), reference these schemas from YAML in `config/packages/nelmio_api_doc.yaml`.
- Run `php -l src/Infrastructure/OpenApi/Schema/*.php` after editing to ensure attribute syntax is valid.

Schemas are intentionally decoupled from Doctrine entities to avoid leaking persistence concerns into public contracts. Update them whenever transformers gain or lose fields.
