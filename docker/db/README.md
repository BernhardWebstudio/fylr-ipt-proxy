# PostgreSQL External Access Configuration

This directory contains the configuration for enabling secure, read-only external access to the PostgreSQL database.

## Overview

The PostgreSQL container is configured to:
- Accept external connections on port 5432 (configurable via `POSTGRES_PORT`)
- Require SSL encryption for all external connections
- Provide a read-only user account with SELECT privileges only
- Use certificate-based SSL authentication
- **Automatically initialize and update all components** (SSL certs, config, users) on every start

## Quick Setup

### 1. Generate SSL Certificates (Host Machine)

Before starting the database for the first time, generate SSL certificates on your host machine:

```bash
mkdir -p docker/db/ssl
cd docker/db/ssl

# Generate CA certificate (10 year validity)
openssl req -new -x509 -days 3650 -nodes -text \
  -out ca.crt -keyout ca.key \
  -subj "/CN=PostgreSQL CA"

# Generate server certificate request
openssl req -new -nodes -text \
  -out server.csr -keyout server.key \
  -subj "/CN=postgres"

# Sign server certificate with CA (10 year validity)
openssl x509 -req -in server.csr -text -days 3650 \
  -CA ca.crt -CAkey ca.key -CAcreateserial \
  -out server.crt

# Set proper permissions (required by PostgreSQL)
chmod 600 server.key ca.key
chmod 644 server.crt ca.crt

# Cleanup temporary files
rm -f server.csr ca.srl

cd ../../..
```

⚠️ **Important**: This must be done **before first start** with an existing database. The container will fail to start without these certificates.

### 2. Configure Read-Only User

Set the read-only user credentials in `.env.local`:

```bash
# Add to .env.local
POSTGRES_READONLY_USER=readonly
POSTGRES_READONLY_PASSWORD=your_secure_password_here
```

⚠️ **Important**: Choose a strong password for production use.

### 3. Configure External Port (Optional)

By default, PostgreSQL is exposed on port 5432. To use a different port:

```bash
# Add to .env.local
POSTGRES_PORT=15432
```

### 4. Start (or Restart) the Database

```bash
docker compose up -d database
```

