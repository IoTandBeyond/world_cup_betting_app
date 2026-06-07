# Multi-tournament hosts

The platform supports **multiple tournaments running in parallel**. Each tournament has its own teams, matches, players, and leaderboard.

## Roles

| Role | Access |
|------|--------|
| **admin** (super-admin) | Create tournaments, assign hosts, activate tournaments, global dashboard |
| **host** | Manage **one** assigned tournament: teams, matches, invitations, results, players |
| **user** | Player — predictions and leaderboard for tournaments they belong to |

## Creating a tournament with a host

1. Log in as **admin** → **Tournaments**.
2. Fill tournament details and **host name + email**.
3. Submit **Create tournament & invite host**.

The host receives an email with a temporary password. On first login they accept the rules, set a password, and land in the host panel.

## Host capabilities

- Add / import teams
- Import matches
- Lock or unlock predictions
- Enter results
- Send player invitations (scoped to their tournament)
- View players and resend temporary passwords for their tournament

## Player invitations

Invitations are **tournament-scoped**:

- **New email** → account created + added to the tournament + temporary password email
- **Existing player** → added to the tournament + notification email (same login)

## Multiple tournaments for players

If a player belongs to more than one tournament, a **Tournament** dropdown appears in the player header to switch context.

## Database migration

```bash
mysql -u root -p world_cup_poll_db < db/migrations/004_hosts_parallel_tournaments.sql
```

This adds:

- `host` user role
- `tournaments.host_user_id`
- `invitations.tournament_id`
- `tournament_members` (player ↔ tournament)

## Parallel active tournaments

Activating a tournament no longer deactivates others. Multiple tournaments can be `active` at the same time.
