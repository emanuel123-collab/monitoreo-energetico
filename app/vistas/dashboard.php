<?php
declare(strict_types=1);

/** Parte "viva" del dashboard: KPIs, consumo por zona y gráfica. La devuelve vivo.php en cada muestreo. */
function vivo_dash(): void
{
    $eqs = equipos();
    $ult = ultimas_lecturas();
    $series = series_recientes();
    $energia = energia_por_equipo(time() - VENTANA_MIN * 60);

    $conectados = array_filter($eqs, fn($e) => $e['conectado']);
    $total = array_sum(array_map(fn($e) => $ult[$e['id']]['w'] ?? 0, $conectados));
    $kwh = array_sum(array_column($energia, 'kwh'));
    $nConectados = count(array_filter($eqs, fn($e) => estado_sensor($e, $ult[$e['id']]['t'] ?? null) === 'conectado'));
    $activas = alertas_activas();
    ?>
    <div class="grid">
      <div class="card"><small>Consumo total en tiempo real</small><div class="kpi"><b><?= fmt($total) ?></b><span> W</span></div></div>
      <div class="card"><small>Energía acumulada (últimos <?= VENTANA_MIN ?> min)</small><div class="kpi"><b><?= fmt_dec($kwh) ?></b><span> kWh</span></div></div>
      <div class="card"><small>Alertas activas</small><div class="kpi"><span class="<?= $activas ? 'txt-er' : 'txt-ok' ?>"><?= $activas ?></span></div></div>
      <div class="card"><small>Sensores conectados</small><div class="kpi"><?= $nConectados ?> / <?= count($eqs) ?></div></div>
    </div>
    <div class="grid">
      <?php foreach (ZONAS as $z):
          $delaZona = array_filter($eqs, fn($e) => $e['zona'] === $z);
          $w = array_sum(array_map(fn($e) => $e['conectado'] ? ($ult[$e['id']]['w'] ?? 0) : 0, $delaZona));
          $umbral = array_sum(array_column($delaZona, 'umbral_w')) ?: 1;
          $alto = $w > $umbral; ?>
      <div class="card">
        <div class="zn"><b><?= e($z) ?></b><span class="pill <?= $alto ? 'er' : 'ok' ?>"><?= $alto ? 'Alto' : 'Normal' ?></span></div>
        <div class="kpi kpi-sm"><?= fmt($w) ?><span> W</span></div>
        <div class="bar mt-8"><i style="width:<?= min(100, $w / $umbral * 100) ?>%;background:<?= COLOR_ZONA[$z] ?>"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php
    $graf = [['color' => '#ffffff', 'datos' => serie_suma(array_column($conectados, 'id'), $series)]];
    foreach (ZONAS as $z) {
        $ids = array_column(array_filter($conectados, fn($e) => $e['zona'] === $z), 'id');
        $graf[] = ['color' => COLOR_ZONA[$z], 'datos' => serie_suma($ids, $series)];
    }
    ?>
    <div class="card">
      <h3>Consumo por zona (últimas <?= PUNTOS_GRAFICO ?> lecturas)</h3>
      <canvas class="cv" data-series="<?= e(json_encode($graf)) ?>" aria-label="Gráfica de consumo por zona"></canvas>
      <div class="lg">
        <span><b style="background:#fff"></b>Total</span>
        <?php foreach (ZONAS as $z): ?><span><b style="background:<?= COLOR_ZONA[$z] ?>"></b><?= e($z) ?></span><?php endforeach; ?>
      </div>
    </div>
    <?php
}
