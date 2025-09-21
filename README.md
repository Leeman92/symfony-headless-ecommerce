# Symfony Headless E-commerce Platform

A modern, performance-focused headless e-commerce platform built with Symfony 6.4 LTS, designed to demonstrate the evolution from functional but unoptimized code to enterprise-grade performance through systematic optimization.

## 🎯 Project Overview

This project follows a unique "performance journey" approach, intentionally starting with common performance bottlenecks that are systematically optimized across multiple phases. Each phase demonstrates different optimization strategies, from infrastructure changes to code-level improvements.

### 🤖 Agentic Development Approach

This project showcases modern **AI-assisted development** using Kiro IDE with comprehensive:
- **Steering Documents**: Development standards and guidelines in `.kiro/steering/`
- **Spec-Driven Development**: Structured requirements, design, and task planning in `.kiro/specs/`
- **Systematic Implementation**: Phase-based development with clear deliverables and metrics

The `.kiro` folder demonstrates how to effectively collaborate with AI agents through structured documentation and clear development workflows.

### Key Features

- **Headless Architecture**: RESTful API-first design for maximum flexibility
- **Guest & User Checkout**: Support for both authenticated users and guest purchases
- **Payment Integration**: Stripe payment processing with webhook handling
- **Performance Monitoring**: Built-in metrics collection and load testing
- **Multi-Phase Optimization**: Systematic performance improvements across 7 phases
- **Enterprise CI/CD**: Complete pipeline with HashiCorp Vault and Harbor registry

## 🏗️ Architecture

### Technology Stack

#### Phase 1: Traditional Stack (Intentionally Unoptimized)
- **Web Server**: Nginx 1.24
- **PHP Runtime**: PHP-FPM 8.4 (Latest with performance improvements and new features)
- **Framework**: Symfony 6.4 LTS
- **Database**: PostgreSQL 16 with JSONB support
- **Cache**: Redis 7.0 (minimal usage)
- **Payment**: Stripe API integration
- **Testing**: PHPUnit + Artillery.io load testing

#### Phase 2+: Optimized Evolution
- **Runtime**: FrankenPHP Worker Mode (60-75% performance improvement)
- **Caching**: Advanced Redis strategies + Varnish HTTP cache
- **Search**: OpenSearch for product discovery and analytics
- **Monitoring**: Grafana + Prometheus for real-time metrics

### Database Strategy: PostgreSQL 16

**Why PostgreSQL over MySQL:**
- Superior ACID compliance and MVCC
- Advanced JSONB support with indexing
- Better query planner and optimizer
- Perfect for flexible product attributes and variants

```sql
-- Example: PostgreSQL JSON queries for product search
SELECT * FROM products 
WHERE attributes->>'color' = 'red' 
  AND (attributes->>'size')::text[] && ARRAY['M', 'L'];
```

## 🚀 Performance Journey

### Phase 1: Baseline (Traditional Infrastructure)
- **Response Time**: 800ms - 2000ms
- **Memory Usage**: 32MB per request
- **Concurrent Users**: 50-100
- **RPS**: 50-100

### Phase 2: Infrastructure Optimization (FrankenPHP)
- **Response Time**: 200ms - 500ms (60-75% improvement)
- **Memory Usage**: 8MB per request (75% reduction)
- **Concurrent Users**: 200-500 (4-5x improvement)
- **RPS**: 300-800 (6-8x improvement)

### Phase 3: Code + Infrastructure
- **Response Time**: 50ms - 150ms (90%+ improvement)
- **Memory Usage**: 4MB per request (87% reduction)
- **Concurrent Users**: 1000+ (10x+ improvement)
- **RPS**: 2000+ (20x+ improvement)

### Phase 4: HTTP Caching (Varnish)
- **Response Time**: 10ms - 50ms (95%+ improvement)
- **Cache Hit Ratio**: 85-95%
- **RPS**: 5000+ (50x+ improvement)

### Phase 5+: Search Optimization (OpenSearch)
- **Search Response**: 5ms - 20ms
- **Search Throughput**: 10,000+ queries/second
- **Advanced Features**: Fuzzy search, NLP, analytics dashboard

## 🛠️ Development Setup

### Prerequisites

- PHP 8.4+ (Latest version with improved performance, JIT optimizations, and new language features)
- Composer
- Docker & Docker Compose
- Node.js (for frontend tooling)
- uv/uvx (for MCP servers)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd symfony-headless-ecommerce
   ```

2. **Set up the project**
   ```bash
   make setup
   ```
   This will:
   - Create necessary directories
   - Generate SSL certificates for local development
   - Create Docker network
   - Set executable permissions on scripts

3. **Start development environment**
   ```bash
   make build
   make start
   ```

4. **Install dependencies**
   ```bash
   make install
   ```

5. **Set up database** (when entities are created)
   ```bash
   make db-create
   make migrate
   ```

6. **Generate JWT keys**
   ```bash
   docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional php bin/console lexik:jwt:generate-keypair
   ```

### Access Points

- **Traditional Phase API**: https://traditional.ecommerce.localhost/api
- **Traefik Dashboard**: https://traefik.ecommerce.localhost
- **API Documentation**: https://traditional.ecommerce.localhost/api/doc

**Note**: All services use HTTPS with self-signed certificates. Accept the certificate in your browser for local development.

### Environment Configuration

```bash
# Database
DATABASE_URL=postgresql://postgres:postgres@localhost:5432/ecommerce

