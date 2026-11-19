<?php
declare(strict_types=1);

/** Configuración local (config/config.local.php) con valores por defecto de XAMPP/MySQL local. */
function config_local(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $defecto = [
            'db_host' => '127.0.0.1', 'db_port' => 3306, 'db_name' => 'monitoreo_energetico',
            'db_user' => 'root', 'db_pass' => '', 'mostrar_demo' => true,
        ];
        $archivo = __DIR__ . '/../config/config.local.php';
        $cfg = is_file($archivo) ? array_merge($defecto, (array) require $archivo) : $defecto;
    }
    return $cfg;
}

/** Conexión PDO única. Con $sinBase = true se conecta sin elegir base (para instalar.php). */
function db(bool $sinBase = false): PDO
{
    static $pdo = null, $pdoSinBase = null;
    $c = config_local();
    if ($sinBase ? $pdoSinBase === null : $pdo === null) {
        $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};charset=utf8mb4" . ($sinBase ? '' : ";dbname={$c['db_name']}");
        $conexion = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $conexion->exec("SET time_zone = '-04:00'");   // hora de Bolivia, igual en PHP y MySQL
        if ($sinBase) {
            $pdoSinBase = $conexion;
        } else {
            $pdo = $conexion;
        }
    }
    return $sinBase ? $pdoSinBase : $pdo;
}

/** Atajo: ejecuta una consulta preparada y devuelve el statement. */
function consulta(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
