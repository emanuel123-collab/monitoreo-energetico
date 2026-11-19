<?php
declare(strict_types=1);
/**
 * Instalador: crea la base de datos, las tablas y los datos de demostración.
 * Funciona una sola vez; cuando termine, ELIMINE este archivo del servidor.
 */
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/instalacion.php';

$mensaje = '';
$ok = false;
$yaInstalado = false;
try {
    $yaInstalado = (int) consulta('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
} catch (Throwable $ex) {
    $yaInstalado = false;   // base o tablas aún no existen
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    if ($yaInstalado) {
        $mensaje = 'El sistema ya está instalado.';
    } else {
        try {
            crear_esquema();
            sembrar_demo();
            $ok = true;
            $mensaje = 'Instalación completa.';
        } catch (Throwable $ex) {
            error_log((string) $ex);
            $mensaje = 'No se pudo instalar. Revise que MySQL esté encendido y los datos de config/config.local.php.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalación – Monitoreo Energético</title>
  <link rel="stylesheet" href="css/variables.css"><link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css"><link rel="stylesheet" href="css/componentes.css">
</head>
<body>
<section id="login"><div class="lbox">
  <div class="centrado"><div class="logo logo-lg" aria-hidden="true">EIS</div></div>
  <h1 class="centrado-txt">Instalación</h1>
  <?php if ($mensaje): ?><p class="<?= $ok ? 'txt-ok' : 'err' ?> mt-10"><?= e($mensaje) ?></p><?php endif; ?>
  <?php if ($ok): ?>
    <p class="mu mt-10">Usuarios de demostración: <code>admin / admin123</code> y <code>tecnico / tecnico123</code>.</p>
    <p class="err">Por seguridad, elimine ahora el archivo <code>instalar.php</code> del servidor.</p>
    <a class="btn ancho-completo mt-8 en-bloque" href="login.php">Ir al inicio de sesión</a>
  <?php elseif ($yaInstalado): ?>
    <p class="mu mt-10">El sistema ya está instalado. Elimine <code>instalar.php</code> del servidor.</p>
    <a class="btn ancho-completo mt-8 en-bloque" href="login.php">Ir al inicio de sesión</a>
  <?php else: ?>
    <p class="mu mt-10">Se creará la base <code><?= e(config_local()['db_name']) ?></code> en <code><?= e(config_local()['db_host']) ?></code> con las tablas y los datos de demostración.</p>
    <form method="post"><?= csrf_campo() ?><button class="btn ancho-completo mt-8">Instalar</button></form>
  <?php endif; ?>
</div></section>
</body>
</html>