# Redis
REDIS_URL=redis://localhost:6379

# Stripe (use test keys for development)
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
```

## 📊 API Documentation

The OpenAPI 3.1 specification is generated with NelmioApiDocBundle and reflects every controller action plus the Symfony JSON login firewall. See [`docs/api/README.md`](docs/api/README.md) for a deeper walkthrough.

### Exploring the Spec

- **Swagger UI**: https://traditional.ecommerce.localhost/api/doc (accept the self-signed certificate). Use the *Authorize* button to provide `Bearer` tokens when trying protected routes.
- **Raw JSON**: https://traditional.ecommerce.localhost/api/doc.json for contract testing, SDK generation, and CLI scripting.
- **Authentication**: Registration and refresh endpoints return structured payloads (`TokenResponse`), while the login route is still handled by the security firewall and returns a raw token envelope.

### Endpoint Families

- **Authentication** – register, login, refresh; responses expose user metadata for faster onboarding.
- **Products** – browsing plus admin CRUD with exhaustive schemas for variants, SEO, and media.
- **Orders** – guest and authenticated checkout, conversion, history, and administrative status updates with explicit guest-email verification rules.
- **Payments** – Stripe intent lifecycle, confirmation, lookup, and webhook processing with mixed authentication (JWT or guest email challenge).

Each operation references reusable schema classes from `src/Infrastructure/OpenApi/Schema`, so clients receive consistent error envelopes and pagination metadata.

### Guest Checkout Flow

```bash
# 1. Browse products (no auth required)
curl -k -X GET https://traditional.ecommerce.localhost/api/products

# 2. Guest checkout
curl -k -X POST https://traditional.ecommerce.localhost/api/orders/guest \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"product_id": 1, "quantity": 2}],
    "guest": {
      "email": "customer@example.com",
      "first_name": "John",
      "last_name": "Doe"
    }
  }'

# 3. Process payment with returned intent details
# (Frontend handles Stripe confirmation)

# 4. Optional: Convert to user order after authentication
curl -k -X POST https://traditional.ecommerce.localhost/api/orders/{order_number}/convert \
  -H "Authorization: Bearer <token>"
```

## 🧪 Testing

### Test Structure (Test Pyramid)
- **70% Unit Tests**: Entities, services, repositories
- **20% Integration Tests**: API endpoints, database integration
- **10% End-to-End Tests**: Complete user journeys

### Running Tests

```bash
# Unit tests
vendor/bin/phpunit tests/Unit

# Integration tests
vendor/bin/phpunit tests/Integration

# All tests with coverage
vendor/bin/phpunit --coverage-html coverage

# Performance tests
docker run --rm -v $(pwd)/load-tests:/tests artilleryio/artillery:latest run /tests/artillery-config.yml
```

### Load Testing

```bash
# Phase 1 performance baseline
artillery run load-tests/phase1-traditional.yml

# Phase 2 performance comparison
artillery run load-tests/phase2-frankenphp.yml

# Generate performance reports
artillery run load-tests/phase1-traditional.yml --output phase1-results.json
artillery report phase1-results.json
```

## 🌳 Branching Strategy

### Phase-Based Development

This project uses a phase-based branching strategy optimized for performance demonstration:

```
main (latest completed phase)
├── phase-1-traditional (baseline: Nginx + PHP-FPM)
├── phase-2-frankenphp (infrastructure optimization)
├── phase-3-optimization (code + infrastructure)
├── phase-4-varnish (HTTP caching)
├── phase-5-opensearch (search optimization)
└── phase-6-enterprise (full enterprise features)
```

**Benefits:**
- Each phase branch is a complete, deployable demonstration
- Easy performance comparisons between phases
- Stable baselines for load testing
- Clear evolution for portfolio/blog content
- Independent maintenance and bugfixes per phase

### Development Workflow

1. **Develop on main**: Work on current phase
2. **Complete phase**: Create frozen phase branch (`phase-X-name`)
3. **Continue on main**: Start next phase development
4. **Deploy any phase**: Each branch is independently deployable

## 🚢 Deployment

### CI/CD Pipeline

The project includes a complete CI/CD pipeline with:

1. **Quality Gates**: PHPStan, PHP-CS-Fixer, security checks
2. **Testing**: Automated test execution with coverage
3. **Building**: Multi-stage Docker images for different phases
4. **Deployment**: Docker Swarm with HashiCorp Vault secrets
5. **Phase Management**: Automatic deployment based on branch naming

### Secret Management

```bash
# Store secrets in HashiCorp Vault
vault kv put secret/ecommerce/staging \
  database_url="postgresql://user:pass@db:5432/ecommerce" \
  stripe_secret_key="sk_test_..." \
  jwt_secret="your-jwt-secret"
