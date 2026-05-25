# World Cup Poll

Private World Cup betting pool — invitation-only registration, match predictions, automatic scoring, and leaderboard.

## Requirements

- PHP 8.0+
- MySQL 8+
- Composer
- Apache with `mod_rewrite` **or** PHP built-in server

## Setup

1. Install dependencies:

```bash
composer install
```

2. Copy environment file and configure database:

```bash
cp .env.example .env
```

3. Import the database:

```bash
mysql -u root -p < db/db.sql
```

4. Default seed admin: `admin@worldcup.local` / `AdminPassword123!`

## Run the app

### Option A — PHP built-in server (recommended for local dev)

From the project root:

```bash
php -S localhost:8000 router.php
```

Open **http://localhost:8000/login** (not `/public`).

Set in `.env`:

```
APP_URL=http://localhost:8000
```

### Option B — Apache / MAMP (project root as document root)

Point the **document root** to this folder (where `index.php` and `.htaccess` live).

Open **http://localhost/login** (or your vhost URL).

### Option C — Apache / MAMP (app in a subfolder)

If the URL is e.g. **http://localhost:8888/worldcuppoll/**:

1. In `.env` add:

```
APP_BASE_PATH=worldcuppoll
APP_URL=http://localhost:8888/worldcuppoll
```

2. In `.htaccess`, uncomment and set:

```apache
RewriteBase /worldcuppoll
```

3. Enable `mod_rewrite` and `AllowOverride All` for this directory.

## Troubleshooting “404 Not Found”

| Cause | Fix |
|--------|-----|
| Using `php -S` without a router | Run `php -S localhost:8000 router.php` |
| Opening `/public` in the browser | Use the project root URL |
| App in a subfolder | Set `APP_BASE_PATH` and `RewriteBase` (see Option C) |
| `mod_rewrite` off | Enable rewrite or use the PHP built-in server |

## Tournament & teams

See **[docs/TOURNAMENT_SETUP.md](docs/TOURNAMENT_SETUP.md)** for the full procedure.

Quick path: **Admin → Tournament** → create World Cup → import teams (CSV) → **Activate** → **Admin → Matches** → upload matches CSV.

Match import details: **[docs/MATCHES_IMPORT.md](docs/MATCHES_IMPORT.md)**

Optional SQL procedures: `mysql ... < db/procedures.sql`

## Invitations & email

New users receive an email from **no-reply@iot4b.ca** with a temporary password (e.g. `ABCD-EFGH-IJKL`). On first login they must set a new password.

Configure `.env`:

```
MAIL_FROM_ADDRESS=no-reply@iot4b.ca
MAIL_FROM_NAME=World Cup Pool
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
```

For existing databases, run migrations:

```bash
mysql -u root -p world_cup_poll_db < db/migrations/001_must_change_password.sql
mysql -u root -p world_cup_poll_db < db/migrations/002_policy_acceptance.sql
```

## Usage

1. Log in as admin → **Invitations** → enter email → **Send email**.
2. User registers via invite link, then logs in.
3. Admin adds matches; users submit predictions before kickoff.
4. Admin enters results → scores update automatically.
5. View **Leaderboard** for rankings.