On every start, the startup script will automatically:
- Configure SSL and authentication settings
- Create the read-only user (if it doesn't exist)
- Update the read-only user password (if it already exists)
- Apply all permissions

**This works with existing databases** - no data will be lost.

## How It Works

This setup uses a custom entrypoint that runs on **every container start**. This allows:

- ✅ Existing databases to be enhanced with SSL and read-only user automatically
- ✅ Password updates without manual SQL commands
- ✅ Idempotent setup - safe to restart containers
- ✅ Configuration changes to take effect on restart

## SSL Certificates

SSL certificates are **required** and must be generated on your host machine before the first start (see Quick Setup step 1).

On every container start, the startup script will:
- Detect the existing certificates in `docker/db/ssl/`
- Configure PostgreSQL to use them
- Fail safely if certificates are missing (with helpful error instructions)

## Connecting from External Clients

### Connection Parameters

- **Host**: Your server's IP address or hostname
- **Port**: 5432 (or your custom `POSTGRES_PORT`)
- **Database**: Value of `POSTGRES_DB` (default: `app`)
- **User**: Value of `POSTGRES_READONLY_USER` (default: `readonly`)
- **Password**: Value of `POSTGRES_READONLY_PASSWORD`
- **SSL Mode**: `require` or `verify-ca`

### Connection String Example

```bash
# PostgreSQL standard connection string
postgresql://readonly:your_password@your-server:5432/app?sslmode=require

# JDBC connection string (for IPT and other Java applications)
jdbc:postgresql://your-server:5432/app?user=readonly&password=your_password&ssl=true&sslmode=require
```

### JDBC Configuration for IPT

When configuring IPT (Integrated Publishing Toolkit) to connect to this database:

1. **Driver**: `org.postgresql.Driver`
2. **URL**: `jdbc:postgresql://your-server:5432/app?ssl=true&sslmode=require`
3. **Username**: `readonly`
4. **Password**: Your `POSTGRES_READONLY_PASSWORD`

IPT JDBC URL parameters:
- `ssl=true` - Enable SSL/TLS encryption
- `sslmode=require` - Require encrypted connection
- `sslmode=verify-ca` - Additionally verify server certificate (requires CA cert)
- `ApplicationName=IPT` - (Optional) Identify the connection in PostgreSQL logs

### Using psql

```bash
# With SSL verification
psql "host=your-server port=5432 dbname=app user=readonly password=your_password sslmode=require sslrootcert=docker/db/ssl/ca.crt"

# Simple connection (requires SSL but doesn't verify certificate)
PGPASSWORD=your_password psql -h your-server -p 5432 -U readonly -d app
```

### Using DBeaver/DataGrip

1. Create a new PostgreSQL connection
2. Enter the connection details above
3. In the SSL tab, select "Require SSL"
4. Optionally, provide the `ca.crt` file for certificate verification (copy from `docker/db/ssl/ca.crt`)

## Security Considerations

### SSL Certificate Distribution

- **Server certificate** (`server.crt`, `server.key`): Stays on the server
- **CA certificate** (`ca.crt`): Copy from `docker/db/ssl/ca.crt` and distribute to clients that want to verify server identity
- Clients can connect with `sslmode=require` (encrypted) or `sslmode=verify-ca` (encrypted + verified)

To retrieve the CA certificate from the running container:
```bash
docker compose cp database:/var/lib/postgresql/ssl/ca.crt ./ca.crt
```

### Read-Only User Permissions

The read-only user has:
- ✅ `SELECT` on all tables (current and future)
- ✅ `USAGE` on sequences (to view ID values)
- ❌ No `INSERT`, `UPDATE`, `DELETE`, or `TRUNCATE` permissions
- ❌ No schema modification permissions

### Firewall Configuration

Ensure your firewall allows connections on the PostgreSQL port:

```bash
# Example for Ubuntu/Debian with ufw
sudo ufw allow 5432/tcp

# Example for CentOS/RHEL with firewalld
sudo firewall-cmd --add-port=5432/tcp --permanent
sudo firewall-cmd --reload
```

### Admin Access from External IPs

By default, the admin user (`app`) can only connect from the Docker network. To allow admin access from specific IPs:

1. Edit `docker/db/conf/pg_hba.conf`
2. Uncomment and configure the admin access line:
   ```
   hostssl all app YOUR_IP_ADDRESS/32 scram-sha-256
   ```
3. Restart the database: `docker compose restart database`

## Troubleshooting

### IPT Connection Issues

**Error**: `Communications link failure` with `com.mysql.cj.jdbc.Driver`

**Problem**: IPT is configured with the wrong JDBC driver for PostgreSQL.

**Solution**:
1. In IPT configuration, change the driver to: `org.postgresql.Driver`
2. Change the JDBC URL from `jdbc:mysql://...` to `jdbc:postgresql://...`
3. Ensure PostgreSQL JDBC driver is available in IPT's classpath
4. Complete JDBC URL: `jdbc:postgresql://your-server:5432/app?ssl=true&sslmode=require`

### Testing Database Connectivity

From the host machine:
```bash
# Test if port is reachable
nc -zv your-server 5432

# Test PostgreSQL connection (requires psql client)
PGPASSWORD=your_password psql -h your-server -p 5432 -U readonly -d app -c "SELECT version();"
```

From the IPT server:
```bash
# Test if port is open from IPT's location
telnet your-server 5432
# or
nc -zv your-server 5432

# Check if PostgreSQL JDBC driver is available
# (location depends on IPT installation)
ls -la /path/to/ipt/WEB-INF/lib/ | grep postgresql
```

### Permission Denied on server.key

If you see "permission denied" errors related to `server.key`:

```bash
chmod 600 docker/db/ssl/server.key
```

### Connection Refused

1. Check if the database is running: `docker compose ps database`
2. Verify port is exposed: `docker compose port database 5432`
3. Check firewall rules on the host machine
4. Verify the port isn't already in use: `netstat -tuln | grep 5432`
5. Test from IPT server: `nc -zv your-server 5432`

### SSL Connection Error

1. Ensure SSL certificates exist in `docker/db/ssl/` - generate them on your host if missing (see Quick Setup step 1)
2. Verify certificate permissions: `chmod 600 docker/db/ssl/server.key`
3. Check PostgreSQL logs: `docker compose logs database | grep SSL`
4. For JDBC, ensure URL includes `ssl=true&sslmode=require`

### Read-Only User Can't Connect

1. Verify the user was created: 
   ```bash
   docker compose exec database psql -U app -d app -c "\du readonly"
   ```
2. Check if password was set in `.env.local`
3. Review authentication logs: `docker compose logs database | grep readonly`
4. Verify `pg_hba.conf` allows SSL connections from external hosts

## Files in this Directory

- `docker-entrypoint.sh` - Custom startup script (runs on every container start)
- `conf/custom-postgresql.conf` - SSL and connection settings
- `conf/pg_hba.conf` - Host-based authentication rules
- `ssl/` - SSL certificates (auto-generated if missing)
- `data/` - PostgreSQL data directory
- `init/` - (Deprecated) Legacy init scripts - no longer used

## Resetting the Database

To completely reset the database (⚠️ **deletes all data**):

```bash
docker compose down database
rm -rf docker/db/data/*
docker compose up -d database
```

The read-only user and SSL certificates will be recreated automatically.

## Upgrading from Manual SSL Setup

If you previously generated SSL certificates manually, the new setup will:
1. Detect your existing certificates in `docker/db/ssl/`
2. Reuse them automatically
3. Continue to work as before

No migration needed - just ensure certificates are in `docker/db/ssl/` and restart the container with the new setup.
