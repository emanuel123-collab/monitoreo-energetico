<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

requerir_vista('rep');

$id = (int) get_texto('id', '0');
$r = consulta('SELECT id, total_kwh, total_bs, detalle FROM reportes WHERE id = ?', [$id])->fetch();
if (!$r) {
    http_response_code(404);
    exit('Reporte no encontrado.');
}
$filas = json_decode($r['detalle'], true) ?: [];

$lineas = [['Equipo', 'Zona', 'Promedio W', 'Maximo W', 'kWh', 'Costo Bs', 'Alertas']];
foreach ($filas as $x) {
    $lineas[] = [$x['nombre'], $x['zona'], number_format((float) $x['prom'], 0, '.', ''), number_format((float) $x['max'], 0, '.', ''),
                 number_format((float) $x['kwh'], 3, '.', ''), number_format((float) $x['costo'], 2, '.', ''), $x['alertas']];
}
$lineas[] = ['TOTAL', '', '', '', number_format((float) $r['total_kwh'], 3, '.', ''), number_format((float) $r['total_bs'], 2, '.', ''), ''];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_' . $r['id'] . '.csv"');
echo "\xEF\xBB\xBF" . implode("\r\n", array_map(fn($f) => implode(';', array_map('celda_csv', $f)), $lineas));
