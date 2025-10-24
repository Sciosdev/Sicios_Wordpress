<?php

namespace Scios\GitBridge\Services;

use RuntimeException;
use Scios\GitBridge\Infrastructure\SCIOS_Filesystem;
use Scios\GitBridge\Infrastructure\SCIOS_Logger;
use Scios\GitBridge\Support\SCIOS_Lock;
use WP_Filesystem_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Triggers GitHub workflow_dispatch events to capture remote snapshots.
 */
class SCIOS_Snapshot_Service
{
    private const OPTION_NAME      = 'scios_git_bridge_settings';
    private const LOG_PREFIX       = 'snapshot';
    private const TRANSIENT_LOCK   = 'scios_git_bridge_snapshot_lock';
    private const TRANSIENT_TTL    = 300; // 5 minutes.
    private const USER_AGENT       = 'Scios-Git-Bridge Snapshot Service';

    private SCIOS_Filesystem $filesystem_helper;

    private SCIOS_Logger $logger;

    private SCIOS_Lock $lock;

    public function __construct(
        ?SCIOS_Filesystem $filesystem_helper = null,
        ?SCIOS_Logger $logger = null,
        ?SCIOS_Lock $lock = null
    ) {
        $this->filesystem_helper = $filesystem_helper ?? new SCIOS_Filesystem();
        $this->logger            = $logger ?? new SCIOS_Logger($this->filesystem_helper);
        $this->lock              = $lock ?? new SCIOS_Lock(self::TRANSIENT_LOCK, self::TRANSIENT_TTL);
    }

