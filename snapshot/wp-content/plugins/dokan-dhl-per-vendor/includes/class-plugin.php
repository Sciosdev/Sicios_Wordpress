<?php
/**
 * Main plugin loader.
 *
 * @package Dokan_DHL_Per_Vendor
 */

if ( ! class_exists( 'Dokan_DHL_Plugin' ) ) {

    class Dokan_DHL_Plugin {

        /**
         * Plugin instance.
         *
         * @var Dokan_DHL_Plugin|null
         */
        protected static $instance = null;

        /**
         * Get singleton instance.
         *
         * @return Dokan_DHL_Plugin
         */
        public static function instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Dokan_DHL_Plugin constructor.
         */
        protected function __construct() {
            $this->load_dependencies();
            $this->boot_services();
            $this->init_hooks();
        }

        /**
         * Load required files.
         */
        protected function load_dependencies() {
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/data/class-credentials-store.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/data/class-order-store.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/admin/class-order-metabox.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/admin/class-order-columns.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/admin/class-settings.php';

            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/api/class-dhl-client.php';

            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/api/class-rest-controller.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/vendor/class-settings-screen.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/vendor/class-order-actions.php';
            require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/class-tracking-manager.php';
        }

        /**
         * Boot background services.
         */
        protected function boot_services() {
            if ( class_exists( 'Dokan_DHL_Tracking_Manager' ) ) {
                Dokan_DHL_Tracking_Manager::boot();
            }
        }

        /**
         * Initialise WordPress hooks.
         */
        protected function init_hooks() {
            add_action( 'init', array( $this, 'load_textdomain' ) );
            add_action( 'init', array( $this, 'maybe_register_rest_controller' ), 20 );
            add_action( 'init', array( $this, 'init_vendor_components' ), 30 );
            add_action( 'init', array( $this, 'init_admin_components' ), 40 );
        }

        /**
         * Load plugin translations.
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'dokan-dhl-per-vendor', false, dirname( plugin_basename( DOKAN_DHL_PER_VENDOR_FILE ) ) . '/languages' );
        }

        /**
         * Register REST controller if dependencies exist.
         */
        public function maybe_register_rest_controller() {
            if ( ! class_exists( 'Dokan_DHL_REST_Controller' ) ) {
                return;
            }

            $controller = new Dokan_DHL_REST_Controller();
            add_action( 'rest_api_init', array( $controller, 'register_routes' ) );
        }

        /**
         * Initialise vendor UI components when Dokan is active.
         */
        public function init_vendor_components() {
            if ( ! function_exists( 'dokan' ) ) {
                return;
            }

            Dokan_DHL_Vendor_Settings_Screen::boot();
            Dokan_DHL_Vendor_Order_Actions::boot();
        }

        /**
         * Initialise admin UI components.
         */
        public function init_admin_components() {
            if ( ! is_admin() ) {
                return;
            }

            if ( class_exists( 'Dokan_DHL_Admin_Order_Metabox' ) ) {
                Dokan_DHL_Admin_Order_Metabox::boot();
            }

            if ( class_exists( 'Dokan_DHL_Admin_Order_Columns' ) ) {
                Dokan_DHL_Admin_Order_Columns::boot();
            }

            if ( class_exists( 'Dokan_DHL_Admin_Settings' ) ) {
                Dokan_DHL_Admin_Settings::boot();
            }
        }

        /**
         * Plugin activation handler.
         */
        public static function activate() {
            self::instance();

            if ( class_exists( 'Dokan_DHL_Tracking_Manager' ) ) {
                Dokan_DHL_Tracking_Manager::activate();
            }
        }

        /**
         * Plugin deactivation handler.
         */
        public static function deactivate() {
            if ( class_exists( 'Dokan_DHL_Tracking_Manager' ) ) {
                Dokan_DHL_Tracking_Manager::deactivate();
            }
        }
    }
}
