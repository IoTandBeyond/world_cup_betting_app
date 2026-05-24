<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BonusPrediction;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\LeaderboardService;

class BonusController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $tournament = Tournament::active();

        if (!$tournament) {
            $this->view('bonus/index', [
                'user' => $user,
                'tournament' => null,
            ]);
            return;
        }

        $tournamentId = (int) $tournament['id'];
        $teams = Team::forTournament($tournamentId);
        $players = Player::forTournament($tournamentId);
        $bonus = BonusPrediction::find((int) $user['id'], $tournamentId);

        $this->view('bonus/index', [
            'user' => $user,
            'tournament' => $tournament,
            'teams' => $teams,
            'players' => $players,
            'bonus' => $bonus,
            'points' => [
                'winner' => Setting::get('points_world_cup_winner', 10),
                'scorer' => Setting::get('points_top_scorer', 10),
                'keeper' => Setting::get('points_best_goalkeeper', 7),
                'mvp' => Setting::get('points_mvp', 7),
            ],
            'totalPoints' => LeaderboardService::userPoints(
                (int) $user['id'],
                $tournamentId
            ),
        ]);
    }

    public function save(): void
    {
        Csrf::validateOrAbort();

        $user = Auth::user();
        $tournament = Tournament::active();

        if (!$tournament) {
            Flash::set('error', 'No active tournament.');
            $this->redirect('/bonus');
        }

        $tournamentId = (int) $tournament['id'];
        $winnerTeamId = (int) ($_POST['world_cup_winner_team_id'] ?? 0);

        if (!$winnerTeamId) {
            Flash::set('error', 'Please select a World Cup winner.');
            $this->redirect('/bonus');
        }

        $scorerId = self::resolvePlayerId(
            $_POST['top_scorer_player_id'] ?? '',
            $_POST['top_scorer_team_id'] ?? '',
            $_POST['top_scorer_name'] ?? '',
            'forward'
        );

        $keeperId = self::resolvePlayerId(
            $_POST['best_goalkeeper_player_id'] ?? '',
            $_POST['best_goalkeeper_team_id'] ?? '',
            $_POST['best_goalkeeper_name'] ?? '',
            'goalkeeper'
        );

        $mvpId = self::resolvePlayerId(
            $_POST['mvp_player_id'] ?? '',
            $_POST['mvp_team_id'] ?? '',
            $_POST['mvp_name'] ?? '',
            'midfielder'
        );

        BonusPrediction::upsert((int) $user['id'], $tournamentId, [
            'world_cup_winner_team_id' => $winnerTeamId,
            'top_scorer_player_id' => $scorerId,
            'best_goalkeeper_player_id' => $keeperId,
            'mvp_player_id' => $mvpId,
        ]);

        Flash::set('success', 'Bonus predictions saved.');
        $this->redirect('/bonus');
    }

    private static function resolvePlayerId(
        string $existingId,
        string $teamId,
        string $name,
        string $position
    ): ?int {
        if ($existingId !== '' && (int) $existingId > 0) {
            return (int) $existingId;
        }

        $name = trim($name);
        $teamId = (int) $teamId;

        if ($name === '' || $teamId <= 0) {
            return null;
        }

        return Player::findOrCreate($teamId, $name, $position);
    }
}
