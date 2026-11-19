<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if (usuario_actual()) {
    redirigir('dashboard.php');
}

$error = '';
$usuario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $usuario = post_texto('usuario', 30);
    $error = iniciar_sesion($usuario, (string) ($_POST['clave'] ?? ''));
    if ($error === null) {
        redirigir('dashboard.php');
    }
}
$demo = (bool) config_local()['mostrar_demo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Sistema de control y monitoreo de consumo energético en micromercados.">
  <meta name="theme-color" content="#0d1829">
  <title>Ingresar – Sistema de Monitoreo Energético</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <?php foreach (['variables', 'base', 'layout', 'componentes'] as $css): ?>
  <link rel="stylesheet" href="css/<?= $css ?>.css">
  <?php endforeach; ?>
</head>
<body>
<section id="login">
  <form class="lbox" method="post" autocomplete="on">
    <?= csrf_campo() ?>
    <div class="centrado"><div class="logo logo-lg" aria-hidden="true">EIS</div></div>
    <h1 class="centrado-txt">Monitoreo Energético</h1>
    <p class="mu centrado-txt">Micromercados · Informática Industrial</p>

    <label for="lu">Usuario</label>
    <input id="lu" name="usuario" value="<?= e($usuario) ?>" maxlength="30" autocomplete="username" autocapitalize="none" required autofocus>
    <label for="lp">Contraseña</label>
    <input id="lp" name="clave" type="password" autocomplete="current-password" required>
    <div class="err" role="alert"><?= e($error) ?></div>
    <button class="btn ancho-completo mt-8">Ingresar</button>

    <?php if ($demo): ?>
    <div class="demo">
      <p class="mu">Cuentas de demostración (clic para rellenar):</p>
      <button type="button" class="btn g s" data-demo-usuario="admin" data-demo-clave="admin123">Administrador</button>
      <button type="button" class="btn g s" data-demo-usuario="tecnico" data-demo-clave="tecnico123">Técnico</button>
    </div>
    <?php endif; ?>
  </form>
</section>
<div id="toasts" aria-live="polite">
  <?php foreach (avisos_pendientes() as [$mensaje, $tipo]): ?>
  <div class="toast <?= e($tipo) ?>" role="status"><?= e($mensaje) ?></div>
  <?php endforeach; ?>
</div>
<script src="js/app.js"></script>
</body>
</html>
