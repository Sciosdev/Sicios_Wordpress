<?php
/**
 * Admin settings integration for WooCommerce shipping tab.
 *
 * @package Dokan_DHL_Per_Vendor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Dokan_DHL_Admin_Settings', false ) ) {

    class Dokan_DHL_Admin_Settings {

        const SECTION_ID = 'dokan_dhl';

        const OPTION_WEBHOOK_TOKEN = 'dokan_dhl_webhook_token';

        const OPTION_NOTIFICATION_EMAIL = 'dokan_dhl_notification_email';

        const OPTION_NOTIFICATION_ENABLED = 'dokan_dhl_notification_enabled';

        const OPTION_FALLBACK_USERNAME = 'dokan_dhl_fallback_username';

        const OPTION_FALLBACK_SECRET = 'dokan_dhl_fallback_secret';

        /**
         * Boot the settings hooks.
         */
        public static function boot() {
            $instance = new self();
            $instance->init_hooks();
        }

        /**
         * Register all WordPress hooks.
         */
        protected function init_hooks() {
            add_action( 'woocommerce_settings_tabs', array( $this, 'guard_capabilities' ), 5 );
            add_filter( 'woocommerce_get_sections_shipping', array( $this, 'register_section' ) );
            add_filter( 'woocommerce_get_settings_shipping', array( $this, 'register_settings' ), 10, 2 );
            add_action( 'woocommerce_admin_field_dokan_dhl_masked', array( $this, 'render_masked_field' ) );
            add_action( 'woocommerce_settings_save_shipping', array( $this, 'save_settings' ), 5 );
        }

        /**
         * Ensure only authorised users can access the section.
         */
        public function guard_capabilities() {
            if ( ! $this->is_active_section() ) {
                return;
            }

            if ( current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            wp_die( esc_html__( 'You do not have permission to manage these settings.', 'scios-dhl-per-vendor' ) );
        }

        /**
         * Register Dokan DHL section inside the WooCommerce shipping settings.
         *
         * @param array $sections Current sections.
         *
         * @return array
         */
        public function register_section( $sections ) {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return $sections;
            }

            $sections[ self::SECTION_ID ] = __( 'SCIOS DHL', 'scios-dhl-per-vendor' );

            return $sections;
        }

        /**
         * Register settings for the Dokan DHL section.
         *
         * @param array  $settings Existing settings for the section.
         * @param string $section_id Current section identifier.
         *
         * @return array
         */
        public function register_settings( $settings, $section_id ) {
            if ( self::SECTION_ID !== $section_id ) {
                return $settings;
            }

            $settings = array(
                array(
                    'title' => __( 'Integración SCIOS DHL', 'scios-dhl-per-vendor' ),
                    'type'  => 'title',
                    'desc'  => __( 'Configura el webhook y las credenciales globales utilizadas cuando no existan credenciales de vendedor.', 'scios-dhl-per-vendor' ),
                    'id'    => 'dokan_dhl_admin_settings',
                ),
                array(
                    'title'       => __( 'Token de webhook', 'scios-dhl-per-vendor' ),
                    'id'          => self::OPTION_WEBHOOK_TOKEN,
                    'type'        => 'dokan_dhl_masked',
                    'desc_tip'    => __( 'Se utiliza para validar las llamadas entrantes. Deja el campo vacío para mantener el token actual.', 'scios-dhl-per-vendor' ),
                    'custom_attributes' => array(
                        'autocomplete' => 'new-password',
                    ),
                    'is_option'   => false,
                ),
                array(
                    'title'     => __( 'Email de notificación', 'scios-dhl-per-vendor' ),
                    'id'        => self::OPTION_NOTIFICATION_EMAIL,
                    'type'      => 'email',
                    'desc'      => __( 'Opcional. Recibirá alertas sobre incidencias del webhook.', 'scios-dhl-per-vendor' ),
                    'default'   => '',
                    'is_option' => false,
                ),
                array(
                    'title'     => __( 'Activar notificaciones', 'scios-dhl-per-vendor' ),
                    'id'        => self::OPTION_NOTIFICATION_ENABLED,
                    'type'      => 'checkbox',
                    'desc'      => __( 'Enviar emails cuando existan eventos nuevos del webhook.', 'scios-dhl-per-vendor' ),
                    'default'   => 'no',
                    'is_option' => false,
                ),
                array(
                    'type'  => 'title',
                    'id'    => 'dokan_dhl_admin_fallback',
                    'title' => __( 'Credenciales fallback', 'scios-dhl-per-vendor' ),
                    'desc'  => __( 'Utilizadas únicamente si el vendedor no tiene credenciales propias. Mantén estos datos seguros y cámbialos sólo cuando sea imprescindible.', 'scios-dhl-per-vendor' ),
                ),
                array(
                    'title'     => __( 'Usuario fallback', 'scios-dhl-per-vendor' ),
                    'id'        => self::OPTION_FALLBACK_USERNAME,
                    'type'      => 'text',
                    'default'   => '',
                    'desc_tip'  => __( 'Se usará como usuario por defecto para las integraciones con DHL.', 'scios-dhl-per-vendor' ),
                    'is_option' => false,
                ),
                array(
                    'title'       => __( 'Secreto fallback', 'scios-dhl-per-vendor' ),
                    'id'          => self::OPTION_FALLBACK_SECRET,
                    'type'        => 'dokan_dhl_masked',
                    'desc_tip'    => __( 'Contraseña o token global. Déjalo vacío para conservar el valor existente.', 'scios-dhl-per-vendor' ),
                    'custom_attributes' => array(
                        'autocomplete' => 'new-password',
                    ),
                    'is_option'   => false,
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'dokan_dhl_admin_settings',
                ),
            );

            return $settings;
        }

        /**
         * Render masked input field used for secrets.
         *
         * @param array $value Field data.
         */
        public function render_masked_field( $value ) {
            $option_value = WC_Admin_Settings::get_option( $value['id'], '' );
            $placeholder  = '';

            if ( is_string( $option_value ) && '' !== $option_value ) {
                $length     = function_exists( 'mb_strlen' ) ? mb_strlen( $option_value ) : strlen( $option_value );
                $length     = min( max( $length, 8 ), 32 );
                $placeholder = str_repeat( '•', $length );
            }

            $field_description = WC_Admin_Settings::get_field_description( $value );
            $description       = $field_description['description'];
            $tooltip_html      = $field_description['tooltip_html'];

            $custom_attributes = array();

            if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
                foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
                    $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
                }
            }

            $field_class = isset( $value['class'] ) ? $value['class'] : '';

            printf(
                '<tr valign="top"><th scope="row" class="titledesc"><label for="%1$s">%2$s</label>%3$s</th><td class="forminp forminp-%4$s"><input type="password" name="%1$s" id="%1$s" value="" placeholder="%5$s" class="regular-input %6$s" %7$s />%8$s</td></tr>',
                esc_attr( $value['id'] ),
                esc_html( $value['title'] ),
                $tooltip_html,
                esc_attr( sanitize_title( $value['type'] ) ),
                esc_attr( $placeholder ),
                esc_attr( $field_class ),
                implode( ' ', $custom_attributes ),
                $description ? '<p class="description">' . wp_kses_post( $description ) . '</p>' : ''
            );
        }

        /**
         * Handle saving the custom settings.
         */
        public function save_settings() {
            if ( ! $this->is_active_section() || ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            check_admin_referer( 'woocommerce-settings' );

            $data = wp_unslash( $_POST );

            if ( isset( $data[ self::OPTION_WEBHOOK_TOKEN ] ) ) {
                $token = trim( (string) $data[ self::OPTION_WEBHOOK_TOKEN ] );
                if ( '' !== $token ) {
                    update_option( self::OPTION_WEBHOOK_TOKEN, sanitize_text_field( $token ) );
                }
            }

            if ( isset( $data[ self::OPTION_NOTIFICATION_EMAIL ] ) ) {
                $raw_email = (string) $data[ self::OPTION_NOTIFICATION_EMAIL ];
                $email     = sanitize_email( $raw_email );

                if ( '' !== $raw_email && '' === $email ) {
                    WC_Admin_Settings::add_error( __( 'Introduce un email de notificación válido.', 'scios-dhl-per-vendor' ) );
                } else {
                    update_option( self::OPTION_NOTIFICATION_EMAIL, $email );
                }
            }

            $notify = isset( $data[ self::OPTION_NOTIFICATION_ENABLED ] ) ? 'yes' : 'no';
            update_option( self::OPTION_NOTIFICATION_ENABLED, $notify );

            if ( isset( $data[ self::OPTION_FALLBACK_USERNAME ] ) ) {
                $username = sanitize_text_field( (string) $data[ self::OPTION_FALLBACK_USERNAME ] );
                update_option( self::OPTION_FALLBACK_USERNAME, $username );
            }

            if ( isset( $data[ self::OPTION_FALLBACK_SECRET ] ) ) {
                $secret = trim( (string) $data[ self::OPTION_FALLBACK_SECRET ] );
                if ( '' !== $secret ) {
                    update_option( self::OPTION_FALLBACK_SECRET, sanitize_text_field( $secret ) );
                }
            }
        }

        /**
         * Check whether the Dokan DHL section is currently active.
         *
         * @return bool
         */
        protected function is_active_section() {
            $tab     = isset( $_GET['tab'] ) ? wc_clean( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $section = isset( $_GET['section'] ) ? wc_clean( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            return 'shipping' === $tab && self::SECTION_ID === $section;
        }
    }
}
