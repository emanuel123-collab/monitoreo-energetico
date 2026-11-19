<?php
declare(strict_types=1);

/** Cabecera común: <head>, barra lateral, encabezado y hero de la pantalla. Abre <div id="view">. */
function cabecera(string $vistaId): void
{
    $u = usuario_actual();
    $info = VISTAS[$vistaId];
    $activas = alertas_activas();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0d1829">
  <title><?= e($info['titulo']) ?> – Sistema de Monitoreo Energético</title>
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <?php foreach (['variables', 'base', 'layout', 'componentes', 'impresion'] as $css): ?>
  <link rel="stylesheet" href="css/<?= $css ?>.css">
  <?php endforeach; ?>
</head>
<body>
<div id="app">
  <aside class="side">
    <div class="brand"><div class="logo" aria-hidden="true">EIS</div><div>Monitoreo Energético<small>Micromercados</small></div></div>
    <nav id="nav" aria-label="Secciones">
      <?php foreach (VISTAS as $id => $v): if (!in_array($u['rol'], $v['roles'], true)) continue; ?>
      <a class="nv <?= $id === $vistaId ? 'on' : '' ?>" href="<?= e($v['archivo']) ?>" <?= $id === $vistaId ? 'aria-current="page"' : '' ?>>
        <span aria-hidden="true"><?= $v['icono'] ?></span><?= e($v['titulo']) ?>
        <?php if ($id === 'alr' && $activas): ?><span class="bd"><?= $activas ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="side-pie">
      <?php if (es_admin()): ?>
      <form method="post" action="restablecer.php" data-confirm="Se borrarán los usuarios, sensores, alertas, lecturas y reportes y se restaurarán los datos de demostración. ¿Continuar?">
        <?= csrf_campo() ?>
        <button class="btn g s ancho-completo">Restablecer datos demo</button>
      </form>
      <?php endif; ?>
      <small>v<?= e(VERSION) ?></small>
    </div>
  </aside>
  <main class="main">
    <header class="top">
      <div><h2><?= e($info['titulo']) ?></h2><small id="clk"><?= e(date('d/m/Y H:i:s')) ?></small></div>
      <div>
        <span class="pill bl"><?= e($u['rol']) ?></span> <b><?= e($u['nombre']) ?></b>
        <form method="post" action="logout.php" class="en-linea"><?= csrf_campo() ?><button class="btn g s">Salir</button></form>
      </div>
    </header>
    <div id="view">
      <div class="hero hero--<?= e($vistaId) ?>"><h3><?= e($info['sub'][0]) ?></h3><p><?= e($info['sub'][1]) ?></p></div>
<?php
}

/**
 * Cierra la página. $vivo = ['vista' => 'dash', 'query' => 'zona=Todas'] activa el refresco
 * automático del contenedor #vivo con lo que devuelve vivo.php.
 */
function pie(?array $vivo = null): void
{
    ?>
    </div>
  </main>
</div>
<div id="toasts" aria-live="polite">
  <?php foreach (avisos_pendientes() as [$mensaje, $tipo]): ?>
  <div class="toast <?= e($tipo) ?>" role="<?= $tipo === 'er' ? 'alert' : 'status' ?>"><?= e($mensaje) ?></div>
  <?php endforeach; ?>
</div>
<div id="config" hidden
     data-ultima-alerta="<?= id_ultima_alerta() ?>"
     data-intervalo="<?= INTERVALO_S * 1000 ?>"
     <?php if ($vivo): ?>data-vista="<?= e($vivo['vista']) ?>" data-query="<?= e($vivo['query'] ?? '') ?>"<?php endif; ?>></div>
<script src="js/graficos.js"></script>
<script src="js/app.js"></script>
</body>
</html>
<?php
}

/** <option> para un select, marcando el valor actual. */
function opciones(array $lista, ?string $actual = null): string
{
    $h = '';
    foreach ($lista as $x) {
        $h .= '<option' . ((string) $x === (string) $actual ? ' selected' : '') . '>' . e($x) . '</option>';
    }
    return $h;
}
