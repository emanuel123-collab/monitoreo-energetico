<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
redirigir(usuario_actual() ? 'dashboard.php' : 'login.php');
