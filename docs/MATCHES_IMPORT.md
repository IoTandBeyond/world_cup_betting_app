# Import matches from CSV

Import all fixtures in one step from **Admin → Matches**.

## Prerequisites

1. Active tournament (**Admin → Tournament** → Activate).
2. All teams imported with FIFA codes (**Admin → Tournament** → import teams).

Match rows reference teams by code. The importer matches either column from your teams table:

- **`fifa_code`** — 2-letter ISO (e.g. `AR`, `BR`) — used for flags
- **`short_name`** — 3-letter display code (e.g. `ARG`, `BRA`) — shown on match cards

Your matches CSV can use **either** form (`ARG` or `AR` for Argentina).

## CSV format

Upload a `.csv` file (max 2 MB) with a header row:

| Column | Required | Example |
|--------|----------|---------|
| `home_fifa` | Yes | `BRA` |
| `away_fifa` | Yes | `GER` |
| `kickoff_at` | Yes | `2026-06-15 14:00:00` |
| `stage` | Yes | `group` |
| `group_name` | No | `A` |
| `venue` | No | `MetLife Stadium` |

### Accepted header aliases

- Home: `home_fifa`, `home_code`, `home_team`, `home`
- Away: `away_fifa`, `away_code`, `away_team`, `away`
- Kickoff: `kickoff_at`, `kickoff`, `datetime`, `date_time`, `date`

### Stage values

`group`, `round_of_16`, `quarter_final`, `semi_final`, `third_place`, `final`

Aliases like `Round of 16` or `quarter final` also work.

### Kickoff datetime

Enter kickoff times in **APP_TIMEZONE** (see `.env`, e.g. `America/Toronto` for EDT/EST).

Supported examples:

- `2026-06-15 14:00:00`
- `2026-06-15 14:00`
- `2026-06-15T14:00`

Bets lock when kickoff time passes in that timezone, not server UTC.

## Example file

Download from the Matches page or use `samples/matches_template.csv` in the project root.

```csv
home_fifa,away_fifa,kickoff_at,stage,group_name,venue
BRA,GER,2026-06-15 14:00:00,group,A,MetLife Stadium
ARG,FRA,2026-06-16 17:00:00,group,B,SoFi Stadium
```

## Behaviour

- Unknown FIFA codes → row skipped with an error message.
- Duplicate fixture (same home, away, kickoff) → skipped silently (counted as duplicate).
- After import, lock predictions per match from the matches list if needed.

## Troubleshooting

| Issue | Fix |
|--------|-----|
| Unknown home/away team | Import teams first; use each team's `short_name` (3-letter) or `fifa_code` (2-letter) |
| Invalid kickoff | Use `YYYY-MM-DD HH:MM:SS` |
| Invalid stage | Use values from the list above |
| 0 imported | Check flash error for line-by-line issues |
