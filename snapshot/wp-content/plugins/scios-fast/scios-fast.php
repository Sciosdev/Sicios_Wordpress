<?php
/**
 * Plugin Name: SCIOS Fast
 * Description: Optimiza las llamadas a la API de Rey Theme con caché y fallback configurables.
 * Version: 1.2.2

 * Author: SCIOS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'SCIOS_Fast_Plugin' ) ) {
    final class SCIOS_Fast_Plugin {
        private const OPTION_NAME = 'scios_fast_options';
        private const CACHE_KEYS_OPTION = 'scios_fast_cache_keys';
        private const CACHE_PREFIX = 'scios_fast_';
        private const BACKUP_PREFIX = 'scios_fast_backup_';
        private const FONT_CACHE_META_OPTION = 'scios_fast_font_cache_meta';
        private const FONT_CACHE_CRON_HOOK = 'scios_fast_warm_font_cache_cron';
        private const TEMPLATE_CACHE_OPTION = 'scios_fast_template_cache_map';
        private const TEMPLATE_FALLBACK_EVENTS_OPTION = 'scios_fast_template_fallbacks';
        private const TEMPLATE_FALLBACK_NOTICE = 'scios_fast_template_fallback_notice';
        private const TEMPLATE_FALLBACK_DIR = __DIR__ . '/templates/';
        private const DEFAULT_TEMPLATE_TIMEOUT = 12;
        private static $instance = null;

        private $options_cache = null;

        private function __construct() {
            add_action( 'after_setup_theme', [ $this, 'replace_theme_filter' ], 20 );
            add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_post_scios_fast_clear_cache', [ $this, 'handle_clear_cache' ] );
            add_action( 'admin_post_scios_fast_warm_fonts', [ $this, 'handle_warm_fonts' ] );
            add_action( 'admin_post_scios_fast_refresh_templates', [ $this, 'handle_refresh_templates' ] );
            add_action( 'init', [ $this, 'maybe_schedule_font_cache_cron' ] );
            add_action( self::FONT_CACHE_CRON_HOOK, [ $this, 'handle_font_cache_cron' ] );
            add_action( 'admin_notices', [ $this, 'maybe_render_global_notice' ] );

            if ( defined( 'WP_CLI' ) && WP_CLI ) {
                \WP_CLI::add_command( 'scios-fast warm-font-cache', [ $this, 'cli_warm_font_cache' ] );
            }
        }

        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function replace_theme_filter() {
            remove_filter( 'pre_http_request', 'override_reytheme_api_call', 10 );
            add_filter( 'pre_http_request', [ $this, 'override_reytheme_api_call' ], 10, 3 );
        }

        public function override_reytheme_api_call( $preempt, $r, $url ) {
            if ( $this->should_bypass_admin_request() ) {
                $this->log_debug( '[SCIOS Fast] Bypassing admin request.' );
                return $preempt;
            }

            if ( ! $this->should_intercept( $url ) ) {
                return $preempt;
            }

            if ( $this->is_template_request( $url ) ) {
                return $this->handle_template_override( $preempt, $r, $url );
            }

            $options   = $this->get_options();
            $cache_key = $this->build_cache_key( $r, $url );
            $cached    = $this->get_cached_response( $cache_key );

            if ( $cached ) {
                $this->log_debug( sprintf( '[SCIOS Fast] Cache hit for %s', $url ) );
                return $cached;
            }

            $this->log_debug( sprintf( '[SCIOS Fast] Cache miss for %s', $url ) );

            $response = $this->dispatch_request( $url, $r );

            if ( is_wp_error( $response ) || $this->is_error_status( $response ) ) {
                $this->log_debug( sprintf( '[SCIOS Fast] Primary request failed for %s. Trying fallback.', $url ) );
                $response = $this->maybe_request_fallback( $url, $r, $options );
            }

            if ( is_wp_error( $response ) || $this->is_error_status( $response ) ) {
                $backup = $this->get_backup_response( $cache_key );
                if ( $backup ) {
                    $this->log_debug( sprintf( '[SCIOS Fast] Returning backup cache for %s', $url ) );
                    return $backup;
                }

                $this->log_debug( sprintf( '[SCIOS Fast] No valid response for %s', $url ) );
                return $preempt;
            }

            $this->store_response( $cache_key, $response, $options['ttl'] );

            return $response;
        }

        private function should_intercept( $url ) {
            return (bool) preg_match( '#^https?://api\.reytheme\.com/#', $url );
        }

        private function should_bypass_admin_request() {
            if ( ! is_admin() ) {
                return false;
            }

            if ( wp_doing_ajax() ) {
                return false;
            }

            if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
                return false;
            }

            $screen_id = '';

            if ( function_exists( 'get_current_screen' ) && did_action( 'current_screen' ) ) {
                $screen = get_current_screen();
                if ( $screen && isset( $screen->id ) ) {
                    $screen_id = (string) $screen->id;
                }
            }

            $sensitive_screens = [
                'plugins',
                'plugin-install',
                'update-core',
                'themes',
                'site-health',
            ];

            if ( $screen_id && in_array( $screen_id, $sensitive_screens, true ) ) {
                return true;
            }

            $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

            foreach ( [ 'plugins.php', 'plugin-install.php', 'update-core.php', 'themes.php', 'site-health.php' ] as $needle ) {
                if ( false !== strpos( $request_uri, '/wp-admin/' . $needle ) ) {
                    return true;
                }
            }

            return false;
        }

        private function dispatch_request( $url, $args ) {
            $method = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'GET';

            if ( 'POST' === $method ) {
                return wp_remote_post( $url, $args );
            }

            return wp_remote_get( $url, $args );
        }

        private function maybe_request_fallback( $url, $args, $options ) {
            if ( empty( $options['fallback_endpoint'] ) ) {
                return new WP_Error( 'scios_fast_no_fallback', 'No fallback endpoint configured.' );
            }

            $fallback_url = $this->build_fallback_url( $url, $options['fallback_endpoint'] );
            if ( ! $fallback_url ) {
                return new WP_Error( 'scios_fast_invalid_fallback', 'Invalid fallback URL.' );
            }

            $this->log_debug( sprintf( '[SCIOS Fast] Fallback request %s', $fallback_url ) );

            return $this->dispatch_request( $fallback_url, $args );
        }

        private function build_fallback_url( $original_url, $fallback_base ) {
            $parsed = wp_parse_url( $original_url );
            if ( empty( $parsed['path'] ) ) {
                return false;
            }

            $fallback_base = trailingslashit( $fallback_base );
            $path          = ltrim( $parsed['path'], '/' );
            $query         = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';

            return $fallback_base . $path . $query;
        }

        private function is_template_request( $url ) {
            $parsed = wp_parse_url( $url );
            if ( empty( $parsed['path'] ) ) {
                return false;
            }

            return false !== strpos( $parsed['path'], 'get_template_data' );
        }

        private function handle_template_override( $preempt, $args, $url ) {
            $template_id = $this->extract_template_id( $args, $url );
            $options     = $this->get_options();
            $cache_key   = $this->build_cache_key( $args, $url );
            $cached      = $this->get_cached_response( $cache_key );

            if ( $cached ) {
                return $cached;
            }

            $args     = $this->apply_template_timeout( $args );
            $response = $this->dispatch_request( $url, $args );

            if ( is_wp_error( $response ) || $this->is_error_status( $response ) ) {
                $this->log_debug( sprintf( '[SCIOS Fast] Remote template request failed for %s', $url ) );
                $fallback = $this->maybe_get_template_fallback_response( $template_id );

                if ( $fallback ) {
                    $this->store_response( $cache_key, $fallback, $options['ttl'] );

                    if ( $template_id ) {
                        $this->register_template_cache( $cache_key, $template_id, [
                            'source'    => 'fallback',
                            'stored_at' => time(),
                        ] );
                        $this->record_template_fallback( $template_id, 'fallback_used' );
                    }

                    return $fallback;
                }

                if ( $template_id ) {
                    $this->record_template_fallback( $template_id, 'fallback_missing' );
                }

                $backup = $this->get_backup_response( $cache_key );
                if ( $backup ) {
                    return $backup;
                }

                return $preempt;
            }

            $this->store_response( $cache_key, $response, $options['ttl'] );

            if ( $template_id ) {
                $this->register_template_cache( $cache_key, $template_id, [
                    'source'    => 'remote',
                    'stored_at' => time(),
                ] );
                $this->clear_template_fallback( $template_id );
            }

            return $response;
        }

        private function apply_template_timeout( $args ) {
            $args = is_array( $args ) ? $args : [];
            $timeout = isset( $args['timeout'] ) ? (float) $args['timeout'] : 0;
            $options = $this->get_options();
            $max_timeout = isset( $options['template_timeout'] ) ? max( 1, absint( $options['template_timeout'] ) ) : self::DEFAULT_TEMPLATE_TIMEOUT;

            if ( $timeout <= 0 ) {
                $args['timeout'] = $max_timeout;
            } elseif ( $timeout > $max_timeout ) {
                $args['timeout'] = $max_timeout;
            } else {
                $args['timeout'] = $timeout;
            }

            return $args;
        }

        private function extract_template_id( $args, $url ) {
            if ( isset( $args['body'] ) ) {
                if ( is_array( $args['body'] ) && isset( $args['body']['sku'] ) ) {
                    return absint( $args['body']['sku'] );
                }

                if ( is_string( $args['body'] ) ) {
                    parse_str( $args['body'], $parsed_body );
                    if ( isset( $parsed_body['sku'] ) ) {
                        return absint( $parsed_body['sku'] );
                    }
                }
            }

            $parsed_url = wp_parse_url( $url );
            if ( isset( $parsed_url['query'] ) ) {
                parse_str( $parsed_url['query'], $query_args );
                if ( isset( $query_args['sku'] ) ) {
                    return absint( $query_args['sku'] );
                }
            }

            return 0;
        }

        private function maybe_get_template_fallback_response( $template_id ) {
            $candidates = [];

            if ( $template_id ) {
                $candidates[] = self::TEMPLATE_FALLBACK_DIR . $template_id . '.json';
                $candidates[] = self::TEMPLATE_FALLBACK_DIR . 'template-' . $template_id . '.json';
            }

            $candidates[] = self::TEMPLATE_FALLBACK_DIR . 'default.json';

            foreach ( $candidates as $file ) {
                if ( ! $file || ! file_exists( $file ) || ! is_readable( $file ) ) {
                    continue;
                }

                $contents = file_get_contents( $file );
                if ( false === $contents ) {
                    continue;
                }

                return [
                    'headers'  => [ 'X-SCIOS-Fast-Fallback' => basename( $file ) ],
                    'body'     => $contents,
                    'response' => [
                        'code'    => 200,
                        'message' => 'OK',
                    ],
                    'cookies'  => [],
                ];
            }

            return null;
        }

        private function build_cache_key( $args, $url ) {
            $method  = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'GET';
            $body    = isset( $args['body'] ) ? $args['body'] : '';
            $payload = is_array( $body ) ? wp_json_encode( $body ) : (string) $body;
            $hash    = md5( wp_json_encode( [ $method, $url, $payload ] ) );

            return self::CACHE_PREFIX . $hash;
        }

        private function store_response( $cache_key, $response, $ttl ) {
            $ttl = absint( $ttl );

            if ( $ttl > 0 ) {
                set_transient( $cache_key, $response, $ttl );
            }

            update_option( self::BACKUP_PREFIX . $cache_key, $response, false );
            $this->register_cache_key( $cache_key );
        }

        private function get_cached_response( $cache_key ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }

            return null;
        }

        private function get_backup_response( $cache_key ) {
            return get_option( self::BACKUP_PREFIX . $cache_key, null );
        }

        private function register_cache_key( $cache_key ) {
            $keys = get_option( self::CACHE_KEYS_OPTION, [] );
            if ( ! in_array( $cache_key, $keys, true ) ) {
                $keys[] = $cache_key;
                update_option( self::CACHE_KEYS_OPTION, $keys, false );
            }
        }

        private function unregister_cache_key( $cache_key ) {
            $keys = get_option( self::CACHE_KEYS_OPTION, [] );
            if ( empty( $keys ) || ! is_array( $keys ) ) {
                return;
            }

            $index = array_search( $cache_key, $keys, true );
            if ( false !== $index ) {
                unset( $keys[ $index ] );
                update_option( self::CACHE_KEYS_OPTION, array_values( $keys ), false );
            }
        }

        private function register_template_cache( $cache_key, $template_id, $meta = [] ) {
            if ( ! $template_id ) {
                return;
            }

            $map = $this->get_template_cache_map();
            $map[ $cache_key ] = wp_parse_args(
                $meta,
                [
                    'template_id' => absint( $template_id ),
                    'stored_at'   => time(),
                ]
            );

            update_option( self::TEMPLATE_CACHE_OPTION, $map, false );
        }

        private function get_template_cache_map() {
            $map = get_option( self::TEMPLATE_CACHE_OPTION, [] );

            return is_array( $map ) ? $map : [];
        }

        private function record_template_fallback( $template_id, $reason ) {
            $events = $this->get_template_fallback_events();

            $events[ (string) $template_id ] = [
                'template_id' => absint( $template_id ),
                'reason'      => $reason,
                'timestamp'   => time(),
            ];

            update_option( self::TEMPLATE_FALLBACK_EVENTS_OPTION, $events, false );

            set_transient(
                self::TEMPLATE_FALLBACK_NOTICE,
                $events[ (string) $template_id ],
                MINUTE_IN_SECONDS * 30
            );
        }

        private function clear_template_fallback( $template_id ) {
            $events = $this->get_template_fallback_events();

            if ( isset( $events[ (string) $template_id ] ) ) {
                unset( $events[ (string) $template_id ] );
                update_option( self::TEMPLATE_FALLBACK_EVENTS_OPTION, $events, false );
            }
        }

        private function get_template_fallback_events() {
            $events = get_option( self::TEMPLATE_FALLBACK_EVENTS_OPTION, [] );

            return is_array( $events ) ? $events : [];
        }

        private function get_fallback_reason_label( $reason ) {
            switch ( $reason ) {
                case 'fallback_used':
                    return __( 'Se usó la copia local por error remoto.', 'scios-fast' );
                case 'fallback_missing':
                    return __( 'No hay plantilla local disponible.', 'scios-fast' );
                default:
                    return __( 'Motivo no especificado.', 'scios-fast' );
            }
        }

        private function remove_template_cache_entry( $cache_key ) {
            delete_transient( $cache_key );
            delete_option( self::BACKUP_PREFIX . $cache_key );
            $this->unregister_cache_key( $cache_key );

            $map = $this->get_template_cache_map();
            if ( isset( $map[ $cache_key ] ) ) {
                unset( $map[ $cache_key ] );
                update_option( self::TEMPLATE_CACHE_OPTION, $map, false );
            }
        }

        private function refresh_template_cache() {
            $map = $this->get_template_cache_map();

            if ( empty( $map ) ) {
                return [
                    'count'  => 0,
                    'errors' => [],
                ];
            }

            if ( ! class_exists( '\ReyTheme_API' ) ) {
                return [
                    'count'  => 0,
                    'errors' => [ __( 'La clase ReyTheme_API no está disponible.', 'scios-fast' ) ],
                ];
            }

            $api    = \ReyTheme_API::getInstance();
            $errors = [];
            $count  = 0;

            foreach ( $map as $cache_key => $data ) {
                $template_id = isset( $data['template_id'] ) ? absint( $data['template_id'] ) : 0;

                $this->remove_template_cache_entry( $cache_key );

                if ( ! $template_id ) {
                    continue;
                }

                $result = $api->get_template_data( $template_id );

                if ( is_wp_error( $result ) ) {
                    $errors[] = sprintf(
                        __( 'Plantilla %1$d: %2$s', 'scios-fast' ),
                        $template_id,
                        $result->get_error_message()
                    );
                } else {
                    $count++;
                }
            }

            return [
                'count'  => $count,
                'errors' => $errors,
            ];
        }

        private function clear_cache() {
            $keys = get_option( self::CACHE_KEYS_OPTION, [] );
            if ( empty( $keys ) || ! is_array( $keys ) ) {
                delete_option( self::TEMPLATE_CACHE_OPTION );
                delete_option( self::TEMPLATE_FALLBACK_EVENTS_OPTION );
                return;
            }

            foreach ( $keys as $cache_key ) {
                delete_transient( $cache_key );
                delete_option( self::BACKUP_PREFIX . $cache_key );
            }

            delete_option( self::CACHE_KEYS_OPTION );
            delete_option( self::TEMPLATE_CACHE_OPTION );
            delete_option( self::TEMPLATE_FALLBACK_EVENTS_OPTION );
        }

        public function register_settings_page() {
            add_options_page(
                __( 'SCIOS Fast', 'scios-fast' ),
                __( 'SCIOS Fast', 'scios-fast' ),
                'manage_options',
                'scios-fast',
                [ $this, 'render_settings_page' ]
            );
        }

        public function register_settings() {
            register_setting( 'scios_fast_settings', self::OPTION_NAME, [ $this, 'sanitize_options' ] );

            add_settings_section(
                'scios_fast_general',
                __( 'Configuración General', 'scios-fast' ),
                '__return_false',
                'scios_fast_settings'
            );

            add_settings_field(
                'scios_fast_ttl',
                __( 'Tiempo de vida del caché (segundos)', 'scios-fast' ),
                [ $this, 'render_ttl_field' ],
                'scios_fast_settings',
                'scios_fast_general'
            );

            add_settings_field(
                'scios_fast_template_timeout',
                __( 'Tiempo máximo para plantillas remotas (segundos)', 'scios-fast' ),
                [ $this, 'render_template_timeout_field' ],
                'scios_fast_settings',
                'scios_fast_general'
            );

            add_settings_field(
                'scios_fast_fallback',
                __( 'Endpoint de fallback', 'scios-fast' ),
                [ $this, 'render_fallback_field' ],
                'scios_fast_settings',
                'scios_fast_general'
            );

            add_settings_field(
                'scios_fast_logging',
                __( 'Habilitar logging', 'scios-fast' ),
                [ $this, 'render_logging_field' ],
                'scios_fast_settings',
                'scios_fast_general'
            );
        }

        public function sanitize_options( $input ) {
            $defaults  = $this->get_default_options();
            $sanitized = [
                'ttl'               => isset( $input['ttl'] ) ? max( 0, absint( $input['ttl'] ) ) : $defaults['ttl'],
                'template_timeout'  => isset( $input['template_timeout'] ) ? max( 1, absint( $input['template_timeout'] ) ) : $defaults['template_timeout'],
                'fallback_endpoint' => isset( $input['fallback_endpoint'] ) ? esc_url_raw( trim( $input['fallback_endpoint'] ) ) : '',
                'enable_logging'    => ! empty( $input['enable_logging'] ) ? 1 : 0,
            ];

            $this->options_cache = null;

            return $sanitized;
        }

        private function get_options() {
            if ( null === $this->options_cache ) {
                $options  = get_option( self::OPTION_NAME, [] );
                $defaults = $this->get_default_options();

                $this->options_cache = wp_parse_args( $options, $defaults );
            }

            return $this->options_cache;
        }

        private function get_default_options() {
            return [
                'ttl'               => 3600,
                'template_timeout'  => self::DEFAULT_TEMPLATE_TIMEOUT,
                'fallback_endpoint' => '',
                'enable_logging'    => 0,
            ];
        }

        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'SCIOS Fast', 'scios-fast' ); ?></h1>
                <?php $this->render_admin_notices(); ?>
                <form action="options.php" method="post">
                    <?php
                        settings_fields( 'scios_fast_settings' );
                        do_settings_sections( 'scios_fast_settings' );
                        submit_button();
                    ?>
                </form>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                    <?php wp_nonce_field( 'scios_fast_clear_cache' ); ?>
                    <input type="hidden" name="action" value="scios_fast_clear_cache" />
                    <?php submit_button( __( 'Limpiar caché', 'scios-fast' ), 'secondary' ); ?>
                </form>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                    <?php wp_nonce_field( 'scios_fast_warm_fonts' ); ?>
                    <input type="hidden" name="action" value="scios_fast_warm_fonts" />
                    <?php submit_button( __( 'Precalentar fuentes ahora', 'scios-fast' ), 'secondary' ); ?>
                </form>

                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                    <?php wp_nonce_field( 'scios_fast_refresh_templates' ); ?>
                    <input type="hidden" name="action" value="scios_fast_refresh_templates" />
                    <?php submit_button( __( 'Refrescar plantillas remotas', 'scios-fast' ), 'secondary' ); ?>
                </form>

                <?php $this->render_template_cache_section(); ?>
                <?php $this->render_font_cache_section(); ?>
            </div>
            <?php
        }

        public function render_ttl_field() {
            $options = $this->get_options();
            ?>
            <input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ttl]" value="<?php echo esc_attr( $options['ttl'] ); ?>" min="0" class="small-text" />
            <?php
        }

        public function render_template_timeout_field() {
            $options = $this->get_options();
            ?>
            <input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[template_timeout]" value="<?php echo esc_attr( $options['template_timeout'] ); ?>" min="1" class="small-text" />
            <p class="description"><?php esc_html_e( 'Define el límite máximo de espera antes de usar un fallback para plantillas remotas.', 'scios-fast' ); ?></p>
            <?php
        }

        public function render_fallback_field() {
            $options = $this->get_options();
            ?>
            <input type="url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fallback_endpoint]" value="<?php echo esc_attr( $options['fallback_endpoint'] ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Introduce la URL base del endpoint de fallback.', 'scios-fast' ); ?></p>
            <?php
        }

        public function render_logging_field() {
            $options = $this->get_options();
            ?>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_logging]" value="1" <?php checked( 1, $options['enable_logging'] ); ?> />
                <?php esc_html_e( 'Escribir eventos en WP_DEBUG_LOG (sólo en modo debug).', 'scios-fast' ); ?>
            </label>
            <?php
        }

        public function handle_clear_cache() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'No tienes permisos suficientes.', 'scios-fast' ) );
            }

            check_admin_referer( 'scios_fast_clear_cache' );
            $this->clear_cache();
            wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'options-general.php?page=scios-fast' ) );
            exit;
        }

        public function handle_warm_fonts() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'No tienes permisos suficientes.', 'scios-fast' ) );
            }

            check_admin_referer( 'scios_fast_warm_fonts' );

            $result = $this->warm_font_cache( [ 'source' => 'manual' ] );

            $redirect_url = wp_get_referer() ? wp_get_referer() : admin_url( 'options-general.php?page=scios-fast' );

            if ( ! empty( $result['errors'] ) ) {
                $redirect_url = add_query_arg( 'scios_fast_warm_error', rawurlencode( implode( ' | ', $result['errors'] ) ), $redirect_url );
            } else {
                $redirect_url = add_query_arg( 'scios_fast_warmed', 1, $redirect_url );
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }

        public function handle_refresh_templates() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'No tienes permisos suficientes.', 'scios-fast' ) );
            }

            check_admin_referer( 'scios_fast_refresh_templates' );

            $result = $this->refresh_template_cache();

            $redirect_url = wp_get_referer() ? wp_get_referer() : admin_url( 'options-general.php?page=scios-fast' );

            if ( ! empty( $result['errors'] ) ) {
                $redirect_url = add_query_arg(
                    'scios_fast_template_error',
                    rawurlencode( implode( ' | ', $result['errors'] ) ),
                    $redirect_url
                );
            } else {
                $redirect_url = add_query_arg( 'scios_fast_template_refreshed', absint( $result['count'] ), $redirect_url );
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }

        public function maybe_schedule_font_cache_cron() {
            if ( wp_installing() ) {
                return;
            }

            if ( ! wp_next_scheduled( self::FONT_CACHE_CRON_HOOK ) ) {
                wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::FONT_CACHE_CRON_HOOK );
            }
        }

        public function handle_font_cache_cron() {
            $this->warm_font_cache( [ 'source' => 'cron' ] );
        }

        public function cli_warm_font_cache() {
            $result = $this->warm_font_cache( [ 'source' => 'cli' ] );

            $after_counts = [];
            foreach ( $result['after'] as $prefix => $items ) {
                $after_counts[ $prefix ] = count( $items );
            }

            $message = sprintf(
                /* translators: 1: Number of prefixes warmed, 2: total transients */
                __( 'Precargadas %1$d categorías de transients (%2$d entradas).', 'scios-fast' ),
                count( $after_counts ),
                array_sum( $after_counts )
            );

            if ( ! empty( $result['errors'] ) ) {
                \WP_CLI::warning( implode( PHP_EOL, $result['errors'] ) );
            }

            \WP_CLI::success( $message );
        }

        private function warm_font_cache( $context = [] ) {
            $this->log_debug( '[SCIOS Fast] Iniciando precalentamiento de fuentes.' );

            $before = $this->get_font_transients();
            $errors = [];
            $actions = [];

            $rey_methods_executed = false;

            if ( false !== has_filter( 'rey/css_styles' ) ) {
                try {
                    apply_filters( 'rey/css_styles', [] );
                    $actions[]             = 'rey/css_styles';
                    $rey_methods_executed = true;
                } catch ( \Throwable $throwable ) {
                    $errors[] = sprintf( 'rey/css_styles: %s', $throwable->getMessage() );
                }
            }

            if ( class_exists( '\\ReyCore\\Plugin' ) ) {
                try {
                    $plugin = \ReyCore\Plugin::instance();
                    if ( ! $rey_methods_executed && isset( $plugin->fonts ) && method_exists( $plugin->fonts, 'elementor_global_fonts_to_rey_typo' ) ) {
                        $plugin->fonts->elementor_global_fonts_to_rey_typo();
                        $actions[] = 'ReyCore::elementor_global_fonts_to_rey_typo';
                    }
                    if ( ! $rey_methods_executed && isset( $plugin->fonts ) && method_exists( $plugin->fonts, 'elementor_pro_custom_fonts_css' ) ) {
                        $plugin->fonts->elementor_pro_custom_fonts_css();
                        $actions[] = 'ReyCore::elementor_pro_custom_fonts_css';
                    }
                } catch ( \Throwable $throwable ) {
                    $errors[] = sprintf( 'ReyCore: %s', $throwable->getMessage() );
                }
            }

            if ( class_exists( '\\ElementorPro\\Modules\\AssetsManager\\AssetTypes\\Fonts\\Custom_Fonts' ) ) {
                try {
                    $custom_fonts = new \ElementorPro\Modules\AssetsManager\AssetTypes\Fonts\Custom_Fonts();
                    $custom_fonts->get_fonts( true );
                    $actions[] = 'ElementorPro::Custom_Fonts::get_fonts';
                } catch ( \Throwable $throwable ) {
                    $errors[] = sprintf( 'ElementorPro: %s', $throwable->getMessage() );
                }
            }

            $after = $this->get_font_transients();

            $meta = [
                'last_run'    => time(),
                'last_source' => isset( $context['source'] ) ? $context['source'] : 'manual',
                'last_counts' => array_map( 'count', $after ),
            ];

            if ( ! empty( $errors ) ) {
                $meta['last_errors'] = $errors;
            }

            update_option( self::FONT_CACHE_META_OPTION, $meta, false );

            $this->log_debug( '[SCIOS Fast] Finalizado precalentamiento de fuentes.' );

            return [
                'before' => $before,
                'after'  => $after,
                'meta'   => $meta,
                'errors' => $errors,
                'actions'=> $actions,
            ];
        }

        public function maybe_render_global_notice() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $notice = get_transient( self::TEMPLATE_FALLBACK_NOTICE );

            if ( false === $notice ) {
                return;
            }

            delete_transient( self::TEMPLATE_FALLBACK_NOTICE );

            $template_id = isset( $notice['template_id'] ) && $notice['template_id'] ? absint( $notice['template_id'] ) : 0;
            $reason      = isset( $notice['reason'] ) ? $notice['reason'] : '';
            $timestamp   = isset( $notice['timestamp'] ) ? absint( $notice['timestamp'] ) : 0;

            $template_label = $template_id ? sprintf( __( 'la plantilla %d', 'scios-fast' ), $template_id ) : __( 'una plantilla desconocida', 'scios-fast' );
            $reason_label   = $this->get_fallback_reason_label( $reason );
            $message_parts  = [
                sprintf(
                    __( 'SCIOS Fast ha usado el fallback local para %1$s. Motivo: %2$s.', 'scios-fast' ),
                    esc_html( $template_label ),
                    esc_html( $reason_label )
                ),
            ];

            if ( $timestamp ) {
                $message_parts[] = sprintf(
                    __( 'Momento del evento: %s.', 'scios-fast' ),
                    esc_html( $this->format_datetime( $timestamp ) )
                );
            }

            echo '<div class="notice notice-warning is-dismissible"><p>' . implode( ' ', $message_parts ) . '</p></div>';
        }

        private function render_admin_notices() {
            if ( isset( $_GET['scios_fast_warmed'] ) ) {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'El precalentamiento de fuentes se ejecutó correctamente.', 'scios-fast' ) . '</p></div>';
            }

            if ( isset( $_GET['scios_fast_warm_error'] ) ) {
                $error_message = sanitize_text_field( wp_unslash( $_GET['scios_fast_warm_error'] ) );
                echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'Errores durante el precalentamiento: %s', 'scios-fast' ), $error_message ) ) . '</p></div>';
            }

            if ( isset( $_GET['scios_fast_template_refreshed'] ) ) {
                $count = absint( $_GET['scios_fast_template_refreshed'] );
                echo '<div class="notice notice-success"><p>' . esc_html( sprintf( _n( 'Se ha refrescado %d plantilla remota.', 'Se han refrescado %d plantillas remotas.', $count, 'scios-fast' ), $count ) ) . '</p></div>';
            }

            if ( isset( $_GET['scios_fast_template_error'] ) ) {
                $error_message = sanitize_text_field( wp_unslash( $_GET['scios_fast_template_error'] ) );
                echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'Errores al refrescar plantillas: %s', 'scios-fast' ), $error_message ) ) . '</p></div>';
            }
        }

        private function render_template_cache_section() {
            $map       = $this->get_template_cache_map();
            $fallbacks = $this->get_template_fallback_events();

            ?>
            <h2><?php esc_html_e( 'Estado de plantillas remotas', 'scios-fast' ); ?></h2>
            <p><?php esc_html_e( 'Controla las plantillas descargadas desde Rey/Core y sus copias locales.', 'scios-fast' ); ?></p>
            <?php if ( empty( $map ) ) : ?>
                <p><?php esc_html_e( 'Aún no se ha almacenado ninguna plantilla remota.', 'scios-fast' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'ID de plantilla', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Estado', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Origen de datos', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Última actualización', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Clave de caché', 'scios-fast' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $map as $cache_key => $data ) : ?>
                            <?php
                            $template_id   = isset( $data['template_id'] ) ? absint( $data['template_id'] ) : 0;
                            $stored_at     = isset( $data['stored_at'] ) ? absint( $data['stored_at'] ) : 0;
                            $source        = isset( $data['source'] ) ? $data['source'] : 'remote';
                            $event         = $template_id && isset( $fallbacks[ (string) $template_id ] ) ? $fallbacks[ (string) $template_id ] : null;
                            $status_label  = $event ? sprintf( __( 'Fallback activo (%s)', 'scios-fast' ), $this->get_fallback_reason_label( isset( $event['reason'] ) ? $event['reason'] : '' ) ) : __( 'OK', 'scios-fast' );
                            $origin_label  = 'fallback' === $source ? __( 'Copia local', 'scios-fast' ) : __( 'Remoto', 'scios-fast' );
                            $updated_label = $stored_at ? $this->format_datetime( $stored_at ) : __( 'Sin datos', 'scios-fast' );
                            ?>
                            <tr>
                                <td><?php echo $template_id ? esc_html( $template_id ) : esc_html__( 'Desconocido', 'scios-fast' ); ?></td>
                                <td><?php echo esc_html( $status_label ); ?></td>
                                <td><?php echo esc_html( $origin_label ); ?></td>
                                <td><?php echo esc_html( $updated_label ); ?></td>
                                <td><code><?php echo esc_html( $cache_key ); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ( ! empty( $fallbacks ) ) : ?>
                <p><?php esc_html_e( 'Últimos fallbacks detectados:', 'scios-fast' ); ?></p>
                <ul>
                    <?php foreach ( $fallbacks as $event ) : ?>
                        <li>
                            <?php
                            $template_id = isset( $event['template_id'] ) ? absint( $event['template_id'] ) : 0;
                            $label       = $template_id ? sprintf( __( 'Plantilla %d', 'scios-fast' ), $template_id ) : __( 'Plantilla desconocida', 'scios-fast' );
                            $time_label  = isset( $event['timestamp'] ) ? $this->format_datetime( absint( $event['timestamp'] ) ) : __( 'Fecha no disponible', 'scios-fast' );
                            $reason      = $this->get_fallback_reason_label( isset( $event['reason'] ) ? $event['reason'] : '' );
                            $line        = sprintf(
                                /* translators: 1: template label, 2: fallback reason, 3: datetime. */
                                __( '%1$s — %2$s (%3$s)', 'scios-fast' ),
                                $label,
                                $reason,
                                $time_label
                            );
                            echo esc_html( $line );
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php
        }

        private function render_font_cache_section() {
            $status = $this->get_font_cache_status();
            $next_run = isset( $status['next_run'] ) ? $status['next_run'] : false;
            $last_run = isset( $status['last_run'] ) ? $status['last_run'] : false;
            ?>
            <h2><?php esc_html_e( 'Estado del caché de fuentes', 'scios-fast' ); ?></h2>
            <p>
                <?php
                if ( $next_run ) {
                    printf(
                        esc_html__( 'Próxima ejecución programada: %s.', 'scios-fast' ),
                        esc_html( $this->format_datetime( $next_run ) )
                    );
                } else {
                    esc_html_e( 'No hay una ejecución programada actualmente.', 'scios-fast' );
                }
                ?>
            </p>
            <?php if ( $last_run ) : ?>
                <p>
                    <?php
                    printf(
                        esc_html__( 'Última ejecución: %1$s (origen: %2$s).', 'scios-fast' ),
                        esc_html( $this->format_datetime( $last_run ) ),
                        esc_html( $status['last_source'] )
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $status['transients'] ) ) : ?>
                <?php $prefix_labels = $this->get_font_transient_prefixes(); ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Prefijo', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Transient', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Caduca en', 'scios-fast' ); ?></th>
                            <th><?php esc_html_e( 'Tamaño (bytes)', 'scios-fast' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $status['transients'] as $prefix => $items ) : ?>
                            <?php $label = isset( $prefix_labels[ $prefix ] ) ? $prefix_labels[ $prefix ] : $prefix; ?>
                            <?php if ( empty( $items ) ) : ?>
                                <tr>
                                    <td><?php echo esc_html( sprintf( '%s (%s)', $label, $prefix ) ); ?></td>
                                    <td colspan="3"><?php esc_html_e( 'No se encontraron transients para este prefijo.', 'scios-fast' ); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $items as $name => $info ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( sprintf( '%s (%s)', $label, $prefix ) ); ?></td>
                                        <td><code><?php echo esc_html( $name ); ?></code></td>
                                        <td>
                                            <?php
                                            if ( $info['expires'] ) {
                                                $diff = $info['expires'] >= time()
                                                    ? sprintf( __( 'en %s', 'scios-fast' ), human_time_diff( time(), $info['expires'] ) )
                                                    : sprintf( __( 'hace %s', 'scios-fast' ), human_time_diff( $info['expires'], time() ) );

                                                printf(
                                                    '%s (%s)',
                                                    esc_html( $this->format_datetime( $info['expires'] ) ),
                                                    esc_html( $diff )
                                                );
                                            } else {
                                                esc_html_e( 'Persistente', 'scios-fast' );
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html( number_format_i18n( $info['size'] ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No se detectaron transients relacionados con fuentes.', 'scios-fast' ); ?></p>
            <?php endif; ?>
            <?php
        }

        private function get_font_cache_status() {
            $meta      = get_option( self::FONT_CACHE_META_OPTION, [] );
            $next_run  = wp_next_scheduled( self::FONT_CACHE_CRON_HOOK );
            $transient_data = $this->get_font_transients();

            return [
                'last_run'    => isset( $meta['last_run'] ) ? (int) $meta['last_run'] : null,
                'last_source' => isset( $meta['last_source'] ) ? $meta['last_source'] : '',
                'next_run'    => $next_run ? (int) $next_run : null,
                'transients'  => $transient_data,
            ];
        }

        private function get_font_transients() {
            global $wpdb;

            if ( ! isset( $wpdb ) || empty( $wpdb->options ) ) {
                return [];
            }

            $data     = [];
            $prefixes = $this->get_font_transient_prefixes();
            $base_len = strlen( '_transient_' );

            foreach ( $prefixes as $prefix => $label ) {
                $like  = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
                $query = $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like );
                $rows  = $wpdb->get_results( $query, ARRAY_A );

                if ( empty( $rows ) ) {
                    $data[ $prefix ] = [];
                    continue;
                }

                $group = [];

                foreach ( $rows as $row ) {
                    $name    = substr( $row['option_name'], $base_len );
                    $timeout = get_option( '_transient_timeout_' . $name );
                    $value   = maybe_unserialize( $row['option_value'] );

                    $group[ $name ] = [
                        'label'   => $label,
                        'expires' => $timeout ? (int) $timeout : null,
                        'size'    => strlen( maybe_serialize( $value ) ),
                    ];
                }

                ksort( $group );
                $data[ $prefix ] = $group;
            }

            return $data;
        }

        private function get_font_transient_prefixes() {
            return [
                'rey_fonts_'        => __( 'Rey/Core', 'scios-fast' ),
                'elementor_fonts_'  => __( 'Elementor', 'scios-fast' ),
            ];
        }

        private function format_datetime( $timestamp ) {
            return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }

        private function log_debug( $message ) {
            $options = $this->get_options();
            if ( empty( $options['enable_logging'] ) ) {
                return;
            }

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( $message );
            }
        }

        private function is_error_status( $response ) {
            if ( is_wp_error( $response ) ) {
                return true;
            }

            $code = wp_remote_retrieve_response_code( $response );
            return empty( $code ) || $code >= 400;
        }
    }

    SCIOS_Fast_Plugin::get_instance();
}
