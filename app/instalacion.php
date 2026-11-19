<?php
declare(strict_types=1);

/** Crea la base y las tablas a partir de database/schema.sql. */
function crear_esquema(): void
{
    $cfg = config_local();
    $nombre = preg_replace('/[^A-Za-z0-9_]/', '', (string) $cfg['db_name']);
    $pdo = db(true);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$nombre` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$nombre`");

    $sql = preg_replace('/--[^\n]*/', '', (string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
        if (preg_match('/^(CREATE DATABASE|USE)\b/i', $sentencia)) {
            continue;
        }
        $pdo->exec($sentencia);
    }
}

/** Borra los datos y carga los usuarios, equipos y lecturas de demostración. */
function sembrar_demo(): void
{
    $pdo = db();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['lecturas', 'alertas', 'reportes', 'equipos', 'usuarios', 'intentos_login'] as $tabla) {
        $pdo->exec("TRUNCATE TABLE `$tabla`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $ins = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)');
    $ins->execute(['Administrador del local', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrador']);
    $ins->execute(['Técnico de campo', 'tecnico', password_hash('tecnico123', PASSWORD_DEFAULT), 'Técnico']);

    foreach (ZONAS as $z) {
        $pdo->prepare('INSERT IGNORE INTO zonas (nombre) VALUES (?)')->execute([$z]);
    }
    $zonas = array_column($pdo->query('SELECT id, nombre FROM zonas')->fetchAll(), 'id', 'nombre');

    $ins = $pdo->prepare('INSERT INTO equipos (nombre, zona_id, sensor, base_w, umbral_w) VALUES (?, ?, ?, ?, ?)');
    foreach (EQUIPOS_DEMO as [$nombre, $zona, $base, $umbral, $sensor]) {
        $ins->execute([$nombre, $zonas[$zona], $sensor, $base, $umbral]);
    }
    foreach (equipos() as $e) {
        historial_inicial($e);
    }
}
