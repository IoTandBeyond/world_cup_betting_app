# Tournament & teams setup

The app uses one **active** tournament at a time. Users predict matches for that tournament; teams must exist before you add matches in **Admin → Matches**.

## Method A — Admin UI (recommended)

1. Log in as **admin**.
2. Open **Admin → Tournament**.
3. **Create tournament**
   - Name: `FIFA World Cup 2026`
   - Year and start/end dates
   - Check **Set as active tournament** (or activate later).
4. Select the tournament in the list on the left.
5. **Add teams** one by one, or **bulk import** CSV:

```text
Brazil,BRA,BRA
Germany,GER,GER
Argentina,ARG,ARG
France,FRA,FRA
```

Format: `Full Name,SHORT_CODE,FIFA_CODE` (one per line).

6. Go to **Admin → Matches** → upload your **matches CSV** (see [MATCHES_IMPORT.md](MATCHES_IMPORT.md)).

## Method B — MySQL stored procedures

Install procedures (once per database):

```bash
mysql -u root -p world_cup_poll_db < db/procedures.sql
```

### Step 1 — Create tournament

```sql
CALL sp_create_tournament(
    'FIFA World Cup 2026',   -- name
    'world-cup-2026',        -- slug (unique)
    2026,                    -- year
    '2026-06-11',            -- start_date
    '2026-07-19',            -- end_date
    'upcoming'               -- status: upcoming | active | finished
);
-- Note the tournament_id from the result (e.g. 1)
```

### Step 2 — Activate (only one active at a time)

```sql
CALL sp_activate_tournament(1);
```

### Step 3 — Add teams

**Single team:**

```sql
CALL sp_add_team(
    1,              -- tournament_id
    'Brazil',       -- name
    'BRA',          -- short_name (max 10 chars)
    'BRA',          -- fifa_code (3 letters)
    NULL            -- flag_url (optional)
);
```

**Bulk import** (paste lines into the procedure):

```sql
CALL sp_import_teams(1, 'Brazil,BRA,BRA
Germany,GER,GER
Argentina,ARG,ARG
France,FRA,FRA
Spain,ESP,ESP
England,ENG,ENG');
```

## Method C — SQL seed file

After creating the tournament with slug `world-cup-2026`:

```bash
mysql -u root -p world_cup_poll_db < db/seeds/world_cup_2026_teams.sql
```

Edit the seed file to add or remove nations before running.

## Checklist before opening predictions

| Step | Done |
|------|------|
| Tournament exists and is **active** | ☐ |
| All participating teams imported | ☐ |
| Matches created with kickoff times | ☐ |
| Users invited and registered | ☐ |

## Troubleshooting

- **“No active tournament”** — Run `CALL sp_activate_tournament(id);` or use **Activate** in the admin UI.
- **Duplicate FIFA code** — Each `fifa_code` must be unique per tournament.
- **Matches page has no teams** — Teams belong to the active tournament; confirm `tournament_id` matches.
