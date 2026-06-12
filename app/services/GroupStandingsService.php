<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;

class GroupStandingsService
{
    /**
     * @return array<string, list<array{
     *     team_id: int,
     *     name: string,
     *     short_name: string,
     *     fifa_code: string,
     *     flag_url: ?string,
     *     played: int,
     *     won: int,
     *     drawn: int,
     *     lost: int,
     *     goals_for: int,
     *     goals_against: int,
     *     goal_diff: int,
     *     points: int
     * }>>
     */
    public static function forTournament(int $tournamentId): array
    {
        $matches = MatchModel::forTournament($tournamentId);
        /** @var array<string, array<int, array>> $groups */
        $groups = [];

        foreach ($matches as $match) {
            if ($match['stage'] !== 'group' || ($match['group_name'] ?? '') === '') {
                continue;
            }

            $groupKey = strtoupper((string) $match['group_name']);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            foreach (['home', 'away'] as $side) {
                $teamId = (int) $match["{$side}_team_id"];

                if (!isset($groups[$groupKey][$teamId])) {
                    $groups[$groupKey][$teamId] = self::emptyRow($match, $side);
                }
            }

            if ($match['status'] !== 'finished') {
                continue;
            }

            $homeScore = $match['home_score'];
            $awayScore = $match['away_score'];

            if ($homeScore === null || $awayScore === null) {
                continue;
            }

            $homeId = (int) $match['home_team_id'];
            $awayId = (int) $match['away_team_id'];
            $homeGoals = (int) $homeScore;
            $awayGoals = (int) $awayScore;

            self::recordResult(
                $groups[$groupKey][$homeId],
                $homeGoals,
                $awayGoals
            );
            self::recordResult(
                $groups[$groupKey][$awayId],
                $awayGoals,
                $homeGoals
            );
        }

        $sorted = [];

        foreach (self::sortGroupKeys(array_keys($groups)) as $groupKey) {
            $rows = array_values($groups[$groupKey]);
            usort($rows, static function (array $a, array $b): int {
                return self::compareRows($a, $b);
            });
            $sorted[$groupKey] = $rows;
        }

        return $sorted;
    }

    /** @param array<string, mixed> $match */
    private static function emptyRow(array $match, string $side): array
    {
        return [
            'team_id' => (int) $match["{$side}_team_id"],
            'name' => (string) $match["{$side}_team_name"],
            'short_name' => (string) $match["{$side}_short_name"],
            'fifa_code' => (string) $match["{$side}_fifa_code"],
            'flag_url' => $match["{$side}_flag_url"] ?? null,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_diff' => 0,
            'points' => 0,
        ];
    }

    /** @param array<string, int> $row */
    private static function recordResult(array &$row, int $goalsFor, int $goalsAgainst): void
    {
        $row['played']++;
        $row['goals_for'] += $goalsFor;
        $row['goals_against'] += $goalsAgainst;
        $row['goal_diff'] = $row['goals_for'] - $row['goals_against'];

        if ($goalsFor > $goalsAgainst) {
            $row['won']++;
            $row['points'] += 3;
        } elseif ($goalsFor < $goalsAgainst) {
            $row['lost']++;
        } else {
            $row['drawn']++;
            $row['points'] += 1;
        }
    }

    /** @param array<string, int> $a @param array<string, int> $b */
    private static function compareRows(array $a, array $b): int
    {
        foreach (['points', 'goal_diff', 'goals_for'] as $field) {
            if ($a[$field] !== $b[$field]) {
                return $b[$field] <=> $a[$field];
            }
        }

        return strcasecmp($a['name'], $b['name']);
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    private static function sortGroupKeys(array $keys): array
    {
        usort($keys, static function (string $a, string $b): int {
            if (ctype_alpha($a) && ctype_alpha($b)) {
                return strcmp($a, $b);
            }

            return strnatcasecmp($a, $b);
        });

        return $keys;
    }
}
