#!/usr/bin/env bash
set -e

SSL_DIR="/var/lib/postgresql/ssl"
CA_CERT="$SSL_DIR/ca.crt"
CA_KEY="$SSL_DIR/ca.key"
SERVER_CERT="$SSL_DIR/server.crt"
SERVER_KEY="$SSL_DIR/server.key"

echo "Checking SSL certificates..."

# Check if certificates already exist
if [ -f "$SERVER_CERT" ] && [ -f "$SERVER_KEY" ] && [ -f "$CA_CERT" ]; then
    echo "SSL certificates already exist, skipping generation."
    exit 0
fi

echo "SSL certificates not found. Generating new certificates..."

# Create SSL directory if it doesn't exist
mkdir -p "$SSL_DIR"

# Generate CA certificate (10 year validity)
echo "Generating CA certificate..."
openssl req -new -x509 -days 3650 -nodes -text \
    -out "$CA_CERT" \
    -keyout "$CA_KEY" \
    -subj "/CN=PostgreSQL CA"

# Generate server certificate request
echo "Generating server certificate request..."
openssl req -new -nodes -text \
    -out "$SSL_DIR/server.csr" \
    -keyout "$SERVER_KEY" \
    -subj "/CN=postgres"

# Sign server certificate with CA (10 year validity)
echo "Signing server certificate..."
openssl x509 -req -in "$SSL_DIR/server.csr" -text -days 3650 \
    -CA "$CA_CERT" -CAkey "$CA_KEY" -CAcreateserial \
    -out "$SERVER_CERT"

# Set proper permissions (required by PostgreSQL)
chmod 600 "$SERVER_KEY" "$CA_KEY"
chmod 644 "$SERVER_CERT" "$CA_CERT"

# Clean up
rm -f "$SSL_DIR/server.csr" "$SSL_DIR/ca.srl"

echo "SSL certificates generated successfully:"
echo "  - CA Certificate: $CA_CERT"
echo "  - Server Certificate: $SERVER_CERT"
echo "  - Server Key: $SERVER_KEY"
echo ""
echo "Note: To use certificate verification from clients, copy ca.crt from the ssl directory."
