<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Usuario.php';

final class UsuarioController
{
    public function registro(): void
    {
        $errores = [];
        $datos = ['nombre' => '', 'apellido_paterno' => '', 'apellido_materno' => '', 'correo' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos['nombre'] = trim($this->campo('nombre'));
            $datos['apellido_paterno'] = trim($this->campo('apellido_paterno'));
            $datos['apellido_materno'] = trim($this->campo('apellido_materno'));
            $datos['correo'] = strtolower(trim($this->campo('correo')));
            $contrasena = $this->campo('contrasena');
            $confirmacion = $this->campo('confirmacion');

            if (!$this->csrfValido()) {
                http_response_code(403);
                $errores[] = 'La sesión del formulario venció. Vuelve a enviarlo.';
            } else {
                if ($datos['nombre'] === '' || mb_strlen($datos['nombre'], 'UTF-8') > 150) {
                    $errores[] = 'Escribe un nombre de hasta 150 caracteres.';
                }

                if ($datos['apellido_paterno'] === '' || mb_strlen($datos['apellido_paterno'], 'UTF-8') > 100) {
                    $errores[] = 'Escribe un apellido paterno de hasta 100 caracteres.';
                }

                if (mb_strlen($datos['apellido_materno'], 'UTF-8') > 100) {
                    $errores[] = 'El apellido materno admite hasta 100 caracteres.';
                }

                if (strlen($datos['correo']) > 254 || !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
                    $errores[] = 'Escribe un correo electrónico válido.';
                }

                if (mb_strlen($contrasena, 'UTF-8') < 8) {
                    $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
                }

                if (strlen($contrasena) > 72 || str_contains($contrasena, "\0")) {
                    $errores[] = 'La contraseña supera el tamaño permitido o contiene un carácter inválido.';
                }

                if ($contrasena !== $confirmacion) {
                    $errores[] = 'Las contraseñas deben coincidir.';
                }

                if ($errores === []) {
                    try {
                        $modelo = new Usuario();
                        $modelo->crear(
                            $datos['nombre'],
                            $datos['apellido_paterno'],
                            $datos['apellido_materno'] === '' ? null : $datos['apellido_materno'],
                            $datos['correo'],
                            $contrasena
                        );

                        $_SESSION['mensaje'] = 'Tu cuenta fue creada. Ya puedes iniciar sesión.';
                        header('Location: index.php?pagina=login', true, 303);
                        return;
                    } catch (PDOException $error) {
                        if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                            throw $error;
                        }

                        $errores[] = 'Ese correo ya está registrado.';
                    }
                }

                http_response_code(422);
            }
        }

        require __DIR__ . '/../vistas/registro.php';
    }

    public function login(): void
    {
        $errores = [];
        $correo = '';
        $mensaje = $_SESSION['mensaje'] ?? '';
        unset($_SESSION['mensaje']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = strtolower(trim($this->campo('correo')));
            $contrasena = $this->campo('contrasena');

            if (!$this->csrfValido()) {
                http_response_code(403);
                $errores[] = 'La sesión del formulario venció. Vuelve a enviarlo.';
            } elseif (strlen($correo) > 254 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $contrasena === '' || strlen($contrasena) > 72 || str_contains($contrasena, "\0")) {
                http_response_code(422);
                $errores[] = 'Revisa el correo y la contraseña.';
            } else {
                $modelo = new Usuario();
                $usuario = $modelo->buscarPorCorreo($correo);

                if ($usuario === null || !password_verify($contrasena, $usuario['contrasena']) || $usuario['estado'] !== 'activo') {
                    http_response_code(401);
                    $errores[] = 'No se pudo iniciar sesión con esos datos.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = (int) $usuario['id'];
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));

                    header('Location: index.php?pagina=panel', true, 303);
                    return;
                }
            }
        }

        require __DIR__ . '/../vistas/login.php';
    }

    public function panel(): void
    {
        $usuario = $this->usuarioActual();

        if ($usuario === null) {
            header('Location: index.php?pagina=login', true, 303);
            return;
        }

        require __DIR__ . '/../vistas/panel.php';
    }

    public function usuarioActual(): ?array
    {
        $usuarioId = $_SESSION['usuario_id'] ?? null;

        if (!is_int($usuarioId)) {
            return null;
        }

        $modelo = new Usuario();
        $usuario = $modelo->buscarPorId($usuarioId);


        if ($usuario === null || $usuario['estado'] !== 'activo') {
            unset($_SESSION['usuario_id']);
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            return null;
        }

        return $usuario;
    }

    public function exigirAdministrador(): bool
    {
        $usuario = $this->usuarioActual();

        if ($usuario === null) {
            header('Location: index.php?pagina=login', true, 303);
            return false;
        }

        if ($usuario['rol'] !== 'administrador') {
            http_response_code(403);
            echo 'Esta operación requiere una cuenta de administrador.';
            return false;
        }

        return true;
    }

    public function cerrarSesion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Para cerrar sesión utiliza el botón del panel.';
            return;
        }

        if (!$this->csrfValido()) {
            http_response_code(403);
            echo 'El formulario venció. Actualiza el panel y vuelve a intentarlo.';
            return;
        }

        $_SESSION = [];
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $parametros['path'],
            'domain' => $parametros['domain'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite'],
        ]);
        session_destroy();

        header('Location: index.php', true, 303);
    }

    private function campo(string $nombre): string
    {
        $valor = $_POST[$nombre] ?? '';

        return is_string($valor) ? $valor : '';
    }

    private function csrfValido(): bool
    {
        $token = $_POST['csrf'] ?? null;

        return is_string($token) && hash_equals($_SESSION['csrf'], $token);
    }
}
