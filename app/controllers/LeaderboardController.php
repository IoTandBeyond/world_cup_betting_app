<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Tournament;
use App\Services\Auth;
use App\Services\LeaderboardService;

class LeaderboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $tournament = Tournament::active();
        $rankings = [];
        $userRank = null;
        $userPoints = 0;

        if ($tournament && $user) {
            $tournamentId = (int) $tournament['id'];
            LeaderboardService::rebuild($tournamentId);
            $rankings = LeaderboardService::overall($tournamentId);
            $userPoints = LeaderboardService::userPoints(
                (int) $user['id'],
                $tournamentId
            );
            $userRank = LeaderboardService::userRank(
                (int) $user['id'],
                $tournamentId
            );
        }

        $this->view('leaderboard/index', [
            'tournament' => $tournament,
            'rankings' => $rankings,
            'user' => $user,
            'userRank' => $userRank,
            'userPoints' => $userPoints,
        ]);
    }
}
