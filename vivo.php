<?php
declare(strict_types=1);
/**
 * Refresco en vivo: toma una lectura de los sensores virtuales y devuelve en JSON el HTML
 * de la parte dinámica de la pantalla, las alertas activas y los avisos de alertas nuevas.
 */
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/vistas/dashboard.php';
require __DIR__ . '/app/vistas/monitoreo.php';
require __DIR__ . '/app/vistas/alertas.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$u = usuario_actual();
$vista = get_texto('v');
session_write_close();   // no bloquea otras peticiones de la misma sesión

if (!$u) {
    http_response_code(401);
    exit('{"error":"sesion"}');
}
if (!in_array($vista, ['dash', 'mon', 'alr'], true) || !puede_ver($vista)) {
    http_response_code(403);
    exit('{"error":"permiso"}');
}

tick();

ob_start();
match ($vista) {
    'dash' => vivo_dash(),
    'mon'  => vivo_mon(monitoreo_params($_GET)),
    'alr'  => vivo_alr(filtro_alerta(get_texto('filtro', 'Todas'))),
};
$html = ob_get_clean();

$desde = (int) get_texto('desde', '0');
$nuevas = array_map(
    fn($a) => sprintf('⚠ %s: %s W supera el umbral de %s W', $a['nombre'], fmt((float) $a['potencia_w']), fmt((float) $a['umbral_w'])),
    alertas_nuevas($desde)
);

echo json_encode([
    'html' => $html,
    'alertas' => alertas_activas(),
    'nuevas' => $nuevas,
    'ultima' => id_ultima_alerta(),
], JSON_UNESCAPED_UNICODE);
