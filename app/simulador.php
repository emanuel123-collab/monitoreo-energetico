<?php
declare(strict_types=1);

/**
 * Sensores virtuales: generan lecturas de potencia (W) y detectan sobreconsumos.
 * Cuando el hardware real (ESP8266 + ACS712/SCT-013) esté conectado, este módulo se reemplaza
 * por un endpoint que reciba las lecturas y las guarde en la tabla `lecturas`; el resto no cambia.
 */

/** Lectura simulada: consumo base ±8 %, ×1.8 si hay falla, ajustada por calibración. */
function lectura_simulada(array $e): float
{
    $azar = 0.92 + (mt_rand() / mt_getrandmax()) * 0.16;
    return (float) $e['base_w'] * ($e['falla'] ? 1.8 : 1) * $azar * (float) $e['calibracion'];
}

/** Equipos con el nombre de su zona. */
function equipos(): array
{
    return consulta(
        'SELECT e.id, e.nombre, z.nombre AS zona, e.sensor, e.base_w, e.umbral_w, e.calibracion, e.conectado, e.falla
         FROM equipos e JOIN zonas z ON z.id = e.zona_id ORDER BY e.id'
    )->fetchAll();
}

function equipo(int $id): ?array
{
    foreach (equipos() as $e) {
        if ((int) $e['id'] === $id) {
            return $e;
        }
    }
    return null;
}

/** Genera `n` lecturas pasadas, una cada INTERVALO_S, terminando ahora (solo sensores virtuales). */
function historial_inicial(array $e, int $n = VENTANA_MIN * 60 / INTERVALO_S): void
{
    $fin = microtime(true);
    $filas = [];
    $params = [];
    for ($i = $n; $i > 0; $i--) {
        $filas[] = '(?, ?, FROM_UNIXTIME(?))';
        array_push($params, $e['id'], round(lectura_simulada($e), 2), number_format($fin - $i * INTERVALO_S, 3, '.', ''));
    }
    $pdo = db();
    $pdo->prepare('DELETE FROM lecturas WHERE equipo_id = ?')->execute([$e['id']]);
    foreach (array_chunk($filas, 300) as $k => $trozo) {
        $pdo->prepare('INSERT INTO lecturas (equipo_id, potencia_w, tomada_en) VALUES ' . implode(',', $trozo))
            ->execute(array_slice($params, $k * 300 * 3, count($trozo) * 3));
    }
}

/**
 * Toma una lectura de cada equipo conectado (como máximo una cada INTERVALO_S, aunque haya
 * varias pestañas abiertas) y gestiona las alertas.
 */
function tick(): void
{
    $pdo = db();
    $bloqueo = $pdo->query("SELECT GET_LOCK('monitoreo_tick', 0)")->fetchColumn();
    if (!$bloqueo) {
        return;   // otra petición ya está muestreando
    }
    try {
        $ahora = microtime(true);
        $ultimas = [];
        foreach (consulta('SELECT equipo_id, MAX(UNIX_TIMESTAMP(tomada_en)) AS t FROM lecturas GROUP BY equipo_id')->fetchAll() as $f) {
            $ultimas[(int) $f['equipo_id']] = (float) $f['t'];
        }
        $abiertas = [];
        foreach (consulta('SELECT id, equipo_id FROM alertas WHERE abierta = 1')->fetchAll() as $a) {
            $abiertas[(int) $a['equipo_id']] = $a;
        }

        foreach (equipos() as $e) {
            if (!$e['conectado']) {
                continue;
            }
            $id = (int) $e['id'];
            $ultima = $ultimas[$id] ?? null;
            if ($ultima === null || $ahora - $ultima > 120) {
                historial_inicial($e);          // sensor nuevo o servidor inactivo: se reconstruye la ventana
                $ultima = $ahora - INTERVALO_S;
            }
            if ($ahora - $ultima < INTERVALO_S - 0.25) {
                continue;
            }
            $w = round(lectura_simulada($e), 2);
            consulta('INSERT INTO lecturas (equipo_id, potencia_w, tomada_en) VALUES (?, ?, FROM_UNIXTIME(?))',
                [$id, $w, number_format($ahora, 3, '.', '')]);

            $umbral = (float) $e['umbral_w'];
            if ($w > $umbral && !isset($abiertas[$id])) {
                consulta('INSERT INTO alertas (equipo_id, potencia_w, umbral_w) VALUES (?, ?, ?)', [$id, $w, $umbral]);
            } elseif ($w <= $umbral && isset($abiertas[$id])) {
                consulta("UPDATE alertas SET abierta = 0, estado = IF(estado = 'activa', 'resuelta automáticamente', estado) WHERE id = ?",
                    [$abiertas[$id]['id']]);
            }
        }
        if (mt_rand(1, 20) === 1) {
            consulta('DELETE FROM lecturas WHERE tomada_en < NOW(3) - INTERVAL ? MINUTE', [VENTANA_MIN]);
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('monitoreo_tick')");
    }
}

function estado_sensor(array $e, ?float $ultimaT): string
{
    if (!$e['conectado']) {
        return 'desconectado';
    }
    return ($ultimaT === null || microtime(true) - $ultimaT > SIN_LECTURA_S) ? 'sin lectura reciente' : 'conectado';
}

function clase_pill(string $estado): string
{
    return $estado === 'conectado' ? 'ok' : ($estado === 'desconectado' ? 'er' : 'wa');
}
