<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\OnboardingService;

class HomeController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        $this->redirect(OnboardingService::redirectAfterLogin());
    }
}
