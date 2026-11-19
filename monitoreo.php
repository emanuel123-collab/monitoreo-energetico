<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/vistas/monitoreo.php';

requerir_vista('mon');
$p = monitoreo_params($_GET);

tick();
cabecera('mon');
?>
      <div class="card">
        <form method="get" class="row">
          <div><label for="fz">Zona</label><select id="fz" name="zona" data-autoenviar><?= opciones(['Todas', ...ZONAS], $p['zona']) ?></select></div>
          <div><label for="fp">Periodo del historial</label>
            <select id="fp" name="periodo" data-autoenviar>
              <?php foreach ([5 => 'Últimos 5 min', 15 => 'Últimos 15 min', 30 => 'Últimos 30 min'] as $min => $txt): ?>
              <option value="<?= $min ?>" <?= $min === $p['periodo'] ? 'selected' : '' ?>><?= e($txt) ?></option>
              <?php endforeach; ?>
            </select></div>
          <noscript><button class="btn">Aplicar</button></noscript>
        </form>
      </div>
      <div id="vivo"><?php vivo_mon($p); ?></div>
<?php pie(['vista' => 'mon', 'query' => http_build_query($p)]);
