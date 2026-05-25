<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;
use App\Services\OnboardingService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect(OnboardingService::redirectAfterLogin());
        }

        $this->view('auth/login', [
            'error' => Flash::get('error'),
        ]);
    }

    public function login(): void
    {
        Csrf::validateOrAbort();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!Validator::email($email) || !Validator::required($password)) {
            Flash::set('error', 'Please enter a valid email and password.');
            $this->redirect('/login');
        }

        if (Auth::attempt($email, $password)) {
            if (!\App\Models\User::hasAcceptedPolicy(Auth::user())) {
                Flash::set(
                    'success',
                    'Please review and accept the Rules & Policy to continue.'
                );
            } elseif (Auth::mustChangePassword()) {
                Flash::set('success', 'Please create your new password to continue.');
            }

            $this->redirect(OnboardingService::redirectAfterLogin());
        }

        Flash::set('error', 'Invalid credentials or inactive account.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
