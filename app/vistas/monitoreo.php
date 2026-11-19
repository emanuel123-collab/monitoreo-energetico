<?php
declare(strict_types=1);

/** Parámetros del monitoreo validados: zona, periodo (min) y equipo seleccionado. */
function monitoreo_params(array $entrada): array
{
    $zona = $entrada['zona'] ?? 'Todas';
    $zona = in_array($zona, ZONAS, true) ? $zona : 'Todas';
    $periodo = (int) ($entrada['periodo'] ?? 30);
    $periodo = in_array($periodo, [5, 15, 30], true) ? $periodo : 30;
    return ['zona' => $zona, 'periodo' => $periodo, 'equipo' => (int) ($entrada['equipo'] ?? 0)];
}

/** Parte "viva" del monitoreo: tabla por equipo, gráfica de detalle e historial. */
function vivo_mon(array $p): void
{
    $lista = array_values(array_filter(equipos(), fn($e) => $p['zona'] === 'Todas' || $e['zona'] === $p['zona']));
    $ult = ultimas_lecturas();
    $series = series_recientes();

    $sel = null;
    foreach ($lista as $e) {
        if ((int) $e['id'] === $p['equipo']) {
            $sel = $e;
        }
    }
    $sel ??= $lista[0] ?? null;
    $base = 'monitoreo.php?' . http_build_query(['zona' => $p['zona'], 'periodo' => $p['periodo']]);
    ?>
    <div class="card sc">
      <h3>Consumo por equipo <small>(clic para ver detalle)</small></h3>
      <table>
        <thead><tr><th>Equipo</th><th>Zona</th><th>Potencia</th><th>Corriente</th><th>% del umbral</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $e):
            $w = $ult[$e['id']]['w'] ?? 0;
            $umbral = (float) $e['umbral_w'];
            $pct = $w / $umbral * 100;
            $est = estado_sensor($e, $ult[$e['id']]['t'] ?? null);
            $off = $est === 'desconectado';
            $color = $pct > 100 ? 'var(--er)' : ($pct > 80 ? 'var(--wa)' : 'var(--ok)'); ?>
          <tr class="cl <?= $sel && $e['id'] === $sel['id'] ? 'sel' : '' ?>" data-href="<?= e($base . '&equipo=' . $e['id']) ?>">
            <td><?= e($e['nombre']) ?></td><td><?= e($e['zona']) ?></td>
            <td><b><?= $off ? '—' : fmt($w) . ' W' ?></b></td>
            <td><?= $off ? '—' : fmt_dec($w / VOLTAJE) . ' A' ?></td>
            <td><div class="bar"><i style="width:<?= min(100, $pct) ?>%;background:<?= $color ?>"></i></div></td>
            <td><span class="pill <?= clase_pill($est) ?>"><span class="dot"></span><?= e($est) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($sel):
        $graf = [['color' => COLOR_ZONA[$sel['zona']], 'datos' => $series[$sel['id']] ?? []]]; ?>
    <div class="card">
      <h3>Detalle: <?= e($sel['nombre']) ?> (umbral <?= fmt((float) $sel['umbral_w']) ?> W)</h3>
      <canvas class="cv" data-series="<?= e(json_encode($graf)) ?>" data-umbral="<?= (float) $sel['umbral_w'] ?>" aria-label="Gráfica de potencia del equipo"></canvas>
    </div>
    <div class="card sc">
      <h3>Historial de lecturas</h3>
      <table>
        <thead><tr><th>Hora</th><th>Potencia (W)</th><th>Corriente (A)</th><th>Resultado</th></tr></thead>
        <tbody>
        <?php
        $filas = consulta(
            'SELECT potencia_w, UNIX_TIMESTAMP(tomada_en) AS t FROM lecturas
             WHERE equipo_id = ? AND tomada_en >= NOW(3) - INTERVAL ? MINUTE ORDER BY id DESC LIMIT 40',
            [$sel['id'], $p['periodo']]
        )->fetchAll();
        foreach ($filas as $f):
            $w = (float) $f['potencia_w'];
            $alto = $w > (float) $sel['umbral_w']; ?>
          <tr><td><?= hora((float) $f['t']) ?></td><td><?= fmt($w) ?></td><td><?= fmt_dec($w / VOLTAJE) ?></td>
              <td><span class="pill <?= $alto ? 'er' : 'ok' ?>"><?= $alto ? 'Sobre umbral' : 'Normal' ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="card"><p class="mu">No hay equipos en esta zona.</p></div>
    <?php endif;
}
