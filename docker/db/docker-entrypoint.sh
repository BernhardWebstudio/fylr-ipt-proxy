#!/usr/bin/env bash
# This script runs every time the database container starts
# It handles dynamic initialization of SSL certs, config, and users

set -e

SSL_DIR="/var/lib/postgresql/ssl"
CA_CERT="$SSL_DIR/ca.crt"
CA_KEY="$SSL_DIR/ca.key"
SERVER_CERT="$SSL_DIR/server.crt"
SERVER_KEY="$SSL_DIR/server.key"

POSTGRES_BIN="/usr/local/bin/docker-entrypoint.sh"

# Function to wait for PostgreSQL to be ready
wait_for_postgres() {
    local max_attempts=30
    local attempt=0

    echo "Waiting for PostgreSQL to be ready..."
    while [ $attempt -lt $max_attempts ]; do
        if pg_isready -h localhost -U "$POSTGRES_USER" -d "$POSTGRES_DB" 2>/dev/null; then
            echo "PostgreSQL is ready"
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 1
    done

    echo "WARNING: PostgreSQL did not become ready within timeout"
    return 1
}

# Function to generate SSL certificates
generate_ssl_certs() {
    echo "[SSL] Checking SSL certificates..."

    if [ -f "$SERVER_CERT" ] && [ -f "$SERVER_KEY" ] && [ -f "$CA_CERT" ]; then
        echo "[SSL] Certificates already exist, skipping generation."
        return 0
    fi

    echo "[SSL] Certificates not found. Generating new certificates..."
    mkdir -p "$SSL_DIR"

    # Generate CA certificate (10 year validity)
    echo "[SSL] Generating CA certificate..."
    openssl req -new -x509 -days 3650 -nodes -text \
        -out "$CA_CERT" \
        -keyout "$CA_KEY" \
        -subj "/CN=PostgreSQL CA" 2>&1 | grep -v "^$"

    # Generate server certificate request
    echo "[SSL] Generating server certificate request..."
    openssl req -new -nodes -text \
        -out "$SSL_DIR/server.csr" \
        -keyout "$SERVER_KEY" \
        -subj "/CN=postgres" 2>&1 | grep -v "^$"

    # Sign server certificate with CA (10 year validity)
    echo "[SSL] Signing server certificate..."
    openssl x509 -req -in "$SSL_DIR/server.csr" -text -days 3650 \
        -CA "$CA_CERT" -CAkey "$CA_KEY" -CAcreateserial \
        -out "$SERVER_CERT" 2>&1 | grep -v "^$" | grep -v "^Signature ok"

    # Set proper permissions (required by PostgreSQL)
    chmod 600 "$SERVER_KEY" "$CA_KEY"
    chmod 644 "$SERVER_CERT" "$CA_CERT"

    # Clean up
    rm -f "$SSL_DIR/server.csr" "$SSL_DIR/ca.srl"

    echo "[SSL] SSL certificates generated successfully"
}

# Function to setup read-only user and permissions
setup_readonly_user() {
    local readonly_user="${POSTGRES_READONLY_USER:-readonly}"
    local readonly_password="${POSTGRES_READONLY_PASSWORD}"

    if [ -z "$readonly_password" ]; then
        echo "[DB] WARNING: POSTGRES_READONLY_PASSWORD not set. Skipping read-only user setup."
        return 0
    fi

    echo "[DB] Setting up read-only user: $readonly_user"

    # Create or update read-only user
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
        -- Create read-only user if it doesn't exist
        DO \$\$
        BEGIN
            IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$readonly_user') THEN
                CREATE ROLE $readonly_user WITH LOGIN PASSWORD '$readonly_password';
                RAISE NOTICE 'Created read-only user: $readonly_user';
            ELSE
                ALTER ROLE $readonly_user WITH PASSWORD '$readonly_password';
                RAISE NOTICE 'Updated password for read-only user: $readonly_user';
            END IF;
        END
        \$\$;

        -- Ensure user can connect
        GRANT CONNECT ON DATABASE $POSTGRES_DB TO $readonly_user;

        -- Grant permissions on schema
        GRANT USAGE ON SCHEMA public TO $readonly_user;

        -- Grant SELECT on all existing tables
        GRANT SELECT ON ALL TABLES IN SCHEMA public TO $readonly_user;

        -- Grant SELECT on all future tables
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO $readonly_user;

        -- Grant USAGE on all sequences (for serial columns)
        GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO $readonly_user;

        -- Grant USAGE on all future sequences
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE ON SEQUENCES TO $readonly_user;

        -- Verify permissions
        SELECT rolname, rolcanlogin, array_agg(privilege) as privileges
        FROM (
            SELECT rolname, rolcanlogin, 'CONNECT' as privilege FROM pg_roles WHERE rolname = '$readonly_user'
        ) AS perms
        GROUP BY rolname, rolcanlogin;
EOSQL

    echo "[DB] Read-only user setup complete"
}

# Main execution
echo "===== PostgreSQL Startup Script ====="
echo "Date: $(date)"
echo ""

# Generate SSL certificates if missing
generate_ssl_certs

echo ""

# Start PostgreSQL in background
echo "[STARTUP] Starting PostgreSQL..."
exec "$POSTGRES_BIN" &
POSTGRES_PID=$!

# Wait for PostgreSQL to be ready
if wait_for_postgres; then
    echo ""
    # Setup read-only user
    setup_readonly_user
    echo ""
    echo "[STARTUP] Database initialization complete"
else
    echo "[STARTUP] WARNING: Could not verify PostgreSQL readiness"
fi

echo "===== PostgreSQL Ready ====="
echo ""

# Wait for the PostgreSQL process
wait $POSTGRES_PID
