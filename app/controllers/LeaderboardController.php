<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TournamentContext;
use App\Services\Auth;
use App\Services\LeaderboardService;

class LeaderboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $tournament = TournamentContext::currentTournament($user);
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
