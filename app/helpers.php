<?php
declare(strict_types=1);

/** Escapa texto para mostrarlo en HTML (evita XSS). */
function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Número entero con separador de miles (es-BO). */
function fmt(float|int $n): string
{
    return number_format(round($n), 0, ',', '.');
}

function fmt_dec(float|int $n, int $dec = 2): string
{
    return number_format($n, $dec, ',', '.');
}

function hora(float|int $t): string
{
    return date('H:i:s', (int) $t);
}

function fecha_hora(float|int $t): string
{
    return date('d/m/Y H:i:s', (int) $t);
}

function redirigir(string $destino): never
{
    header('Location: ' . $destino);
    exit;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Exige que la petición sea POST con un token CSRF válido. */
function exigir_post(): void
{
    $ok = $_SERVER['REQUEST_METHOD'] === 'POST'
        && hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''));
    if (!$ok) {
        http_response_code(400);
        exit('Solicitud no válida. Vuelva atrás y recargue la página.');
    }
}

/* ---------- Avisos (toasts) que sobreviven a una redirección ---------- */

function aviso(string $mensaje, string $tipo = ''): void
{
    $_SESSION['avisos'][] = [$mensaje, $tipo];
}

function avisos_pendientes(): array
{
    $a = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return $a;
}

/* ---------- Entrada ---------- */

function post_texto(string $campo, int $max = 255): string
{
    return mb_substr(trim((string) ($_POST[$campo] ?? '')), 0, $max);
}

function post_num(string $campo): ?float
{
    $v = str_replace(',', '.', trim((string) ($_POST[$campo] ?? '')));
    return is_numeric($v) ? (float) $v : null;
}

function get_texto(string $campo, string $defecto = ''): string
{
    return isset($_GET[$campo]) && is_string($_GET[$campo]) ? $_GET[$campo] : $defecto;
}

/** Texto seguro para celdas CSV: neutraliza fórmulas de Excel (=, +, -, @) y protege ; " y saltos. */
function celda_csv(mixed $v): string
{
    $t = (string) $v;
    if ($t !== '' && str_contains('=+-@', $t[0])) {
        $t = "'" . $t;
    }
    return preg_match('/[;"\n\r]/', $t) ? '"' . str_replace('"', '""', $t) . '"' : $t;
}
