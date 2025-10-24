<?php

namespace Scios\GitBridge\Services;

use RuntimeException;
use Scios\GitBridge\Infrastructure\SCIOS_Filesystem;
use Scios\GitBridge\Infrastructure\SCIOS_Logger;
use WP_Filesystem_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Executes simple smoke tests against the current site to validate availability.
 */
class SCIOS_Smoke_Test_Service
{
    private const LOG_PREFIX = 'smoke-test';

    private SCIOS_Filesystem $filesystem_helper;

    private SCIOS_Logger $logger;

    public function __construct(?SCIOS_Filesystem $filesystem_helper = null, ?SCIOS_Logger $logger = null)
    {
        $this->filesystem_helper = $filesystem_helper ?? new SCIOS_Filesystem();
        $this->logger            = $logger ?? new SCIOS_Logger($this->filesystem_helper);
    }

    /**
     * Runs the configured smoke tests.
     */
    public function run(string $reason = ''): void
    {
        $log_lines = [];
        $results   = [];
        $status    = 'success';
        $error     = null;
        $reason    = $this->normalise_reason($reason);

        $this->log($log_lines, sprintf('Iniciando smoke-test. Motivo: %s', $reason));

        try {
            $endpoints = $this->get_endpoints();

            if ($endpoints === []) {
                $this->log($log_lines, __('No se configuraron endpoints para el smoke-test.', 'scios-git-bridge'));
            }

            foreach ($endpoints as $endpoint) {
                $result   = $this->perform_request($endpoint, $log_lines);
                $results[] = $result;

                if (empty($result['success'])) {
                    $status = 'error';
                }
            }
        } catch (\Throwable $exception) {
            $status = 'error';
            $error  = $exception->getMessage();
            $this->log($log_lines, 'ERROR: ' . $error);
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

    /**
     * Builds the list of endpoints to probe.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_endpoints(): array
    {
        $endpoints = [
            [
                'slug'            => 'home',
                'url'             => home_url('/'),
                'expected_status' => [200],
            ],
            [
                'slug'            => 'wp-json',
                'url'             => rest_url(),
                'expected_status' => [200],
            ],
        ];

        $filtered = apply_filters('scios_git_bridge_smoke_test_endpoints', $endpoints);

        if (!is_array($filtered)) {
            return $endpoints;
        }

        $normalised = [];

        foreach ($filtered as $endpoint) {
            if (!is_array($endpoint)) {
                continue;
            }

            if (empty($endpoint['url'])) {
                continue;
            }

            $normalised[] = [
                'slug'            => isset($endpoint['slug']) ? (string) $endpoint['slug'] : md5((string) $endpoint['url']),
                'url'             => esc_url_raw((string) $endpoint['url']),
                'expected_status' => $this->normalise_expected_status($endpoint['expected_status'] ?? [200]),
            ];
        }

        return $normalised;
    }

    /**
     * Normalises the expected status codes value.
     *
     * @param mixed $value Raw value provided.
     *
     * @return array<int, int>
     */
    private function normalise_expected_status($value): array
    {
        if (is_numeric($value)) {
            return [(int) $value];
        }

        if (!is_array($value)) {
            return [200];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (!is_numeric($item)) {
                continue;
            }

            $normalized[] = (int) $item;
        }

        if ($normalized === []) {
            $normalized[] = 200;
        }

        return $normalized;
    }

    /**
     * Executes the HTTP request for the provided endpoint definition.
     *
     * @param array<string, mixed> $endpoint Endpoint definition.
     * @param array<int, string>   $log      Log buffer reference.
     *
     * @return array<string, mixed>
     */
    private function perform_request(array $endpoint, array &$log): array
    {
        $slug        = (string) ($endpoint['slug'] ?? '');
        $url         = (string) ($endpoint['url'] ?? '');
        $expectation = $endpoint['expected_status'] ?? [200];

        if ($slug === '') {
            $slug = md5($url);
        }

        if ($url === '') {
            throw new RuntimeException(__('Se recibió un endpoint sin URL para el smoke-test.', 'scios-git-bridge'));
        }

        $this->log($log, sprintf('Probando endpoint %s (%s).', $slug, $url));

        $start    = microtime(true);
        $response = wp_remote_get(
            $url,
            [
                'timeout'     => 20,
                'redirection' => 5,
            ]
        );
        $elapsed = microtime(true) - $start;

        $result = [
            'slug'      => $slug,
            'url'       => $url,
            'latency'   => round($elapsed, 4),
            'success'   => false,
            'code'      => null,
            'message'   => '',
        ];

        if (is_wp_error($response)) {
            $result['message'] = $response->get_error_message();
            $this->log($log, sprintf('ERROR en %s: %s', $slug, $result['message']));

            return $result;
        }

        $code    = (int) wp_remote_retrieve_response_code($response);
        $message = (string) wp_remote_retrieve_response_message($response);

        $result['code']    = $code;
        $result['message'] = $message;

        if (in_array($code, (array) $expectation, true)) {
            $result['success'] = true;
            $this->log($log, sprintf('Endpoint %s respondió %d %s.', $slug, $code, $message));
        } else {
            $this->log(
                $log,
                sprintf(
                    'Endpoint %1$s respondió un código no esperado (%2$d). Esperados: %3$s.',
                    $slug,
                    $code,
                    implode(', ', array_map('strval', (array) $expectation))
                )
            );
        }

        return $result;
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

            $this->update_metadata($filesystem, ['last_smoke_test' => $metadata]);
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
            throw new RuntimeException(__('No se pudo serializar la metadata del smoke-test.', 'scios-git-bridge'));
        }

        if (!$filesystem->put_contents($file, $encoded, FS_CHMOD_FILE)) {
            throw new RuntimeException(__('No se pudo escribir el archivo de metadata del smoke-test.', 'scios-git-bridge'));
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
