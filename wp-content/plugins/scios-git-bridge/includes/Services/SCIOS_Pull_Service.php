<?php

namespace Scios\GitBridge\Services;

use RuntimeException;
use Scios\GitBridge\Infrastructure\SCIOS_Filesystem;
use Scios\GitBridge\Infrastructure\SCIOS_Logger;
use Scios\GitBridge\Support\SCIOS_Cache_Purger;
use WP_Filesystem_Base;
use ZipArchive;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles pulling changes from a remote Git repository using GitHub zipball API.
 */
class SCIOS_Pull_Service
{
    private const OPTION_NAME     = 'scios_git_bridge_settings';
    private const ALLOWED_PATHS   = ['wp-content/'];
    private const LOG_PREFIX_DEPLOY  = 'deploy';
    private const LOG_PREFIX_DRY_RUN = 'dry-run';

    private SCIOS_Filesystem $filesystem_helper;

    private SCIOS_Logger $logger;

    private SCIOS_Cache_Purger $cache_purger;

    public function __construct(
        ?SCIOS_Filesystem $filesystem_helper = null,
        ?SCIOS_Logger $logger = null,
        ?SCIOS_Cache_Purger $cache_purger = null
    ) {
        $this->filesystem_helper = $filesystem_helper ?? new SCIOS_Filesystem();
        $this->logger            = $logger ?? new SCIOS_Logger($this->filesystem_helper);
        $this->cache_purger      = $cache_purger ?? new SCIOS_Cache_Purger();
    }

    /**
     * Runs a dry run of the deployment process.
     *
     * @return void
     */
    public function dry_run(): void
    {
        $this->execute(true);
    }

    /**
     * Executes the deployment.
     *
     * @return void
     */
    public function deploy(): void
    {
        $this->execute(false);
    }

    /**
     * Executes either the dry-run or the deployment.
     *
     * @param bool $simulate True for dry-run, false for deployment.
     *
     * @return void
     */
    private function execute(bool $simulate): void
    {
        $log_lines    = [];
        $status       = 'success';
        $error        = null;
        $files        = [];
        $backups      = [];
        $backup_dir   = null;
        $backup_zip   = null;
        $cache_purges = [];
        $tmp_file     = null;
        $zip          = null;
        $filesystem   = null;
        $settings     = $this->get_settings();
        $operation    = $simulate ? 'dry-run' : 'deploy';

        $this->log($log_lines, sprintf('Iniciando %s para la rama %s.', $operation, $settings['deployment_branch']));

        try {
            if ($settings['repository_url'] === '') {
                throw new RuntimeException(__('La URL del repositorio no está configurada.', 'scios-git-bridge'));
            }

            if ($settings['deployment_branch'] === '') {
                throw new RuntimeException(__('La rama de despliegue no está configurada.', 'scios-git-bridge'));
            }

            $zip_url  = $this->build_zip_url($settings['repository_url'], $settings['deployment_branch']);
            $tmp_file = $this->download_zip($zip_url, $settings['deploy_key'], $log_lines);

            $zip = $this->open_zip($tmp_file);

            if (!$simulate) {
                $filesystem = $this->filesystem_helper->get_wp_filesystem();
                $backup_dir = $this->filesystem_helper->prepare_backup_directory();
                $this->log($log_lines, sprintf('Directorio de backups: %s', $backup_dir));
            }

            $process_result = $this->process_zip(
                $zip,
                $filesystem,
                !$simulate,
                $backup_dir,
                $log_lines
            );

            $files   = $simulate ? $process_result['preview'] : $process_result['written'];
            $backups = $process_result['backups'];

            if (!$simulate) {
                $cache_purges = $this->purge_caches($log_lines);
                if ($backup_dir !== null) {
                    $backup_zip = $this->create_backup_zip($backup_dir, $log_lines);
                }
            }

            $this->log($log_lines, sprintf('%d archivos %s.', count($files), $simulate ? 'analizados' : 'actualizados'));
        } catch (\Throwable $exception) {
            $status = 'error';
            $error  = $exception->getMessage();
            $this->log($log_lines, 'ERROR: ' . $error);
        } finally {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }

            if (is_string($tmp_file) && file_exists($tmp_file)) {
                unlink($tmp_file);
            }
        }

