<?php

namespace Scios\GitBridge\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the administration area for the Scios Git Bridge plugin.
 */
class SCIOS_Admin
{
    private const OPTION_GROUP = 'scios_git_bridge_settings_group';
    private const OPTION_NAME  = 'scios_git_bridge_settings';
    private const PAGE_SLUG    = 'scios-git-bridge';

    /**
     * Registers the WordPress hooks for the admin functionality.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_scios_git_bridge_action', [$this, 'handle_admin_post']);
    }

    /**
     * Registers the admin menu page.
     *
     * @return void
     */
    public function register_menu(): void
    {
        add_menu_page(
            esc_html__('Scios Git Bridge', 'scios-git-bridge'),
            esc_html__('Scios Git Bridge', 'scios-git-bridge'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page'],
            'dashicons-migrate'
        );
    }

    /**
     * Registers the plugin settings sections and fields.
     *
     * @return void
     */
    public function register_settings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => [
                    'repository_url'    => '',
                    'deployment_branch' => '',
                    'deploy_key'        => '',
                ],
            ]
        );

        add_settings_section(
            'scios_git_bridge_connection',
            esc_html__('Repositorio remoto', 'scios-git-bridge'),
            static function (): void {
                echo '<p>' . esc_html__('Configura los detalles de conexión con tu repositorio.', 'scios-git-bridge') . '</p>';
            },
            self::PAGE_SLUG
        );

        $this->add_text_field(
            'repository_url',
            esc_html__('URL del repositorio', 'scios-git-bridge'),
            esc_html__('Ejemplo: https://github.com/empresa/proyecto.git', 'scios-git-bridge')
        );

        $this->add_text_field(
            'deployment_branch',
            esc_html__('Rama de despliegue', 'scios-git-bridge'),
            esc_html__('Nombre de la rama que se usará para desplegar cambios.', 'scios-git-bridge')
        );

        $this->add_password_field(
            'deploy_key',
            esc_html__('Clave de despliegue', 'scios-git-bridge'),
            esc_html__('Clave o token que utilizará el puente para autenticarse.', 'scios-git-bridge')
        );
    }

    /**
     * Renders the admin page content.
     *
     * @return void
     */
    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos suficientes para acceder a esta página.', 'scios-git-bridge'));
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Scios Git Bridge', 'scios-git-bridge'); ?></h1>
            <?php settings_errors(self::OPTION_NAME); ?>
            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button();
                ?>
            </form>

            <?php $this->render_status_panel(); ?>
            <?php $this->render_action_buttons(); ?>
        </div>
        <?php
    }

    /**
     * Handles the admin-post action triggered by the action buttons.
     *
     * @return void
     */
    public function handle_admin_post(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acción no autorizada.', 'scios-git-bridge'));
        }

        check_admin_referer('scios_git_bridge_action');

        $requested_action = isset($_POST['scios_git_bridge_action'])
            ? sanitize_key(wp_unslash($_POST['scios_git_bridge_action']))
            : '';

        switch ($requested_action) {
            case 'refresh-status':
                /**
                 * Fires when the user triggers the refresh status action.
                 */
                do_action('scios_git_bridge_refresh_status');
                $this->add_notice('updated', esc_html__('Estado actualizado correctamente.', 'scios-git-bridge'));
                break;
            case 'trigger-deploy':
                /**
                 * Fires when the user triggers the deploy action.
                 */
                do_action('scios_git_bridge_trigger_deploy');
                $this->add_notice('updated', esc_html__('Proceso de despliegue iniciado.', 'scios-git-bridge'));
                break;
            default:
                $this->add_notice('error', esc_html__('Acción no reconocida.', 'scios-git-bridge'));
                break;
        }

        wp_safe_redirect($this->get_admin_page_url());
        exit;
    }

    /**
     * Sanitises the settings values before they are stored.
     *
     * @param array<string, mixed>|mixed $settings Settings submitted by the form.
     *
     * @return array<string, string>
     */
    public function sanitize_settings($settings): array
    {
        if (!is_array($settings)) {
            return [
                'repository_url'    => '',
                'deployment_branch' => '',
                'deploy_key'        => '',
            ];
        }

        return [
            'repository_url'    => isset($settings['repository_url']) ? esc_url_raw((string) $settings['repository_url']) : '',
            'deployment_branch' => isset($settings['deployment_branch']) ? sanitize_text_field((string) $settings['deployment_branch']) : '',
            'deploy_key'        => isset($settings['deploy_key']) ? sanitize_text_field((string) $settings['deploy_key']) : '',
        ];
    }

    /**
     * Retrieves the stored settings.
     *
     * @return array<string, string>
     */
    private function get_settings(): array
    {
        $defaults = [
            'repository_url'    => '',
            'deployment_branch' => '',
            'deploy_key'        => '',
        ];

        $settings = get_option(self::OPTION_NAME, $defaults);

        if (!is_array($settings)) {
            return $defaults;
        }

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Adds a regular text field to the settings page.
     *
     * @param string $key         Field key.
     * @param string $label       Field label.
     * @param string $description Field description.
     *
     * @return void
     */
    private function add_text_field(string $key, string $label, string $description = ''): void
    {
        add_settings_field(
            $key,
            $label,
            function () use ($key, $description): void {
                $settings = $this->get_settings();
                printf(
                    '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
                    esc_attr($key),
                    esc_attr(self::OPTION_NAME),
                    esc_attr($settings[$key] ?? '')
                );

                if ($description !== '') {
                    printf(
                        '<p class="description">%s</p>',
                        esc_html($description)
                    );
                }
            },
            self::PAGE_SLUG,
            'scios_git_bridge_connection'
        );
    }

    /**
     * Adds a password field to the settings page.
     *
     * @param string $key         Field key.
     * @param string $label       Field label.
     * @param string $description Field description.
     *
     * @return void
     */
    private function add_password_field(string $key, string $label, string $description = ''): void
    {
        add_settings_field(
            $key,
            $label,
            function () use ($key, $description): void {
                $settings = $this->get_settings();
                printf(
                    '<input type="password" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" autocomplete="off" />',
                    esc_attr($key),
                    esc_attr(self::OPTION_NAME),
                    esc_attr($settings[$key] ?? '')
                );

                if ($description !== '') {
                    printf(
                        '<p class="description">%s</p>',
                        esc_html($description)
                    );
                }
            },
            self::PAGE_SLUG,
            'scios_git_bridge_connection'
        );
    }

    /**
     * Renders the deployment status panel.
     *
     * @return void
     */
    private function render_status_panel(): void
    {
        $metadata = $this->load_deploy_metadata();
        $logs     = $this->load_recent_logs();
        ?>
        <hr />
        <h2><?php esc_html_e('Estado de despliegue', 'scios-git-bridge'); ?></h2>
        <?php if (empty($metadata)) : ?>
            <p><?php esc_html_e('No se encontró información de despliegue.', 'scios-git-bridge'); ?></p>
        <?php else : ?>
            <table class="widefat">
                <tbody>
                <?php foreach ($metadata as $key => $value) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $key))); ?></th>
                        <td>
                            <?php
                            if (is_scalar($value)) {
                                echo esc_html((string) $value);
                            } elseif (is_array($value)) {
                                echo '<pre>' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3><?php esc_html_e('Registros recientes', 'scios-git-bridge'); ?></h3>
        <?php if (empty($logs)) : ?>
            <p><?php esc_html_e('No se encontraron logs de despliegue.', 'scios-git-bridge'); ?></p>
        <?php else : ?>
            <?php foreach ($logs as $log) : ?>
                <h4><?php echo esc_html($log['name']); ?></h4>
                <pre class="scios-git-bridge-log-output"><?php echo esc_html($log['content']); ?></pre>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
    }

    /**
     * Renders the action buttons.
     *
     * @return void
     */
    private function render_action_buttons(): void
    {
        ?>
        <hr />
        <h2><?php esc_html_e('Acciones', 'scios-git-bridge'); ?></h2>
        <div class="scios-git-bridge-actions">
            <?php $this->render_action_form('refresh-status', esc_html__('Actualizar estado', 'scios-git-bridge'), 'secondary'); ?>
            <?php $this->render_action_form('trigger-deploy', esc_html__('Iniciar despliegue', 'scios-git-bridge'), 'primary'); ?>
        </div>
        <?php
    }

    /**
     * Helper to render the action forms.
     *
     * @param string $action Action identifier.
     * @param string $label  Button label.
     * @param string $class  Button class.
     *
     * @return void
     */
    private function render_action_form(string $action, string $label, string $class = 'secondary'): void
    {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:1rem;">
            <?php wp_nonce_field('scios_git_bridge_action'); ?>
            <input type="hidden" name="action" value="scios_git_bridge_action" />
            <input type="hidden" name="scios_git_bridge_action" value="<?php echo esc_attr($action); ?>" />
            <?php submit_button($label, $class, '', false); ?>
        </form>
        <?php
    }

    /**
     * Loads deployment metadata from the .scios-deploy.json file.
     *
     * @return array<string, mixed>
     */
    private function load_deploy_metadata(): array
    {
        $file = trailingslashit(ABSPATH) . '.scios-deploy.json';

        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return [];
        }

        $data = json_decode($contents, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Retrieves recent log files that match the Scios deployment naming.
     *
     * @return array<int, array<string, string>>
     */
    private function load_recent_logs(): array
    {
        $logs      = [];
        $log_paths = $this->get_log_files();

        foreach ($log_paths as $path) {
            if (!is_readable($path)) {
                continue;
            }

            $content = file_get_contents($path);

            if ($content === false) {
                continue;
            }

            $logs[] = [
                'name'    => basename($path),
                'content' => $this->trim_log_output($content),
            ];
        }

        return $logs;
    }

    /**
     * Retrieves the potential log files.
     *
     * @return array<int, string>
     */
    private function get_log_files(): array
    {
        $paths = [];
        $upload_dir = wp_upload_dir();

        if (!empty($upload_dir['basedir'])) {
            $potential = trailingslashit($upload_dir['basedir']) . 'scios';
            if (is_dir($potential)) {
                $paths = array_merge($paths, glob($potential . '/*.log') ?: []);
            }
        }

        $plugin_dir     = dirname(__DIR__, 2);
        $plugin_log_dir = trailingslashit($plugin_dir) . 'logs';
        if (is_dir($plugin_log_dir)) {
            $paths = array_merge($paths, glob(trailingslashit($plugin_log_dir) . '*.log') ?: []);
        }

        return array_values(array_unique($paths));
    }

    /**
     * Limits the amount of log content displayed to keep the admin page manageable.
     *
     * @param string $content Raw log content.
     *
     * @return string
     */
    private function trim_log_output(string $content): string
    {
        $lines = preg_split('/\r?\n/', $content);

        if ($lines === false) {
            return '';
        }

        $lines = array_filter($lines, static function ($line) {
            return $line !== '';
        });

        $max_lines = 50;
        $total     = count($lines);

        if ($total > $max_lines) {
            $lines = array_slice($lines, -$max_lines);
            array_unshift($lines, sprintf('... (%d %s)', $total - $max_lines, esc_html__('líneas omitidas', 'scios-git-bridge')));
        }

        return implode("\n", $lines);
    }

    /**
     * Adds an admin notice to be displayed on the settings page.
     *
     * @param string $type    Notice type (updated|error).
     * @param string $message Notice message.
     *
     * @return void
     */
    private function add_notice(string $type, string $message): void
    {
        add_settings_error(self::OPTION_NAME, 'scios_git_bridge_' . $type, $message, $type);
    }

    /**
     * Generates the URL for the admin settings page.
     *
     * @return string
     */
    private function get_admin_page_url(): string
    {
        return add_query_arg('page', self::PAGE_SLUG, admin_url('admin.php'));
    }
}
