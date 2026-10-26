# infokobuleti runbook

Everything here runs on the VPS unless it says "on the PC". The VPS is reached from the owner's PC
with `ssh hostinger-vps`. Secrets live in `/root/infokobuleti-secrets.env` (root only) and in each
site's `shared/.env`. Never commit either.

## Layout

| | Staging | Production |
|---|---|---|
| Address | https://dev.infokobuleti.com (password) | https://infokobuleti.com |
| Folder | `/var/www/infokobuleti-staging` | `/var/www/infokobuleti-production` |
| Database | `infokobuleti` | `infokobuleti_production` (role `infokobuleti_prod`) |
| Redis DBs | 0 / 1 | 2 / 3 (prefixes `infokobuleti-production-*`) |
| Search index prefix | none | `production_` |
| PHP-FPM socket | `/run/php/infokobuleti.sock` | `/run/php/infokobuleti-production.sock` |
| Horizon (supervisor) | `infokobuleti-horizon` | `infokobuleti-production-horizon` |
| Backups | `/var/backups/infokobuleti/staging` | `/var/backups/infokobuleti/production` |

Each site folder has `releases/<date-time>` (the five newest), `shared/.env`, `shared/storage`
(uploads, logs) and `current`, a symlink to the live release. nginx, cron and Horizon always use
`current`. The pre-Phase-11 staging folder `/var/www/infokobuleti` is no longer used; delete it once
you are sure nothing is missing (`sudo rm -rf /var/www/infokobuleti`, after a snapshot).

## Deploy

- **Automatic:** a push to `develop` deploys staging, a push to `main` deploys production, after the
  tests pass in GitHub Actions (`.github/workflows/ci.yml`). Needs the repository secrets
  `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_KNOWN_HOSTS`, `DEPLOY_SSH_KEY`.
- **From the PC:** `M:\infokobuleti.com\.provision\p11-deploy.cmd staging` (or `production`).
- **What happens** (`deploy/activate.sh`): unpack into a new release, link `.env` and `storage`,
  install PHP packages if the package has none, `migrate --force`, `optimize`, regenerate the
  sitemaps, switch `current` atomically, reload PHP-FPM, restart Horizon, then call `/up`. If the
  site does not answer, the previous release is put back automatically.
- **Migrations** run while the old release is still live: add columns / tables in one release and
  remove the old ones in a later release, never both at once.
- Production goes live by merging `develop` into `main` and pushing `main`.

## Roll back

```bash
sudo /usr/local/bin/infokobuleti-deploy releases production   # list, shows the live one
sudo /usr/local/bin/infokobuleti-deploy rollback production    # back to the previous release
```

Only code goes back; database migrations stay. If a migration must be undone, restore the backup
taken before the deploy (see below) or write a new migration.

## Open the site to the public (launch)

1. `cd /var/www/infokobuleti-production/current && php artisan launch:check` — fix what is red.
2. Admin → Settings → Maintenance: switch maintenance off. robots.txt opens by itself (it stays
   closed while maintenance is on).
3. Submit `https://infokobuleti.com/sitemap.xml` in Google Search Console.

## Backups

- `backup:run` every night at 02:00 (database + uploads), `backup:clean` at 01:30 (keeps every
  backup for 7 days, then one per day for 7 more days, one per week for 4 weeks, one per month for
  6 months), `backup:monitor` at 06:00.
- Every Sunday at 05:00 `backup:restore-test` restores the newest database backup into the scratch
  database `infokobuleti_restore_test` and compares row counts; a failure is an alert.
- Archives are password-protected: `BACKUP_ARCHIVE_PASSWORD` in `shared/.env` (a copy is in
  `/root/infokobuleti-secrets.env`). Keep that password somewhere outside the server too.
- `php artisan backup:list` shows every backup and its health.
- Backups are on the same server for now. Copy them off-site as soon as an account exists (Cloudflare
  R2 or Backblaze B2: add an `s3` disk and put it in `config/backup.php` destinations).

**Restore production from a backup**

```bash
cd /var/www/infokobuleti-production/current
php artisan down                                       # maintenance page for everyone
ls -lt /var/backups/infokobuleti/production/*/          # pick the archive
7z x -p"$BACKUP_ARCHIVE_PASSWORD" -o/tmp/restore <archive>.zip   # AES-encrypted: db-dumps/*.sql.gz and app/
gunzip /tmp/restore/db-dumps/*.gz
sudo -u postgres psql -d infokobuleti_production -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public AUTHORIZATION infokobuleti_prod; CREATE EXTENSION vector; CREATE EXTENSION pg_trgm;"
psql -h 127.0.0.1 -U infokobuleti_prod -d infokobuleti_production -f /tmp/restore/db-dumps/<dump>.sql
rsync -a /tmp/restore/app/ /var/www/infokobuleti-production/shared/storage/app/
php artisan optimize:clear && php artisan optimize && php artisan search:sync
php artisan up && rm -rf /tmp/restore
```

## Monitoring and alerts

- `monitor:check` every five minutes: free disk, free memory, Horizon, failed jobs (last hour),
  queue backlog, age of the last backup, SSL certificate. Results show on the admin dashboard
  ("System health"); problems are alerts.
- Alerts are written to `shared/storage/logs/alerts-*.log`. Telegram: once the bot exists, set
  `TELEGRAM_BOT_TOKEN` and `MONITORING_TELEGRAM_CHAT_ID` in `shared/.env`, run `php artisan
  optimize`, then `php artisan monitor:test-alert`.
- Pulse (performance): https://infokobuleti.com/pulse (super admins). Horizon: `/horizon`.
- Logs: `shared/storage/logs/laravel-*.log` (14 days), `slow-queries-*.log`, `auth.log` (failed
  sign-ins, read by fail2ban: `sudo fail2ban-client status infokobuleti-auth`), nginx logs in
  `/var/log/nginx/`.
- Outside checks (server down, network): add https://infokobuleti.com/up to an uptime service
  (e.g. UptimeRobot, free) once the site is public.

## Keys and passwords

- **APP_KEY:** never change it on a live site (encrypted 2FA secrets and sessions depend on it).
- **Database password:** `ALTER ROLE ... PASSWORD`, update `shared/.env` and `/root/infokobuleti-secrets.env`, then
  `php artisan optimize` and `sudo systemctl reload php8.4-fpm`.
- **Any other key** (Google, Twilio, banks, Turnstile, VAPID): edit `shared/.env`, then
  `php artisan optimize`, `sudo systemctl reload php8.4-fpm`, `php artisan horizon:terminate`.
- **GitHub deploy key:** `/home/deploy/.ssh/github_actions.pub` is in `authorized_keys` with a
  forced command (it can only run the deploy entry point). To replace it: delete both files and the
  `github-actions-deploy` line in `authorized_keys`, run `.provision\p11-setup.cmd` again and update
  the `DEPLOY_SSH_KEY` secret. Delete the private key from the server after copying it to GitHub.

## Add an admin

```bash
cd /var/www/infokobuleti-production/current
php artisan staff:create person@example.com "Name Surname" --role=admin   # super_admin, admin or moderator
```

The command prints a one-time password; the person signs in at `/staff/login` and must set up
two-factor authentication. Roles can also be changed in Admin → Users.
