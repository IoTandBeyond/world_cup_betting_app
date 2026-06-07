<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Tournament;

class TournamentAuth
{
    public static function canAccessAdmin(): bool
    {
        return Auth::isSuperAdmin() || Auth::isHost();
    }

    public static function requireSuperAdmin(): void
    {
        if (!Auth::isSuperAdmin()) {
            http_response_code(403);
            die('403 Forbidden');
        }
    }

    public static function canManageTournament(int $tournamentId): bool
    {
        if (Auth::isSuperAdmin()) {
            return true;
        }

        if (!Auth::isHost()) {
            return false;
        }

        $hosted = Tournament::findByHostUserId((int) Auth::user()['id']);

        return $hosted && (int) $hosted['id'] === $tournamentId;
    }

    public static function requireCanManageTournament(int $tournamentId): void
    {
        if (!self::canManageTournament($tournamentId)) {
            http_response_code(403);
            die('403 Forbidden');
        }
    }

    public static function hostedTournament(): ?array
    {
        if (!Auth::isHost()) {
            return null;
        }

        return Tournament::findByHostUserId((int) Auth::user()['id']);
    }

    /** Tournament context for admin/host operations (matches, invitations, etc.). */
    public static function adminTournamentId(?int $requestedId = null): ?int
    {
        if (Auth::isHost()) {
            $hosted = self::hostedTournament();

            return $hosted ? (int) $hosted['id'] : null;
        }

        if ($requestedId && $requestedId > 0) {
            return $requestedId;
        }

        $active = Tournament::active();

        return $active ? (int) $active['id'] : null;
    }

    public static function requireMatchInManagedTournament(int $matchId): int
    {
        $match = MatchModel::findById($matchId);

        if (!$match) {
            http_response_code(404);
            die('Match not found.');
        }

        $tournamentId = (int) $match['tournament_id'];
        self::requireCanManageTournament($tournamentId);

        return $tournamentId;
    }
}
