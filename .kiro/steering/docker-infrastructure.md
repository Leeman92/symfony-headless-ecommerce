# Docker Infrastructure Guide

## Multi-Phase Infrastructure Strategy
This project uses different Docker configurations for each optimization phase to demonstrate performance improvements.

## Phase 1: Traditional Stack (Nginx + PHP-FPM)
```yaml
# docker-compose.traditional.yml
version: '3.8'
services:
  nginx:
    image: nginx:1.24-alpine
    ports:
      - "8080:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - .:/var/www/html
    depends_on:
      - php-fpm
      
  php-fpm:
    build:
      context: .
      dockerfile: Dockerfile.traditional
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=dev
    depends_on:
      - postgres
      - redis
```

## Phase 2: FrankenPHP Worker Mode
```yaml
# docker-compose.frankenphp.yml
version: '3.8'
services:
  frankenphp:
    build:
      context: .
      dockerfile: Dockerfile.frankenphp
    ports:
      - "8081:80"
    volumes:
      - .:/app
    environment:
      - APP_ENV=prod
      - FRANKENPHP_CONFIG=worker ./public/index.php
```

## Database Configuration
- **PostgreSQL 16:** Primary database with JSONB support
- **Redis 7.0:** Caching layer (minimal usage in Phase 1)
- Persistent volumes for data retention
- Environment-specific configurations

## Load Testing Environment
- Dedicated Artillery.io container for performance testing
- Isolated test database for consistent baselines
- Rate limiting disabled in test environment
- Clean state reset between test runs

## Monitoring Stack
```yaml
services:
  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
      
  prometheus:
    image: prom/prometheus:latest
    ports:
      - "9090:9090"
```

## CI/CD Integration
- Multi-stage Dockerfiles for optimization
- Harbor registry for image storage
- HashiCorp Vault for secret management
- Docker Swarm for simple orchestration
- Rolling updates for zero-downtime deployment

## Environment Variables
```bash
# Database
DATABASE_URL=postgresql://postgres:postgres@postgres:5432/ecommerce

# Redis
REDIS_URL=redis://redis:6379

# Stripe
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
```

## Phase-Based Development Workflow

### Branch and Infrastructure Mapping
- `main` branch: Current phase development
- `phase-1-traditional`: Nginx + PHP-FPM stack (frozen)
- `phase-2-frankenphp`: FrankenPHP Worker Mode (frozen)
- `phase-3-optimization`: Optimized code + infrastructure (frozen)
- `phase-4-varnish`: HTTP caching layer (frozen)
- `phase-5-opensearch`: Search optimization (frozen)
- `phase-6-enterprise`: Full enterprise stack (frozen)

### Development Workflow
1. **Phase 1**: Develop on main with traditional stack
2. **Phase Completion**: Create phase branch, freeze infrastructure
3. **Phase 2**: Continue on main, switch to FrankenPHP
4. **Performance Testing**: Compare phases using separate deployments
5. **Repeat**: Each phase builds on previous, maintains separate deployable branches

## Security Considerations
- Use Docker secrets for sensitive data
- Implement proper network isolation
- Regular security updates for base images
- Vault integration for production secrets
## Pha
se-Specific Docker Configurations

### Phase 1: Traditional Stack (Baseline)
```yaml
# docker-compose.traditional.yml - Frozen after Phase 1 completion
version: '3.8'
services:
  nginx:
    image: nginx:1.24-alpine
    # Intentionally unoptimized configuration
  php-fpm:
    # Traditional PHP-FPM setup
    # No worker mode, process-per-request
```

### Phase 2: FrankenPHP Worker Mode
```yaml
# docker-compose.frankenphp.yml - Frozen after Phase 2 completion
version: '3.8'
services:
  frankenphp:
    # FrankenPHP with worker mode
    # Persistent processes, shared memory
```

### Phase 3+: Optimized Stack
```yaml
# docker-compose.optimized.yml - Evolves with phases 3-6
version: '3.8'
services:
  frankenphp:
    # Optimized FrankenPHP configuration
  varnish:
    # Added in Phase 4
  opensearch:
    # Added in Phase 5
```

## Performance Comparison Infrastructure

### Multi-Environment Deployment
```bash
# Deploy Phase 1 for baseline testing
docker-compose -f docker-compose.traditional.yml up -d --project-name ecommerce-phase1

# Deploy Phase 2 for comparison
docker-compose -f docker-compose.frankenphp.yml up -d --project-name ecommerce-phase2

# Run comparative load tests
artillery run load-tests/baseline.yml --target http://localhost:8080 --output phase1.json
artillery run load-tests/baseline.yml --target http://localhost:8081 --output phase2.json
```

### Automated Phase Deployment
```bash
#!/bin/bash
# scripts/deploy-phase.sh
PHASE=$1
PORT_BASE=$((8080 + ${PHASE#phase-}))

case $PHASE in
  "phase-1-traditional")
    docker-compose -f docker-compose.traditional.yml up -d --project-name ecommerce-$PHASE
    ;;
  "phase-2-frankenphp")
    docker-compose -f docker-compose.frankenphp.yml up -d --project-name ecommerce-$PHASE
    ;;
  *)
    docker-compose -f docker-compose.optimized.yml up -d --project-name ecommerce-$PHASE
    ;;
esac
```