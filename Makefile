# Symfony Headless E-commerce Makefile
.PHONY: help setup build start stop test load-test clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Set up the project (install dependencies, create directories)
	@echo "Setting up Symfony Headless E-commerce project..."
	@mkdir -p var/cache var/log config/jwt performance-results docker/traefik/certs
	@chmod +x scripts/run-load-test.sh scripts/generate-certs.sh || true
	@echo "Generating SSL certificates for local development..."
	@./scripts/generate-certs.sh
	@echo "Creating Docker network..."
	@docker network create ecommerce-network || true
	@echo "Project structure created successfully!"

regenerate-certs: ## Regenerate SSL certificates
	@echo "Regenerating SSL certificates..."
	@rm -f docker/traefik/certs/ecommerce.localhost.*
	@./scripts/generate-certs.sh
	@echo "Certificates regenerated. Restart services with 'make restart'"

restart: ## Restart all services
	@echo "Restarting all services..."
	@make stop
	@make start

restart-traditional: ## Restart only traditional phase services
	@echo "Restarting traditional phase services..."
	docker-compose -f docker-compose.traditional.yml up -d --force-recreate

fix-composer: ## Fix Composer issues (git ownership, install dependencies)
	@echo "Fixing Composer issues..."
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional git config --global --add safe.directory /var/www/html
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional composer install --optimize-autoloader --no-scripts
	@echo "Composer issues fixed!"

clear-cache: ## Clear Symfony cache manually (without console command)
	@echo "Clearing Symfony cache..."
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional rm -rf var/cache/*
	@echo "Cache cleared!"

warm-cache: ## Clear and warm Symfony cache (requires rate limiter)
	@echo "Clearing and warming Symfony cache..."
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional rm -rf var/cache/*
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional php bin/console cache:clear --no-warmup
	@echo "Cache cleared and warmed!"

build: ## Build Docker containers for Phase 1 (Traditional)
	@echo "Building Docker containers for Phase 1 (Traditional stack)..."
	@echo "Using USER_ID=$(shell id -u) and GROUP_ID=$(shell id -g)"
	@echo "Note: composer.lock will be created during build if it doesn't exist"
	docker-compose -f docker-compose.yml up -d --build
	USER_ID=$(shell id -u) GROUP_ID=$(shell id -g) docker-compose -f docker-compose.traditional.yml build --no-cache

start: ## Start Phase 1 development environment
	@echo "Starting shared services (Traefik, PostgreSQL, Redis)..."
	docker-compose -f docker-compose.yml up -d
	@echo "Starting Phase 1 (Traditional) development environment..."
	@echo "Using USER_ID=$(shell id -u) and GROUP_ID=$(shell id -g)"
	USER_ID=$(shell id -u) GROUP_ID=$(shell id -g) docker-compose -f docker-compose.traditional.yml up -d
	@echo "Environment started!"
	@echo "Traditional Phase: https://traditional.ecommerce.localhost"
	@echo "Traefik Dashboard: https://traefik.ecommerce.localhost"
	@echo ""
	@echo "Note: Accept the self-signed certificate in your browser for local development"

stop: ## Stop all Docker containers
	@echo "Stopping all containers..."
	docker-compose -f docker-compose.traditional.yml down
	docker-compose -f docker-compose.yml down

test: ## Run PHPUnit tests
	@echo "Running tests..."
	docker-compose -f docker-compose.traditional.yml exec php-fpm vendor/bin/phpunit

load-test: ## Run load test against Phase 1
	@echo "Running load test against Phase 1..."
	@mkdir -p performance-results
	./scripts/run-load-test.sh phase-1-traditional https://traditional.ecommerce.localhost

clean: ## Clean up Docker containers and volumes
	@echo "Cleaning up..."
	docker-compose -f docker-compose.traditional.yml down -v
	docker-compose -f docker-compose.yml down -v
	docker network rm ecommerce-network || true
	docker system prune -f

install: ## Install Composer dependencies (run inside container)
	@echo "Installing Composer dependencies..."
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional composer install --optimize-autoloader
	@echo "Dependencies installed successfully!"

composer-update: ## Update Composer dependencies and create lock file
	@echo "Updating Composer dependencies..."
	docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional composer update --lock
	@echo "Dependencies updated and lock file created!"

install-package: ## Install a Composer package (usage: make install-package package=symfony/rate-limiter)
	@if [ -z "$(package)" ]; then \
		echo "Error: Please specify a package. Usage: make install-package package=symfony/rate-limiter"; \
		exit 1; \
	fi
	@echo "Installing package: $(package)..."
	USER_ID=$(shell id -u) GROUP_ID=$(shell id -g) docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional composer require $(package) --no-scripts
	@echo "Package $(package) installed successfully!"

fix-permissions: ## Fix file permissions for development
	@echo "Fixing file permissions..."
	@echo "Setting ownership to $(shell id -u):$(shell id -g)"
	sudo chown -R $(shell id -u):$(shell id -g) .
	@echo "Creating cache and log directories with correct permissions..."
	mkdir -p var/cache var/log
	chmod -R 775 var/
	@echo "Permissions fixed!"

rebuild: ## Rebuild containers with correct permissions and test
	@echo "Rebuilding containers with correct user permissions..."
	make fix-permissions
	make build
	make start
	@echo "Testing cache clearing..."
	make clear-cache
	@echo "Rebuild complete!"

# Code Quality Commands
cs-fix: ## Fix code style with PHP-CS-Fixer
	@echo "Fixing code style with PHP-CS-Fixer..."
	USER_ID=$(shell id -u) GROUP_ID=$(shell id -g) docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php -v
	@echo "Code style fixed!"

cs-check: ## Check code style with PHP-CS-Fixer (dry-run)
	@echo "Checking code style with PHP-CS-Fixer..."
	USER_ID=$(shell id -u) GROUP_ID=$(shell id -g) docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run -v
	@echo "Code style check complete!"

phpstan: ## Run PHPStan static analysis
	@echo "Running PHPStan static analysis..."
	USER_ID=$(shell id -u) GROUP_ID=$(shell id -g) docker-compose -f docker-compose.traditional.yml exec php-fpm-traditional vendor/bin/phpstan analyse --configuration=phpstan.neon
	@echo "Static analysis complete!"

quality: ## Run all code quality checks
	@echo "Running all code quality checks..."
	make cs-check
	make phpstan
	@echo "All quality checks complete!"

quality-fix: ## Fix all code quality issues
	@echo "Fixing all code quality issues..."
	make cs-fix
	@echo "All quality issues fixed!"

migrate: ## Run database migrations
	docker-compose -f docker-compose.traditional.yml exec php-fpm bin/console doctrine:migrations:migrate --no-interaction

db-create: ## Create database
	docker-compose -f docker-compose.traditional.yml exec php-fpm bin/console doctrine:database:create --if-not-exists

db-reset: ## Reset database (drop, create, migrate)
	docker-compose -f docker-compose.traditional.yml exec php-fpm bin/console doctrine:database:drop --force --if-exists
	docker-compose -f docker-compose.traditional.yml exec php-fpm bin/console doctrine:database:create
	docker-compose -f docker-compose.traditional.yml exec php-fpm bin/console doctrine:migrations:migrate --no-interaction