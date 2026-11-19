<?php
declare(strict_types=1);

/** Última lectura de cada equipo: [equipo_id => ['w' => float, 't' => float]]. */
function ultimas_lecturas(): array
{
    $r = [];
    $sql = 'SELECT l.equipo_id, l.potencia_w, UNIX_TIMESTAMP(l.tomada_en) AS t
            FROM lecturas l JOIN (SELECT MAX(id) AS id FROM lecturas GROUP BY equipo_id) m ON m.id = l.id';
    foreach (consulta($sql)->fetchAll() as $f) {
        $r[(int) $f['equipo_id']] = ['w' => (float) $f['potencia_w'], 't' => (float) $f['t']];
    }
    return $r;
}

/** Últimas $n potencias de cada equipo en orden cronológico: [equipo_id => [w, w, ...]]. */
function series_recientes(int $n = PUNTOS_GRAFICO): array
{
    $sql = 'SELECT equipo_id, potencia_w FROM (
              SELECT equipo_id, potencia_w, id, ROW_NUMBER() OVER (PARTITION BY equipo_id ORDER BY id DESC) AS rn
              FROM lecturas WHERE tomada_en >= NOW(3) - INTERVAL ? MINUTE
            ) x WHERE rn <= ? ORDER BY equipo_id, id';
    $r = [];
    foreach (consulta($sql, [VENTANA_MIN, $n])->fetchAll() as $f) {
        $r[(int) $f['equipo_id']][] = (float) $f['potencia_w'];
    }
    return $r;
}

/** Suma, lectura a lectura (alineadas por el final), la potencia de los equipos indicados. */
function serie_suma(array $ids, array $series, int $n = PUNTOS_GRAFICO): array
{
    $suma = array_fill(0, $n, 0.0);
    foreach ($ids as $id) {
        $datos = $series[$id] ?? [];
        $c = count($datos);
        for ($k = 0; $k < $n; $k++) {
            $i = $c - $n + $k;
            if ($i >= 0) {
                $suma[$k] += $datos[$i];
            }
        }
    }
    return $suma;
}

/**
 * Energía (kWh) por equipo desde `$desde` (timestamp) hasta la última lectura, integrando la
 * potencia con la regla del trapecio y las marcas de tiempo reales:
 *   kWh = Σ ((P[i-1] + P[i]) / 2) · Δt / 3 600 000   (Δt en ms)
 * Los intervalos mayores a HUECO_MAX_S (sensor sin datos) no se integran.
 * Devuelve [equipo_id => ['kwh', 'prom', 'max', 'n']].
 */
function energia_por_equipo(int $desde): array
{
    $sql = 'SELECT equipo_id,
                   SUM(CASE WHEN pw IS NOT NULL AND dt <= ? THEN (w + pw) / 2 * dt / 3600 / 1000 ELSE 0 END) AS kwh,
                   AVG(CASE WHEN t >= ? THEN w END) AS prom,
                   MAX(CASE WHEN t >= ? THEN w END) AS maximo,
                   SUM(t >= ?) AS n
            FROM (
              SELECT equipo_id, potencia_w AS w, UNIX_TIMESTAMP(tomada_en) AS t,
                     LAG(potencia_w) OVER (PARTITION BY equipo_id ORDER BY tomada_en, id) AS pw,
                     UNIX_TIMESTAMP(tomada_en) - LAG(UNIX_TIMESTAMP(tomada_en)) OVER (PARTITION BY equipo_id ORDER BY tomada_en, id) AS dt
              FROM lecturas WHERE tomada_en >= FROM_UNIXTIME(?)
            ) x
            WHERE t >= ?
            GROUP BY equipo_id';
    $margen = $desde - HUECO_MAX_S;
    $r = [];
    foreach (consulta($sql, [HUECO_MAX_S, $desde, $desde, $desde, $margen, $desde])->fetchAll() as $f) {
        $r[(int) $f['equipo_id']] = [
            'kwh' => (float) $f['kwh'], 'prom' => (float) $f['prom'], 'max' => (float) $f['maximo'], 'n' => (int) $f['n'],
        ];
    }
    return $r;
}

function alertas_activas(): int
{
    return (int) consulta("SELECT COUNT(*) FROM alertas WHERE estado = 'activa'")->fetchColumn();
}

function id_ultima_alerta(): int
{
    return (int) consulta('SELECT COALESCE(MAX(id), 0) FROM alertas')->fetchColumn();
}

/** Alertas creadas después de la indicada, para avisar en pantalla. */
function alertas_nuevas(int $desdeId): array
{
    return consulta(
        'SELECT a.id, a.potencia_w, a.umbral_w, e.nombre FROM alertas a JOIN equipos e ON e.id = a.equipo_id
         WHERE a.id > ? ORDER BY a.id',
        [$desdeId]
    )->fetchAll();
}
