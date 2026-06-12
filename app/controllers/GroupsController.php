<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\GroupStandingsService;
use App\Services\TournamentContext;

class GroupsController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $tournament = TournamentContext::currentTournament($user);
        $groups = [];

        if ($tournament) {
            $groups = GroupStandingsService::forTournament((int) $tournament['id']);
        }

        $this->view('groups/index', [
            'tournament' => $tournament,
            'groups' => $groups,
            'user' => $user,
        ]);
    }
}
