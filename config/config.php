<?php
declare(strict_types=1);

/** Constantes del sistema. Los datos de la base van en config/config.local.php (no se sube a Git). */

const VERSION = '3.0.0-php';
const VOLTAJE = 220;               // V (red monofásica)
const INTERVALO_S = 2;             // periodo de muestreo de los sensores virtuales
const VENTANA_MIN = 30;            // minutos de historial que se conservan
const PUNTOS_GRAFICO = 90;         // lecturas que se dibujan en las gráficas
const SIN_LECTURA_S = 7;           // sin datos por más de esto => "sin lectura reciente"
const HUECO_MAX_S = 10;            // intervalos mayores no se integran al calcular kWh
const TARIFA_DEFAULT = 0.95;       // Bs/kWh
const MAX_INTENTOS = 5;            // intentos fallidos antes de bloquear el login
const BLOQUEO_S = 30;
const CLAVE_MIN = 6;

const ZONAS = ['Refrigeración', 'Iluminación', 'Climatización'];
const COLOR_ZONA = ['Refrigeración' => '#3b82f6', 'Iluminación' => '#f5c518', 'Climatización' => '#2ecc8f'];
const ROLES = ['Administrador', 'Técnico'];
const SENSORES = ['ACS712', 'SCT-013'];
const ESTADOS_ALERTA = ['activa', 'atendida', 'resuelta automáticamente'];

/** Pantallas: archivo, icono, título, roles con acceso y texto de la cabecera. */
const VISTAS = [
    'dash' => ['archivo' => 'dashboard.php', 'icono' => '▦', 'titulo' => 'Dashboard', 'roles' => ['Administrador', 'Técnico'], 'sub' => ['Resumen de su micromercado', 'Consumo eléctrico en tiempo real']],
    'sen'  => ['archivo' => 'sensores.php',  'icono' => '⚙', 'titulo' => 'Sensores',  'roles' => ['Administrador', 'Técnico'], 'sub' => ['Sensores y equipos', 'Registre, calibre y verifique cada punto de medición']],
    'mon'  => ['archivo' => 'monitoreo.php', 'icono' => '◉', 'titulo' => 'Monitoreo', 'roles' => ['Administrador', 'Técnico'], 'sub' => ['Monitoreo en vivo', 'Potencia, corriente e historial por equipo']],
    'alr'  => ['archivo' => 'alertas.php',   'icono' => '⚠', 'titulo' => 'Alertas',   'roles' => ['Administrador'],            'sub' => ['Alertas de consumo', 'Detección automática de consumos anormales']],
    'rep'  => ['archivo' => 'reportes.php',  'icono' => '▤', 'titulo' => 'Reportes',  'roles' => ['Administrador'],            'sub' => ['Reportes de consumo', 'Energía, costos y alertas por periodo']],
    'usr'  => ['archivo' => 'usuarios.php',  'icono' => '☺', 'titulo' => 'Usuarios',  'roles' => ['Administrador'],            'sub' => ['Usuarios y roles', 'Control de acceso a la plataforma']],
];

/** Equipos de demostración: nombre, zona, consumo base (W), umbral (W), sensor. */
const EQUIPOS_DEMO = [
    ['Refrigerador de lácteos', 'Refrigeración', 450, 650, 'SCT-013'],
    ['Congelador de helados', 'Refrigeración', 380, 600, 'SCT-013'],
    ['Vitrina de bebidas', 'Refrigeración', 300, 500, 'ACS712'],
    ['Luminarias del salón', 'Iluminación', 220, 320, 'ACS712'],
    ['Letrero exterior', 'Iluminación', 90, 160, 'ACS712'],
    ['Aire acondicionado', 'Climatización', 900, 1300, 'SCT-013'],
    ['Extractor de ventilación', 'Climatización', 150, 260, 'ACS712'],
];
