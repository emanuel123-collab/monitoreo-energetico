<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/instalacion.php';

if (!es_admin()) {
    redirigir('login.php');
}
exigir_post();
sembrar_demo();
// Los ids de usuario se reinician: se vuelve a iniciar sesión como el administrador de demostración.
session_regenerate_id(true);
$_SESSION['uid'] = (int) consulta("SELECT id FROM usuarios WHERE usuario = 'admin'")->fetchColumn();
aviso('Datos de demostración restablecidos.', 'ok');
redirigir('dashboard.php');
