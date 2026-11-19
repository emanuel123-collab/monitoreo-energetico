# Manual de usuario

## 1. Ingresar
Abra el sistema en el navegador (por ejemplo `http://localhost/monitoreo-energetico/`), escriba usuario y contraseña (o pulse **Administrador** / **Técnico** para rellenar la cuenta de demostración) y presione **Ingresar**. Cinco intentos fallidos bloquean el acceso 30 s. Use **Salir** (arriba a la derecha) para cerrar la sesión.

## 2. Dashboard
Muestra el consumo total, la energía acumulada, las alertas activas y los sensores conectados, además del consumo por zona; se actualiza solo cada 2 s. El panel **Simulación** permite provocar un sobreconsumo en un equipo (×1.8) y **Normalizar** para volver a la normalidad.

## 3. Sensores
- **Registrar:** complete equipo, zona, tipo de sensor y umbral; pulse **Registrar**.
- **Verificar:** comprueba si el sensor envía lecturas recientes.
- **Calibrar:** escriba el factor (0.50 – 1.50) en la casilla de la fila y pulse **Calibrar**.
- **Desconectar / Conectar:** simula el corte o la reconexión del sensor.

## 4. Monitoreo
Filtre por zona y periodo. Haga clic en un equipo para ver su gráfica (la línea roja es el umbral) y su historial de lecturas.

## 5. Alertas (Administrador)
Edite los umbrales y pulse **Guardar umbrales**. Cuando la lectura supera el umbral aparece un aviso y una fila **activa** en el registro; use **Registrar atención** para marcarla como atendida. Si el consumo vuelve a la normalidad antes, se marca como *resuelta automáticamente*.

## 6. Reportes (Administrador)
Elija equipo o zona, periodo y tarifa (Bs/kWh) y pulse **Generar**. Puede exportar a **CSV** (se abre en Excel) o a **PDF** (el navegador abre el cuadro de impresión: elija "Guardar como PDF"). Los reportes quedan guardados en la base de datos.

## 7. Usuarios (Administrador)
Registre usuarios (contraseña de al menos 6 caracteres), cambie su rol o elimínelos. Las contraseñas se guardan cifradas (bcrypt) y no se pueden ver.

## 8. Restablecer datos
El botón **Restablecer datos demo** (barra lateral, solo Administrador) borra usuarios, sensores, lecturas, alertas y reportes de la base de datos y restaura los datos iniciales.
