<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\TournamentContext;

class TournamentController extends Controller
{
    public function switch(): void
    {
        Csrf::validateOrAbort();

        $user = Auth::user();
        $tournamentId = (int) ($_POST['tournament_id'] ?? 0);

        try {
            TournamentContext::switchTournament((int) $user['id'], $tournamentId);
            Flash::set('success', 'Tournament switched.');
        } catch (\InvalidArgumentException $e) {
            Flash::set('error', $e->getMessage());
        }

        $redirect = $_POST['redirect'] ?? '/dashboard';
        $redirect = is_string($redirect) && str_starts_with($redirect, '/')
            ? $redirect
            : '/dashboard';

        $this->redirect($redirect);
    }
}
