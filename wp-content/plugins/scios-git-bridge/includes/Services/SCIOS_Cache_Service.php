<?php

namespace Scios\GitBridge\Services;

use RuntimeException;
use Scios\GitBridge\Infrastructure\SCIOS_Filesystem;
use Scios\GitBridge\Infrastructure\SCIOS_Logger;
use Scios\GitBridge\Support\SCIOS_Cache_Purger;
use WP_Filesystem_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides a logged wrapper around cache purge operations.
 */
class SCIOS_Cache_Service
{
    private const LOG_PREFIX = 'cache-purge';

    private SCIOS_Cache_Purger $purger;

    private SCIOS_Filesystem $filesystem_helper;

    private SCIOS_Logger $logger;

    public function __construct(
        ?SCIOS_Cache_Purger $purger = null,
        ?SCIOS_Filesystem $filesystem_helper = null,
        ?SCIOS_Logger $logger = null
    ) {
        $this->purger             = $purger ?? new SCIOS_Cache_Purger();
        $this->filesystem_helper  = $filesystem_helper ?? new SCIOS_Filesystem();
        $this->logger             = $logger ?? new SCIOS_Logger($this->filesystem_helper);
    }

    /**
     * Executes cache purge routines and persists their results.
     */
    public function purge(string $reason = ''): void
    {
        $log_lines   = [];
        $status      = 'success';
        $error       = null;
        $reason      = $this->normalise_reason($reason);
        $results     = [];
        $has_failure = false;

        $this->log($log_lines, sprintf('Iniciando purga de caché. Motivo: %s', $reason));

        try {
            $results = $this->purger->purge();

            if ($results === []) {
                $this->log($log_lines, __('No se detectaron mecanismos de caché para purgar.', 'scios-git-bridge'));
            }

            foreach ($results as $result) {
                if (!is_array($result)) {
                    continue;
                }

                $slug    = (string) ($result['slug'] ?? 'desconocido');
                $success = !empty($result['success']);

                if ($success) {
                    $this->log($log_lines, sprintf(__('Caché purgada correctamente: %s', 'scios-git-bridge'), $slug));
                    continue;
                }

                $has_failure = true;
                $message     = isset($result['message']) ? (string) $result['message'] : '';

                if ($message !== '') {
                    $this->log($log_lines, sprintf(__('Fallo al purgar %1$s: %2$s', 'scios-git-bridge'), $slug, $message));
                } else {
                    $this->log($log_lines, sprintf(__('Fallo al purgar la caché %s.', 'scios-git-bridge'), $slug));
                }
            }
        } catch (\Throwable $exception) {
            $status = 'error';
            $error  = $exception->getMessage();
            $this->log($log_lines, 'ERROR: ' . $error);
        }

        if ($status === 'success' && $has_failure) {
            $status = 'warning';
        }

        $metadata = [
            'status'    => $status,
            'timestamp' => gmdate('c'),
            'reason'    => $reason,
            'results'   => $results,
        ];

        if ($error !== null) {
            $metadata['error'] = $error;
        }

        $this->finalise($log_lines, $metadata);
    }

    private function finalise(array $log_lines, array $metadata): void
    {
        $log_path = '';

        try {
            $filesystem = $this->filesystem_helper->get_wp_filesystem();
            $log_path   = $this->logger->write($log_lines, self::LOG_PREFIX);

            if ($log_path !== '') {
                $metadata['log_file'] = basename($log_path);
                $metadata['log_path'] = $this->filesystem_helper->relative_path($log_path);
            }

            $this->update_metadata($filesystem, ['last_cache_purge' => $metadata]);
        } catch (\Throwable $exception) {
            $log_lines[] = sprintf('[%s] ERROR al persistir la información: %s', gmdate('c'), $exception->getMessage());
            $this->logger->write($log_lines, self::LOG_PREFIX, $log_path);
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
            throw new RuntimeException(__('No se pudo serializar la metadata de la purga de caché.', 'scios-git-bridge'));
        }

        if (!$filesystem->put_contents($file, $encoded, FS_CHMOD_FILE)) {
            throw new RuntimeException(__('No se pudo escribir la metadata de la purga de caché.', 'scios-git-bridge'));
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
