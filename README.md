# Sistema de Control y Monitoreo de Consumo Energético en Micromercados

Proyecto de grado – Informática Industrial
Escuela Industrial Superior "Pedro Domingo Murillo" · La Paz, Bolivia

**Autor:** Emanuel Rodolfo Yapura Ramos
**Tutor:** Ing. Juan Cazana Hidalgo

Plataforma web en **PHP + MySQL** para visualizar en tiempo real el consumo eléctrico de los equipos de un micromercado (refrigeración, iluminación y climatización), detectar sobreconsumos y generar reportes de energía y costo.

> **Estado:** funcional con **sensores virtuales**. El servidor PHP genera las lecturas y las guarda en MySQL; la conexión con el hardware real (ESP8266 + ACS712/SCT-013) es el siguiente paso (ver [`docs/arquitectura.md`](docs/arquitectura.md)).

## Funcionalidades

| Módulo | Qué permite |
|---|---|
| Dashboard | Consumo total, energía acumulada, alertas activas, sensores conectados y gráfica por zona |
| Sensores | Registrar, verificar, calibrar y conectar/desconectar sensores (CU-01 · HU-01) |
| Monitoreo | Potencia y corriente por equipo, historial y gráfica con umbral |
| Alertas | Configurar umbrales (HU-07) y registrar la atención de alertas (HU-08) |
| Reportes | Energía (kWh) y costo (Bs) por equipo o zona; exportación a CSV/Excel y PDF (CU-04) |
| Usuarios | Alta, cambio de rol y baja de usuarios (HU-12) |

Roles: **Administrador** (acceso total) y **Técnico** (Dashboard, Sensores y Monitoreo).

## Requisitos

- PHP **8.1 o superior** con las extensiones `pdo_mysql` y `mbstring` (vienen activas en XAMPP).
- MySQL **8.0+** o MariaDB **10.2+** (se usan funciones de ventana).
- Apache (XAMPP/WAMP/Laragon) o el servidor integrado de PHP.

## Cómo instalarlo (XAMPP)

1. Copie la carpeta `monitoreo-energetico` dentro de `htdocs`.
2. En el panel de XAMPP encienda **Apache** y **MySQL**.
3. Copie `config/config.local.example.php` como `config/config.local.php` y ajuste usuario y clave de MySQL si no usa los de XAMPP (`root` sin clave).
4. Abra `http://localhost/monitoreo-energetico/instalar.php` y pulse **Instalar**: crea la base `monitoreo_energetico`, las tablas y los datos de demostración.
5. **Elimine `instalar.php`** y entre por `http://localhost/monitoreo-energetico/`.

Sin Apache también sirve el servidor integrado:

```bash
php -S localhost:8000      # dentro de la carpeta del proyecto
```

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | `admin123` |
| Técnico | `tecnico` | `tecnico123` |

Para probar las alertas: en el Dashboard use **Provocar sobreconsumo** sobre un equipo.
Antes de publicarlo en un servidor real cambie estas contraseñas y ponga `'mostrar_demo' => false` en `config/config.local.php`.

## Estructura del proyecto

```
monitoreo-energetico/
├── index.php  login.php  logout.php       Entrada y sesión
├── dashboard.php  sensores.php            Una pantalla por archivo .php
├── monitoreo.php  alertas.php
├── reportes.php  reporte_csv.php  usuarios.php
├── vivo.php                               Refresco en vivo (toma una lectura y devuelve HTML en JSON)
├── instalar.php  restablecer.php          Instalación y restablecimiento de datos demo
├── app/
│   ├── bootstrap.php                      Sesión segura, cabeceras de seguridad, errores
│   ├── db.php  helpers.php  auth.php      PDO, escape/CSRF/avisos, login con bloqueo
│   ├── simulador.php                      Sensores virtuales y detección de alertas
│   ├── consultas.php                      Energía (kWh), series y últimas lecturas en SQL
│   ├── layout.php  instalacion.php        Plantilla común y creación de la base
│   └── vistas/                            Fragmentos que se refrescan en vivo
├── config/
│   ├── config.php                         Constantes (voltaje, intervalo, zonas, roles)
│   └── config.local.example.php           Datos de MySQL (copiar a config.local.php)
├── css/  js/  assets/                     Estilos, JS mínimo (gráficas y refresco), imágenes SVG
├── database/schema.sql                    Esquema MySQL
└── docs/                                  Arquitectura, requisitos, manual y guía de Git
```

## Tecnologías

PHP 8 (PDO) · MySQL/MariaDB · HTML/CSS generados desde PHP · JavaScript mínimo (Canvas para las gráficas y `fetch` para el refresco en vivo).
Arquitectura objetivo: Arduino/ESP8266, sensores ACS712 y SCT-013.

## Seguridad aplicada

- Contraseñas con `password_hash()` (bcrypt) y `password_verify()`; sesión regenerada al ingresar.
- Consultas preparadas (PDO) contra inyección SQL y escape de todo texto mostrado contra XSS.
- Token CSRF en todos los formularios que modifican datos; las acciones solo aceptan POST.
- Permisos por rol verificados **en el servidor** en cada pantalla y en el refresco en vivo.
- Bloqueo de 30 s tras 5 intentos fallidos desde la misma IP.
- CSV con neutralización de fórmulas de Excel; carpetas `app/`, `config/`, `database/` y `docs/` cerradas con `.htaccess`.

## Limitaciones conocidas

- Los datos siguen siendo simulados; el historial se conserva 30 minutos. Si el sistema pasa más de 2 minutos sin visitas, la ventana se reconstruye con lecturas simuladas.
- Las lecturas se generan mientras haya una pantalla abierta (cada 2 s); es un comportamiento propio del simulador y desaparece al conectar el hardware real.

## Próximos pasos

- [ ] Endpoint `api/lectura.php` (con clave de API) que reciba las lecturas del ESP8266 y las guarde en `lecturas`.
- [ ] Desactivar el simulador (`tick()`) cuando existan sensores reales.
- [ ] Notificaciones por correo o Telegram cuando se supere un umbral.

## Documentación

- [Arquitectura](docs/arquitectura.md)
- [Requisitos y casos de uso](docs/requisitos.md)
- [Manual de usuario](docs/manual-usuario.md)
- [Guía de Git y GitHub](docs/guia-git.md)
