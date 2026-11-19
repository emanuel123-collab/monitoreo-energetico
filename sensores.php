<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

requerir_vista('sen');

$error = '';
$v = ['nombre' => '', 'zona' => ZONAS[0], 'sensor' => SENSORES[0], 'umbral' => '500'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $accion = post_texto('accion', 20);
    $e = equipo((int) ($_POST['id'] ?? 0));

    if ($accion === 'agregar') {                                   // CU-01 · HU-01
        $v = ['nombre' => post_texto('nombre', 60), 'zona' => post_texto('zona', 40), 'sensor' => post_texto('sensor', 10), 'umbral' => post_texto('umbral', 10)];
        $umbral = post_num('umbral');
        if ($v['nombre'] === '' || $umbral === null || $umbral <= 0 || $umbral > 20000
            || !in_array($v['zona'], ZONAS, true) || !in_array($v['sensor'], SENSORES, true)) {
            $error = 'Complete todos los campos del formulario (umbral entre 1 y 20 000 W).';
        } elseif (consulta('SELECT 1 FROM equipos WHERE LOWER(nombre) = LOWER(?)', [$v['nombre']])->fetch()) {
            $error = 'El sensor/equipo ya se encuentra registrado. Verifique los datos ingresados.';
        } else {
            consulta('INSERT INTO equipos (nombre, zona_id, sensor, base_w, umbral_w) VALUES (?, (SELECT id FROM zonas WHERE nombre = ?), ?, ?, ?)',
                [$v['nombre'], $v['zona'], $v['sensor'], $umbral * 0.6, $umbral]);
            $nuevo = equipo((int) db()->lastInsertId());
            historial_inicial($nuevo, PUNTOS_GRAFICO);
            aviso("Sensor registrado: {$v['nombre']}", 'ok');
            redirigir('sensores.php');
        }
    } elseif ($e && $accion === 'verificar') {
        $est = estado_sensor($e, ultimas_lecturas()[$e['id']]['t'] ?? null);
        aviso("{$e['nombre']}: $est", $est === 'conectado' ? 'ok' : 'er');
        redirigir('sensores.php');
    } elseif ($e && $accion === 'calibrar') {
        $factor = post_num('factor');
        if ($factor === null || $factor < 0.5 || $factor > 1.5) {
            aviso('El factor de calibración debe estar entre 0.50 y 1.50.', 'er');
        } else {
            consulta('UPDATE equipos SET calibracion = ? WHERE id = ?', [round($factor, 2), $e['id']]);
            aviso('Calibración actualizada', 'ok');
        }
        redirigir('sensores.php');
    } elseif ($e && $accion === 'alternar') {
        consulta('UPDATE equipos SET conectado = 1 - conectado WHERE id = ?', [$e['id']]);
        redirigir('sensores.php');
    }
}

tick();
$ult = ultimas_lecturas();
cabecera('sen');
?>
      <div class="card">
        <h3>Registrar nuevo sensor / equipo <small>(CU-01 · HU-01)</small></h3>
        <form method="post" class="row">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="agregar">
          <div><label for="nn">Equipo</label><input id="nn" name="nombre" maxlength="60" placeholder="Ej: Cámara frigorífica" value="<?= e($v['nombre']) ?>"></div>
          <div><label for="nz">Zona</label><select id="nz" name="zona"><?= opciones(ZONAS, $v['zona']) ?></select></div>
          <div><label for="nm">Tipo de sensor</label><select id="nm" name="sensor"><?= opciones(SENSORES, $v['sensor']) ?></select></div>
          <div><label for="nu">Umbral inicial (W)</label><input id="nu" name="umbral" type="number" min="1" max="20000" step="any" value="<?= e($v['umbral']) ?>"></div>
          <button class="btn">Registrar</button>
        </form>
        <div class="err" role="alert"><?= e($error) ?></div>
      </div>
      <div class="card sc">
        <h3>Sensores registrados</h3>
        <table>
          <thead><tr><th>ID</th><th>Equipo</th><th>Zona</th><th>Sensor</th><th>Calibración</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php foreach (equipos() as $e):
              $est = estado_sensor($e, $ult[$e['id']]['t'] ?? null); ?>
            <tr>
              <td>S-<?= str_pad((string) $e['id'], 2, '0', STR_PAD_LEFT) ?></td><td><?= e($e['nombre']) ?></td><td><?= e($e['zona']) ?></td><td><?= e($e['sensor']) ?></td>
              <td>×<?= number_format((float) $e['calibracion'], 2, '.', '') ?></td>
              <td><span class="pill <?= clase_pill($est) ?>"><?= e($est) ?></span></td>
              <td>
                <form method="post" class="en-linea"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
                  <button class="btn g s" name="accion" value="verificar">Verificar</button>
                  <input class="in-cal" type="number" name="factor" min="0.5" max="1.5" step="0.01" value="<?= number_format((float) $e['calibracion'], 2, '.', '') ?>" aria-label="Factor de calibración de <?= e($e['nombre']) ?> (0.50 a 1.50)">
                  <button class="btn g s" name="accion" value="calibrar">Calibrar</button>
                  <button class="btn g s" name="accion" value="alternar"><?= $e['conectado'] ? 'Desconectar' : 'Conectar' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
<?php pie();
