<?php
declare(strict_types=1);

function filtro_alerta(string $valor): string
{
    return in_array($valor, ESTADOS_ALERTA, true) ? $valor : 'Todas';
}

function pill_alerta(string $estado): string
{
    return $estado === 'activa' ? 'er' : ($estado === 'atendida' ? 'bl' : 'ok');
}

/** Registro de alertas (parte "viva"): se refresca solo cuando cambia. */
function vivo_alr(string $filtro): void
{
    $sql = 'SELECT a.id, a.potencia_w, a.umbral_w, a.estado, UNIX_TIMESTAMP(a.creada_en) AS t, e.nombre
            FROM alertas a JOIN equipos e ON e.id = a.equipo_id'
         . ($filtro !== 'Todas' ? ' WHERE a.estado = ?' : '') . ' ORDER BY a.id DESC LIMIT 200';
    $lista = consulta($sql, $filtro !== 'Todas' ? [$filtro] : [])->fetchAll();
    ?>
    <table>
      <thead><tr><th>Hora</th><th>Equipo</th><th>Lectura</th><th>Umbral</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php if (!$lista): ?>
        <tr><td colspan="6" class="mu">Sin alertas registradas.</td></tr>
      <?php endif; foreach ($lista as $a): ?>
        <tr><td><?= hora((float) $a['t']) ?></td><td><?= e($a['nombre']) ?></td>
          <td><?= fmt((float) $a['potencia_w']) ?> W</td><td><?= fmt((float) $a['umbral_w']) ?> W</td>
          <td><span class="pill <?= pill_alerta($a['estado']) ?>"><?= e($a['estado']) ?></span></td>
          <td><?php if ($a['estado'] === 'activa'): ?>
            <form method="post" action="alertas.php" class="en-linea"><?= csrf_campo() ?>
              <input type="hidden" name="accion" value="atender"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
              <button class="btn s">Registrar atención</button></form>
          <?php endif; ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php
}
