#!/usr/bin/env bash
# This script runs every time the database container starts
# It handles dynamic initialization of SSL certs, config, and users

set -e

SSL_DIR="/var/lib/postgresql/ssl"
CA_CERT="$SSL_DIR/ca.crt"
CA_KEY="$SSL_DIR/ca.key"
SERVER_CERT="$SSL_DIR/server.crt"
SERVER_KEY="$SSL_DIR/server.key"

# Use the official PostgreSQL entrypoint
POSTGRES_BIN="/usr/local/bin/docker-entrypoint.sh"

# Function to generate SSL certificates
generate_ssl_certs() {
    echo "[SSL] Checking SSL certificates..."

    if [ -f "$SERVER_CERT" ] && [ -f "$SERVER_KEY" ] && [ -f "$CA_CERT" ]; then
        echo "[SSL] Certificates already exist, skipping generation."
        return 0
    fi

    echo "[SSL] WARNING: SSL certificates not found at $SSL_DIR"
    echo "[SSL] Certificates must be generated on the host machine before first start."
    echo "[SSL]"
    echo "[SSL] To generate certificates, run on your host machine:"
    echo "[SSL]"
    echo "[SSL]   mkdir -p docker/db/ssl"
    echo "[SSL]   cd docker/db/ssl"
    echo "[SSL]"
    echo "[SSL]   # Generate CA certificate"
    echo "[SSL]   openssl req -new -x509 -days 3650 -nodes -text \\"
    echo "[SSL]     -out ca.crt -keyout ca.key -subj \"/CN=PostgreSQL CA\""
    echo "[SSL]"
    echo "[SSL]   # Generate server certificate"
    echo "[SSL]   openssl req -new -nodes -text \\"
    echo "[SSL]     -out server.csr -keyout server.key -subj \"/CN=postgres\""
    echo "[SSL]"
    echo "[SSL]   # Sign server certificate"
    echo "[SSL]   openssl x509 -req -in server.csr -text -days 3650 \\"
    echo "[SSL]     -CA ca.crt -CAkey ca.key -CAcreateserial -out server.crt"
    echo "[SSL]"
    echo "[SSL]   # Set permissions"
    echo "[SSL]   chmod 600 server.key ca.key"
    echo "[SSL]   chmod 644 server.crt ca.crt"
    echo "[SSL]"
    echo "[SSL]   # Cleanup"
    echo "[SSL]   rm -f server.csr ca.srl"
    echo "[SSL]   cd ../../.."
    echo "[SSL]"
    echo "[SSL] After generating certificates, restart the container:"
    echo "[SSL]   docker compose restart database"
    echo "[SSL]"

    # Exit with error - container cannot proceed without SSL certs
    exit 1
}

# Function to create init script for read-only user
create_readonly_init_script() {
    local readonly_user="${POSTGRES_READONLY_USER:-readonly}"
    local readonly_password="${POSTGRES_READONLY_PASSWORD}"
    # Path to init scripts that will run when postgres starts
    local init_script_dir="/docker-entrypoint-initdb.d"

    if [ -z "$readonly_password" ]; then
        echo "[DB] WARNING: POSTGRES_READONLY_PASSWORD not set. Skipping read-only user setup."
        return 0
    fi

    # Create init script directory if it doesn't exist
    mkdir -p "$init_script_dir"

    # Create SQL init script for read-only user
    cat > "$init_script_dir/01-create-readonly-user.sql" <<-EOSQL
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
EOSQL

    echo "[DB] Created read-only user init script"
}

# Main execution
echo "===== PostgreSQL Startup Script ====="
echo "Date: $(date)"
echo ""

# Generate SSL certificates if missing
generate_ssl_certs

echo ""

# Create init script for read-only user
create_readonly_init_script

echo ""
echo "[STARTUP] Starting PostgreSQL via official entrypoint..."

# Pass control to the official PostgreSQL entrypoint
# It will become PID 1 and handle all the PostgreSQL logic
exec "$POSTGRES_BIN"
