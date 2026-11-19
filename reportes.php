<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$u = requerir_vista('rep');

/** Reporte guardado (con sus filas) o null. */
function cargar_reporte(int $id): ?array
{
    $f = consulta('SELECT id, descripcion, tarifa_bs, total_kwh, total_bs, detalle, UNIX_TIMESTAMP(generado_en) AS t FROM reportes WHERE id = ?', [$id])->fetch();
    if (!$f) {
        return null;
    }
    return ['id' => (int) $f['id'], 'descripcion' => $f['descripcion'], 'tarifa' => (float) $f['tarifa_bs'],
            'totalKwh' => (float) $f['total_kwh'], 'totalCosto' => (float) $f['total_bs'], 'fecha' => fecha_hora((float) $f['t']),
            'filas' => json_decode($f['detalle'], true) ?: []];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {                       // CU-04: generar reporte
    exigir_post();
    $destino = post_texto('destino', 80);
    $minutos = (int) ($_POST['periodo'] ?? 30);
    $tarifa = max(0.0, min(9999.0, post_num('tarifa') ?? 0.0));
    if (!in_array($minutos, [5, 15, 30], true)) {
        $minutos = 30;
    }
    $desde = time() - $minutos * 60;
    $energia = energia_por_equipo($desde);
    $alertas = [];
    foreach (consulta('SELECT equipo_id, COUNT(*) AS n FROM alertas WHERE creada_en >= FROM_UNIXTIME(?) GROUP BY equipo_id', [$desde])->fetchAll() as $a) {
        $alertas[(int) $a['equipo_id']] = (int) $a['n'];
    }

    $filas = [];
    foreach (equipos() as $e) {
        $coincide = $destino === 'Todos los equipos' || $destino === 'Zona: ' . $e['zona'] || $destino === $e['nombre'];
        $d = $energia[$e['id']] ?? null;
        if (!$coincide || !$d || $d['n'] === 0) {
            continue;
        }
        $filas[] = ['nombre' => $e['nombre'], 'zona' => $e['zona'], 'prom' => $d['prom'], 'max' => $d['max'],
                    'kwh' => $d['kwh'], 'costo' => $d['kwh'] * $tarifa, 'alertas' => $alertas[$e['id']] ?? 0];
    }
    if (!$filas) {
        aviso('No hay datos disponibles para generar el reporte', 'er');
        redirigir('reportes.php');
    }
    $kwh = array_sum(array_column($filas, 'kwh'));
    consulta('INSERT INTO reportes (descripcion, tarifa_bs, total_kwh, total_bs, detalle, generado_por) VALUES (?, ?, ?, ?, ?, ?)',
        ["$destino · $minutos min", $tarifa, $kwh, $kwh * $tarifa, json_encode($filas, JSON_UNESCAPED_UNICODE), $u['id']]);
    aviso('Reporte generado', 'ok');
    redirigir('reportes.php?id=' . db()->lastInsertId());
}

tick();
$actual = isset($_GET['id']) ? cargar_reporte((int) $_GET['id']) : null;
$historicos = consulta('SELECT id, descripcion, UNIX_TIMESTAMP(generado_en) AS t FROM reportes ORDER BY id DESC LIMIT 50')->fetchAll();
$destinos = array_merge(['Todos los equipos'], array_map(fn($z) => "Zona: $z", ZONAS), array_column(equipos(), 'nombre'));
cabecera('rep');
?>
      <div class="card noprint">
        <h3>Generar reporte de consumo <small>(CU-04)</small></h3>
        <form method="post" class="row">
          <?= csrf_campo() ?>
          <div><label for="rq">Equipo o zona</label><select id="rq" name="destino"><?= opciones($destinos) ?></select></div>
          <div><label for="rp">Periodo</label>
            <select id="rp" name="periodo"><option value="5">Últimos 5 min</option><option value="15">Últimos 15 min</option><option value="30" selected>Últimos 30 min</option></select></div>
          <div><label for="rt">Tarifa (Bs/kWh)</label><input id="rt" name="tarifa" type="number" min="0" step="0.01" value="<?= TARIFA_DEFAULT ?>"></div>
          <button class="btn">Generar</button>
        </form>
      </div>

      <?php if ($actual): ?>
      <div class="card sc">
        <div class="zn"><h3>Reporte #<?= $actual['id'] ?> · <?= e($actual['descripcion']) ?></h3>
          <span class="noprint"><a class="btn g s" href="reporte_csv.php?id=<?= $actual['id'] ?>">Exportar Excel (CSV)</a>
          <button type="button" class="btn s" data-imprimir>Exportar PDF</button></span></div>
        <small>Generado: <?= e($actual['fecha']) ?> · Tarifa: <?= fmt_dec($actual['tarifa']) ?> Bs/kWh · Sistema de Monitoreo Energético – Micromercados</small>
        <table class="mt-10">
          <thead><tr><th>Equipo</th><th>Zona</th><th>Prom. (W)</th><th>Máx. (W)</th><th>Energía (kWh)</th><th>Costo (Bs)</th><th>Alertas</th></tr></thead>
          <tbody>
          <?php foreach ($actual['filas'] as $x): ?>
            <tr><td><?= e($x['nombre']) ?></td><td><?= e($x['zona']) ?></td><td><?= fmt((float) $x['prom']) ?></td><td><?= fmt((float) $x['max']) ?></td>
              <td><?= fmt_dec((float) $x['kwh'], 3) ?></td><td><?= fmt_dec((float) $x['costo']) ?></td><td><?= (int) $x['alertas'] ?></td></tr>
          <?php endforeach; ?>
            <tr><td colspan="4"><b>TOTAL</b></td><td><b><?= fmt_dec($actual['totalKwh'], 3) ?></b></td><td><b><?= fmt_dec($actual['totalCosto']) ?></b></td><td></td></tr>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <div class="card noprint sc">
        <h3>Reportes históricos</h3>
        <table><tbody>
        <?php if (!$historicos): ?><tr><td class="mu">Aún no se generaron reportes.</td></tr><?php endif; ?>
        <?php foreach ($historicos as $r): ?>
          <tr><td>#<?= (int) $r['id'] ?></td><td><?= e(fecha_hora((float) $r['t'])) ?></td><td><?= e($r['descripcion']) ?></td>
            <td><a class="btn g s" href="reportes.php?id=<?= (int) $r['id'] ?>">Abrir</a></td></tr>
        <?php endforeach; ?>
        </tbody></table>
      </div>
<?php pie();
