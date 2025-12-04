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

### 1. Configure Read-Only User

Set the read-only user credentials in `.env.local`:

```bash
# Add to .env.local
POSTGRES_READONLY_USER=readonly
POSTGRES_READONLY_PASSWORD=your_secure_password_here
```

⚠️ **Important**: Choose a strong password for production use.

### 2. Configure External Port (Optional)

By default, PostgreSQL is exposed on port 5432. To use a different port:

```bash
# Add to .env.local
POSTGRES_PORT=15432
```

### 3. Start (or Restart) the Database

```bash
docker compose up -d database
```

On every start, the startup script will automatically:
- Generate SSL certificates (if they don't exist)
- Configure SSL and authentication settings
- Create the read-only user (if it doesn't exist)
- Update the read-only user password (if it already exists)
- Apply all permissions

**This works with existing databases** - no data will be lost.

## How It Works

Unlike typical PostgreSQL Docker images that only run init scripts on first start, this setup uses a custom entrypoint that runs on **every container start**. This allows:

- ✅ Existing databases to be enhanced with SSL and read-only user automatically
- ✅ Password updates without manual SQL commands
- ✅ Idempotent setup - safe to restart containers
- ✅ Configuration changes to take effect on restart

#### SSL Certificates

SSL certificates are **automatically generated** on first container start if they don't exist in `docker/db/ssl/`. The generated certificates include:
- `ca.crt` - CA certificate (distribute to clients for verification)
- `ca.key` - CA private key (kept on server)
- `server.crt` - Server certificate
- `server.key` - Server private key

Subsequent container starts will detect and reuse the existing certificates.

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
postgresql://readonly:your_password@your-server:5432/app?sslmode=require
```

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

### Permission Denied on server.key

If you see "permission denied" errors related to `server.key`:

```bash
chmod 600 docker/db/ssl/server.key
```

### Connection Refused

1. Check if the database is running: `docker compose ps database`
2. Verify port is exposed: `docker compose port database 5432`
3. Check firewall rules
4. Verify the port isn't already in use: `netstat -tuln | grep 5432`

### SSL Connection Error

1. Ensure SSL certificates are properly generated
2. Verify certificate permissions (600 for .key files)
3. Check PostgreSQL logs: `docker compose logs database`

### Read-Only User Can't Connect

1. Verify the user was created: 
   ```bash
   docker compose exec database psql -U app -d app -c "\du readonly"
   ```
2. Check if password was set in `.env.local`
3. Review authentication logs: `docker compose logs database | grep readonly`

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
2. Reuse them automatically (no regeneration)
3. Continue to work as before

No migration needed - just restart the container with the new setup.
