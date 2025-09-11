---
inclusion: manual
---

# CI/CD Pipeline Configuration

## Pipeline Overview
Multi-stage CI/CD pipeline: Quality → Test → Build → Deploy

## Phase-Based Branching Strategy

### Branch Structure for Performance Journey
- `main`: Current phase development (always deployable)
- `phase-1-traditional`: Frozen baseline (Nginx + PHP-FPM)
- `phase-2-frankenphp`: Infrastructure optimization phase
- `phase-3-optimization`: Code + infrastructure optimization
- `phase-4-varnish`: HTTP caching implementation
- `phase-5-opensearch`: Search optimization
- `phase-6-enterprise`: Full enterprise features

### Development Workflow
1. Develop current phase on `main` branch
2. When phase is complete and tested, create `phase-X-name` branch
3. Freeze phase branch (no further development)
4. Continue next phase development on `main`
5. Each phase branch remains independently deployable

## GitHub Actions Workflow
```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, 'phase-*' ]
  pull_request:
    branches: [ main ]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo, pdo_pgsql, redis
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse
        
      - name: Run PHP-CS-Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
        
      - name: Security check
        run: symfony security:check

  test:
    needs: quality
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: ecommerce_test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
          
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          
      - name: Install dependencies
        run: composer install
        
      - name: Run database migrations
        run: php bin/console doctrine:migrations:migrate --no-interaction
        env:
          DATABASE_URL: postgresql://postgres:postgres@localhost:5432/ecommerce_test
          
      - name: Run tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml
        
      - name: Upload coverage
        uses: codecov/codecov-action@v3

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Login to Harbor Registry
        uses: docker/login-action@v2
        with:
          registry: ${{ secrets.HARBOR_REGISTRY }}
          username: ${{ secrets.HARBOR_USERNAME }}
          password: ${{ secrets.HARBOR_PASSWORD }}
          
      - name: Build and push Docker images
        run: |
          # Build traditional stack image
          docker build -f Dockerfile.traditional -t ${{ secrets.HARBOR_REGISTRY }}/ecommerce/app:traditional-${{ github.sha }} .
          docker push ${{ secrets.HARBOR_REGISTRY }}/ecommerce/app:traditional-${{ github.sha }}
          
          # Build FrankenPHP image
          docker build -f Dockerfile.frankenphp -t ${{ secrets.HARBOR_REGISTRY }}/ecommerce/app:frankenphp-${{ github.sha }} .
          docker push ${{ secrets.HARBOR_REGISTRY }}/ecommerce/app:frankenphp-${{ github.sha }}

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main' || startsWith(github.ref, 'refs/heads/phase-')
    steps:
      - uses: actions/checkout@v4
      
      - name: Determine deployment environment
        id: deploy-env
        run: |
          if [[ "${{ github.ref }}" == "refs/heads/main" ]]; then
            echo "environment=staging" >> $GITHUB_OUTPUT
            echo "phase=latest" >> $GITHUB_OUTPUT
          elif [[ "${{ github.ref }}" =~ refs/heads/phase-([0-9]+)-(.*) ]]; then
            echo "environment=demo-${BASH_REMATCH[1]}" >> $GITHUB_OUTPUT
            echo "phase=phase-${BASH_REMATCH[1]}-${BASH_REMATCH[2]}" >> $GITHUB_OUTPUT
          fi
      
      - name: Deploy to environment
        run: |
          # Deploy using Docker Swarm with Vault secrets
          ./scripts/deploy.sh ${{ steps.deploy-env.outputs.environment }} ${{ github.sha }} ${{ steps.deploy-env.outputs.phase }}
        env:
          VAULT_ADDR: ${{ secrets.VAULT_ADDR }}
          VAULT_TOKEN: ${{ secrets.VAULT_TOKEN }}
```

## HashiCorp Vault Integration

### Secret Management
```bash
# Store secrets in Vault
vault kv put secret/ecommerce/staging \
  database_url="postgresql://user:pass@db:5432/ecommerce" \
  stripe_secret_key="sk_test_..." \
  jwt_secret="your-jwt-secret"

vault kv put secret/ecommerce/production \
  database_url="postgresql://user:pass@db:5432/ecommerce_prod" \
  stripe_secret_key="sk_live_..." \
  jwt_secret="your-production-jwt-secret"
```

