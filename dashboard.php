<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/vistas/dashboard.php';

requerir_vista('dash');

// Panel de simulación: provocar / normalizar un sobreconsumo (sensores virtuales).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $e = equipo((int) ($_POST['equipo'] ?? 0));
    if ($e) {
        $falla = (int) ($_POST['falla'] ?? 0) === 1 ? 1 : 0;
        consulta('UPDATE equipos SET falla = ? WHERE id = ?', [$falla, $e['id']]);
        aviso($falla ? "Sobreconsumo simulado en {$e['nombre']}" : "{$e['nombre']} vuelve a consumo normal", $falla ? 'er' : 'ok');
    }
    redirigir('dashboard.php');
}

tick();
cabecera('dash');
?>
      <div id="vivo"><?php vivo_dash(); ?></div>
      <div class="card noprint">
        <h3>Panel de simulación (sensores virtuales)</h3>
        <p class="mu mb-10">Provoque un sobreconsumo para ver cómo reacciona el sistema de alertas.</p>
        <form method="post" class="row">
          <?= csrf_campo() ?>
          <div><label for="sf">Equipo</label>
            <select id="sf" name="equipo">
              <?php foreach (equipos() as $e): ?><option value="<?= (int) $e['id'] ?>"><?= e($e['nombre']) ?><?= $e['falla'] ? ' (en falla)' : '' ?></option><?php endforeach; ?>
            </select></div>
          <button class="btn r" name="falla" value="1">Provocar sobreconsumo</button>
          <button class="btn g" name="falla" value="0">Normalizar</button>
        </form>
      </div>
<?php pie(['vista' => 'dash']);
