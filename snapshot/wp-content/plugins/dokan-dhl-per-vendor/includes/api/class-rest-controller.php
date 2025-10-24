<?php
/**
 * REST API controller.
 *
 * @package Dokan_DHL_Per_Vendor\API
 */

if ( ! class_exists( 'Dokan_DHL_REST_Controller' ) ) {

    class Dokan_DHL_REST_Controller extends WP_REST_Controller {

        /**
         * Namespace for routes.
         *
         * @var string
         */
        protected $namespace = 'dokan-dhl/v1';

        /**
         * Order store instance.
         *
         * @var Dokan_DHL_Order_Store
         */
        protected $order_store;

        /**
         * Credentials store instance.
         *
         * @var Dokan_DHL_Credentials_Store
         */
        protected $credentials_store;

        /**
         * DHL client instance.
         *
         * @var Dokan_DHL_Client
         */
        protected $client;

        /**
         * Constructor.
         *
         * @param Dokan_DHL_Order_Store       $order_store       Order store instance.
         * @param Dokan_DHL_Credentials_Store $credentials_store Credentials store instance.
         * @param Dokan_DHL_Client            $client            DHL client instance.
         */
        public function __construct( ?Dokan_DHL_Order_Store $order_store = null, ?Dokan_DHL_Credentials_Store $credentials_store = null, ?Dokan_DHL_Client $client = null ) {
            $this->order_store       = $order_store ? $order_store : new Dokan_DHL_Order_Store();
            $this->credentials_store = $credentials_store ? $credentials_store : new Dokan_DHL_Credentials_Store();
            $this->client            = $client ? $client : new Dokan_DHL_Client( $this->credentials_store );
        }

        /**
         * Register routes.
         */
        public function register_routes() {
            register_rest_route(
                $this->namespace,
                '/orders/(?P<id>\d+)/label',
                array(
                    array(
                        'methods'             => WP_REST_Server::CREATABLE,
                        'callback'            => array( $this, 'create_label' ),
                        'permission_callback' => array( $this, 'check_permissions' ),
                    ),
                    array(
                        'methods'             => WP_REST_Server::READABLE,
                        'callback'            => array( $this, 'download_label' ),
                        'permission_callback' => array( $this, 'check_permissions' ),
                    ),
                )
            );

            register_rest_route(
                $this->namespace,
                '/orders/(?P<id>\d+)/tracking',
                array(
                    array(
                        'methods'             => WP_REST_Server::READABLE,
                        'callback'            => array( $this, 'get_tracking' ),
                        'permission_callback' => array( $this, 'check_permissions' ),
                    ),
                )
            );

            register_rest_route(
                $this->namespace,
                '/webhook',
                array(
                    array(
                        'methods'             => WP_REST_Server::CREATABLE,
                        'callback'            => array( $this, 'handle_webhook' ),
                        'permission_callback' => array( $this, 'check_webhook_permissions' ),
                    ),
                )
            );
        }

        /**
         * Handle label creation.
         *
         * @param WP_REST_Request $request Request instance.
         *
         * @return WP_REST_Response|WP_Error
         */
        public function create_label( WP_REST_Request $request ) {
            $order_id = absint( $request['id'] );
            $order    = $this->get_order( $order_id );

            if ( is_wp_error( $order ) ) {
                return $order;
            }

            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( ! $vendor_id ) {
                return new WP_Error( 'dokan_dhl_missing_vendor', __( 'Unable to determine the vendor for this order.', 'dokan-dhl-per-vendor' ), array( 'status' => 400 ) );
            }

            $credentials = $this->credentials_store->get( $vendor_id );

            $package_defaults = array(
                'weight' => $this->sanitize_decimal_value( isset( $credentials['default_weight'] ) ? $credentials['default_weight'] : '', 3 ),
                'length' => $this->sanitize_decimal_value( isset( $credentials['default_length'] ) ? $credentials['default_length'] : '', 1 ),
                'width'  => $this->sanitize_decimal_value( isset( $credentials['default_width'] ) ? $credentials['default_width'] : '', 1 ),
                'height' => $this->sanitize_decimal_value( isset( $credentials['default_height'] ) ? $credentials['default_height'] : '', 1 ),
            );

            $package = array(
                'weight' => $this->sanitize_decimal_value( $request->get_param( 'weight' ), 3 ),
                'length' => $this->sanitize_decimal_value( $request->get_param( 'length' ), 1 ),
                'width'  => $this->sanitize_decimal_value( $request->get_param( 'width' ), 1 ),
                'height' => $this->sanitize_decimal_value( $request->get_param( 'height' ), 1 ),
            );

            foreach ( $package as $key => $value ) {
                if ( '' === $value && isset( $package_defaults[ $key ] ) ) {
                    $package[ $key ] = $package_defaults[ $key ];
                }
            }

            $package_validation = $this->validate_package_values( $package );

            if ( is_wp_error( $package_validation ) ) {
                return $package_validation;
            }

            $shipping_validation = $this->validate_order_shipping_details( $order );

            if ( is_wp_error( $shipping_validation ) ) {
                return $shipping_validation;
            }

            $shipment = $this->client->create_shipment( $vendor_id, $order, $package );

            if ( is_wp_error( $shipment ) ) {
                return $shipment;
            }

            $saved = $this->order_store->save_shipment( $order_id, $vendor_id, $shipment );

            if ( is_wp_error( $saved ) ) {
                return $saved;
            }

            $rest_nonce  = wp_create_nonce( 'wp_rest' );
            $label_route = untrailingslashit( rest_url( $this->namespace . '/orders/' . $order_id ) ) . '/label';

            $response = array(
                'order_id'        => $order_id,
                'awb'             => isset( $saved['awb'] ) ? $saved['awb'] : '',
                'download_url'    => add_query_arg( '_wpnonce', $rest_nonce, $label_route ),
                'tracking_status' => isset( $saved['tracking_status'] ) ? $saved['tracking_status'] : '',
                'tracking_events' => isset( $saved['tracking_events'] ) ? $saved['tracking_events'] : array(),
                'package'         => isset( $saved['package'] ) ? $saved['package'] : $package,
                'rest_nonce'      => $rest_nonce,
                'message'         => __( 'DHL label created successfully.', 'dokan-dhl-per-vendor' ),
            );

            return rest_ensure_response( $response );
        }

        /**
         * Download the stored label PDF.
         *
         * @param WP_REST_Request $request Request instance.
         *
         * @return void|WP_Error
         */
        public function download_label( WP_REST_Request $request ) {
            $order_id = absint( $request['id'] );
            $order    = $this->get_order( $order_id );

            if ( is_wp_error( $order ) ) {
                return $order;
            }

            $label_path = $this->order_store->get_label_path( $order_id );

            if ( '' === $label_path || ! file_exists( $label_path ) || ! is_readable( $label_path ) ) {
                return new WP_Error( 'dokan_dhl_label_missing', __( 'The DHL label could not be found.', 'dokan-dhl-per-vendor' ), array( 'status' => 404 ) );
            }

            $filename = basename( $label_path );
            $filetype = wp_check_filetype( $filename );
            $mime     = $filetype['type'] ? $filetype['type'] : 'application/pdf';
            $length   = filesize( $label_path );

            nocache_headers();
            status_header( 200 );

            header( 'Content-Type: ' . $mime );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

            if ( false !== $length ) {
                header( 'Content-Length: ' . $length );
            }

            while ( ob_get_level() ) {
                ob_end_clean();
            }

            readfile( $label_path );
            exit;
        }

        /**
         * Retrieve tracking information.
         *
         * @param WP_REST_Request $request Request instance.
         *
         * @return WP_REST_Response|WP_Error
         */
        public function get_tracking( WP_REST_Request $request ) {
            $order_id = absint( $request['id'] );
            $order    = $this->get_order( $order_id );

            if ( is_wp_error( $order ) ) {
                return $order;
            }

            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( ! $vendor_id ) {
                return new WP_Error( 'dokan_dhl_missing_vendor', __( 'Unable to determine the vendor for this order.', 'dokan-dhl-per-vendor' ), array( 'status' => 400 ) );
            }

            $stored_data = $this->order_store->get_order_data( $order_id );
            $awb         = isset( $stored_data['awb'] ) ? $stored_data['awb'] : '';

            if ( '' === $awb ) {
                return new WP_Error( 'dokan_dhl_missing_awb', __( 'No DHL airway bill found for this order.', 'dokan-dhl-per-vendor' ), array( 'status' => 400 ) );
            }

            $tracking = $this->client->get_tracking( $vendor_id, $awb );

            if ( is_wp_error( $tracking ) ) {
                return $tracking;
            }

            $updated = $this->order_store->save_tracking( $order_id, $tracking );

            $rest_nonce = wp_create_nonce( 'wp_rest' );

            $label_route = untrailingslashit( rest_url( $this->namespace . '/orders/' . $order_id ) ) . '/label';

            $response = array(
                'order_id'        => $order_id,
                'awb'             => $awb,
                'tracking_status' => isset( $updated['tracking_status'] ) ? $updated['tracking_status'] : '',
                'tracking_events' => isset( $updated['tracking_events'] ) ? $updated['tracking_events'] : array(),
                'package'         => isset( $updated['package'] ) ? $updated['package'] : array(),
                'rest_nonce'      => $rest_nonce,
                'download_url'    => add_query_arg( '_wpnonce', $rest_nonce, $label_route ),
                'message'         => __( 'Tracking information retrieved from DHL.', 'dokan-dhl-per-vendor' ),
            );

            return rest_ensure_response( $response );
        }

        /**
         * Handle webhook notifications from DHL.
         *
         * @param WP_REST_Request $request Request instance.
         *
         * @return WP_REST_Response|WP_Error
         */
        public function handle_webhook( WP_REST_Request $request ) {
            $payload = $request->get_json_params();

            if ( ! is_array( $payload ) ) {
                $payload = array();
            }

            $awb = isset( $payload['awb'] ) ? sanitize_text_field( (string) $payload['awb'] ) : '';

            if ( '' === $awb ) {
                return new WP_Error( 'dokan_dhl_webhook_missing_awb', __( 'Webhook payload is missing an airway bill.', 'dokan-dhl-per-vendor' ), array( 'status' => 400 ) );
            }

            $status = isset( $payload['status'] ) ? sanitize_text_field( (string) $payload['status'] ) : '';
            $events = isset( $payload['events'] ) && is_array( $payload['events'] ) ? $payload['events'] : array();

            $order_ids = $this->order_store->get_order_ids_by_awb( $awb );

            if ( empty( $order_ids ) ) {
                return new WP_Error( 'dokan_dhl_webhook_order_not_found', __( 'No orders were found for the provided airway bill.', 'dokan-dhl-per-vendor' ), array( 'status' => 404 ) );
            }

            $tracking_payload = array(
                'status' => $status,
                'events' => $events,
                'meta'   => array(
                    'source'      => 'webhook',
                    'received_at' => current_time( 'mysql', true ),
                ),
            );

            $updated = array();

            foreach ( $order_ids as $order_id ) {
                $saved = $this->order_store->save_tracking(
                    $order_id,
                    $tracking_payload,
                    array( 'awb' => $awb )
                );

                $updated[] = array(
                    'order_id'        => $order_id,
                    'tracking_status' => isset( $saved['tracking_status'] ) ? $saved['tracking_status'] : '',
                    'tracking_events' => isset( $saved['tracking_events'] ) ? $saved['tracking_events'] : array(),
                );
            }

            do_action( 'dokan_dhl_webhook_processed', $awb, $order_ids, $payload );

            $response = array(
                'awb'     => $awb,
                'count'   => count( $updated ),
                'updated' => $updated,
                'message' => __( 'Tracking information updated via webhook.', 'dokan-dhl-per-vendor' ),
            );

            return rest_ensure_response( $response );
        }

        /**
         * Permission callback.
         *
         * @param WP_REST_Request $request Request instance.
         *
         * @return true|WP_Error
         */
        public function check_permissions( WP_REST_Request $request ) {
            $order_id = absint( $request['id'] );

            if ( $order_id <= 0 ) {
                return new WP_Error( 'dokan_dhl_invalid_order', __( 'Invalid order identifier.', 'dokan-dhl-per-vendor' ), array( 'status' => 404 ) );
            }

            if ( ! is_user_logged_in() ) {
                return new WP_Error( 'dokan_dhl_not_logged_in', __( 'You must be logged in to manage DHL shipments.', 'dokan-dhl-per-vendor' ), array( 'status' => rest_authorization_required_code() ) );
            }

            $order = $this->get_order( $order_id );

            if ( is_wp_error( $order ) ) {
                return $order;
            }

            $user_id   = get_current_user_id();
            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( $vendor_id && $vendor_id === $user_id ) {
                return true;
            }

            if ( user_can( $user_id, 'manage_woocommerce' ) || user_can( $user_id, 'manage_options' ) ) {
                return true;
            }

            return new WP_Error( 'dokan_dhl_forbidden', __( 'You are not allowed to access this DHL resource.', 'dokan-dhl-per-vendor' ), array( 'status' => rest_authorization_required_code() ) );
        }

        /**
         * Verify webhook request signature.
         *
         * @param WP_REST_Request $request Request instance.
         *
         * @return true|WP_Error
         */
        public function check_webhook_permissions( WP_REST_Request $request ) {
            $expected = $this->get_webhook_token();

            if ( '' === $expected ) {
                return new WP_Error( 'dokan_dhl_webhook_token_missing', __( 'Webhook token is not configured.', 'dokan-dhl-per-vendor' ), array( 'status' => 403 ) );
            }

            $provided = $request->get_header( 'x-dhl-signature' );
            $provided = is_string( $provided ) ? trim( $provided ) : '';

            if ( '' === $provided ) {
                return new WP_Error( 'dokan_dhl_webhook_signature_missing', __( 'Webhook signature header is missing.', 'dokan-dhl-per-vendor' ), array( 'status' => 403 ) );
            }

            if ( ! $this->safe_compare( $expected, $provided ) ) {
                return new WP_Error( 'dokan_dhl_webhook_signature_invalid', __( 'Invalid webhook signature.', 'dokan-dhl-per-vendor' ), array( 'status' => 403 ) );
            }

            return true;
        }

        /**
         * Retrieve the webhook token from configuration.
         *
         * @return string
         */
        protected function get_webhook_token() {
            if ( defined( 'DOKAN_DHL_WEBHOOK_TOKEN' ) && DOKAN_DHL_WEBHOOK_TOKEN ) {
                return trim( (string) DOKAN_DHL_WEBHOOK_TOKEN );
            }

            $option = get_option( 'dokan_dhl_webhook_token', '' );

            return is_string( $option ) ? trim( $option ) : '';
        }

        /**
         * Time-safe string comparison.
         *
         * @param string $expected Expected token.
         * @param string $provided Provided token.
         *
         * @return bool
         */
        protected function safe_compare( $expected, $provided ) {
            $expected = (string) $expected;
            $provided = (string) $provided;

            if ( function_exists( 'hash_equals' ) ) {
                return hash_equals( $expected, $provided );
            }

            return $expected === $provided;
        }

        /**
         * Retrieve the WooCommerce order instance.
         *
         * @param int $order_id Order ID.
         *
         * @return WC_Order|WP_Error
         */
        protected function get_order( $order_id ) {
            if ( ! function_exists( 'wc_get_order' ) ) {
                return new WP_Error( 'dokan_dhl_missing_woocommerce', __( 'WooCommerce is required to manage DHL shipments.', 'dokan-dhl-per-vendor' ), array( 'status' => 500 ) );
            }

            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                return new WP_Error( 'dokan_dhl_order_not_found', __( 'The requested order could not be found.', 'dokan-dhl-per-vendor' ), array( 'status' => 404 ) );
            }

            return $order;
        }

        /**
         * Get the vendor ID associated with an order.
         *
         * @param int $order_id Order ID.
         *
         * @return int
         */
        protected function get_order_vendor_id( $order_id ) {
            $vendor_id = get_post_meta( $order_id, '_dokan_vendor_id', true );

            $vendor_id = absint( $vendor_id );

            return $vendor_id > 0 ? $vendor_id : 0;
        }

        /**
         * Validate package weight and dimensions before creating the shipment.
         *
         * @param array $package Package data.
         *
         * @return true|WP_Error
         */
        protected function validate_package_values( $package ) {
            $labels = array(
                'weight' => __( 'package weight', 'dokan-dhl-per-vendor' ),
                'length' => __( 'package length', 'dokan-dhl-per-vendor' ),
                'width'  => __( 'package width', 'dokan-dhl-per-vendor' ),
                'height' => __( 'package height', 'dokan-dhl-per-vendor' ),
            );

            foreach ( $labels as $key => $label ) {
                $value = isset( $package[ $key ] ) ? $package[ $key ] : '';

                if ( '' === $value ) {
                    return new WP_Error(
                        'dokan_dhl_missing_package_' . $key,
                        sprintf( __( 'Please provide a %s before creating the shipping label.', 'dokan-dhl-per-vendor' ), $label ),
                        array( 'status' => 400 )
                    );
                }

                if ( ! is_numeric( $value ) || (float) $value <= 0 ) {
                    return new WP_Error(
                        'dokan_dhl_invalid_package_' . $key,
                        sprintf( __( 'The %s must be a positive number.', 'dokan-dhl-per-vendor' ), $label ),
                        array( 'status' => 400 )
                    );
                }
            }

            return true;
        }

        /**
         * Ensure the order has the shipping details required for DHL.
         *
         * @param WC_Order $order Order instance.
         *
         * @return true|WP_Error
         */
        protected function validate_order_shipping_details( WC_Order $order ) {
            $name = trim( $order->get_formatted_shipping_full_name() );

            if ( '' === $name ) {
                return new WP_Error(
                    'dokan_dhl_missing_shipping_name',
                    __( 'The order is missing a shipping name.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $address1 = trim( $order->get_shipping_address_1() );

            if ( '' === $address1 ) {
                return new WP_Error(
                    'dokan_dhl_missing_shipping_address',
                    __( 'The order is missing the primary shipping address.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $city = trim( $order->get_shipping_city() );

            if ( '' === $city ) {
                return new WP_Error(
                    'dokan_dhl_missing_shipping_city',
                    __( 'The order is missing the shipping city.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $country = strtoupper( trim( $order->get_shipping_country() ) );

            if ( '' === $country ) {
                return new WP_Error(
                    'dokan_dhl_missing_shipping_country',
                    __( 'The order is missing the shipping country.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $state          = trim( $order->get_shipping_state() );
            $requires_state = false;

            if ( function_exists( 'WC' ) ) {
                $wc = WC();

                if ( $wc && isset( $wc->countries ) && is_object( $wc->countries ) && method_exists( $wc->countries, 'get_states' ) ) {
                    $states = $wc->countries->get_states( $country );

                    if ( is_array( $states ) && ! empty( $states ) ) {
                        $requires_state = true;
                    }
                }
            }

            if ( $requires_state && '' === $state ) {
                return new WP_Error(
                    'dokan_dhl_missing_shipping_state',
                    __( 'The order is missing the shipping state.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $postcode = trim( $order->get_shipping_postcode() );

            if ( '' === $postcode ) {
                return new WP_Error(
                    'dokan_dhl_missing_shipping_postcode',
                    __( 'The order is missing the shipping postcode.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $phone = trim( $order->get_billing_phone() );

            if ( '' === $phone ) {
                return new WP_Error(
                    'dokan_dhl_missing_phone',
                    __( 'A billing phone number is required for DHL shipments.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            $email = trim( $order->get_billing_email() );

            if ( '' === $email ) {
                return new WP_Error(
                    'dokan_dhl_missing_email',
                    __( 'A billing email address is required for DHL shipments.', 'dokan-dhl-per-vendor' ),
                    array( 'status' => 400 )
                );
            }

            return true;
        }

        /**
         * Sanitize decimal values from REST input.
         *
         * @param mixed $value     Raw value.
         * @param int   $precision Precision.
         *
         * @return string
         */
        protected function sanitize_decimal_value( $value, $precision = 3 ) {
            if ( null === $value || '' === $value ) {
                return '';
            }

            if ( is_string( $value ) ) {
                $value = str_replace( ',', '.', $value );
            }

            if ( ! is_numeric( $value ) ) {
                $value = preg_replace( '/[^0-9.\-]/', '', (string) $value );
            }

            if ( '' === $value ) {
                return '';
            }

            $float = (float) $value;
            $float = round( $float, $precision );

            $formatted = sprintf( '%.' . $precision . 'f', $float );
            $formatted = rtrim( rtrim( $formatted, '0' ), '.' );

            return $formatted;
        }
    }
}
