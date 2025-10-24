<?php
/**
 * Vendor settings integration.
 *
 * @package Dokan_DHL_Per_Vendor\Vendor
 */

if ( ! class_exists( 'Dokan_DHL_Vendor_Settings_Screen' ) ) {

    class Dokan_DHL_Vendor_Settings_Screen {

        const NAV_KEY = 'dhl';

        /**
         * Singleton instance.
         *
         * @var Dokan_DHL_Vendor_Settings_Screen|null
         */
        protected static $instance = null;

        /**
         * Credentials store.
         *
         * @var Dokan_DHL_Credentials_Store
         */
        protected $credentials_store;

        /**
         * Form values for the current request.
         *
         * @var array|null
         */
        protected $submitted_form_data = null;

        /**
         * Whether the vendor requested to clear the stored API secret.
         *
         * @var bool
         */
        protected $clear_secret_requested = false;

        /**
         * DHL API client instance.
         *
         * @var Dokan_DHL_Client
         */
        protected $client;

        /**
         * Cached nonce for the test connection endpoint.
         *
         * @var string
         */
        protected $test_connection_nonce = '';

        /**
         * Whether the test connection script has been localised.
         *
         * @var bool
         */
        protected $test_script_localized = false;

        /**
         * Boot the settings screen singleton.
         *
         * @return Dokan_DHL_Vendor_Settings_Screen
         */
        public static function boot() {
            if ( null === self::$instance ) {
                $credentials_store = new Dokan_DHL_Credentials_Store();
                self::$instance    = new self( $credentials_store, new Dokan_DHL_Client( $credentials_store ) );
            }

            return self::$instance;
        }

        /**
         * Constructor.
         *
         * @param Dokan_DHL_Credentials_Store $credentials_store Credentials repository.
         */
        public function __construct( Dokan_DHL_Credentials_Store $credentials_store, ?Dokan_DHL_Client $client = null ) {
            $this->credentials_store = $credentials_store;
            $this->client            = $client ? $client : new Dokan_DHL_Client( $credentials_store );
            $this->register_hooks();
        }

        /**
         * Register WordPress hooks.
         */
        protected function register_hooks() {
            add_filter( 'dokan_get_dashboard_settings_nav', array( $this, 'register_settings_tab' ) );
            add_action( 'dokan_render_settings_content', array( $this, 'render_settings_content' ), 15 );
            add_action( 'template_redirect', array( $this, 'maybe_handle_form_submission' ) );
            add_action( 'dokan_process_settings_save', array( $this, 'handle_settings_save' ), 10, 2 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'wp_ajax_dokan_dhl_test_connection', array( $this, 'ajax_test_connection' ) );
        }

        /**
         * Add the DHL tab to the vendor settings navigation.
         *
         * @param array $tabs Existing tabs.
         *
         * @return array
         */
        public function register_settings_tab( $tabs ) {
            $vendor_id = $this->get_current_vendor_id();

            if ( ! $vendor_id ) {
                return $tabs;
            }

            $tabs[ self::NAV_KEY ] = array(
                'title'      => __( 'DHL', 'dokan-dhl-per-vendor' ),
                'icon'       => '<i class="fas fa-shipping-fast"></i>',
                'url'        => function_exists( 'dokan_get_navigation_url' ) ? dokan_get_navigation_url( 'settings/' . self::NAV_KEY ) : '',
                'pos'        => 115,
                'permission' => 'dokan_view_store_settings_menu',
            );

            return $tabs;
        }

        /**
         * Render the DHL settings form.
         *
         * @param array $query_vars Current query vars.
         */
        public function render_settings_content( $query_vars ) {
            if ( ! isset( $query_vars['settings'] ) || self::NAV_KEY !== $query_vars['settings'] ) {
                return;
            }

            $vendor_id = $this->get_current_vendor_id();

            if ( ! $vendor_id ) {
                $this->add_notice( __( 'You must be a vendor to access DHL settings.', 'dokan-dhl-per-vendor' ), 'error' );
                return;
            }

            $values = $this->credentials_store->get( $vendor_id );
            $values = wp_parse_args( $values, $this->get_default_values() );

            if ( is_array( $this->submitted_form_data ) ) {
                $values = array_merge( $values, $this->submitted_form_data );
            }

            $field_groups = $this->get_field_groups();
            $clear_secret_checked = $this->clear_secret_requested;
            $test_connection_nonce = $this->get_test_connection_nonce();

            require DOKAN_DHL_PER_VENDOR_PATH . 'templates/settings-form.php';
        }

        /**
         * Enqueue assets required for the DHL settings screen.
         */
        public function enqueue_assets() {
            if ( ! $this->is_settings_screen() ) {
                return;
            }

            if ( ! wp_script_is( 'dokan-dhl-vendor-settings', 'registered' ) ) {
                wp_register_script(
                    'dokan-dhl-vendor-settings',
                    DOKAN_DHL_PER_VENDOR_URL . 'assets/js/vendor-settings.js',
                    array( 'jquery' ),
                    DOKAN_DHL_PER_VENDOR_VERSION,
                    true
                );
            }

            wp_enqueue_script( 'dokan-dhl-vendor-settings' );

            if ( ! $this->test_script_localized ) {
                $data = array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => $this->get_test_connection_nonce(),
                    'i18n'    => array(
                        'testing'    => __( 'Testing connection…', 'dokan-dhl-per-vendor' ),
                        'success'    => __( 'Connection successful.', 'dokan-dhl-per-vendor' ),
                        'error'      => __( 'Unable to verify the DHL connection. Please review your credentials.', 'dokan-dhl-per-vendor' ),
                        'unexpected' => __( 'An unexpected error occurred. Please try again.', 'dokan-dhl-per-vendor' ),
                        'nonceError' => __( 'Your session has expired. Please refresh the page and try again.', 'dokan-dhl-per-vendor' ),
                    ),
                );

                wp_localize_script( 'dokan-dhl-vendor-settings', 'DokanDHLVendorSettings', $data );
                $this->test_script_localized = true;
            }
        }

        /**
         * Maybe handle settings form submission.
         */
        public function maybe_handle_form_submission() {
            $this->clear_secret_requested = false;

            if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore
                return;
            }

            if ( empty( $_POST['dokan_dhl_settings_submit'] ) ) {
                return;
            }

            $vendor_id = $this->get_current_vendor_id();

            if ( ! $vendor_id ) {
                $this->add_notice( __( 'You do not have permission to update DHL settings.', 'dokan-dhl-per-vendor' ), 'error' );
                return;
            }

            $nonce = isset( $_POST['dokan_dhl_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['dokan_dhl_settings_nonce'] ) ) : '';

            $raw_values = $this->get_submitted_values();
            $this->clear_secret_requested = ! empty( $_POST['dokan_dhl_clear_api_secret'] );

            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'dokan_dhl_save_settings' ) ) {
                list( $sanitized_values ) = $this->validate_submission( $raw_values );
                $this->submitted_form_data = $sanitized_values;
                $this->add_notice( __( 'Security check failed. Please try again.', 'dokan-dhl-per-vendor' ), 'error' );
                return;
            }

            list( $data, $errors ) = $this->validate_submission( $raw_values );

            $this->submitted_form_data = $data;

            $has_errors = method_exists( $errors, 'has_errors' ) ? $errors->has_errors() : ! empty( $errors->get_error_codes() );

            if ( $has_errors ) {
                foreach ( (array) $errors->get_error_messages() as $message ) {
                    $this->add_notice( $message, 'error' );
                }

                return;
            }

            $this->submitted_form_data = null;

            do_action( 'dokan_process_settings_save', $vendor_id, $data );
        }

        /**
         * Persist the submitted settings.
         *
         * @param int   $vendor_id Vendor identifier.
         * @param array $data      Sanitized settings data.
         */
        public function handle_settings_save( $vendor_id, $data = array() ) {
            if ( empty( $_POST['dokan_dhl_settings_submit'] ) || empty( $_POST['dokan_dhl_settings_nonce'] ) ) {
                return;
            }

            $nonce = sanitize_text_field( wp_unslash( $_POST['dokan_dhl_settings_nonce'] ) );

            if ( ! wp_verify_nonce( $nonce, 'dokan_dhl_save_settings' ) ) {
                return;
            }

            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 || ! is_array( $data ) ) {
                return;
            }

            $existing_credentials = $this->credentials_store->get( $vendor_id );

            if ( $this->clear_secret_requested ) {
                $data['api_secret'] = '';
            } elseif ( array_key_exists( 'api_secret', $data ) ) {
                $secret = (string) $data['api_secret'];

                if ( '' === trim( $secret ) && isset( $existing_credentials['api_secret'] ) && '' !== $existing_credentials['api_secret'] ) {
                    $data['api_secret'] = $existing_credentials['api_secret'];
                }
            }

            $this->clear_secret_requested = false;

            $result = $this->credentials_store->save( $vendor_id, $data );

            if ( is_wp_error( $result ) ) {
                $this->add_notice( $result->get_error_message(), 'error' );
                return;
            }

            $this->add_notice( __( 'DHL settings saved successfully.', 'dokan-dhl-per-vendor' ), 'success' );
        }

        /**
         * Handle AJAX requests to test the DHL connection.
         */
        public function ajax_test_connection() {
            if ( ! check_ajax_referer( 'dokan_dhl_test_connection', 'nonce', false ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Security check failed. Please refresh the page and try again.', 'dokan-dhl-per-vendor' ),
                    ),
                    403
                );
            }

            $requested_vendor = isset( $_POST['vendor_id'] ) ? absint( wp_unslash( $_POST['vendor_id'] ) ) : 0;
            $current_vendor   = $this->get_current_vendor_id();
            $user_id          = get_current_user_id();

            if ( $current_vendor ) {
                if ( $requested_vendor && $requested_vendor !== $current_vendor ) {
                    wp_send_json_error(
                        array(
                            'message' => __( 'You are not allowed to test another vendor’s connection.', 'dokan-dhl-per-vendor' ),
                        ),
                        403
                    );
                }

                $requested_vendor = $current_vendor;
            } elseif ( $requested_vendor <= 0 || ( $user_id && ! user_can( $user_id, 'manage_woocommerce' ) && ! user_can( $user_id, 'manage_options' ) ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'You do not have permission to test this connection.', 'dokan-dhl-per-vendor' ),
                    ),
                    403
                );
            }

            if ( $requested_vendor <= 0 ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Unable to determine which vendor to test.', 'dokan-dhl-per-vendor' ),
                    ),
                    400
                );
            }

            $result = $this->client->test_connection( $requested_vendor );

            if ( is_wp_error( $result ) ) {
                $data = $result->get_error_data();
                $code = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;

                wp_send_json_error(
                    array(
                        'message' => wp_strip_all_tags( $result->get_error_message() ),
                        'code'    => $result->get_error_code(),
                    ),
                    $code
                );
            }

            wp_send_json_success(
                array(
                    'message' => __( 'Connection successful.', 'dokan-dhl-per-vendor' ),
                )
            );
        }

        /**
         * Fetch submitted values from the request.
         *
         * @return array
         */
        protected function get_submitted_values() {
            $submitted = isset( $_POST['dokan_dhl'] ) ? wp_unslash( $_POST['dokan_dhl'] ) : array();

            if ( ! is_array( $submitted ) ) {
                return array();
            }

            return $submitted;
        }

        /**
         * Validate and sanitize submitted values.
         *
         * @param array $values Raw submitted values.
         *
         * @return array{0:array,1:WP_Error}
         */
        protected function validate_submission( $values ) {
            $defaults = $this->get_default_values();
            $data     = array();
            $errors   = new WP_Error();

            foreach ( $defaults as $key => $default ) {
                $raw_value = isset( $values[ $key ] ) ? $values[ $key ] : '';
                $raw_value = is_array( $raw_value ) ? '' : (string) $raw_value;

                switch ( $key ) {
                    case 'email':
                        $sanitized = sanitize_email( $raw_value );
                        if ( '' !== trim( $raw_value ) && ! is_email( $sanitized ) ) {
                            $errors->add( 'dokan_dhl_invalid_email', __( 'Please provide a valid email address.', 'dokan-dhl-per-vendor' ) );
                            $sanitized = '';
                        }
                        break;
                    case 'country':
                        $sanitized = strtoupper( sanitize_text_field( $raw_value ) );
                        if ( '' !== $sanitized && 2 !== strlen( $sanitized ) ) {
                            $errors->add( 'dokan_dhl_invalid_country', __( 'Country must use the 2-letter ISO format.', 'dokan-dhl-per-vendor' ) );
                            $sanitized = '';
                        }
                        break;
                    case 'default_weight':
                    case 'default_length':
                    case 'default_width':
                    case 'default_height':
                        $normalized = sanitize_text_field( $raw_value );
                        $normalized = str_replace( ',', '.', $normalized );
                        if ( '' !== $normalized && ! is_numeric( $normalized ) ) {
                            $errors->add( 'dokan_dhl_invalid_dimension', __( 'Please enter numeric values for package defaults.', 'dokan-dhl-per-vendor' ) );
                            $normalized = '';
                        }
                        $sanitized = $normalized;
                        break;
                    case 'service':
                    case 'incoterm':
                        $sanitized = strtoupper( sanitize_text_field( $raw_value ) );
                        if ( 'incoterm' === $key && '' !== $sanitized && strlen( $sanitized ) > 3 ) {
                            $errors->add( 'dokan_dhl_invalid_incoterm', __( 'Incoterm must be three characters or less.', 'dokan-dhl-per-vendor' ) );
                            $sanitized = '';
                        }
                        break;
                    default:
                        $sanitized = sanitize_text_field( $raw_value );
                        break;
                }

                $data[ $key ] = $sanitized;
            }

            return array( $data, $errors );
        }

        /**
         * Default values for the settings form.
         *
         * @return array
         */
        protected function get_default_values() {
            return array(
                'api_key'        => '',
                'api_secret'     => '',
                'account'        => '',
                'shipper_name'   => '',
                'company'        => '',
                'phone'          => '',
                'email'          => '',
                'address1'       => '',
                'address2'       => '',
                'city'           => '',
                'state'          => '',
                'postcode'       => '',
                'country'        => '',
                'default_weight' => '',
                'default_length' => '',
                'default_width'  => '',
                'default_height' => '',
                'service'        => '',
                'incoterm'       => '',
            );
        }

        /**
         * Retrieve the settings form definition.
         *
         * @return array
         */
        protected function get_field_groups() {
            return array(
                'credentials' => array(
                    'title'  => __( 'API Credentials', 'dokan-dhl-per-vendor' ),
                    'fields' => array(
                        'api_key'    => array(
                            'label'       => __( 'API Key', 'dokan-dhl-per-vendor' ),
                            'type'        => 'text',
                            'autocomplete'=> 'off',
                        ),
                        'api_secret' => array(
                            'label'       => __( 'API Secret', 'dokan-dhl-per-vendor' ),
                            'type'        => 'password',
                            'autocomplete'=> 'new-password',
                            'placeholder' => '********',
                            'description' => __( 'Leave blank to keep the previously saved secret.', 'dokan-dhl-per-vendor' ),
                        ),
                        'account'    => array(
                            'label'       => __( 'Account Number', 'dokan-dhl-per-vendor' ),
                            'type'        => 'text',
                        ),
                    ),
                ),
                'shipper'     => array(
                    'title'  => __( 'Shipper Information', 'dokan-dhl-per-vendor' ),
                    'fields' => array(
                        'shipper_name' => array(
                            'label' => __( 'Contact Name', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'company'      => array(
                            'label' => __( 'Company', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'phone'        => array(
                            'label' => __( 'Phone', 'dokan-dhl-per-vendor' ),
                            'type'  => 'tel',
                            'placeholder' => '+521234567890',
                        ),
                        'email'        => array(
                            'label' => __( 'Email', 'dokan-dhl-per-vendor' ),
                            'type'  => 'email',
                        ),
                        'address1'     => array(
                            'label' => __( 'Address Line 1', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'address2'     => array(
                            'label' => __( 'Address Line 2', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'city'         => array(
                            'label' => __( 'City', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'state'        => array(
                            'label' => __( 'State / Province', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'postcode'     => array(
                            'label' => __( 'Postcode', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                        ),
                        'country'      => array(
                            'label'       => __( 'Country (ISO 2)', 'dokan-dhl-per-vendor' ),
                            'type'        => 'text',
                            'maxlength'   => 2,
                            'autocomplete'=> 'country',
                            'placeholder' => 'MX',
                        ),
                    ),
                ),
                'defaults'    => array(
                    'title'  => __( 'Default Package', 'dokan-dhl-per-vendor' ),
                    'fields' => array(
                        'default_weight' => array(
                            'label' => __( 'Weight (kg)', 'dokan-dhl-per-vendor' ),
                            'type'  => 'number',
                            'step'  => '0.001',
                            'min'   => '0',
                        ),
                        'default_length' => array(
                            'label' => __( 'Length (cm)', 'dokan-dhl-per-vendor' ),
                            'type'  => 'number',
                            'step'  => '0.1',
                            'min'   => '0',
                        ),
                        'default_width'  => array(
                            'label' => __( 'Width (cm)', 'dokan-dhl-per-vendor' ),
                            'type'  => 'number',
                            'step'  => '0.1',
                            'min'   => '0',
                        ),
                        'default_height' => array(
                            'label' => __( 'Height (cm)', 'dokan-dhl-per-vendor' ),
                            'type'  => 'number',
                            'step'  => '0.1',
                            'min'   => '0',
                        ),
                    ),
                ),
                'options'     => array(
                    'title'  => __( 'Shipping Preferences', 'dokan-dhl-per-vendor' ),
                    'fields' => array(
                        'service'  => array(
                            'label' => __( 'Preferred Service', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                            'description' => __( 'Example: P for Express Worldwide (doc/non-doc).', 'dokan-dhl-per-vendor' ),
                        ),
                        'incoterm' => array(
                            'label' => __( 'Incoterm', 'dokan-dhl-per-vendor' ),
                            'type'  => 'text',
                            'maxlength' => 3,
                            'placeholder' => 'DAP',
                        ),
                    ),
                ),
            );
        }

        /**
         * Retrieve the current vendor ID.
         *
         * @return int
         */
        protected function get_current_vendor_id() {
            if ( function_exists( 'dokan_get_current_user_id' ) ) {
                $vendor_id = dokan_get_current_user_id();
            } else {
                $vendor_id = get_current_user_id();
            }

            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 ) {
                return 0;
            }

            if ( function_exists( 'dokan_is_user_seller' ) && ! dokan_is_user_seller( $vendor_id ) ) {
                return 0;
            }

            return $vendor_id;
        }

        /**
         * Retrieve the nonce used for AJAX test connection requests.
         *
         * @return string
         */
        protected function get_test_connection_nonce() {
            if ( '' === $this->test_connection_nonce ) {
                $this->test_connection_nonce = wp_create_nonce( 'dokan_dhl_test_connection' );
            }

            return $this->test_connection_nonce;
        }

        /**
         * Determine whether the current screen is the DHL settings page.
         *
         * @return bool
         */
        protected function is_settings_screen() {
            if ( function_exists( 'dokan_is_seller_dashboard' ) && ! dokan_is_seller_dashboard() ) {
                return false;
            }

            global $wp;

            if ( isset( $wp->query_vars['settings'] ) && self::NAV_KEY === $wp->query_vars['settings'] ) {
                return true;
            }

            $setting = isset( $_GET['settings'] ) ? sanitize_key( wp_unslash( $_GET['settings'] ) ) : ''; // phpcs:ignore

            return self::NAV_KEY === $setting;
        }

        /**
         * Add a notice in the dashboard if possible.
         *
         * @param string $message Notice message.
         * @param string $type    Notice type (success|error|warning|info).
         */
        protected function add_notice( $message, $type = 'success' ) {
            if ( function_exists( 'dokan_add_notice' ) ) {
                dokan_add_notice( $message, $type );
                return;
            }

            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( $message, $type );
                return;
            }
        }
    }
}
