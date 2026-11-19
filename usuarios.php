<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$yo = requerir_vista('usr');

function total_admins(): int
{
    return (int) consulta("SELECT COUNT(*) FROM usuarios WHERE rol = 'Administrador'")->fetchColumn();
}

$error = '';
$v = ['nombre' => '', 'usuario' => '', 'rol' => ROLES[0]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $accion = post_texto('accion', 20);
    $objetivo = consulta('SELECT id, usuario, rol FROM usuarios WHERE id = ?', [(int) ($_POST['id'] ?? 0)])->fetch();

    if ($accion === 'agregar') {                                   // HU-12
        $v = ['nombre' => post_texto('nombre', 60), 'usuario' => post_texto('usuario', 30), 'rol' => post_texto('rol', 20)];
        $clave = (string) ($_POST['clave'] ?? '');
        if ($v['nombre'] === '' || $v['usuario'] === '' || mb_strlen($clave) < CLAVE_MIN || !in_array($v['rol'], ROLES, true)) {
            $error = 'Complete los campos (contraseña de al menos ' . CLAVE_MIN . ' caracteres).';
        } elseif (consulta('SELECT 1 FROM usuarios WHERE usuario = ?', [$v['usuario']])->fetch()) {
            $error = 'Ese nombre de usuario ya existe.';
        } else {
            consulta('INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)',
                [$v['nombre'], $v['usuario'], password_hash($clave, PASSWORD_DEFAULT), $v['rol']]);
            aviso('Usuario registrado', 'ok');
            redirigir('usuarios.php');
        }
    } elseif ($objetivo && $accion === 'rol') {
        $rol = post_texto('rol', 20);
        if ((int) $objetivo['id'] === (int) $yo['id']) {
            aviso('No puede cambiar su propio rol.', 'er');
        } elseif (!in_array($rol, ROLES, true)) {
            aviso('Rol no válido.', 'er');
        } elseif ($objetivo['rol'] === 'Administrador' && $rol !== 'Administrador' && total_admins() === 1) {
            aviso('Debe existir al menos un administrador', 'er');
        } else {
            consulta('UPDATE usuarios SET rol = ? WHERE id = ?', [$rol, $objetivo['id']]);
            aviso('Rol actualizado', 'ok');
        }
        redirigir('usuarios.php');
    } elseif ($objetivo && $accion === 'eliminar') {
        if ((int) $objetivo['id'] === (int) $yo['id']) {
            aviso('No puede eliminar su propio usuario.', 'er');
        } elseif ($objetivo['rol'] === 'Administrador' && total_admins() === 1) {
            aviso('Debe existir al menos un administrador', 'er');
        } else {
            consulta('DELETE FROM usuarios WHERE id = ?', [$objetivo['id']]);
            aviso('Usuario eliminado', 'ok');
        }
        redirigir('usuarios.php');
    }
}

tick();
$usuarios = consulta('SELECT id, nombre, usuario, rol FROM usuarios ORDER BY id')->fetchAll();
cabecera('usr');
?>
      <div class="card">
        <h3>Registrar usuario <small>(HU-12)</small></h3>
        <form method="post" class="row">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="agregar">
          <div><label for="un">Nombre</label><input id="un" name="nombre" maxlength="60" value="<?= e($v['nombre']) ?>"></div>
          <div><label for="uu">Usuario</label><input id="uu" name="usuario" maxlength="30" autocomplete="off" value="<?= e($v['usuario']) ?>"></div>
          <div><label for="up">Contraseña</label><input id="up" name="clave" type="password" autocomplete="new-password"></div>
          <div><label for="ur">Rol</label><select id="ur" name="rol"><?= opciones(ROLES, $v['rol']) ?></select></div>
          <button class="btn">Registrar</button>
        </form>
        <div class="err" role="alert"><?= e($error) ?></div>
      </div>
      <div class="card sc">
        <h3>Usuarios del sistema</h3>
        <table>
          <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($usuarios as $x): $soyYo = (int) $x['id'] === (int) $yo['id']; ?>
            <tr><td><?= e($x['nombre']) ?></td><td><?= e($x['usuario']) ?></td>
              <td>
                <form method="post" class="en-linea"><?= csrf_campo() ?><input type="hidden" name="accion" value="rol"><input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                  <select name="rol" class="w-auto" aria-label="Rol de <?= e($x['usuario']) ?>" data-autoenviar <?= $soyYo ? 'disabled' : '' ?>><?= opciones(ROLES, $x['rol']) ?></select>
                  <noscript><?php if (!$soyYo): ?><button class="btn g s">Guardar</button><?php endif; ?></noscript>
                </form>
              </td>
              <td><?php if (!$soyYo): ?>
                <form method="post" class="en-linea" data-confirm="¿Eliminar al usuario «<?= e($x['usuario']) ?>»?"><?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
                  <button class="btn r s">Eliminar</button></form>
              <?php endif; ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
<?php pie();
