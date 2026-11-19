<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
exigir_post();
cerrar_sesion();
redirigir('login.php');
