<?php
declare(strict_types=1);

/** Usuario con sesión iniciada (se consulta en cada petición para que un cambio de rol se aplique al instante). */
function usuario_actual(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $fila = consulta('SELECT id, nombre, usuario, rol FROM usuarios WHERE id = ?', [$_SESSION['uid']])->fetch();
            $u = $fila ?: null;
            if (!$u) {
                unset($_SESSION['uid']);
            }
        }
    }
    return $u;
}

function es_admin(): bool
{
    $u = usuario_actual();
    return $u !== null && $u['rol'] === 'Administrador';
}

function puede_ver(string $vistaId): bool
{
    $u = usuario_actual();
    return $u !== null && isset(VISTAS[$vistaId]) && in_array($u['rol'], VISTAS[$vistaId]['roles'], true);
}

/** Exige sesión y permiso para la pantalla; si no, redirige. Devuelve el usuario. */
function requerir_vista(string $vistaId): array
{
    $u = usuario_actual();
    if (!$u) {
        redirigir('login.php');
    }
    if (!puede_ver($vistaId)) {
        aviso('No tiene permiso para ver esa pantalla.', 'er');
        redirigir('dashboard.php');
    }
    return $u;
}

function ip_cliente(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/** Segundos de bloqueo restantes para esta IP (0 = sin bloqueo). */
function bloqueo_restante(): int
{
    $st = consulta(
        'SELECT COUNT(*) AS n, MAX(UNIX_TIMESTAMP(creado_en)) AS ultimo FROM intentos_login
         WHERE ip = ? AND creado_en >= NOW() - INTERVAL ? SECOND',
        [ip_cliente(), BLOQUEO_S]
    )->fetch();
    if ((int) $st['n'] < MAX_INTENTOS) {
        return 0;
    }
    return max(1, (int) ceil($st['ultimo'] + BLOQUEO_S - time()));
}

/** Intenta iniciar sesión. Devuelve null si fue bien o el mensaje de error. */
function iniciar_sesion(string $usuario, string $clave): ?string
{
    $espera = bloqueo_restante();
    if ($espera > 0) {
        return "Demasiados intentos. Espere {$espera} s.";
    }
    $u = consulta('SELECT id, clave FROM usuarios WHERE usuario = ?', [$usuario])->fetch();
    // Se verifica siempre contra un hash para no revelar si el usuario existe por el tiempo de respuesta.
    $hash = $u['clave'] ?? '$2y$10$hFJeXFHPhXTvNYwSMFAdPOwi91VLxTtxD52LpW9FRy.0aEGFaJVVK';
    if (!password_verify($clave, $hash) || !$u) {
        consulta('INSERT INTO intentos_login (ip) VALUES (?)', [ip_cliente()]);
        return bloqueo_restante() > 0
            ? 'Acceso bloqueado temporalmente por intentos fallidos.'
            : 'Credenciales incorrectas. Intente nuevamente.';
    }
    consulta('DELETE FROM intentos_login WHERE ip = ?', [ip_cliente()]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return null;
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
