# Historial de cambios

## [3.0.0] – 2026-11
### Migración a PHP + MySQL
- Las pantallas pasan de un `index.html` con JavaScript a archivos `.php` (`dashboard.php`, `sensores.php`, `monitoreo.php`, `alertas.php`, `reportes.php`, `usuarios.php`) que generan el HTML en el servidor.
- Los datos (usuarios, sensores, lecturas, alertas y reportes) se guardan en MySQL con PDO en lugar de `localStorage`.
- El simulador de sensores corre en PHP (`app/simulador.php`) y el navegador solo pide el refresco a `vivo.php`.
- Instalador (`instalar.php`) que crea la base, las tablas y los datos de demostración.

### Seguridad
- Contraseñas con bcrypt (`password_hash`), consultas preparadas, token CSRF, permisos por rol en el servidor, bloqueo de login por IP y cabeceras HTTP de seguridad.

### Cambios visibles
- Calibrar se hace con una casilla en la fila del sensor (ya no hay cuadro emergente).
- Los umbrales se guardan juntos con **Guardar umbrales**.
- La contraseña mínima de un usuario nuevo pasa de 4 a 6 caracteres y ya no se muestra el hash en la lista de usuarios.
- Los kWh no integran los tramos de más de 10 s sin lecturas.

## [2.0.0] – 2026-11
### Reestructuración
- El código se separó en módulos (`js/`), hojas de estilo (`css/`), imágenes locales (`assets/`), base de datos (`database/`) y documentación (`docs/`).
- Se eliminó el JavaScript escrito dentro del HTML: los eventos se manejan por delegación (`data-accion`, `data-cambio`).

### Mejoras
- **Seguridad:** todo texto escrito por el usuario se escapa antes de mostrarse (evita XSS); bloqueo temporal tras 5 intentos fallidos de login; la exportación CSV neutraliza fórmulas de Excel; las pantallas se validan también por rol.
- **Datos:** los usuarios, sensores, umbrales, alertas y reportes se guardan en `localStorage`; botón "Restablecer datos demo" para el administrador.
- **Cálculo:** la energía (kWh) ahora se integra con las marcas de tiempo reales de las lecturas (antes se asumía un intervalo fijo).
- **Interfaz:** cuadro de diálogo propio para calibrar (reemplaza `prompt()`), imágenes locales (funciona sin internet), accesibilidad básica (etiquetas, `aria-*`, foco visible) y menú adaptado a celular.
- **Reglas de usuarios:** no se puede eliminar ni degradar al último administrador, ni cambiar el propio rol.

### Cambios visibles
- El KPI "Energía estimada hoy" pasa a llamarse "Energía acumulada (últimos 30 min)", porque la demo solo conserva 30 minutos de historial.

## [1.0.0] – 2026-11
- Primera versión: demo con sensores virtuales en `index.html`, `css/estilos.css` y `js/app.js`.
