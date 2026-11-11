<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\LicenseJourneyService;

class LicenseJourneyController extends Controller
{
    private function mode($value): string
    {
        if (!is_string($value) || !in_array($value, ['primera', 'renovacion'], true)) {
            throw new \DomainException('Selecciona primera licencia o renovación.', 422);
        }
        return $value;
    }

    private function sessionKey(string $mode): string
    {
        $user = AuthService::user();
        return ($user === null ? 'visitante' : 'usuario-' . (int)$user['id']) . '-' . $mode;
    }

    private function problem(\DomainException $exception): void
    {
        http_response_code($exception->getCode());
        $this->render('personal/problem', ['title' => 'Revisa tu ruta', 'message' => $exception->getMessage()]);
    }

    public function index(): void
    {
        try {
            $mode = $this->mode($_GET['modalidad'] ?? 'primera');
            $service = new LicenseJourneyService();
            $context = $service->context($mode);
            $profile = $service->defaults($mode);
            $plan = null;
            $notice = null;
            $key = $this->sessionKey($mode);
            $stored = $_SESSION['license_journeys'][$key] ?? null;
            if (is_array($stored)) {
                if (($stored['version'] ?? null) === $context['version'] && is_array($stored['profile'] ?? null)) {
                    $validated = $service->validate($stored['profile'], $mode);
                    if ($validated['errors'] === []) {
                        $profile = $validated['data'];
                        $plan = $service->plan($profile);
                    }
                } else {
                    unset($_SESSION['license_journeys'][$key]);
                    $notice = 'La información de la guía cambió. Revisa y vuelve a indicar tus documentos.';
                }
            }
            $this->render('journey/index', ['title' => 'Personaliza tu licencia · Beta', 'mode' => $mode,
                'context' => $context, 'profile' => $profile, 'plan' => $plan, 'errors' => [], 'notice' => $notice]);
        } catch (\DomainException $exception) { $this->problem($exception); }
    }

    public function calculate(): void
    {
        try {
            $mode = $this->mode($_POST['modalidad'] ?? null);
            $service = new LicenseJourneyService();
            $context = $service->context($mode);
            $validated = $service->validate($_POST, $mode);
            $errors = $validated['errors'];
            $version = $_POST['version'] ?? null;
            $outdated = !is_string($version) || !hash_equals($context['version'], $version);
            if ($outdated) { $errors['version'] = 'La guía cambió o el formulario venció. Revisa tus elecciones y vuelve a calcular.'; }
            if ($errors !== []) {
                http_response_code($outdated ? 409 : 422);
                $this->render('journey/index', ['title' => 'Revisa tu ruta', 'mode' => $mode, 'context' => $context,
                    'profile' => $validated['data'], 'plan' => null, 'errors' => $errors, 'notice' => null]);
                return;
            }
            // Solo preferencias y declaraciones. No conserva archivos ni cambia progreso oficial.
            $_SESSION['license_journeys'][$this->sessionKey($mode)] = ['version' => $context['version'], 'profile' => $validated['data']];
            redirect('mi-ruta?modalidad=' . $mode . '#tu-ruta');
        } catch (\DomainException $exception) { $this->problem($exception); }
    }

    public function reset(): void
    {
        try {
            $mode = $this->mode($_POST['modalidad'] ?? null);
            unset($_SESSION['license_journeys'][$this->sessionKey($mode)]);
            redirect('mi-ruta?modalidad=' . $mode);
        } catch (\DomainException $exception) { $this->problem($exception); }
    }
}
