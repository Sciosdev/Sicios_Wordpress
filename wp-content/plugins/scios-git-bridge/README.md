# Notas técnicas de Scios Git Bridge

Este documento resume cómo el plugin gestiona respaldos, logs y el archivo de estado `.scios-deploy.json`.

## Respaldos

- El servicio de despliegue crea un directorio con marca de tiempo dentro de `wp-content/uploads/scios/` para guardar copias de los archivos que serán modificados. Si la carpeta de subidas no está disponible se utiliza `wp-content/plugins/scios-git-bridge/backups/scios/` como ruta alternativa.【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Filesystem.php†L118-L169】
- Cada archivo sobrescrito se copia en la misma estructura de subdirectorios dentro de la carpeta de respaldo antes de ser reemplazado.【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Filesystem.php†L170-L207】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L170-L232】
- Al finalizar un despliegue exitoso se intenta comprimir el directorio en un ZIP (`<timestamp>.zip`) para facilitar descargas o restauraciones manuales.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L212-L272】

## Archivo `.scios-deploy.json`

El archivo se escribe en la raíz de WordPress y centraliza el estado de las operaciones más recientes.

- **`last_deploy`**: generado tras cada despliegue; incluye rama, archivos escritos, rutas de backup y, si existe, el ZIP final. En caso de error agrega el mensaje correspondiente.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L232-L340】
- **`last_dry_run`**: contiene la vista previa de archivos y errores de la última simulación.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L232-L296】
- **`last_snapshot`**: almacena el estado de la petición enviada a GitHub Actions, junto a los encabezados devueltos cuando la llamada es exitosa.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L42-L200】
- **`last_smoke_test`**: detalla los endpoints evaluados, códigos de respuesta y latencias del último smoke-test.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Smoke_Test_Service.php†L24-L212】
- **`last_cache_purge`**: registra los resultados de purgas de caché, diferenciando éxitos y fallos.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Cache_Service.php†L24-L126】
- **`last_rollback`**: documenta la restauración de archivos, el ZIP usado como origen y un listado de ficheros reescritos.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Rollback_Service.php†L24-L220】

Cada servicio captura errores de escritura al generar el archivo y deja trazas adicionales en los logs para facilitar la depuración.【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Pull_Service.php†L270-L340】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Snapshot_Service.php†L400-L452】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Rollback_Service.php†L280-L352】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Smoke_Test_Service.php†L212-L284】【F:wp-content/plugins/scios-git-bridge/includes/Services/SCIOS_Cache_Service.php†L126-L172】

## Logs

- Los registros se escriben en `wp-content/uploads/scios/` y el plugin ofrece un filtro (`scios_git_bridge_logs_directory`) para personalizar la ubicación. Si la ruta falla, se hace un intento de escritura directo usando `wp_mkdir_p` para garantizar que exista el directorio.【F:wp-content/plugins/scios-git-bridge/includes/Infrastructure/SCIOS_Logger.php†L24-L87】
- El panel de administración muestra los últimos archivos de log detectados en esa ruta o en `wp-content/plugins/scios-git-bridge/logs/`, limitando la salida a 50 líneas por archivo para mantener la página liviana.【F:wp-content/plugins/scios-git-bridge/includes/Admin/SCIOS_Admin.php†L404-L470】