```

### Docker Deployment

```bash
# Deploy to staging
./scripts/deploy.sh staging $COMMIT_SHA

# Deploy to production (requires approval)
./scripts/deploy.sh production $COMMIT_SHA
```

## 📈 Performance Monitoring

### Built-in Metrics

The application includes performance monitoring middleware that tracks:
- Response times
- Memory usage
- Database query counts
- Cache hit/miss ratios

### Monitoring Stack

- **Grafana**: Real-time dashboards
- **Prometheus**: Metrics collection
- **OpenSearch Dashboards**: Search analytics (Phase 5+)

Access monitoring:
- Grafana: http://localhost:3000 (admin/admin)
- Prometheus: http://localhost:9090

## 🎓 Learning Objectives

This project demonstrates:

1. **Infrastructure Impact**: How runtime choice affects performance
2. **Code Optimization**: Database queries, caching, serialization
3. **HTTP Caching**: Varnish configuration and cache strategies
4. **Search Architecture**: When to move from database to search engine
5. **Enterprise Deployment**: CI/CD, secret management, monitoring

## 📝 Blog Series

Each optimization phase will be documented in detailed blog posts:

1. "Why I Chose Symfony Over Laravel: The Doctrine Advantage"
2. "The Hidden Cost of PHP-FPM: Why Your API is Slower Than It Should Be"
3. "FrankenPHP Worker Mode: 10x Performance Without Changing Code"
4. "HTTP Caching with Varnish: When Application Caching Isn't Enough"
5. "OpenSearch for E-commerce: Beyond Basic Product Search"
6. "Search-First Architecture: When Your Database Becomes the Bottleneck"
7. "From 100 to 10,000+ RPS: A Complete E-commerce Optimization Journey"

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Follow PSR standards and write tests
4. Ensure all quality gates pass
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Links

- [Symfony Documentation](https://symfony.com/doc)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [PostgreSQL JSON Functions](https://www.postgresql.org/docs/current/functions-json.html)
- [FrankenPHP](https://frankenphp.dev/)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Artillery.io Load Testing](https://artillery.io/docs/)

---

**Note**: This project is designed for educational purposes to demonstrate performance optimization techniques. Each phase builds upon the previous one, showing measurable improvements through systematic optimization.
### 
Phase Deployment Examples

```bash
# Deploy Phase 1 (traditional stack)
git checkout phase-1-traditional
./scripts/deploy.sh staging phase-1

# Deploy Phase 2 (FrankenPHP) for comparison
git checkout phase-2-frankenphp  
./scripts/deploy.sh staging phase-2

# Deploy latest development
git checkout main
./scripts/deploy.sh staging latest
```

### Performance Comparison Workflow

```bash
# Run load tests against Phase 1
git checkout phase-1-traditional
docker-compose -f docker-compose.traditional.yml up -d
artillery run load-tests/baseline.yml --output phase1-results.json

# Run load tests against Phase 2
git checkout phase-2-frankenphp
docker-compose -f docker-compose.frankenphp.yml up -d
artillery run load-tests/baseline.yml --output phase2-results.json

# Compare results
artillery report phase1-results.json phase2-results.json
```

## 🤖 Agentic Development Showcase

### Steering Documents (`.kiro/steering/`)
This project demonstrates advanced AI collaboration through comprehensive steering documents:

- **`symfony-ecommerce-standards.md`**: Development standards, architecture principles, and coding guidelines
- **`performance-optimization-guide.md`**: Phase-based optimization strategy with specific metrics and targets
- **`testing-standards.md`**: Comprehensive testing pyramid with examples and coverage requirements
- **`ci-cd-pipeline.md`**: Complete CI/CD configuration with HashiCorp Vault and phase-based deployments
- **`docker-infrastructure.md`**: Multi-phase infrastructure configurations and deployment strategies
- **`stripe-payment-integration.md`**: Payment processing guidelines and security considerations

### Spec-Driven Development (`.kiro/specs/`)
Structured approach to complex feature development:

- **`requirements.md`**: Detailed user stories and acceptance criteria in EARS format
- **`design.md`**: Comprehensive architecture and technical design decisions
- **`tasks.md`**: Actionable implementation plan with phase management

### Benefits of This Approach
1. **Consistency**: AI agents follow established patterns and standards
2. **Quality**: Comprehensive guidelines ensure best practices
3. **Efficiency**: Structured specs enable faster, more accurate implementation
4. **Maintainability**: Clear documentation supports long-term project evolution
5. **Collaboration**: Human-AI partnership with clear boundaries and expectations

### Portfolio Value
This demonstrates:
- **Modern Development Practices**: Using AI tools effectively in professional workflows
- **Process Excellence**: Systematic approach to complex projects
- **Technical Leadership**: Ability to create comprehensive development standards
- **Future-Ready Skills**: Proficiency with emerging AI-assisted development tools