        $prefix = $simulate ? self::LOG_PREFIX_DRY_RUN : self::LOG_PREFIX_DEPLOY;

        try {
            if (!$filesystem instanceof WP_Filesystem_Base) {
                $filesystem = $this->filesystem_helper->get_wp_filesystem();
            }

            $log_path = $this->logger->write($log_lines, $prefix);

            $metadata = [
                'repository'    => $settings['repository_url'],
                'branch'        => $settings['deployment_branch'],
                'status'        => $status,
                'timestamp'     => gmdate('c'),
                'files'         => $files,
                'backups'       => $backups,
            ];

            if ($log_path !== '') {
                $metadata['log_file'] = basename($log_path);
                $metadata['log_path'] = $this->filesystem_helper->relative_path($log_path);
            }

            if ($simulate) {
                if ($error !== null) {
                    $metadata['error'] = $error;
                }

                $payload = ['last_dry_run' => $metadata];
            } else {
                $metadata['backup_directory'] = $backup_dir ? $this->filesystem_helper->relative_path($backup_dir) : null;
                if ($backup_zip !== null) {
                    $metadata['backup_zip'] = $backup_zip;
                }
                if ($error !== null) {
                    $metadata['error'] = $error;
                }

                if ($cache_purges !== []) {
                    $metadata['cache_purges'] = $cache_purges;
                }

                $payload = ['last_deploy' => $metadata];
            }

            try {
                $this->update_metadata($filesystem, $payload);
            } catch (\Throwable $persist_exception) {
                $this->log($log_lines, 'ERROR al persistir información: ' . $persist_exception->getMessage());
                $log_path = $this->logger->write($log_lines, $prefix, $log_path);
            }
        } catch (\Throwable $fs_exception) {
            $this->log($log_lines, 'ERROR al preparar el sistema de archivos: ' . $fs_exception->getMessage());
            $this->logger->write($log_lines, $prefix);
        }
    }

    /**
     * Retrieves plugin settings.
     *
     * @return array<string, string>
     */
    private function get_settings(): array
    {
        $defaults = [
            'repository_url'    => '',
            'deployment_branch' => 'main',
            'deploy_key'        => '',
        ];

        $settings = get_option(self::OPTION_NAME, $defaults);

        if (!is_array($settings)) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Builds the GitHub zipball URL for the repository and branch.
     *
     * @param string $repository_url Repository URL.
     * @param string $branch         Branch name.
     *
     * @return string
     */
    private function build_zip_url(string $repository_url, string $branch): string
    {
        $path = '';

        if (strpos($repository_url, 'git@github.com:') === 0) {
            $path = substr($repository_url, strlen('git@github.com:'));
        } else {
            $parsed = wp_parse_url($repository_url);
            if (is_array($parsed) && isset($parsed['path'])) {
                $path = ltrim((string) $parsed['path'], '/');
            }
        }

        $path = preg_replace('/\.git$/', '', (string) $path);

        if ($path === '') {
            throw new RuntimeException(__('No se pudo determinar el repositorio de GitHub.', 'scios-git-bridge'));
        }

        return sprintf('https://api.github.com/repos/%s/zipball/%s', $path, rawurlencode($branch));
    }

    /**
     * Downloads the zip file streaming it to a temporary file.
     *
     * @param string               $zip_url  Zip URL.
     * @param string               $token    Authentication token.
     * @param array<int, string>   $log      Log lines array.
     *
     * @return string
     */
    private function download_zip(string $zip_url, string $token, array &$log): string
    {
        $this->log($log, sprintf('Descargando ZIP desde %s', $zip_url));

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $tmp = wp_tempnam($zip_url);

        if ($tmp === false) {
            throw new RuntimeException(__('No se pudo crear el archivo temporal para la descarga.', 'scios-git-bridge'));
        }

        $headers = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version'),
        ];

        if ($token !== '') {
            $headers['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get(
            $zip_url,
            [
                'timeout'  => 60,
                'stream'   => true,
                'filename' => $tmp,
                'headers'  => $headers,
            ]
        );

        if (is_wp_error($response)) {
            throw new RuntimeException($response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            throw new RuntimeException(sprintf(__('La descarga del ZIP falló con código %d.', 'scios-git-bridge'), $code));
        }

        if (!file_exists($tmp) || filesize($tmp) === 0) {
            throw new RuntimeException(__('El archivo ZIP descargado está vacío.', 'scios-git-bridge'));
        }

        return $tmp;
    }

    /**
     * Opens the downloaded zip archive.
     *
     * @param string $file File path.
     *
     * @return ZipArchive
     */
    private function open_zip(string $file): ZipArchive
    {
        $zip = new ZipArchive();
        $open_result = $zip->open($file);

        if ($open_result !== true) {
            throw new RuntimeException(__('No se pudo abrir el archivo ZIP descargado.', 'scios-git-bridge'));
        }

        return $zip;
    }

    /**
     * Processes the zip archive either for preview or actual extraction.
     *
     * @param ZipArchive            $zip         Zip archive instance.
     * @param WP_Filesystem_Base|null $filesystem File system handler.
     * @param bool                  $write       True to write files, false for preview.
     * @param string|null           $backup_dir  Backup directory.
     * @param array<int, string>    $log         Log lines.
     *
     * @return array<string, array<int, string>>
     */
    private function process_zip(
        ZipArchive $zip,
        ?WP_Filesystem_Base $filesystem,
        bool $write,
        ?string $backup_dir,
        array &$log
    ): array {
        $written = [];
        $preview = [];
        $backups = [];
        $root    = $this->detect_root_prefix($zip);

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry_name = $zip->getNameIndex($index);

            if ($entry_name === false) {
                continue;
            }

            $relative = $this->strip_root($entry_name, $root);

            if ($relative === '' || !$this->should_extract($relative)) {
                continue;
            }

            if (substr($relative, -1) === '/') {
                if ($write && $filesystem instanceof WP_Filesystem_Base) {
                    $this->filesystem_helper->ensure_directory(trailingslashit(ABSPATH) . $relative);
                }
                continue;
            }

            if (!$write) {
                $preview[] = $relative;
                continue;
            }

            if (!$filesystem instanceof WP_Filesystem_Base) {
                throw new RuntimeException(__('El sistema de archivos de WordPress no está disponible.', 'scios-git-bridge'));
            }

            $destination = trailingslashit(ABSPATH) . $relative;
            $this->filesystem_helper->ensure_directory(dirname($destination));

            if ($backup_dir && $filesystem->exists($destination) && !$filesystem->is_dir($destination)) {
                $backup_path = $this->filesystem_helper->backup_file($destination, $backup_dir);
                if ($backup_path !== null) {
                    $backups[] = $backup_path;
                    $this->log($log, sprintf('Backup creado: %s', $backup_path));
                }
            }

            $contents = $zip->getFromIndex($index);
            if ($contents === false) {
                throw new RuntimeException(sprintf(__('No se pudo leer el archivo %s del ZIP.', 'scios-git-bridge'), $relative));
            }

            if (!$filesystem->put_contents($destination, $contents, FS_CHMOD_FILE)) {
                throw new RuntimeException(sprintf(__('No se pudo escribir el archivo %s.', 'scios-git-bridge'), $relative));
            }

            $written[] = $relative;
            $this->log($log, sprintf('Archivo actualizado: %s', $relative));
        }

        return [
            'written' => $written,
            'preview' => $preview,
            'backups' => $backups,
        ];
    }

    /**
     * Determines if a file should be extracted based on allowed prefixes.
     *
     * @param string $path Relative path.
     *
     * @return bool
     */
    private function should_extract(string $path): bool
    {
        foreach (self::ALLOWED_PATHS as $allowed) {
            if (strpos($path, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detects the root prefix of the zip archive.
     *
     * @param ZipArchive $zip Zip archive instance.
     *
     * @return string
     */
    private function detect_root_prefix(ZipArchive $zip): string
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if ($name === false) {
                continue;
            }

            $position = strpos($name, '/');
            if ($position !== false) {
                return substr($name, 0, $position + 1);
            }
        }

        return '';
    }

    /**
     * Strips the root prefix from a zip entry name.
     *
     * @param string $name Entry name.
     * @param string $root Root prefix.
     *
     * @return string
     */
    private function strip_root(string $name, string $root): string
    {
        if ($root !== '' && strpos($name, $root) === 0) {
            return substr($name, strlen($root));
        }

        return $name;
    }

    /**
     * Attempts to purge detected caches and logs their outcomes.
     *
     * @param array<int, string> $log Log lines buffer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function purge_caches(array &$log): array
    {
        $results = $this->cache_purger->purge();

        if ($results === []) {
            $this->log($log, __('No se detectaron mecanismos de caché para purgar.', 'scios-git-bridge'));

            return [];
        }

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $slug    = (string) ($result['slug'] ?? 'desconocido');
            $success = !empty($result['success']);

            if ($success) {
                $this->log($log, sprintf(__('Caché purgada: %s', 'scios-git-bridge'), $slug));
                continue;
            }

            $message = isset($result['message']) ? (string) $result['message'] : '';

            if ($message !== '') {
                $this->log(
                    $log,
                    sprintf(__('Error al purgar la caché %1$s: %2$s', 'scios-git-bridge'), $slug, $message)
                );
            } else {
                $this->log($log, sprintf(__('Error al purgar la caché %s.', 'scios-git-bridge'), $slug));
            }
        }

        return $results;
    }

    /**
     * Creates a zip archive with the backup contents to ease future rollbacks.
     *
     * @param string               $backup_dir Backup directory path.
     * @param array<int, string>   $log        Log buffer.
     *
     * @return string|null Relative path of the generated zip when successful.
     */
    private function create_backup_zip(string $backup_dir, array &$log): ?string
    {
        $normalized_dir = untrailingslashit(wp_normalize_path($backup_dir));

        if ($normalized_dir === '') {
            return null;
        }

        $destination = $normalized_dir . '.zip';

        try {
            $absolute = $this->filesystem_helper->create_zip([$normalized_dir], $destination);
            $relative = $this->filesystem_helper->relative_path($absolute);
            $this->log($log, sprintf('ZIP de respaldo generado: %s', $relative));

            return $relative;
        } catch (\Throwable $exception) {
            $this->log($log, 'ERROR al generar ZIP de respaldo: ' . $exception->getMessage());

            return null;
        }
    }

    private function update_metadata(WP_Filesystem_Base $filesystem, array $payload): void
    {
        $file = trailingslashit(ABSPATH) . '.scios-deploy.json';
        $data = [];

        if ($filesystem->exists($file)) {
            $existing = $filesystem->get_contents($file);
            if ($existing !== false) {
                $decoded = json_decode($existing, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }

        $data = array_merge($data, $payload);

        $encoded = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new RuntimeException(__('No se pudo serializar la metadata del despliegue.', 'scios-git-bridge'));
        }

        if (!$filesystem->put_contents($file, $encoded, FS_CHMOD_FILE)) {
            throw new RuntimeException(__('No se pudo escribir el archivo de metadata de despliegue.', 'scios-git-bridge'));
        }
    }

    /**
     * Adds a message to the log.
     *
     * @param array<int, string> $log     Log lines.
     * @param string             $message Message to append.
     *
     * @return void
     */
    private function log(array &$log, string $message): void
    {
        $log[] = sprintf('[%s] %s', gmdate('c'), $message);
    }
}
