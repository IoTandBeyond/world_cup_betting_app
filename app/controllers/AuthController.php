<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Flash;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
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
            if (Auth::mustChangePassword()) {
                Flash::set(
                    'success',
                    'Please create a new password to continue.'
                );
                $this->redirect('/password/change');
            }

            $target = Auth::isAdmin() ? '/admin' : '/dashboard';
            $this->redirect($target);
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
