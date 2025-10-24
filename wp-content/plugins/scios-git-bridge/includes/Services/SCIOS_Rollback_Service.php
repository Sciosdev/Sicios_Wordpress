<?php

namespace Scios\GitBridge\Services;

use RuntimeException;
use Scios\GitBridge\Infrastructure\SCIOS_Filesystem;
use Scios\GitBridge\Infrastructure\SCIOS_Logger;
use WP_Filesystem_Base;
use ZipArchive;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Restores files from the latest deployment backup zip to perform rollbacks.
 */
class SCIOS_Rollback_Service
{
    private const LOG_PREFIX = 'rollback';
    private const ALLOWED_PATHS = ['wp-content/'];

    private SCIOS_Filesystem $filesystem_helper;

    private SCIOS_Logger $logger;

    public function __construct(?SCIOS_Filesystem $filesystem_helper = null, ?SCIOS_Logger $logger = null)
    {
        $this->filesystem_helper = $filesystem_helper ?? new SCIOS_Filesystem();
        $this->logger            = $logger ?? new SCIOS_Logger($this->filesystem_helper);
    }

    /**
     * Restores a backup zip.
     */
    public function rollback(string $zip_reference = '', string $reason = ''): void
    {
        $log_lines = [];
        $status    = 'success';
        $error     = null;
        $restored  = [];
        $zip       = null;
        $filesystem = null;
        $zip_path  = '';

        $reason = $this->normalise_reason($reason);

        try {
            $zip_path = $this->resolve_zip_path($zip_reference);
            $relative = $this->filesystem_helper->relative_path($zip_path);

            $this->log($log_lines, sprintf('Iniciando rollback desde %s. Motivo: %s', $relative, $reason));

            $zip = $this->open_zip($zip_path);
            $filesystem = $this->filesystem_helper->get_wp_filesystem();

            $root = $this->detect_root_prefix($zip);
            $root = $root !== '' ? $root : '';

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry_name = $zip->getNameIndex($index);

                if ($entry_name === false) {
                    continue;
                }

                $relative_entry = ltrim($this->strip_root($entry_name, $root), '/');

                if ($relative_entry === '') {
                    continue;
                }

                $normalized = str_replace('\\\\', '/', wp_normalize_path($relative_entry));

                if (!$this->should_restore($normalized)) {
                    continue;
                }

                if (substr($normalized, -1) === '/') {
                    $destination_dir = trailingslashit(ABSPATH) . $normalized;
                    $this->filesystem_helper->ensure_directory($destination_dir);
                    continue;
                }

                if (strpos($normalized, '../') !== false || strpos($normalized, '..\\') !== false) {
                    throw new RuntimeException(sprintf(__('Se detectó un archivo con rutas no válidas: %s', 'scios-git-bridge'), $normalized));
                }

                $destination = wp_normalize_path(trailingslashit(ABSPATH) . $normalized);
                $root_path   = wp_normalize_path(trailingslashit(ABSPATH));

                if (strpos($destination, $root_path) !== 0) {
                    throw new RuntimeException(sprintf(__('Intento de escritura fuera del directorio permitido: %s', 'scios-git-bridge'), $destination));
                }

                $contents = $zip->getFromIndex($index);
                if ($contents === false) {
                    throw new RuntimeException(sprintf(__('No se pudo leer el archivo %s del ZIP de rollback.', 'scios-git-bridge'), $normalized));
                }

                $this->filesystem_helper->ensure_directory(dirname($destination));

                if (!$filesystem instanceof WP_Filesystem_Base) {
                    throw new RuntimeException(__('El sistema de archivos de WordPress no está disponible.', 'scios-git-bridge'));
                }

                if (!$filesystem->put_contents($destination, $contents, FS_CHMOD_FILE)) {
                    throw new RuntimeException(sprintf(__('No se pudo restaurar el archivo %s.', 'scios-git-bridge'), $normalized));
                }

                $restored[] = $normalized;
                $this->log($log_lines, sprintf('Archivo restaurado: %s', $normalized));
            }

            $this->log($log_lines, sprintf('Rollback completado. %d archivos restaurados.', count($restored)));
        } catch (\Throwable $exception) {
            $status = 'error';
            $error  = $exception->getMessage();
            $this->log($log_lines, 'ERROR: ' . $error);
        } finally {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }
        }

        $relative_zip = '';

        if ($zip_path !== '') {
            $relative_zip = $this->filesystem_helper->relative_path($zip_path);
        } elseif (trim($zip_reference) !== '') {
            $relative_zip = trim($zip_reference);
        }

        $metadata = [
            'status'    => $status,
            'timestamp' => gmdate('c'),
            'source'    => $relative_zip,
            'reason'    => $reason,
            'files'     => $restored,
        ];

        if ($error !== null) {
            $metadata['error'] = $error;
        }

        $this->finalise($log_lines, $metadata, $relative_zip, $status);
    }

    /**
     * Attempts to resolve the zip path based on the provided reference or metadata.
     */
    private function resolve_zip_path(string $reference): string
    {
        $reference = trim($reference);

        if ($reference === '') {
            $metadata = $this->load_metadata();

            if (isset($metadata['last_deploy']['backup_zip']) && $metadata['last_deploy']['backup_zip'] !== '') {
                $reference = (string) $metadata['last_deploy']['backup_zip'];
            } elseif (isset($metadata['last_deploy']['backup_directory']) && $metadata['last_deploy']['backup_directory'] !== '') {
                $directory = $this->make_absolute((string) $metadata['last_deploy']['backup_directory']);
                $candidate = rtrim($directory, '/\\') . '.zip';
                if (file_exists($candidate)) {
                    $reference = $this->filesystem_helper->relative_path($candidate);
                }
            }
        }

        if ($reference === '') {
            throw new RuntimeException(__('No se pudo determinar el ZIP de respaldo para el rollback.', 'scios-git-bridge'));
        }

        $absolute = $this->make_absolute($reference);

        if (!file_exists($absolute) || !is_readable($absolute)) {
            throw new RuntimeException(sprintf(__('No se encontró el archivo ZIP de respaldo: %s', 'scios-git-bridge'), $reference));
        }

        return $absolute;
    }

    /**
     * Converts a relative path into an absolute path inside the installation.
     */
    private function make_absolute(string $path): string
    {
        $path = wp_normalize_path($path);

        if ($path === '') {
            return $path;
        }

        if (preg_match('#^(?:[A-Za-z]:)?/#', $path)) {
            return $path;
        }

        return wp_normalize_path(trailingslashit(ABSPATH) . ltrim($path, '/'));
    }

    /**
     * Loads the stored deployment metadata.
     *
     * @return array<string, mixed>
     */
    private function load_metadata(): array
    {
        $file = trailingslashit(ABSPATH) . '.scios-deploy.json';

        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function open_zip(string $path): ZipArchive
    {
        $zip = new ZipArchive();
        $result = $zip->open($path);

        if ($result !== true) {
            throw new RuntimeException(__('No se pudo abrir el ZIP de respaldo para el rollback.', 'scios-git-bridge'));
        }

        return $zip;
    }

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

    private function strip_root(string $name, string $root): string
    {
        if ($root !== '' && strpos($name, $root) === 0) {
            return substr($name, strlen($root));
        }

        return $name;
    }

    private function should_restore(string $path): bool
    {
        foreach (self::ALLOWED_PATHS as $allowed) {
            if (strpos($path, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    private function finalise(array $log_lines, array $metadata, string $relative_zip, string $status): void
    {
        $log_path = '';

        try {
            $filesystem = $this->filesystem_helper->get_wp_filesystem();
            $log_path   = $this->logger->write($log_lines, self::LOG_PREFIX);

            if ($log_path !== '') {
                $metadata['log_file'] = basename($log_path);
                $metadata['log_path'] = $this->filesystem_helper->relative_path($log_path);
            }

            $deploy_updates = [];

            if ($relative_zip !== '') {
                $deploy_updates['rollback_source'] = $relative_zip;
            }

            if ($status === 'success') {
                $deploy_updates['status']          = 'rolled-back';
                $deploy_updates['rolled_back_at']  = gmdate('c');
            } else {
                $deploy_updates['status']              = 'rollback-error';
                $deploy_updates['rollback_failed_at'] = gmdate('c');
            }

            $this->update_metadata($filesystem, $metadata, $deploy_updates);
        } catch (\Throwable $exception) {
            $log_lines[] = sprintf('[%s] ERROR al persistir la información: %s', gmdate('c'), $exception->getMessage());
            $this->logger->write($log_lines, self::LOG_PREFIX, $log_path);
        }
    }

    private function update_metadata(WP_Filesystem_Base $filesystem, array $rollback_metadata, array $deploy_updates): void
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

        $data['last_rollback'] = $rollback_metadata;

        if ($deploy_updates !== []) {
            $current = [];
            if (isset($data['last_deploy']) && is_array($data['last_deploy'])) {
                $current = $data['last_deploy'];
            }

            $data['last_deploy'] = array_merge($current, $deploy_updates);
        }

        $encoded = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new RuntimeException(__('No se pudo serializar la metadata del rollback.', 'scios-git-bridge'));
        }

        if (!$filesystem->put_contents($file, $encoded, FS_CHMOD_FILE)) {
            throw new RuntimeException(__('No se pudo escribir el archivo de metadata tras el rollback.', 'scios-git-bridge'));
        }
    }

    private function normalise_reason(string $reason): string
    {
        $reason = trim(wp_strip_all_tags($reason));

        if ($reason === '') {
            return __('Motivo no especificado', 'scios-git-bridge');
        }

        return $reason;
    }

    private function log(array &$log, string $message): void
    {
        $log[] = sprintf('[%s] %s', gmdate('c'), $message);
    }
}
