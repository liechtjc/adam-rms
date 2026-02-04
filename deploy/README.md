# AdamRMS Deployment Configuration

Docker Compose files for deploying AdamRMS via Portainer.

## Files

| File | Purpose | Branch |
|------|---------|--------|
| `docker-compose.dev.yml` | Development/testing stack | `dev` |
| `docker-compose.prod.yml` | Production stack | `main` |

## Portainer Setup

### 1. Create Stack in Portainer

1. Go to **Stacks** → **Add stack**
2. Choose **Repository**
3. Configure:
   - **Repository URL:** `https://github.com/liechtjc/adam-rms`
   - **Reference:** `dev` or `main` (matching your target)
   - **Compose path:** `deploy/docker-compose.dev.yml` or `deploy/docker-compose.prod.yml`

### 2. Environment Variables

Set these in Portainer's **Environment variables** section:

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DOMAIN` | Public domain for this deployment | `dev-loc.example.com` |
| `MYSQL_ROOT_PASSWORD` | MySQL root password | (generate secure password) |
| `DB_PASSWORD` | Application database password | (generate secure password) |

#### Optional Variables (have defaults)

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_DATABASE` | `adamrms` | Database name |
| `DB_USERNAME` | `adamrms` | Database user |
| `GIT_BRANCH` | `dev` or `main` | Branch to build from |
| `COMPOSE_PROJECT_NAME` | `adamrms-dev` / `adamrms` | Container name prefix |

### 3. Deploy

Click **Deploy the stack** and wait for containers to start.

## Services

### Development Stack (docker-compose.dev.yml)

| Service | URL | Description |
|---------|-----|-------------|
| AdamRMS | `https://{DOMAIN}/` | Main application |
| Mailpit | `https://{DOMAIN}/mail/` | Email testing UI |
| S3Mock | `https://{DOMAIN}/s3/` | File storage (mock) |

### Production Stack (docker-compose.prod.yml)

| Service | URL | Description |
|---------|-----|-------------|
| AdamRMS | `https://{DOMAIN}/` | Main application |

Production uses external S3 (Wasabi) configured via AdamRMS web UI.

## Database Persistence

- Database data is stored in Docker volume `db_data`
- **Updates preserve data:** Phinx migrations run automatically on container start
- **Fresh install:** Seeds populate default data

### To preserve data when updating:
Just redeploy - migrations handle schema changes without data loss.

### To reset database (destructive):
In Portainer: Stop stack → Delete volume `db_data` → Redeploy

## Updating Deployment

1. Push changes to the appropriate branch (`dev` or `main`)
2. In Portainer, go to your stack
3. Click **Pull and redeploy**
4. Check **Re-pull image** to rebuild from latest code

## Troubleshooting

### View logs
In Portainer: Stack → Container → Logs

### Database issues
- Check `adamrms-*-db` container logs for MySQL errors
- Verify environment variables match between `db` and `adamrms` services

### Build failures
- Check `adamrms-*-app` container logs
- Verify the git branch exists and Dockerfile is valid
