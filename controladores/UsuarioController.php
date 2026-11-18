<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelos/Usuario.php';

final class UsuarioController
{
    private const MAX_INTENTOS_LOGIN = 5;
    private const SEGUNDOS_BLOQUEO_LOGIN = 300;

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
                foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'correo', 'contrasena', 'confirmacion'] as $campo) {
                    if (!is_string($_POST[$campo] ?? '')) {
                        $errores[] = 'Los campos del registro deben contener texto.';
                        break;
                    }
                }

                if (!$this->nombreValido($datos['nombre'], 150)) {
                    $errores[] = 'Escribe un nombre de hasta 150 caracteres, usando letras, espacios, guiones o apóstrofes.';
                }

                if (!$this->nombreValido($datos['apellido_paterno'], 100)) {
                    $errores[] = 'Escribe un apellido paterno válido de hasta 100 caracteres.';
                }

                if ($datos['apellido_materno'] !== '' && !$this->nombreValido($datos['apellido_materno'], 100)) {
                    $errores[] = 'El apellido materno debe contener letras y admitir hasta 100 caracteres.';
                }

                if (strlen($datos['correo']) > 254 || !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
                    $errores[] = 'Escribe un correo electrónico válido.';
                }

                if (mb_strlen($contrasena, 'UTF-8') < 8) {
                    $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
                }

                if (!mb_check_encoding($contrasena, 'UTF-8') || strlen($contrasena) > 72 || str_contains($contrasena, "\0")) {
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

            $segundosBloqueo = $this->segundosDeBloqueo();

            if (!$this->csrfValido()) {
                http_response_code(403);
                $errores[] = 'La sesión del formulario venció. Vuelve a enviarlo.';
            } elseif ($segundosBloqueo > 0) {
                http_response_code(429);
                header('Retry-After: ' . $segundosBloqueo);
                $errores[] = 'Demasiados intentos fallidos. Espera ' . (int) ceil($segundosBloqueo / 60) . ' minuto(s) antes de volver a intentarlo.';
            } elseif (strlen($correo) > 254 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $contrasena === '' || strlen($contrasena) > 72 || str_contains($contrasena, "\0")) {
                http_response_code(422);
                $errores[] = 'Revisa el correo y la contraseña.';
            } else {
                $modelo = new Usuario();
                $usuario = $modelo->buscarPorCorreo($correo);

                if ($usuario === null || !password_verify($contrasena, $usuario['contrasena']) || $usuario['estado'] !== 'activo') {
                    $this->registrarIntentoFallido();
                    http_response_code(401);
                    $errores[] = 'No se pudo iniciar sesión con esos datos.';
                } else {
                    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueado_hasta']);
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

    public function listarUsuarios(): void
    {
        if (!$this->exigirAdministrador()) {
            return;
        }

        $busqueda = $_GET['buscar'] ?? '';

        if (!is_string($busqueda) || !mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda, 'UTF-8') > 200) {
            http_response_code(400);
            echo 'Escribe una búsqueda de hasta 200 caracteres.';
            return;
        }

        $this->mostrarUsuarios(trim($busqueda));
    }

    public function eliminarUsuario(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Utiliza la confirmación del listado para eliminar un usuario.';
            return;
        }

        if (!$this->exigirAdministrador()) {
            return;
        }

        if (!$this->csrfValido()) {
            http_response_code(403);
            $this->mostrarUsuarios('', 'El formulario venció. Actualiza el listado y vuelve a intentarlo.');
            return;
        }

        $id = $this->idValido($_POST['id'] ?? null);

        if ($id === null) {
            http_response_code(400);
            $this->mostrarUsuarios('', 'El identificador del usuario es inválido.');
            return;
        }

        if (($_POST['confirmar'] ?? null) !== '1') {
            http_response_code(422);
            $this->mostrarUsuarios('', 'Debes confirmar la eliminación del usuario seleccionado.');
            return;
        }

        $modelo = new Usuario();

        if ($modelo->buscarPorId($id) === null) {
            http_response_code(404);
            $this->mostrarUsuarios('', 'El usuario seleccionado no existe.');
            return;
        }

        try {
            $cambio = $modelo->desactivar($id, $_SESSION['usuario_id']);
            $_SESSION['mensaje_usuarios'] = $cambio
                ? 'Usuario eliminado lógicamente: la cuenta quedó inactiva y conserva su historial.'
                : 'El usuario ya estaba inactivo. No se realizó otra modificación.';

            header('Location: index.php?pagina=usuarios', true, 303);
        } catch (DomainException $error) {
            http_response_code(422);
            $this->mostrarUsuarios('', $error->getMessage());
        }
    }

    private function mostrarUsuarios(string $busqueda, string $error = ''): void
    {
        $modelo = new Usuario();
        $usuarios = $modelo->listar($busqueda);
        $administradoresActivos = $modelo->administradoresActivos();
        $usuarioActualId = $_SESSION['usuario_id'];
        $seleccionado = null;

        if ($error === '' && isset($_GET['confirmar'])) {
            $id = $this->idValido($_GET['confirmar']);

            if ($id === null) {
                http_response_code(400);
                $error = 'El identificador del usuario es inválido.';
            } else {
                $seleccionado = $modelo->buscarPorId($id);

                if ($seleccionado === null) {
                    http_response_code(404);
                    $error = 'El usuario seleccionado no existe.';
                } elseif ($id === $usuarioActualId) {
                    http_response_code(422);
                    $error = 'No puedes eliminar tu propia cuenta.';
                } elseif ($seleccionado['estado'] !== 'activo') {
                    http_response_code(422);
                    $error = 'El usuario ya está inactivo.';
                } elseif ($seleccionado['rol'] === 'administrador' && $administradoresActivos <= 1) {
                    http_response_code(422);
                    $error = 'Debe quedar al menos un administrador activo.';
                }
            }
        }

        if ($error !== '') {
            $seleccionado = null;
        }

        $mensaje = $_SESSION['mensaje_usuarios'] ?? '';
        unset($_SESSION['mensaje_usuarios']);

        require __DIR__ . '/../vistas/usuarios.php';
    }

    private function idValido(mixed $valor): ?int
    {
        if (!is_string($valor) && !is_int($valor)) {
            return null;
        }

        $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);

        return $id === false ? null : $id;
    }

    private function nombreValido(string $valor, int $maximo): bool
    {
        return $valor !== '' && mb_check_encoding($valor, 'UTF-8')
            && mb_strlen($valor, 'UTF-8') <= $maximo
            && preg_match("/\A[\p{L}\p{M}]+(?:[ '\x{2019}-][\p{L}\p{M}]+)*\z/u", $valor) === 1;
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

    /** Segundos que faltan para que termine el bloqueo de inicio de sesión; 0 si no hay bloqueo. */
    private function segundosDeBloqueo(): int
    {
        $hasta = $_SESSION['login_bloqueado_hasta'] ?? 0;

        if (!is_int($hasta) || $hasta <= time()) {
            unset($_SESSION['login_bloqueado_hasta']);
            return 0;
        }

        return $hasta - time();
    }

    private function registrarIntentoFallido(): void
    {
        $intentos = $_SESSION['login_intentos'] ?? 0;
        $intentos = (is_int($intentos) ? $intentos : 0) + 1;

        if ($intentos >= self::MAX_INTENTOS_LOGIN) {
            $_SESSION['login_bloqueado_hasta'] = time() + self::SEGUNDOS_BLOQUEO_LOGIN;
            $intentos = 0;
        }

        $_SESSION['login_intentos'] = $intentos;
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
