<?php

namespace Scios\GitBridge\Infrastructure;

use RuntimeException;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple file-based logger used across plugin services.
 */
class SCIOS_Logger
{
    /**
     * Filesystem helper reference.
     */
    private SCIOS_Filesystem $filesystem;

    /**
     * Directory used to store log files.
     */
    private string $directory;

    public function __construct(SCIOS_Filesystem $filesystem, ?string $directory = null)
    {
        $this->filesystem = $filesystem;

        if ($directory === null) {
            $uploads = wp_upload_dir();
            if (is_array($uploads) && empty($uploads['error']) && !empty($uploads['basedir'])) {
                $directory = trailingslashit($uploads['basedir']) . 'scios';
            } else {
                $directory = trailingslashit(dirname(__DIR__, 2)) . 'logs';
            }
        }

        $directory = wp_normalize_path($directory);
        $directory = untrailingslashit($directory);
        $directory = apply_filters('scios_git_bridge_logs_directory', $directory);

        $this->directory = wp_normalize_path($directory);
    }

    /**
     * Returns the directory used to store log files.
     */
    public function get_directory(): string
    {
        return $this->directory;
    }

    /**
     * Persists the provided log entries into a file.
     *
     * @param array<int, string> $lines        Log lines to write.
     * @param string             $prefix       File prefix.
     * @param string             $existingPath Optional existing path to overwrite.
     */
    public function write(array $lines, string $prefix, string $existingPath = ''): string
    {
        $lines   = array_map('strval', $lines);
        $lines   = apply_filters('scios_git_bridge_log_lines', $lines, $prefix);
        $content = implode("\n", $lines) . "\n";
        $path    = $existingPath !== '' ? wp_normalize_path($existingPath) : $this->build_filename($prefix);

        try {
            $filesystem = $this->filesystem->get_wp_filesystem();
            $this->filesystem->ensure_directory(dirname($path));

            if (!$filesystem->put_contents($path, $content, FS_CHMOD_FILE)) {
                throw new RuntimeException(__('No se pudo escribir el archivo de log.', 'scios-git-bridge'));
            }
        } catch (\Throwable $exception) {
            $this->ensure_directory_fallback(dirname($path));
            @file_put_contents($path, $content);
        }

        return $path;
    }

    /**
     * Retrieves the most recent log entries matching the provided prefix.
     *
     * @return array<int, string>
     */
    public function get_recent_entries(string $prefix, int $limit = 50): array
    {
        $limit = max(1, (int) $limit);
        $pattern = trailingslashit($this->directory) . $prefix . '-*.log';
        $files   = glob($pattern);

        if (!is_array($files) || $files === []) {
            return [];
        }

        usort(
            $files,
            static function (string $a, string $b): int {
                return (int) @filemtime($b) <=> (int) @filemtime($a);
            }
        );

        $entries = [];

        foreach ($files as $file) {
            $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!is_array($lines)) {
                continue;
            }

            for ($index = count($lines) - 1; $index >= 0; $index--) {
                $line = trim((string) $lines[$index]);
                if ($line === '') {
                    continue;
                }

                $entries[] = $line;

                if (count($entries) >= $limit) {
                    break 2;
                }
            }
        }

        return array_reverse($entries);
    }

    private function build_filename(string $prefix): string
    {
        $filename = sprintf('%s-%s.log', sanitize_key($prefix), gmdate('Ymd-His'));
        return trailingslashit($this->directory) . $filename;
    }

    private function ensure_directory_fallback(string $directory): void
    {
        $normalized = wp_normalize_path($directory);
        if ($normalized === '') {
            return;
        }

        if (!is_dir($normalized)) {
            wp_mkdir_p($normalized);
        }
    }
}
