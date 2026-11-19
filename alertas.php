<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/vistas/alertas.php';

$u = requerir_vista('alr');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $accion = post_texto('accion', 20);
    $destino = 'alertas.php';

    if ($accion === 'umbrales') {                                  // HU-07
        $nuevos = (array) ($_POST['umbral'] ?? []);
        $validos = [];
        foreach (equipos() as $e) {
            $x = str_replace(',', '.', (string) ($nuevos[$e['id']] ?? ''));
            if (!is_numeric($x) || (float) $x <= 0 || (float) $x > 20000) {
                aviso("El umbral de {$e['nombre']} debe ser mayor que 0 y no pasar de 20 000 W.", 'er');
                $validos = null;
                break;
            }
            $validos[$e['id']] = (float) $x;
        }
        if ($validos !== null) {
            foreach ($validos as $id => $umbral) {
                consulta('UPDATE equipos SET umbral_w = ? WHERE id = ?', [$umbral, $id]);
            }
            aviso('Umbrales actualizados', 'ok');
        }
    } elseif ($accion === 'atender') {                             // HU-08
        $n = consulta("UPDATE alertas SET estado = 'atendida', atendida_por = ? WHERE id = ? AND estado = 'activa'",
            [$u['id'], (int) ($_POST['id'] ?? 0)])->rowCount();
        if ($n) {
            aviso('Atención registrada', 'ok');
        }
        $destino .= '?' . http_build_query(['filtro' => filtro_alerta(post_texto('filtro', 30))]);
    }
    redirigir($destino);
}

$filtro = filtro_alerta(get_texto('filtro', 'Todas'));
tick();
cabecera('alr');
?>
      <div class="card sc">
        <h3>Configuración de umbrales <small>(HU-07)</small></h3>
        <form method="post">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="umbrales">
          <table>
            <thead><tr><th>Equipo</th><th>Zona</th><th>Umbral (W)</th></tr></thead>
            <tbody>
            <?php foreach (equipos() as $e): ?>
              <tr><td><?= e($e['nombre']) ?></td><td><?= e($e['zona']) ?></td>
                <td><input type="number" class="in-num" name="umbral[<?= (int) $e['id'] ?>]" min="1" max="20000" step="any"
                     value="<?= e((float) $e['umbral_w']) ?>" aria-label="Umbral de <?= e($e['nombre']) ?>"></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <button class="btn mt-10">Guardar umbrales</button>
        </form>
      </div>
      <div class="card sc">
        <div class="zn">
          <h3>Registro de alertas <small>(HU-08)</small></h3>
          <form method="get"><select name="filtro" class="w-auto" aria-label="Filtrar por estado" data-autoenviar><?= opciones(['Todas', ...ESTADOS_ALERTA], $filtro) ?></select>
            <noscript><button class="btn s">Filtrar</button></noscript></form>
        </div>
        <div id="vivo"><?php vivo_alr($filtro); ?></div>
      </div>
<?php pie(['vista' => 'alr', 'query' => http_build_query(['filtro' => $filtro])]);
