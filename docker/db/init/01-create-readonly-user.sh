#!/bin/bash
set -e

# Create read-only user if it doesn't exist
# Credentials are provided via environment variables:
# - POSTGRES_READONLY_USER (default: readonly)
# - POSTGRES_READONLY_PASSWORD (required)

READONLY_USER="${POSTGRES_READONLY_USER:-readonly}"
READONLY_PASSWORD="${POSTGRES_READONLY_PASSWORD}"

if [ -z "$READONLY_PASSWORD" ]; then
    echo "WARNING: POSTGRES_READONLY_PASSWORD not set. Skipping read-only user creation."
    exit 0
fi

echo "Creating read-only user: $READONLY_USER"

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    -- Create read-only user
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$READONLY_USER') THEN
            CREATE ROLE $READONLY_USER WITH LOGIN PASSWORD '$READONLY_PASSWORD';
        END IF;
    END
    \$\$;

    -- Grant CONNECT privilege on database
    GRANT CONNECT ON DATABASE $POSTGRES_DB TO $READONLY_USER;

    -- Grant USAGE on schema
    GRANT USAGE ON SCHEMA public TO $READONLY_USER;

    -- Grant SELECT on all existing tables
    GRANT SELECT ON ALL TABLES IN SCHEMA public TO $READONLY_USER;

    -- Grant SELECT on all future tables
    ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO $READONLY_USER;

    -- Grant USAGE on all sequences (for serial columns)
    GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO $READONLY_USER;

    -- Grant USAGE on all future sequences
    ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE ON SEQUENCES TO $READONLY_USER;
EOSQL

echo "Read-only user created successfully"
