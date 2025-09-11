# Performance Optimization Guide

## Intentional Performance Issues (Phase 1)
When implementing the initial version, include these performance bottlenecks for demonstration:

### Database Issues
- Missing indexes on frequently queried fields
- N+1 query problems in product listings
- No JSON indexing on JSONB fields initially
- Inefficient relationship loading (lazy vs eager)
- Basic queries without proper joins

### Application Issues
- No caching layer implementation
- Inefficient serialization without custom normalizers
- Process-per-request overhead (PHP-FPM)
- Cold start penalties for each request
- Memory allocation/deallocation per request

### Expected Phase 1 Performance
- Response Time: 800ms - 2000ms
- Memory Usage: 32MB per request
- Concurrent Users: 50-100 users
- Requests/Second: 50-100 RPS

## Optimization Phases

### Phase 2: Infrastructure Optimization (FrankenPHP)
- Switch to FrankenPHP Worker Mode
- Persistent PHP processes (no cold starts)
- Shared memory between requests
- Connection pooling benefits
- Expected 60-75% response time improvement

### Phase 3: Code + Infrastructure
- Add database indexes and query optimization
- Implement Redis caching
- Optimize Doctrine queries with proper joins
- Custom serialization normalizers
- Expected 90%+ improvement from Phase 1

### Phase 4: HTTP Caching (Varnish)
- HTTP-level caching with Varnish
- Cache invalidation strategies
- ESI for dynamic content
- Expected 95%+ improvement from Phase 1

### Phase 5+: Search Optimization (OpenSearch)
- Product search with OpenSearch
- Fuzzy search and NLP
- Search analytics dashboard
- Search-first architecture for listings

## Phase-Based Performance Monitoring

### Metrics Collection with Phase Tracking
```php
class PerformanceMiddleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $handler->handle($request);
        
        // Log metrics with phase information for comparison
        $this->metricsLogger->log([
            'phase' => $this->getCurrentPhase(), // phase-1-traditional, phase-2-frankenphp, etc.
            'endpoint' => $request->getPathInfo(),
            'execution_time' => microtime(true) - $startTime,
            'memory_usage' => memory_get_usage() - $startMemory,
            'query_count' => $this->queryCounter->getCount(),
            'infrastructure' => $this->getInfrastructureType(), // 'php-fpm', 'frankenphp-worker', etc.
            'timestamp' => time()
        ]);
        
        return $response;
    }
    
    private function getCurrentPhase(): string
    {
        // Detect phase from environment or git branch
        return $_ENV['DEPLOYMENT_PHASE'] ?? 'development';
    }
    
    private function getInfrastructureType(): string
    {
        return isset($_SERVER['FRANKENPHP_VERSION']) ? 'frankenphp-worker' : 'php-fpm';
    }
}
```

### Load Testing Configuration
- Use Artillery.io for consistent load testing
- Separate test environments for each phase
- Disable rate limiting during performance tests
- Clean state reset between test runs
- Document performance improvements with real metrics

## Blog Documentation Strategy
Each optimization phase should be documented with:
1. Technical explanation of changes
2. Before/after performance metrics
3. Code examples showing improvements
4. Infrastructure configuration details
5. Real-world applicability and best practices

## Key Performance Indicators
- Response time reduction percentages
- Memory usage optimization
- Concurrent user capacity increases
- Requests per second improvements
- Cache hit ratios (Phase 4+)
- Search query performance (Phase 5+)##
 Phase Comparison Strategy

### Automated Performance Comparison
```bash
#!/bin/bash
# scripts/compare-phases.sh

PHASES=("phase-1-traditional" "phase-2-frankenphp" "phase-3-optimization")
RESULTS_DIR="performance-results/$(date +%Y%m%d-%H%M%S)"
mkdir -p $RESULTS_DIR

for phase in "${PHASES[@]}"; do
    echo "Testing $phase..."
    
    # Deploy phase
    git checkout $phase
    ./scripts/deploy-phase.sh $phase
    
    # Wait for startup
    sleep 30
    
    # Run load test
    artillery run load-tests/baseline.yml \
        --target "http://localhost:808${phase: -1}" \
        --output "$RESULTS_DIR/$phase.json"
    
    # Generate report
    artillery report "$RESULTS_DIR/$phase.json" \
        --output "$RESULTS_DIR/$phase.html"
done

# Generate comparison report
python scripts/generate-comparison.py $RESULTS_DIR
```

### Performance Regression Detection
```php
class PerformanceRegressionDetector
{
    public function detectRegression(string $currentPhase, string $previousPhase): array
    {
        $currentMetrics = $this->getPhaseMetrics($currentPhase);
        $previousMetrics = $this->getPhaseMetrics($previousPhase);
        
        $regressions = [];
        
        // Check for performance regressions
        if ($currentMetrics['avg_response_time'] > $previousMetrics['avg_response_time'] * 1.1) {
            $regressions[] = 'Response time regression detected';
        }
        
        if ($currentMetrics['avg_memory_usage'] > $previousMetrics['avg_memory_usage'] * 1.2) {
            $regressions[] = 'Memory usage regression detected';
        }
        
        return $regressions;
    }
}
```

## Branch-Specific Performance Targets

### Phase 1: Traditional (Baseline)
- **Target**: Establish baseline metrics
- **Expected**: 800ms-2000ms response time
- **Focus**: Intentional bottlenecks for demonstration

### Phase 2: FrankenPHP Infrastructure
- **Target**: 60-75% response time improvement
- **Expected**: 200ms-500ms response time
- **Focus**: Infrastructure optimization only

### Phase 3: Code + Infrastructure
- **Target**: 90%+ improvement from Phase 1
- **Expected**: 50ms-150ms response time
- **Focus**: Database optimization, caching

### Phase 4: HTTP Caching
- **Target**: 95%+ improvement from Phase 1
- **Expected**: 10ms-50ms response time
- **Focus**: Varnish cache hit ratios

### Phase 5+: Search Optimization
- **Target**: Sub-20ms search responses
- **Expected**: 5ms-20ms search time
- **Focus**: OpenSearch performance