-- Initialize PostgreSQL database for Symfony e-commerce
-- This script runs when the container is first created

-- Create test database
CREATE DATABASE ecommerce_test;

-- Enable necessary extensions
\c ecommerce;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

\c ecommerce_test;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Grant permissions
GRANT ALL PRIVILEGES ON DATABASE ecommerce TO postgres;
GRANT ALL PRIVILEGES ON DATABASE ecommerce_test TO postgres;