### Deployment Script with Vault and Phase Support
```bash
#!/bin/bash
# scripts/deploy.sh

ENVIRONMENT=$1
COMMIT_SHA=$2
PHASE=${3:-"latest"}

echo "Deploying to environment: $ENVIRONMENT, phase: $PHASE, commit: $COMMIT_SHA"

# Retrieve secrets from Vault
DATABASE_URL=$(vault kv get -field=database_url secret/ecommerce/$ENVIRONMENT)
STRIPE_SECRET_KEY=$(vault kv get -field=stripe_secret_key secret/ecommerce/$ENVIRONMENT)
JWT_SECRET=$(vault kv get -field=jwt_secret secret/ecommerce/$ENVIRONMENT)

# Determine Docker Compose file based on phase
COMPOSE_FILE="docker-compose.production.yml"
if [[ "$PHASE" == "phase-1-traditional" ]]; then
    COMPOSE_FILE="docker-compose.traditional.yml"
elif [[ "$PHASE" == "phase-2-frankenphp" ]]; then
    COMPOSE_FILE="docker-compose.frankenphp.yml"
elif [[ "$PHASE" =~ phase-[3-6]-.* ]]; then
    COMPOSE_FILE="docker-compose.optimized.yml"
fi

echo "Using compose file: $COMPOSE_FILE"

# Deploy with Docker Swarm
docker stack deploy \
  --with-registry-auth \
  --compose-file docker-compose.production.yml \
  ecommerce-$ENVIRONMENT

# Update service with new image and secrets
docker service update \
  --image $HARBOR_REGISTRY/ecommerce/app:frankenphp-$COMMIT_SHA \
  --secret-rm database_url_old \
  --secret-add source=database_url,target=/run/secrets/database_url \
  ecommerce-${ENVIRONMENT}_app
```

## Harbor Registry Configuration
```yaml
# .harbor/project.yml
project:
  name: ecommerce
  public: false
  vulnerability_scanning: true
  
repositories:
  - name: app
    description: "E-commerce application images"
    auto_scan: true
    
policies:
  - type: retention
    rule: "keep last 10 versions"
  - type: vulnerability
    rule: "block critical vulnerabilities"
```

## Docker Swarm Deployment
```yaml
# docker-compose.production.yml
version: '3.8'
services:
  app:
    image: ${HARBOR_REGISTRY}/ecommerce/app:frankenphp-${COMMIT_SHA}
    deploy:
      replicas: 3
      update_config:
        parallelism: 1
        delay: 10s
        order: start-first
      restart_policy:
        condition: on-failure
    secrets:
      - database_url
      - stripe_secret_key
      - jwt_secret
    environment:
      - APP_ENV=prod
      
  postgres:
    image: postgres:16
    deploy:
      replicas: 1
      placement:
        constraints: [node.role == manager]
    volumes:
      - postgres_data:/var/lib/postgresql/data
    secrets:
      - postgres_password
      
secrets:
  database_url:
    external: true
  stripe_secret_key:
    external: true
  jwt_secret:
    external: true
  postgres_password:
    external: true
    
volumes:
  postgres_data:
```

## Environment-Specific Configuration

### Staging Environment
- Automated deployment on main branch
- Full test suite execution
- Performance baseline collection
- Vault secret injection

### Production Environment
- Manual approval required
- Blue-green deployment strategy
- Health checks and rollback capability
- Monitoring and alerting integration

## Quality Gates
1. **Code Quality:** PHPStan level 8, PHP-CS-Fixer compliance
2. **Security:** Symfony security checker, dependency scanning
3. **Testing:** 80% code coverage, all tests passing
4. **Performance:** No regression in baseline metrics
5. **Vulnerability:** No critical vulnerabilities in dependencies

## Monitoring Integration
- Application performance monitoring
- Error tracking and alerting
- Deployment success/failure notifications
- Performance regression detection