<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MatchModel;
use App\Models\Prediction;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;

class PredictionController extends Controller
{
    public function save(): void
    {
        Csrf::validateOrAbort();

        $user = Auth::user();
        $matchId = (int) ($_POST['match_id'] ?? 0);
        $home = (int) ($_POST['predicted_home_score'] ?? -1);
        $away = (int) ($_POST['predicted_away_score'] ?? -1);

        $match = MatchModel::findById($matchId);

        $returnTab = $_POST['return_tab'] ?? 'upcoming';

        if (!in_array($returnTab, ['upcoming', 'mine', 'results'], true)) {
            $returnTab = 'upcoming';
        }

        $redirect = '/dashboard?tab=' . $returnTab;

        if (!$match || !MatchModel::canPredict($match)) {
            Flash::set('error', 'Predictions are locked for this match.');
            $this->redirect($redirect);
        }

        if ($home < 0 || $away < 0 || $home > 20 || $away > 20) {
            Flash::set('error', 'Enter valid scores (0–20).');
            $this->redirect($redirect);
        }

        Prediction::upsert((int) $user['id'], $matchId, $home, $away);

        Flash::set('success', 'Your bet was saved.');
        $this->redirect($redirect);
    }
}
