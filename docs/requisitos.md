# Requisitos y casos de uso implementados

Las referencias (HU = historia de usuario, CU = caso de uso) son las que aparecen en la interfaz. Complete la tabla con el resto de las historias del perfil del proyecto.

| Código | Descripción | Rol | Pantalla | Estado |
|---|---|---|---|---|
| CU-01 · HU-01 | Registrar un nuevo sensor/equipo | Administrador, Técnico | Sensores | Implementado |
| HU-07 | Configurar umbrales de consumo por equipo | Administrador | Alertas | Implementado |
| HU-08 | Consultar el registro de alertas y registrar su atención | Administrador | Alertas | Implementado |
| CU-04 | Generar reporte de consumo (energía y costo) y exportarlo | Administrador | Reportes | Implementado |
| HU-12 | Registrar usuarios y asignar roles | Administrador | Usuarios | Implementado |
| — | Monitoreo en tiempo real por equipo y zona | Administrador, Técnico | Dashboard, Monitoreo | Implementado |
| — | Calibrar, verificar y conectar/desconectar sensores | Administrador, Técnico | Sensores | Implementado |

## Reglas de negocio

- No se pueden registrar dos equipos con el mismo nombre.
- Un usuario no puede cambiar su propio rol ni eliminarse; siempre debe existir al menos un administrador.
- El umbral debe ser mayor que 0 W; el factor de calibración está entre 0.50 y 1.50.
- Cinco intentos fallidos de inicio de sesión desde la misma IP bloquean el acceso durante 30 s.
- La contraseña de un usuario nuevo debe tener al menos 6 caracteres y se guarda cifrada (bcrypt).
- Los permisos por rol se verifican en el servidor, no solo en el menú.
- El Técnico no puede ver Alertas, Reportes ni Usuarios.
