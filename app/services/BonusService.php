<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;

class BonusService
{
    /** Bonus picks lock when the first tournament match kicks off. */
    public static function canSubmit(int $tournamentId): bool
    {
        $firstKickoff = MatchModel::firstKickoffAt($tournamentId);

        if ($firstKickoff === null) {
            return true;
        }

        $kickoff = date_create(
            $firstKickoff,
            timezone_open(date_default_timezone_get())
        );

        if ($kickoff === false) {
            return true;
        }

        return $kickoff->getTimestamp() > time();
    }

    public static function firstKickoffAt(int $tournamentId): ?string
    {
        return MatchModel::firstKickoffAt($tournamentId);
    }
}
