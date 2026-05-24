<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;

class HomeController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }

        if (Auth::mustChangePassword()) {
            $this->redirect('/password/change');
        }

        $this->redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
    }
}
