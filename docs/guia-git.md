# Guía de Git y GitHub para este proyecto

## Primera vez (Actividad 9)

```bash
git config --global user.name "Emanuel Rodolfo Yapura Ramos"
git config --global user.email "tu_correo@gmail.com"

cd /d E:\ruta\monitoreo-energetico
git init
git add .
git commit -m "feat: estructura inicial del sistema de monitoreo energético"
```

En GitHub cree el repositorio `monitoreo-energetico` (público, **sin** README, .gitignore ni licencia, porque el proyecto ya los incluye) y luego:

```bash
git remote add origin https://github.com/TU_USUARIO/monitoreo-energetico.git
git branch -M main
git push -u origin main
```

Para añadir al tutor o a un compañero: **Settings → Collaborators → Add people**.

## Trabajo diario

```bash
git status                       # qué cambió
git add .                        # preparar cambios
git commit -m "fix: corrige el cálculo de kWh"
git push                         # subir a GitHub
git pull                         # traer cambios de otros
```

## Convención de mensajes de commit

| Prefijo | Uso |
|---|---|
| `feat:` | Nueva funcionalidad |
| `fix:` | Corrección de un error |
| `docs:` | Cambios en documentación |
| `style:` | Formato o estilos, sin cambiar la lógica |
| `refactor:` | Reorganizar código sin cambiar lo que hace |

## Ramas (para probar cambios sin romper `main`)

```bash
git switch -c feature/notificaciones   # crear y cambiar de rama
git push -u origin feature/notificaciones
# al terminar: abrir un Pull Request en GitHub y fusionar a main
```
