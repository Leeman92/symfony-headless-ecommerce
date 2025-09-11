#!/bin/bash

# Load testing script for performance comparison between phases
set -e

PHASE=${1:-"phase-1-traditional"}
TARGET=${2:-"https://traditional.ecommerce.localhost"}
OUTPUT_DIR="performance-results/$(date +%Y%m%d-%H%M%S)"

echo "Running load test for $PHASE..."
echo "Target: $TARGET"
echo "Output directory: $OUTPUT_DIR"

# Create output directory
mkdir -p "$OUTPUT_DIR"

# Run Artillery load test
docker run --rm \
  --network host \
  -v "$(pwd)/load-tests:/tests" \
  -v "$(pwd)/$OUTPUT_DIR:/output" \
  artilleryio/artillery:latest \
  run /tests/baseline.yml \
  --target "$TARGET" \
  --output "/output/$PHASE.json"

# Generate HTML report
docker run --rm \
  -v "$(pwd)/$OUTPUT_DIR:/output" \
  artilleryio/artillery:latest \
  report "/output/$PHASE.json" \
  --output "/output/$PHASE.html"

echo "Load test completed. Results saved to $OUTPUT_DIR"
echo "View HTML report: $OUTPUT_DIR/$PHASE.html"