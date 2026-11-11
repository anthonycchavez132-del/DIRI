<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\AuthValidator;
use App\Services\LoginLimiter;

class AuthController extends Controller
{
    private function guestOnly(): void
    {
        if (AuthService::user() !== null) {
            redirect('dashboard');
        }
    }

    public function showRegister(): void
    {
        $this->guestOnly();
        $this->render('auth/register', ['title' => 'Crear cuenta', 'errors' => [], 'old' => []]);
    }

    public function register(): void
    {
        $this->guestOnly();
        [$data, $errors] = AuthValidator::registration($_POST);
        if ($errors === []) {
            try {
                (new AuthService())->register($data);
                $_SESSION['notice'] = 'Tu cuenta está creada. Ya puedes iniciar sesión.';
                redirect('login');
            } catch (\PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }
                $errors['email'] = 'No pudimos crear una cuenta con ese correo. Prueba iniciar sesión.';
            }
        }
        // No devolver contraseñas al HTML ni guardarlas en sesión.
        unset($data['password']);
        http_response_code(422);
        $this->render('auth/register', ['title' => 'Crear cuenta', 'errors' => $errors, 'old' => $data]);
    }

    public function showLogin(): void
    {
        $this->guestOnly();
        $notice = $_SESSION['notice'] ?? null;
        unset($_SESSION['notice']);
        $this->render('auth/login', ['title' => 'Iniciar sesión', 'errors' => [], 'old' => [], 'notice' => $notice]);
    }

    public function login(): void
    {
        $this->guestOnly();
        $email = AuthValidator::email($_POST);
        $password = AuthValidator::password($_POST);
        $errors = [];
        $status = 422;
        if (!AuthValidator::emailValid($email) || !AuthValidator::passwordValid($password)) {
            $errors['credentials'] = 'Correo o contraseña incorrectos.';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
            if (!LoginLimiter::consume($email, $ip)) {
                $status = 429;
                header('Retry-After: 900');
                $errors['credentials'] = 'Alcanzaste el límite de intentos. Vuelve a probar en 15 minutos.';
            } elseif ((new AuthService())->login($email, $password)) {
                LoginLimiter::reset($email, $ip);
                redirect('dashboard');
            } else {
                $status = 401;
                $errors['credentials'] = 'Correo o contraseña incorrectos.';
            }
        }
        http_response_code($status);
        $this->render('auth/login', ['title' => 'Iniciar sesión', 'errors' => $errors, 'old' => ['email' => $email], 'notice' => null]);
    }

    public function profile(): void
    {
        $this->render('auth/profile', ['title' => 'Mi perfil', 'activePage' => 'perfil', 'user' => AuthService::user()]);
    }

    public function logout(): void
    {
        (new AuthService())->logout();
        redirect('login');
    }
}
