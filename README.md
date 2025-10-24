# Scios Git Bridge

Este repositorio contiene el código del plugin **Scios Git Bridge** para WordPress. El complemento automatiza despliegues desde GitHub, genera respaldos previos, ejecuta pruebas básicas y mantiene registros centralizados de cada operación.

## Requisitos previos

- WordPress 5.9 o superior y PHP 7.4 o superior en el entorno donde se instalará el plugin.【F:wp-content/plugins/scios-git-bridge/scios-git-bridge.php†L18-L41】
- Acceso de administrador al sitio WordPress para activar el plugin y configurar sus opciones.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L24-L75】
- Tokens o llaves con permisos suficientes para descargar el código fuente y disparar workflows en GitHub (ver sección de *Secrets*).

## Instalación

1. Copia el directorio `wp-content/plugins/scios-git-bridge/` dentro de la instalación de WordPress objetivo.
2. Accede al panel de administración del sitio y activa **Scios Git Bridge** desde el menú *Plugins*.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L34-L53】
3. Tras la activación, aparecerá una entrada *Scios Git Bridge* en el menú de administración. Úsala para completar la configuración inicial.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L34-L75】

## Configuración inicial

En la pantalla de ajustes del plugin se almacenan todas las credenciales necesarias para comunicar WordPress con GitHub.

### Campos obligatorios

- **URL del repositorio**: dirección completa del repositorio Git que se usará como origen del despliegue.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L55-L91】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L78-L140】
- **Rama de despliegue**: rama que se descargará cuando se ejecute un despliegue.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L92-L98】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L50-L110】
- **Clave de despliegue**: token personal o deploy key con permisos de lectura sobre el repositorio para consumir el ZIP desde la API de GitHub.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L92-L98】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L158-L210】

### Opciones para snapshots remotos

El plugin puede disparar workflows de GitHub Actions que generen snapshots del proyecto. Configura los siguientes campos para habilitar esta característica:

- **Repositorio (owner/repo)**: repositorio donde vive el workflow encargado del snapshot. Puede inferirse desde la URL principal si se deja vacío.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L100-L123】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L200-L284】
- **Workflow de snapshot**: nombre del archivo `.yml` o ID del workflow a ejecutar.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L100-L123】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L250-L268】
- **Token de snapshot**: token personal con permisos para `workflow_dispatch` en el repositorio que aloja el flujo.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L100-L123】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L88-L150】

## Manejo de secrets

- Los valores se guardan como opciones en WordPress. Para evitar que queden expuestos en historiales o respaldos locales, escribe los secretos desde la interfaz de administración o mediante `wp option update` en un entorno seguro.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L180-L216】
- El token de despliegue se envía como encabezado `Authorization: token` al descargar el ZIP del repositorio. Asegúrate de que sólo tenga permisos de lectura.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L158-L210】
- El token de snapshot se usa en una cabecera `Authorization: Bearer` al invocar la API de GitHub Actions. Debe limitarse a scopes mínimos (por ejemplo, `repo` y `workflow`).【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L82-L146】
- Si administras múltiples entornos, considera aplicar un filtro (`scios_git_bridge_logs_directory`, `scios_git_bridge_smoke_test_endpoints`) para derivar rutas y endpoints basados en constantes definidas en `wp-config.php`. Esto evita que secretos aparezcan en rutas predecibles.【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Logger.php†L24-L87】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Smoke_Test_Service.php†L60-L132】

## Uso general

1. Desde el panel *Scios Git Bridge* puedes lanzar:
   - **Dry run** para obtener un listado de archivos que se actualizarían sin escribirlos en disco.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L400-L445】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L32-L118】
   - **Deploy** para descargar la rama configurada, respaldar los archivos modificados y aplicar los cambios.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L400-L445】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L118-L240】
   - **Snapshot**, **Smoke test**, **Rollback** y **Purge caches**, cada uno con su propio servicio y registro dedicado.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L164-L238】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L42-L200】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Smoke_Test_Service.php†L30-L120】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Rollback_Service.php†L24-L160】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Cache_Service.php†L26-L112】
2. El plugin también dispara automáticamente un snapshot y un smoke-test después de que WordPress instala o actualiza plugins, temas o el core, facilitando verificaciones posteriores al mantenimiento.【F:wp-content/plugins/scios-git-bridge/scios-git-bridge.php†L170-L222】
3. Consulta la tabla de estado y los registros recientes en la misma página para revisar resultados anteriores. La información proviene del archivo `.scios-deploy.json` y de los logs generados en cada ejecución.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L300-L380】【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Logger.php†L18-L87】

## Respaldo y estado

- Antes de escribir archivos durante un despliegue, el servicio crea una copia en `wp-content/uploads/scios/<timestamp>/` (o en `wp-content/plugins/scios-git-bridge/backups/scios/` si la carpeta de subidas no está disponible). Luego empaqueta el contenido en un ZIP con el mismo nombre de carpeta.【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Filesystem.php†L86-L169】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L118-L240】
- Los resultados de cada operación se guardan en `.scios-deploy.json` (raíz de WordPress) con claves como `last_deploy`, `last_dry_run`, `last_snapshot`, `last_smoke_test`, `last_cache_purge` y `last_rollback`. Cada bloque incluye estado, marca de tiempo, rutas de backup/log y mensajes de error si existen.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L240-L340】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L42-L200】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Smoke_Test_Service.php†L24-L188】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Cache_Service.php†L24-L138】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Rollback_Service.php†L24-L220】
- Los logs se escriben por defecto en `wp-content/uploads/scios/`. Si la carpeta no está disponible, se usa `wp-content/plugins/scios-git-bridge/logs/`. Puedes ajustar la ruta mediante el filtro `scios_git_bridge_logs_directory`.【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Logger.php†L24-L87】【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L412-L470】

## Internacionalización

El catálogo base de traducciones se encuentra en `wp-content/plugins/scios-git-bridge/languages/scios-git-bridge.pot`. Úsalo como plantilla para generar archivos `.po/.mo` para otros idiomas.【F:wp-content/plugins/scios-git-bridge/languages/scios-git-bridge.pot†L1-L20】

## Recursos adicionales

- Revisa `wp-content/plugins/scios-git-bridge/README.md` para detalles técnicos sobre la estructura de respaldos y el archivo de estado interno del plugin.

