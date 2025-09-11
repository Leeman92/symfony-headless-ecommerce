# Symfony E-commerce Development Standards

## Project Overview
This is a headless e-commerce platform built with Symfony 6.4 LTS, designed to demonstrate performance optimization from unoptimized to enterprise-grade. The project follows a "performance journey" approach with intentional bottlenecks that will be systematically optimized.

## Architecture Principles

### Domain-Driven Design (DDD)
- Use repository pattern instead of Active Record
- Separate business logic from data access
- Implement proper service layer abstraction
- Keep entities focused on business rules

### Database Strategy
- **Primary Database:** PostgreSQL 16 (superior ACID compliance and JSON support)
- **Why PostgreSQL over MySQL:** Better MVCC, advanced JSON support with JSONB, superior query planner
- Leverage PostgreSQL JSON columns for flexible product attributes and variants
- Use proper Doctrine annotations and relationships

### Performance Journey Approach (Phase-Based Branching)
- **main branch:** Current phase development (always deployable)
- **phase-1-traditional:** Nginx + PHP-FPM baseline (frozen after completion)
- **phase-2-frankenphp:** FrankenPHP Worker Mode (frozen after completion)
- **phase-3-optimization:** Code + Infrastructure optimization (frozen after completion)
- **phase-4-varnish:** HTTP Caching with Varnish (frozen after completion)
- **phase-5-opensearch:** OpenSearch integration (frozen after completion)
- **phase-6-enterprise:** Full enterprise features (frozen after completion)

### Branch Management for Agentic Development
- Each phase branch represents a complete, deployable milestone
- Frozen phase branches allow independent maintenance and bugfixes
- Performance comparisons use separate deployments of different phases
- Blog content and documentation reference specific phase branches

## Code Standards

### Entity Design
```php
// Use PostgreSQL JSON columns for flexible data
#[ORM\Column(type: Types::JSON, nullable: true)]
private ?array $attributes = null; // size, color, material, etc.

// Proper Doctrine relationships
#[ORM\ManyToOne(targetEntity: Category::class)]
#[ORM\JoinColumn(nullable: false)]
private ?Category $category = null;
```

### Repository Pattern
- Always use custom repository classes extending ServiceEntityRepository
- Implement repository interfaces for dependency injection
- Create intentional N+1 query issues in Phase 1 for demonstration
- Use Doctrine Query Builder for complex queries

### Service Layer
- Separate business logic from controllers
- Use dependency injection for all services
- Implement proper transaction handling
- Create custom exception hierarchy for business logic

### API Design
- RESTful endpoints with proper HTTP status codes
- Consistent JSON response format
- OpenAPI/Swagger documentation with NelmioApiDocBundle
- JWT authentication with LexikJWTAuthenticationBundle

## Guest Checkout Requirements
- Support both authenticated users and guest checkout
- Guest orders should store customer information directly
- Provide guest-to-user account conversion functionality
- Ensure all order workflows work for both user types

## Performance Monitoring
- Implement performance metrics collection middleware
- Track query counts, execution time, and memory usage
- Create load testing infrastructure with Artillery.io
- Environment-aware rate limiting (disabled for load testing)

## Testing Strategy
- 70% Unit Tests (entities, services, repositories)
- 20% Integration Tests (API endpoints, database integration)
- 10% End-to-End Tests (complete user journeys)
- Include both user and guest checkout workflows in tests

## Security Standards
- JWT-based authentication with refresh tokens
- Role-based access control (RBAC)
- Password hashing with bcrypt
- SQL injection prevention through Doctrine ORM
- Proper input validation and sanitization

## CI/CD Requirements
- Multi-stage pipeline: quality → test → build → deploy
- Code quality checks (PHPStan, PHP-CS-Fixer)
- Docker image building and Harbor registry integration
- HashiCorp Vault integration for secret management
- Automated deployment with zero-downtime rolling updates## Ag
entic Development Guidelines

### Phase Development Workflow
1. **Start Phase**: Work on main branch for current phase development
2. **Complete Phase**: When phase is fully tested and documented:
   - Create phase branch: `git checkout -b phase-X-name`
   - Freeze branch (no further development)
   - Tag release: `git tag v1.X.0`
3. **Continue Development**: Return to main branch for next phase
4. **Maintain Phases**: Apply critical bugfixes to specific phase branches if needed

### Performance Testing Protocol
- Each phase must have baseline performance metrics
- Load testing configurations stored in `load-tests/` directory
- Automated comparison scripts in `scripts/` directory
- Performance regression detection between phases
- Metrics collection includes phase identification

### Documentation Requirements
- Each phase branch includes phase-specific README updates
- Performance metrics documented in phase completion
- Blog post content references specific phase branches
- Infrastructure configurations frozen with each phase

### Deployment Strategy
- Each phase branch is independently deployable
- CI/CD pipeline supports phase-specific deployments
- Environment naming: `demo-1`, `demo-2`, etc. for phase demonstrations
- Production deployment uses latest completed phase (main branch)