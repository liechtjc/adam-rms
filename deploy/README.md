# AdamRMS Deployment Configuration

Docker Compose files for deploying AdamRMS via Portainer.

## Files

| File | Purpose | Branch |
|------|---------|--------|
| `docker-compose.stage.yml` | Staging/testing stack | `stage` |
| `docker-compose.prod.yml` | Production stack | `production` |

## Portainer Setup

### 1. Create Stack in Portainer

1. Go to **Stacks** → **Add stack**
2. Choose **Repository**
3. Configure:
   - **Repository URL:** `https://github.com/liechtjc/adam-rms`
   - **Reference:** `refs/heads/stage` or `refs/heads/production` (matching your target)
   - **Compose path:** `deploy/docker-compose.stage.yml` or `deploy/docker-compose.prod.yml`

### 2. Environment Variables

Set these in Portainer's **Environment variables** section:

#### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DOMAIN` | Public domain for this deployment | `staging.example.com` |
| `MYSQL_ROOT_PASSWORD` | MySQL root password | (generate secure password) |
| `DB_PASSWORD` | Application database password | (generate secure password) |
| `CONFIG_ROOTURL` | Full HTTPS URL (prevents mixed content behind Traefik) | `https://staging.example.com` |

#### Optional Variables (have defaults)

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_DATABASE` | `adamrms` | Database name |
| `DB_USERNAME` | `adamrms` | Database user |
| `GIT_BRANCH` | `stage` or `production` | Branch to build from |
| `COMPOSE_PROJECT_NAME` | `adamrms-stage` / `adamrms-prod` | Container name prefix |
| `MAILPIT_AUTH` | `admin:changeme` | Mailpit UI credentials (stage stack only, format: `user:password`) |

### 3. Deploy

Click **Deploy the stack** and wait for containers to start.

## Services

### Staging Stack (docker-compose.stage.yml)

| Service | URL | Description |
|---------|-----|-------------|
| AdamRMS | `https://{DOMAIN}/` | Main application |
| Mailpit | `https://{DOMAIN}/mail/` | Email testing UI (protected by `MAILPIT_AUTH`) |
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

1. Push changes to the appropriate branch (`stage` or `production`)
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
