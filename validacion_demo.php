<?php
$mensaje = "";
$nombre = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    if ($nombre === "") {
        $mensaje = "Error: el nombre del sensor no puede estar vacío.";
    } else {
        $mensaje = "Registro válido para el sensor: " .
            htmlspecialchars($nombre, ENT_QUOTES, "UTF-8");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validación de sensor</title>
</head>
<body>
    <h2>Validación de nombre de sensor</h2>
    <form method="POST">
        <label>Nombre del sensor:</label>
        <input type="text" name="nombre"
               value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Validar</button>
    </form>
    <p><?= $mensaje ?></p>
</body>
</html>