    /**
     * Triggers the configured GitHub snapshot workflow.
     *
     * @param string $reason Snapshot reason provided by the caller.
     *
     * @return void
     */
    public function trigger_snapshot(string $reason = ''): void
    {
        $log_lines = [];
        $status    = 'success';
        $error     = null;
        $reason    = $this->normalise_reason($reason);

        $this->log($log_lines, sprintf('Iniciando solicitud de snapshot. Motivo: %s', $reason));

        if ($this->lock->is_locked()) {
            $status = 'locked';
            $error  = __('Ya existe un snapshot en curso. Operación bloqueada mediante transient.', 'scios-git-bridge');
            $this->log($log_lines, 'ERROR: ' . $error);
            $this->finalise($log_lines, $status, $reason, null, null, $error);

            return;
        }

        $lock_acquired = $this->lock->acquire();
        if (!$lock_acquired) {
            $this->log($log_lines, 'ADVERTENCIA: no se pudo establecer el transient de bloqueo. Se continuará con la ejecución.');
        }

        $repository = '';
        $workflow   = '';
        $ref        = '';
        $response   = null;

        try {
            $settings   = $this->get_settings();
            $repository = $this->resolve_repository($settings);
            $workflow   = $this->resolve_workflow($settings);
            $token      = $this->resolve_token($settings);
            $ref        = $this->resolve_ref($settings);

            [$owner, $repo] = $this->split_repository($repository);

            $endpoint = sprintf(
                'https://api.github.com/repos/%s/%s/actions/workflows/%s/dispatches',
                rawurlencode($owner),
                rawurlencode($repo),
                rawurlencode($workflow)
            );

            $payload = $this->build_payload($ref, $reason);
            $encoded = wp_json_encode($payload);

            if (!is_string($encoded)) {
                throw new RuntimeException(__('No fue posible serializar la petición hacia GitHub.', 'scios-git-bridge'));
            }

            $this->log($log_lines, sprintf('Solicitando workflow_dispatch en %s (workflow: %s, ref: %s).', $repository, $workflow, $ref));

            $response = wp_remote_post(
                $endpoint,
                [
                    'headers' => [
                        'Authorization'        => 'Bearer ' . $token,
                        'Accept'               => 'application/vnd.github+json',
                        'Content-Type'         => 'application/json',
                        'User-Agent'           => self::USER_AGENT,
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                    'body'    => $encoded,
                    'timeout' => 20,
                ]
            );

            if (is_wp_error($response)) {
                throw new RuntimeException($response->get_error_message());
            }

            $status_code = (int) wp_remote_retrieve_response_code($response);
            $status_text = (string) wp_remote_retrieve_response_message($response);
            $body        = (string) wp_remote_retrieve_body($response);

            $this->log($log_lines, sprintf('Respuesta de GitHub: %d %s', $status_code, $status_text));
            if ($body !== '') {
                $this->log($log_lines, 'Cuerpo recibido: ' . $body);
            }

            if ($status_code !== 204 && $status_code !== 201) {
                throw new RuntimeException(sprintf(__('La API devolvió un estado no esperado: %d.', 'scios-git-bridge'), $status_code));
            }
        } catch (\Throwable $exception) {
            $status = 'error';
            $error  = $exception->getMessage();
            $this->log($log_lines, 'ERROR: ' . $error);
        } finally {
            if ($lock_acquired) {
                $this->lock->release();
            }
        }

        $this->finalise(
            $log_lines,
            $status,
            $reason,
            $repository !== '' ? $repository : null,
            $workflow !== '' ? $workflow : null,
            $error,
            $ref !== '' ? $ref : null,
            is_array($response) ? $response : null
        );
    }

    /**
     * Normalises the provided reason string.
     *
     * @param string $reason Raw reason.
     *
     * @return string
     */
    private function normalise_reason(string $reason): string
    {
        $reason = trim(wp_strip_all_tags($reason));

        if ($reason === '') {
            $reason = __('Motivo no especificado', 'scios-git-bridge');
        }

        return $reason;
    }

    /**
     * Retrieves plugin settings.
     *
     * @return array<string, string>
     */
    private function get_settings(): array
    {
        $defaults = [
            'repository_url'      => '',
            'deployment_branch'   => '',
            'deploy_key'          => '',
            'snapshot_repository' => '',
            'snapshot_workflow'   => '',
            'snapshot_token'      => '',
        ];

        $settings = get_option(self::OPTION_NAME, $defaults);
        if (!is_array($settings)) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Resolves the repository identifier from the settings.
     *
     * @param array<string, string> $settings Plugin settings.
     *
     * @return string
     */
    private function resolve_repository(array $settings): string
    {
        $repository = trim((string) ($settings['snapshot_repository'] ?? ''));
        if ($repository !== '') {
            return $repository;
        }

        $repository_url = (string) ($settings['repository_url'] ?? '');
        if ($repository_url === '') {
            throw new RuntimeException(__('No se ha configurado el repositorio para snapshots.', 'scios-git-bridge'));
        }

        $parsed = wp_parse_url($repository_url);
        if (!is_array($parsed) || !isset($parsed['path'])) {
            throw new RuntimeException(__('La URL del repositorio no es válida para determinar owner/repo.', 'scios-git-bridge'));
        }

        $path = trim((string) $parsed['path'], '/');
        if ($path === '') {
            throw new RuntimeException(__('No se pudo inferir el repositorio desde la URL configurada.', 'scios-git-bridge'));
        }

        if (substr($path, -4) === '.git') {
            $path = substr($path, 0, -4);
        }

        return $path;
    }

    /**
     * Splits the repository string into owner and repository parts.
     *
     * @param string $repository Repository in owner/repo format.
     *
     * @return array{0: string, 1: string}
     */
    private function split_repository(string $repository): array
    {
        $parts = explode('/', $repository, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new RuntimeException(__('El repositorio debe tener formato owner/repo.', 'scios-git-bridge'));
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * Resolves the workflow identifier.
     *
     * @param array<string, string> $settings Plugin settings.
     *
     * @return string
     */
    private function resolve_workflow(array $settings): string
    {
        $workflow = trim((string) ($settings['snapshot_workflow'] ?? ''));
        if ($workflow === '') {
            throw new RuntimeException(__('No se ha configurado el workflow de snapshot.', 'scios-git-bridge'));
        }

        return $workflow;
    }

    /**
     * Resolves the authentication token.
     *
     * @param array<string, string> $settings Plugin settings.
     *
     * @return string
     */
    private function resolve_token(array $settings): string
    {
        $token = trim((string) ($settings['snapshot_token'] ?? ''));
        if ($token === '') {
            throw new RuntimeException(__('No se ha configurado el token para snapshots.', 'scios-git-bridge'));
        }

        return $token;
    }

    /**
     * Resolves the ref (branch or tag) to use when dispatching the workflow.
     *
     * @param array<string, string> $settings Plugin settings.
     *
     * @return string
     */
    private function resolve_ref(array $settings): string
    {
        $ref = trim((string) ($settings['deployment_branch'] ?? ''));
        if ($ref === '') {
            $ref = 'main';
        }

        return $ref;
    }

    /**
     * Builds the payload sent to GitHub.
     *
     * @param string $ref    Branch or tag reference.
     * @param string $reason Snapshot reason.
     *
     * @return array<string, mixed>
     */
    private function build_payload(string $ref, string $reason): array
    {
        $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);

        return [
            'ref'    => $ref,
            'inputs' => [
                'reason'      => $reason,
                'initiator'   => get_bloginfo('name'),
                'environment' => $host !== '' ? $host : home_url(),
            ],
        ];
    }

    /**
     * Persists log and metadata information.
     *
     * @param array<int, string>         $log_lines Log entries.
     * @param string                     $status    Execution status.
     * @param string                     $reason    Reason used for the snapshot.
     * @param string|null                $repository Repository identifier.
     * @param string|null                $workflow Workflow identifier.
     * @param string|null                $error     Error message if any.
     * @param string|null                $ref       Git reference used.
     * @param array<string, mixed>|null  $response  Raw HTTP response for context.
     *
     * @return void
     */
    private function finalise(
        array $log_lines,
        string $status,
        string $reason,
        ?string $repository,
        ?string $workflow,
        ?string $error,
        ?string $ref = null,
        ?array $response = null
    ): void {
        $log_lines[] = sprintf('[%s] Estado final del snapshot: %s', gmdate('c'), $status);

        $metadata = [
            'status'     => $status,
            'timestamp'  => gmdate('c'),
            'reason'     => $reason,
        ];

        if ($repository !== null) {
            $metadata['repository'] = $repository;
        }

        if ($workflow !== null) {
            $metadata['workflow'] = $workflow;
        }

        if ($ref !== null) {
            $metadata['ref'] = $ref;
        }

        if ($error !== null) {
            $metadata['error'] = $error;
        }

        if (is_array($response)) {
            $headers = wp_remote_retrieve_headers($response);

            if (is_object($headers) && method_exists($headers, 'getAll')) {
                $headers = $headers->getAll();
            }

            $metadata['response'] = [
                'code'    => wp_remote_retrieve_response_code($response),
                'headers' => is_array($headers) ? $headers : (array) $headers,
            ];
        }

        $this->persist_results($log_lines, $metadata);
    }

    /**
     * Writes the log and metadata to disk.
     *
     * @param array<int, string>        $log_lines Log entries.
     * @param array<string, mixed>      $metadata  Metadata payload.
     *
     * @return void
     */
    private function persist_results(array $log_lines, array $metadata): void
    {
        $log_path = '';

        try {
            $filesystem = $this->filesystem_helper->get_wp_filesystem();
            $log_path   = $this->logger->write($log_lines, self::LOG_PREFIX);

            if ($log_path !== '') {
                $metadata['log_file'] = basename($log_path);
                $metadata['log_path'] = $this->filesystem_helper->relative_path($log_path);
            }

            $this->update_metadata($filesystem, ['last_snapshot' => $metadata]);
        } catch (\Throwable $exception) {
            $log_lines[] = sprintf('[%s] ERROR al persistir la información: %s', gmdate('c'), $exception->getMessage());
            $this->logger->write($log_lines, self::LOG_PREFIX, $log_path);
        }
    }

    private function log(array &$lines, string $message): void
    {
        $lines[] = sprintf('[%s] %s', gmdate('c'), $message);
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
            throw new RuntimeException(__('No se pudo serializar la metadata del snapshot.', 'scios-git-bridge'));
        }

        if (!$filesystem->put_contents($file, $encoded, FS_CHMOD_FILE)) {
            throw new RuntimeException(__('No se pudo escribir el archivo de metadata del snapshot.', 'scios-git-bridge'));
        }
    }
}
