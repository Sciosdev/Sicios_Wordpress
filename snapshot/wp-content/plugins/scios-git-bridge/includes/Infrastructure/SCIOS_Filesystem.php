<?php

namespace Scios\GitBridge\Infrastructure;

use RuntimeException;
use WP_Filesystem_Base;
use ZipArchive;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper responsible for interacting with the WordPress filesystem API.
 */
class SCIOS_Filesystem
{
    /**
     * Cached instance of the WordPress filesystem.
     *
     * @var WP_Filesystem_Base|null
     */
    private ?WP_Filesystem_Base $filesystem = null;

    /**
     * Returns an initialised instance of the WordPress filesystem handler.
     *
     * @throws RuntimeException When the filesystem cannot be initialised.
     */
    public function get_wp_filesystem(): WP_Filesystem_Base
    {
        if ($this->filesystem instanceof WP_Filesystem_Base) {
            return $this->filesystem;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        if (!WP_Filesystem()) {
            throw new RuntimeException(__('No se pudo inicializar el sistema de archivos de WordPress.', 'scios-git-bridge'));
        }

        global $wp_filesystem;

        if (!$wp_filesystem instanceof WP_Filesystem_Base) {
            throw new RuntimeException(__('No se obtuvo una instancia válida de WP_Filesystem.', 'scios-git-bridge'));
        }

        $this->filesystem = $wp_filesystem;

        return $this->filesystem;
    }

    /**
     * Ensures that a directory exists by creating it recursively when required.
     */
    public function ensure_directory(string $directory): void
    {
        $normalized = wp_normalize_path($directory);
        $normalized = untrailingslashit($normalized);

        if ($normalized === '') {
            return;
        }

        $filesystem = $this->get_wp_filesystem();

        if ($filesystem->is_dir($normalized)) {
            return;
        }

        $segments = explode('/', $normalized);
        $current  = '';

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                if ($index === 0) {
                    $current = '/';
                }
                continue;
            }

            if ($index === 0 && preg_match('/^[A-Za-z]:$/', $segment)) {
                $current = $segment;
                continue;
            }

            if ($current === '' || $current === '/') {
                $current .= ($current === '/' ? '' : '') . $segment;
            } elseif (preg_match('/^[A-Za-z]:$/', $current)) {
                $current .= '/' . $segment;
            } else {
                $current .= '/' . $segment;
            }

            if ($filesystem->is_dir($current)) {
                continue;
            }

            if (!$filesystem->mkdir($current, FS_CHMOD_DIR)) {
                throw new RuntimeException(sprintf(__('No se pudo crear el directorio %s.', 'scios-git-bridge'), $current));
            }
        }
    }

    /**
     * Copies a file using the WordPress filesystem API.
     *
     * @throws RuntimeException When the copy operation fails.
     */
    public function copy(string $source, string $destination, bool $overwrite = true): void
    {
        $filesystem = $this->get_wp_filesystem();

        if (!$overwrite && $filesystem->exists($destination)) {
            return;
        }

        $destination_dir = dirname($destination);
        if ($destination_dir !== '' && $destination_dir !== '.') {
            $this->ensure_directory($destination_dir);
        }

        if (!$filesystem->copy($source, $destination, $overwrite, FS_CHMOD_FILE)) {
            throw new RuntimeException(sprintf(__('No se pudo copiar el archivo hacia %s.', 'scios-git-bridge'), $destination));
        }
    }

    /**
     * Creates a zip archive from the provided list of files or directories.
     *
     * @param array<int, string> $paths Paths to include in the archive.
     *
     * @return string Absolute path of the generated archive.
     */
    public function create_zip(array $paths, string $destination): string
    {
        $destination = wp_normalize_path($destination);
        $destination_dir = dirname($destination);

        if ($destination_dir !== '' && $destination_dir !== '.') {
            if (!is_dir($destination_dir)) {
                wp_mkdir_p($destination_dir);
            }
        }

        $zip = new ZipArchive();
        $open_result = $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($open_result !== true) {
            throw new RuntimeException(__('No se pudo crear el archivo ZIP solicitado.', 'scios-git-bridge'));
        }

        foreach ($paths as $path) {
            $absolute = wp_normalize_path($path);
            if ($absolute === '' || !file_exists($absolute)) {
                continue;
            }

            if (is_dir($absolute)) {
                $this->add_directory_to_zip($zip, $absolute, basename($absolute));
            } else {
                $zip->addFile($absolute, basename($absolute));
            }
        }

        $zip->close();

        return $destination;
    }

    /**
     * Creates and returns the backup directory path.
     */
    public function prepare_backup_directory(): string
    {
        $this->get_wp_filesystem();
        $uploads = wp_upload_dir();

        if (is_array($uploads) && empty($uploads['error']) && !empty($uploads['basedir'])) {
            $base = trailingslashit($uploads['basedir']) . 'scios/';
        } else {
            $base = trailingslashit(dirname(__DIR__, 2)) . 'backups/scios/';
        }

        $directory = wp_normalize_path($base . gmdate('Ymd-His'));
        $this->ensure_directory($directory);

        return trailingslashit($directory);
    }

    /**
     * Stores a backup copy of the provided file into the backup directory.
     *
     * @return string|null Relative path of the backup file when successful.
     */
    public function backup_file(string $file, string $backup_dir): ?string
    {
        $filesystem = $this->get_wp_filesystem();
        $contents   = $filesystem->get_contents($file);

        if ($contents === false) {
            return null;
        }

        $normalized_dir = wp_normalize_path($backup_dir);
        $relative       = ltrim(str_replace(wp_normalize_path(trailingslashit(ABSPATH)), '', wp_normalize_path($file)), '/');
        $destination    = trailingslashit($normalized_dir) . $relative;
        $this->ensure_directory(dirname($destination));

        if (!$filesystem->put_contents($destination, $contents, FS_CHMOD_FILE)) {
            return null;
        }

        return $this->relative_path($destination);
    }

    /**
     * Converts an absolute path into a path relative to the installation root or plugin directory.
     */
    public function relative_path(string $path): string
    {
        $normalized  = str_replace('\\\\', '/', wp_normalize_path($path));
        $root        = str_replace('\\\\', '/', wp_normalize_path(trailingslashit(ABSPATH)));

        if (strpos($normalized, $root) === 0) {
            return ltrim(substr($normalized, strlen($root)), '/');
        }

        $plugin_root = str_replace('\\\\', '/', wp_normalize_path(trailingslashit(dirname(__DIR__, 2))));
        if (strpos($normalized, $plugin_root) === 0) {
            return ltrim(substr($normalized, strlen($plugin_root)), '/');
        }

        return $normalized;
    }

    /**
     * Adds a directory contents recursively to the provided zip archive.
     *
     * @param ZipArchive $zip       Zip instance.
     * @param string     $directory Directory to add.
     * @param string     $base_name Base name used inside the archive.
     */
    private function add_directory_to_zip(ZipArchive $zip, string $directory, string $base_name): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file_info) {
            $path     = $file_info->getPathname();
            $relative = $base_name . '/' . ltrim(str_replace($directory, '', $path), '/');

            if ($file_info->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($path, $relative);
            }
        }
    }
}
