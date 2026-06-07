<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentMember;

class TournamentContext
{
    public static function currentTournamentId(?array $user): ?int
    {
        if (!$user) {
            return null;
        }

        $userId = (int) $user['id'];

        if (isset($_SESSION['tournament_id'])) {
            $sessionId = (int) $_SESSION['tournament_id'];

            if (TournamentMember::isMember($userId, $sessionId)) {
                return $sessionId;
            }

            unset($_SESSION['tournament_id']);
        }

        $memberships = TournamentMember::tournamentsForUser($userId);

        if ($memberships !== []) {
            foreach ($memberships as $tournament) {
                if (in_array($tournament['status'], ['active', 'upcoming'], true)) {
                    return (int) $tournament['id'];
                }
            }

            return (int) $memberships[0]['id'];
        }

        $fallback = Tournament::active();

        return $fallback ? (int) $fallback['id'] : null;
    }

    public static function currentTournament(?array $user): ?array
    {
        $id = self::currentTournamentId($user);

        return $id ? Tournament::findById($id) : null;
    }

    /** @return list<array> */
    public static function availableTournaments(?array $user): array
    {
        if (!$user) {
            return [];
        }

        $memberships = TournamentMember::tournamentsForUser((int) $user['id']);

        if ($memberships !== []) {
            return $memberships;
        }

        $fallback = Tournament::active();

        return $fallback ? [$fallback] : [];
    }

    public static function switchTournament(int $userId, int $tournamentId): void
    {
        if (!TournamentMember::isMember($userId, $tournamentId)) {
            throw new \InvalidArgumentException('You are not a member of that tournament.');
        }

        $_SESSION['tournament_id'] = $tournamentId;
    }
}
