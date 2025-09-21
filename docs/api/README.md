# API Documentation Guide

The platform ships with fully described OpenAPI 3.1 definitions exposed through NelmioApiDocBundle. Use this guide to explore the contract quickly, generate client SDKs, or verify behaviour while developing new features.

## Accessing the Swagger UI

1. Boot the stack with `make start` (after running `make setup` and `make build` once).
2. Navigate to https://traditional.ecommerce.localhost/api/doc (the UI is served locally but loads Swagger assets from the unpkg CDN).
3. Accept the self-signed certificate when prompted.
4. Use the **Authorize** button to paste your JWT (`Bearer <token>`). Protected endpoints will then include live response examples.

The raw OpenAPI JSON is available at https://traditional.ecommerce.localhost/api/doc.json for tooling that consumes machine-readable specs.

## Endpoint Families

- **Authentication**: Registration, login, and token refresh flows. Login remains backed by Symfony's JSON login firewall, while registration and refresh endpoints return structured payloads that include user information.
- **Products**: CRUD operations with detailed request/response schemas covering attributes, variants, media, and SEO metadata. Admin-only endpoints enforce `ROLE_ADMIN` via JWT.
- **Orders**: Guest checkout, authenticated order placement, conversion, history, and status management with explicit guest email fallback rules.
- **Payments**: Stripe intent creation, confirmation, lookup, and webhook processing with mixed authentication (JWT or guest email challenge).

Each operation in the UI now lists:

- Query/path parameter definitions with validation hints
- Request bodies referencing reusable schema objects (e.g. `ProductWriteRequest`, `GuestOrderRequest`)
- Response payloads including error envelopes for consistent handling

## Generating Client Code

Because schemas are shared across operations, client generation tools (e.g. `openapi-generator-cli`, `stoplight/prism`) can produce type-safe SDKs. Example:

```bash
openapi-generator-cli generate \
  -i https://traditional.ecommerce.localhost/api/doc.json \
  -g typescript-axios \
  -o build/sdk
```

The generated clients align with the transformers defined in `src/Infrastructure/Controller/Transformer` and surface nullable fields correctly.

## Keeping the Spec Current

- Add or adjust schema classes in `src/Infrastructure/OpenApi/Schema` when payloads evolve.
- Annotate controller actions with `OpenApi\Attributes` so Nelmio captures parameters, security requirements, and response shapes.
- Extend `config/packages/nelmio_api_doc.yaml` when documenting framework-provided routes (e.g. the JSON login endpoint).
- Run `php -l src/Infrastructure/OpenApi/Schema/*.php` or `make cs-check` before pushing to keep annotations syntax-safe.

To contribute new documentation sections, create a README alongside the feature (for example `src/Infrastructure/OpenApi/Schema/README.md`) and cross-link it here for discoverability.
