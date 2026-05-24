<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MatchModel;
use App\Models\Prediction;
use App\Models\Tournament;
use App\Services\Auth;
use App\Services\LeaderboardService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $tournament = Tournament::active();
        $tab = $this->normalizeTab($_GET['tab'] ?? 'upcoming');

        if (!$tournament) {
            $this->view('dashboard/index', [
                'user' => $user,
                'tournament' => null,
                'tab' => $tab,
                'matches' => [],
                'predictions' => [],
                'points' => 0,
                'stats' => [],
            ]);
            return;
        }

        $tournamentId = (int) $tournament['id'];
        $allMatches = MatchModel::forTournament($tournamentId);
        $predictions = Prediction::forUser((int) $user['id'], $tournamentId);
        $matches = self::filterMatches($allMatches, $predictions, $tab);
        $points = LeaderboardService::userPoints((int) $user['id'], $tournamentId);
        $rank = LeaderboardService::userRank((int) $user['id'], $tournamentId);

        $this->view('dashboard/index', [
            'user' => $user,
            'tournament' => $tournament,
            'tab' => $tab,
            'matches' => $matches,
            'predictions' => $predictions,
            'points' => $points,
            'rank' => $rank,
            'stats' => self::buildStats($allMatches, $predictions),
        ]);
    }

    private function normalizeTab(string $tab): string
    {
        return in_array($tab, ['upcoming', 'mine', 'results'], true)
            ? $tab
            : 'upcoming';
    }

    /**
     * @param array<int, array> $matches
     * @param array<int, array> $predictions
     * @return list<array>
     */
    private static function filterMatches(
        array $matches,
        array $predictions,
        string $tab
    ): array {
        $filtered = [];

        foreach ($matches as $match) {
            $matchId = (int) $match['id'];
            $hasPrediction = isset($predictions[$matchId]);
            $finished = $match['status'] === 'finished';

            if ($tab === 'upcoming' && !$finished) {
                $filtered[] = $match;
            } elseif ($tab === 'mine' && $hasPrediction) {
                $filtered[] = $match;
            } elseif ($tab === 'results' && $finished) {
                $filtered[] = $match;
            }
        }

        if ($tab === 'results') {
            usort($filtered, static function ($a, $b) {
                return strtotime($b['kickoff_at']) <=> strtotime($a['kickoff_at']);
            });
        }

        return $filtered;
    }

    /**
     * @param array<int, array> $matches
     * @param array<int, array> $predictions
     * @return array{upcoming: int, to_pick: int, picked: int, finished: int, points_from_matches: int}
     */
    private static function buildStats(array $matches, array $predictions): array
    {
        $upcoming = 0;
        $toPick = 0;
        $picked = 0;
        $finished = 0;
        $pointsFromMatches = 0;

        foreach ($matches as $match) {
            $matchId = (int) $match['id'];
            $isFinished = $match['status'] === 'finished';
            $canPredict = MatchModel::canPredict($match);
            $hasPred = isset($predictions[$matchId]);

            if (!$isFinished) {
                $upcoming++;
            }

            if ($isFinished) {
                $finished++;
                if ($hasPred) {
                    $pointsFromMatches += (int) $predictions[$matchId]['points_awarded'];
                }
            } elseif ($canPredict && !$hasPred) {
                $toPick++;
            } elseif ($hasPred) {
                $picked++;
            }
        }

        return [
            'upcoming' => $upcoming,
            'to_pick' => $toPick,
            'picked' => $picked,
            'finished' => $finished,
            'points_from_matches' => $pointsFromMatches,
        ];
    }
}
