# HTTP Controllers

Controllers expose the REST API over `/api/*`. They focus on request orchestration and delegate to application services plus transformers for serialization. Key conventions:

- **Routing**: Paths are defined with Symfony attributes and inherit the `/api` prefix configured in `config/routes.yaml`.
- **Documentation**: Every action carries `OpenApi\Attributes` so Nelmio can render Swagger UI and client schemas. Shared payload shapes live in `../OpenApi/Schema`.
- **Transformers**: Responses are normalised using the pure PHP transformers in `Transformer/` to keep presentation concerns out of entities.
- **Request Mappers**: JSON payloads are validated and converted into DTOs or aggregates via classes in `Request/` to centralise validation rules.

When adding a new controller:

1. Create or update schema classes describing the request/response contract.
2. Annotate the action with `OA\*` attributes for parameters, bodies, responses, and security requirements.
3. Extend transformers instead of returning entities directly to maintain consistent payloads.
4. Document the controller in this README or link to a feature-specific README when the behaviour warrants deeper explanation.
