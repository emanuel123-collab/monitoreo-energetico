<?php
declare(strict_types=1);

/** Arranque común: configuración, sesión segura y utilidades. Todas las páginas lo incluyen primero. */
require __DIR__ . '/../config/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/simulador.php';
require __DIR__ . '/consultas.php';
require __DIR__ . '/layout.php';

date_default_timezone_set('America/La_Paz');
mb_internal_encoding('UTF-8');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none'");

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_name('monitoreo');
session_start();

/** Errores de base de datos: mensaje claro en vez de un volcado técnico. */
set_exception_handler(function (Throwable $ex): void {
    error_log((string) $ex);
    http_response_code(500);
    $sinInstalar = $ex instanceof PDOException && in_array((string) $ex->getCode(), ['1049', '42S02'], true);
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Error</title><body style="font:16px system-ui;max-width:560px;margin:60px auto;padding:0 16px">';
    if ($sinInstalar) {
        echo '<h1>Base de datos sin instalar</h1><p>Abra <a href="instalar.php">instalar.php</a> para crear las tablas y los datos de demostración.</p>';
    } elseif ($ex instanceof PDOException) {
        echo '<h1>No se pudo conectar a la base de datos</h1><p>Revise que MySQL esté encendido y los datos de <code>config/config.local.php</code>.</p>';
    } else {
        echo '<h1>Ocurrió un error</h1><p>Revise el registro de errores de PHP.</p>';
    }
});
