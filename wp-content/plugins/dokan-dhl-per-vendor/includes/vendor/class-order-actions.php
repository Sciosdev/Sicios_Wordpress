<?php
/**
 * Placeholder for vendor order actions.

 * Vendor order actions (UI placeholders, no API calls yet).
 *
 * @package Dokan_DHL_Per_Vendor\Vendor
 */

if ( ! class_exists( 'Dokan_DHL_Vendor_Order_Actions' ) ) {

    class Dokan_DHL_Vendor_Order_Actions {

        const ACTION_CREATE_LABEL   = 'dokan_dhl_create_label';
        const ACTION_DOWNLOAD_LABEL = 'dokan_dhl_download_label';
        const BULK_ACTION_CREATE    = 'dokan_dhl_bulk_create_label';
        const NOTICE_TRANSIENT_BASE = 'dokan_dhl_notice_';

        /**
         * Singleton instance.
         *
         * @var Dokan_DHL_Vendor_Order_Actions|null
         */
        protected static $instance = null;

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
         * Flag to avoid localising the script multiple times.
         *
         * @var bool
         */
        protected $script_localized = false;

        /**
         * REST nonce shared with front-end interactions.
         *
         * @var string
         */
        protected $rest_nonce = '';

        /**

         * Bootstraps the order actions integration.
         *
         * @return Dokan_DHL_Vendor_Order_Actions
         */
        public static function boot() {
            if ( null === self::$instance ) {
                self::$instance = new self( new Dokan_DHL_Order_Store(), new Dokan_DHL_Credentials_Store() );
            }

            return self::$instance;
        }

        /**
         * Constructor.
         *
         * @param Dokan_DHL_Order_Store       $order_store       Order store.
         * @param Dokan_DHL_Credentials_Store $credentials_store Credentials store.
         */
        public function __construct( Dokan_DHL_Order_Store $order_store, Dokan_DHL_Credentials_Store $credentials_store ) {
            $this->order_store       = $order_store;
            $this->credentials_store = $credentials_store;

            $this->register_hooks();
        }

        /**
         * Register WordPress hooks.
         */
        protected function register_hooks() {
            add_action( 'dokan_order_details_after_order_items', array( $this, 'render_order_panel' ), 20, 1 );
            add_action( 'dokan_order_detail_actions', array( $this, 'render_dokan_order_detail_actions' ), 10, 1 );
            add_filter( 'dokan_order_item_action_triggers', array( $this, 'register_dokan_order_action_trigger' ) );
            add_filter( 'dokan_bulk_actions', array( $this, 'register_dokan_bulk_action' ) );
            add_filter( 'dokan_bulk_order_statuses', array( $this, 'register_dokan_bulk_action' ) );
            add_filter( 'woocommerce_admin_order_actions', array( $this, 'register_wc_order_actions' ), 20, 2 );
            add_filter( 'woocommerce_order_actions', array( $this, 'register_wc_order_dropdown_actions' ), 20, 2 );
            add_action( 'woocommerce_order_action_' . self::ACTION_CREATE_LABEL, array( $this, 'handle_wc_order_action_create_label' ) );
            add_action( 'woocommerce_order_action_' . self::ACTION_DOWNLOAD_LABEL, array( $this, 'handle_wc_order_action_download_label' ) );
            add_action( 'template_redirect', array( $this, 'maybe_handle_vendor_bulk_action' ), 9 );
            add_action( 'admin_post_' . self::ACTION_CREATE_LABEL, array( $this, 'handle_admin_post_create_label' ) );
            add_action( 'admin_post_' . self::BULK_ACTION_CREATE, array( $this, 'handle_admin_post_bulk_create_label' ) );
            add_action( 'dokan_dashboard_content_inside_before', array( $this, 'render_vendor_flash_notices' ), 5 );
            add_action( 'admin_notices', array( $this, 'render_admin_flash_notices' ) );
        }

        /**
         * Render the DHL panel inside the Dokan order details screen.
         *
         * @param mixed $order Order object or ID.
         */
        public function render_order_panel( $order ) {
            $order_id = $this->get_order_id( $order );

            if ( ! $order_id ) {
                return;
            }

            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( ! $vendor_id ) {
                return;
            }

            if ( ! $this->current_user_can_manage_order( $vendor_id ) ) {
                return;
            }

            $shipment_data = $this->order_store->get_order_data( $order_id );

            if ( ! is_array( $shipment_data ) ) {
                $shipment_data = array();
            }

            $has_label = ! empty( $shipment_data['awb'] );

            $defaults = $this->credentials_store->get( $vendor_id );

            if ( ! is_array( $defaults ) ) {
                $defaults = array();
            }

            $package_defaults = $this->prepare_package_defaults( $shipment_data, $defaults );

            $this->enqueue_assets();

            $rest_base   = untrailingslashit( rest_url( 'dokan-dhl/v1/orders/' . $order_id ) );
            $rest_nonce  = $this->rest_nonce ? $this->rest_nonce : wp_create_nonce( 'wp_rest' );
            $download_url = '';

            if ( $has_label ) {
                $download_url = add_query_arg( '_wpnonce', $rest_nonce, $rest_base . '/label' );
            }


            $template_data = array(
                'order_id'         => $order_id,
                'vendor_id'        => $vendor_id,
                'has_label'        => $has_label,
                'shipment_data'    => $shipment_data,
                'package_defaults' => $package_defaults,
                'download_url'     => $download_url,
                'rest_nonce'       => $rest_nonce,

            );

            $this->load_template( 'order-panel.php', $template_data );
        }

        /**
         * Register Dokan order triggers with our label action.
         *
         * @param array $triggers Registered triggers.
         *
         * @return array
         */
        public function register_dokan_order_action_trigger( $triggers ) {
            if ( ! is_array( $triggers ) ) {
                $triggers = array();
            }

            $triggers[ self::ACTION_CREATE_LABEL ] = __( 'Crear etiqueta DHL', 'dokan-dhl-per-vendor' );

            return $triggers;
        }

        /**
         * Register the Dokan bulk action entry.
         *
         * @param array $actions Bulk actions.
         *
         * @return array
         */
        public function register_dokan_bulk_action( $actions ) {
            if ( ! is_array( $actions ) ) {
                $actions = array();
            }

            $actions[ self::BULK_ACTION_CREATE ] = __( 'Crear etiqueta DHL', 'dokan-dhl-per-vendor' );

            return $actions;
        }

        /**
         * Register WooCommerce order list/table actions.
         *
         * @param array    $actions Existing actions.
         * @param WC_Order $order   Current order.
         *
         * @return array
         */
        public function register_wc_order_actions( $actions, $order ) {
            $order_id = $this->get_order_id( $order );

            if ( ! $order_id || ! $this->is_vendor_order( $order_id ) ) {
                return $actions;
            }

            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( ! $this->current_user_can_manage_order( $vendor_id ) ) {
                return $actions;
            }

            if ( $this->order_has_label( $order_id ) ) {
                $actions[ self::ACTION_DOWNLOAD_LABEL ] = array(
                    'url'    => $this->get_label_download_url( $order_id ),
                    'name'   => __( 'Descargar etiqueta DHL', 'dokan-dhl-per-vendor' ),
                    'action' => 'dokan-dhl-download-label',
                    'icon'   => '<span class="dashicons dashicons-download"></span>',
                );
            } else {
                $actions[ self::ACTION_CREATE_LABEL ] = array(
                    'url'    => $this->get_label_creation_url( $order_id ),
                    'name'   => __( 'Crear etiqueta DHL', 'dokan-dhl-per-vendor' ),
                    'action' => 'dokan-dhl-create-label',
                    'icon'   => '<span class="dashicons dashicons-media-default"></span>',
                );
            }

            return $actions;
        }

        /**
         * Register WooCommerce single order dropdown actions.
         *
         * @param array    $actions Current dropdown options.
         * @param WC_Order $order   Order instance.
         *
         * @return array
         */
        public function register_wc_order_dropdown_actions( $actions, $order ) {
            $order_id = $this->get_order_id( $order );

            if ( ! $order_id || ! $this->is_vendor_order( $order_id ) ) {
                return $actions;
            }

            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( ! $this->current_user_can_manage_order( $vendor_id ) ) {
                return $actions;
            }

            if ( $this->order_has_label( $order_id ) ) {
                $actions[ self::ACTION_DOWNLOAD_LABEL ] = __( 'Descargar etiqueta DHL', 'dokan-dhl-per-vendor' );
            } else {
                $actions[ self::ACTION_CREATE_LABEL ] = __( 'Crear etiqueta DHL', 'dokan-dhl-per-vendor' );
            }

            return $actions;
        }

        /**
         * Handle WooCommerce order action for creating the label.
         *
         * @param WC_Order $order Order instance.
         */
        public function handle_wc_order_action_create_label( $order ) {
            $order_id = $this->get_order_id( $order );

            if ( ! $order_id ) {
                return;
            }

            $results = $this->process_label_requests( array( $order_id ), 'admin' );
            $this->store_flash_messages( $results, 'admin' );
        }

        /**
         * Handle WooCommerce order action for downloading the label.
         *
         * @param WC_Order $order Order instance.
         */
        public function handle_wc_order_action_download_label( $order ) {
            $order_id = $this->get_order_id( $order );

            if ( ! $order_id ) {
                return;
            }

            $download = $this->get_label_download_url( $order_id );

            if ( ! $download ) {
                $results = array(
                    'created' => array(),
                    'errors'  => array(
                        $order_id => new WP_Error( 'dokan_dhl_missing_label', __( 'No se encontró ninguna etiqueta DHL para este pedido.', 'dokan-dhl-per-vendor' ) ),
                    ),
                    'skipped' => array(),
                );

                $this->store_flash_messages( $results, 'admin' );
                return;
            }

            $message = sprintf(
                /* translators: 1: order number, 2: download url */
                __( 'Etiqueta DHL lista para el pedido #%1$s. <a href="%2$s" target="_blank" rel="noopener noreferrer">Descargar etiqueta</a>.', 'dokan-dhl-per-vendor' ),
                esc_html( $this->get_order_number( $order_id ) ),
                esc_url( $download )
            );

            $this->store_manual_message( $message, 'success', 'admin' );
        }

        /**
         * Handle vendor bulk submission for creating labels.
         */
        public function maybe_handle_vendor_bulk_action() {
            if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                return;
            }

            if ( ! isset( $_POST['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                return;
            }

            $action = sanitize_text_field( wp_unslash( $_POST['status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ( self::BULK_ACTION_CREATE !== $action ) {
                return;
            }

            if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['security'] ) ), 'bulk_order_status_change' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                return;
            }

            $order_ids = isset( $_POST['bulk_orders'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['bulk_orders'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ( empty( $order_ids ) ) {
                return;
            }

            $results = $this->process_label_requests( $order_ids, 'vendor' );
            $this->store_flash_messages( $results, 'vendor' );

            // Prevent Dokan from attempting to treat the action as a status change.
            $_POST['status'] = '-1'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

            $redirect = wp_get_referer();

            if ( ! $redirect && function_exists( 'dokan_get_navigation_url' ) ) {
                $redirect = dokan_get_navigation_url( 'orders' );
            }

            if ( ! $redirect ) {
                $redirect = home_url();
            }

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Handle admin-post single label creation.
         */
        public function handle_admin_post_create_label() {
            $order_id = isset( $_REQUEST['order_id'] ) ? absint( wp_unslash( $_REQUEST['order_id'] ) ) : 0;

            if ( $order_id <= 0 ) {
                wp_safe_redirect( $this->get_redirect_url_from_request() );
                exit;
            }

            check_admin_referer( 'dokan_dhl_label_' . $order_id );

            $context  = isset( $_REQUEST['context'] ) ? sanitize_key( wp_unslash( $_REQUEST['context'] ) ) : 'vendor';
            $results  = $this->process_label_requests( array( $order_id ), $context );
            $this->store_flash_messages( $results, $context );
            $redirect = $this->get_redirect_url_from_request( $context );

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Handle admin-post bulk label creation.
         */
        public function handle_admin_post_bulk_create_label() {
            $order_ids = isset( $_REQUEST['order_ids'] ) ? wp_unslash( $_REQUEST['order_ids'] ) : array();

            if ( is_string( $order_ids ) ) {
                $order_ids = explode( ',', $order_ids );
            }

            $order_ids = array_map( 'absint', (array) $order_ids );
            $order_ids = array_filter( $order_ids );

            $context = isset( $_REQUEST['context'] ) ? sanitize_key( wp_unslash( $_REQUEST['context'] ) ) : 'admin';

            if ( empty( $order_ids ) ) {
                $this->store_flash_messages(
                    array(
                        'created' => array(),
                        'errors'  => array(),
                        'skipped' => array(),
                    ),
                    $context
                );

                wp_safe_redirect( $this->get_redirect_url_from_request( $context ) );
                exit;
            }

            foreach ( $order_ids as $order_id ) {
                check_admin_referer( 'dokan_dhl_label_' . $order_id );
            }

            $results  = $this->process_label_requests( $order_ids, $context );
            $this->store_flash_messages( $results, $context );
            $redirect = $this->get_redirect_url_from_request( $context );

            wp_safe_redirect( $redirect );
            exit;
        }

        /**
         * Output stored vendor notices.
         */
        public function render_vendor_flash_notices() {
            $user_id = get_current_user_id();

            if ( $user_id <= 0 ) {
                return;
            }

            $key      = $this->get_flash_transient_key( 'vendor', $user_id );
            $messages = get_transient( $key );

            if ( empty( $messages ) || ! is_array( $messages ) ) {
                return;
            }

            delete_transient( $key );

            foreach ( $messages as $message ) {
                $type    = isset( $message['type'] ) ? $this->map_notice_type( $message['type'] ) : 'info';
                $content = isset( $message['text'] ) ? $message['text'] : '';

                if ( '' === $content ) {
                    continue;
                }

                printf( '<div class="dokan-alert dokan-alert-%1$s">%2$s</div>', esc_attr( $type ), wp_kses_post( wpautop( $content ) ) );
            }
        }

        /**
         * Output stored admin notices.
         */
        public function render_admin_flash_notices() {
            $user_id = get_current_user_id();

            if ( $user_id <= 0 ) {
                return;
            }

            $key      = $this->get_flash_transient_key( 'admin', $user_id );
            $messages = get_transient( $key );

            if ( empty( $messages ) || ! is_array( $messages ) ) {
                return;
            }

            delete_transient( $key );

            foreach ( $messages as $message ) {
                $type    = isset( $message['type'] ) ? $message['type'] : 'info';
                $content = isset( $message['text'] ) ? $message['text'] : '';

                if ( '' === $content ) {
                    continue;
                }

                $class = 'notice';

                if ( 'error' === $type ) {
                    $class .= ' notice-error';
                } elseif ( 'success' === $type ) {
                    $class .= ' notice-success';
                } elseif ( 'warning' === $type ) {
                    $class .= ' notice-warning';
                } else {
                    $class .= ' notice-info';
                }

                printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( wpautop( $content ) ) );
            }
        }

        /**
         * Output order action buttons in the Dokan header section.
         *
         * @param mixed $order Order object or ID.
         */
        public function render_dokan_order_detail_actions( $order ) {
            $order_id = $this->get_order_id( $order );

            if ( ! $order_id || ! $this->is_vendor_order( $order_id ) ) {
                return;
            }

            $vendor_id = $this->get_order_vendor_id( $order_id );

            if ( ! $this->current_user_can_manage_order( $vendor_id ) ) {
                return;
            }

            $has_label = $this->order_has_label( $order_id );

            if ( $has_label ) {
                $url  = $this->get_label_download_url( $order_id );
                $text = __( 'Descargar etiqueta DHL', 'dokan-dhl-per-vendor' );
                $class = 'dokan-btn dokan-btn-default dokan-btn-sm';
            } else {
                $url  = $this->get_label_creation_url( $order_id, 'vendor' );
                $text = __( 'Crear etiqueta DHL', 'dokan-dhl-per-vendor' );
                $class = 'dokan-btn dokan-btn-theme dokan-btn-sm';
            }

            if ( ! $url ) {
                return;
            }

            printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $text ) );
        }

        /**
         * Determine if the order belongs to a Dokan vendor.
         *
         * @param int $order_id Order ID.
         *
         * @return bool
         */
        protected function is_vendor_order( $order_id ) {
            return $this->get_order_vendor_id( $order_id ) > 0;
        }

        /**
         * Determine whether the order already has a stored label.
         *
         * @param int $order_id Order ID.
         *
         * @return bool
         */
        protected function order_has_label( $order_id ) {
            $data = $this->order_store->get_order_data( $order_id );

            return isset( $data['awb'] ) && '' !== $data['awb'];
        }

        /**
         * Retrieve the download URL for an order label if available.
         *
         * @param int $order_id Order ID.
         *
         * @return string
         */
        protected function get_label_download_url( $order_id ) {
            if ( ! $this->order_has_label( $order_id ) ) {
                return '';
            }

            $nonce = wp_create_nonce( 'wp_rest' );

            return add_query_arg(
                '_wpnonce',
                $nonce,
                untrailingslashit( rest_url( 'dokan-dhl/v1/orders/' . $order_id ) ) . '/label'
            );
        }

        /**
         * Build the action URL used to trigger label creation.
         *
         * @param int    $order_id Order ID.
         * @param string $context  Context identifier.
         *
         * @return string
         */
        protected function get_label_creation_url( $order_id, $context = '' ) {
            if ( '' === $context ) {
                $context = is_admin() ? 'admin' : 'vendor';
            }

            $args = array(
                'action'   => self::ACTION_CREATE_LABEL,
                'order_id' => $order_id,
                'context'  => $context,
            );

            $redirect = $this->get_current_request_url();

            if ( $redirect ) {
                $args['redirect'] = rawurlencode( $redirect );
            }

            $url = add_query_arg( $args, admin_url( 'admin-post.php' ) );

            return wp_nonce_url( $url, 'dokan_dhl_label_' . $order_id );
        }

        /**
         * Determine the current request URL.
         *
         * @return string
         */
        protected function get_current_request_url() {
            $url = add_query_arg( array() );

            if ( ! is_string( $url ) || '' === $url ) {
                return '';
            }

            return $url;
        }

        /**
         * Resolve the redirect URL from request parameters.
         *
         * @param string $context Context identifier.
         *
         * @return string
         */
        protected function get_redirect_url_from_request( $context = 'vendor' ) {
            $fallback = 'admin' === $context ? admin_url( 'edit.php?post_type=shop_order' ) : home_url();

            if ( 'vendor' === $context && function_exists( 'dokan_get_navigation_url' ) ) {
                $fallback = dokan_get_navigation_url( 'orders' );
            }

            $redirect = isset( $_REQUEST['redirect'] ) ? wp_unslash( $_REQUEST['redirect'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            if ( '' !== $redirect ) {
                $decoded  = rawurldecode( $redirect );
                $validated = wp_validate_redirect( $decoded, $fallback );

                if ( $validated ) {
                    return $validated;
                }
            }

            $referer = wp_get_referer();

            if ( $referer ) {
                $validated = wp_validate_redirect( $referer, $fallback );

                if ( $validated ) {
                    return $validated;
                }
            }

            return $fallback;
        }

        /**
         * Process label creation for a batch of orders.
         *
         * @param array  $order_ids Order IDs.
         * @param string $context   Context identifier.
         *
         * @return array
         */
        protected function process_label_requests( $order_ids, $context ) {
            $results = array(
                'created' => array(),
                'errors'  => array(),
                'skipped' => array(),
            );

            foreach ( $order_ids as $order_id ) {
                $order_id = absint( $order_id );

                if ( $order_id <= 0 ) {
                    continue;
                }

                $vendor_id = $this->get_order_vendor_id( $order_id );

                if ( ! $vendor_id ) {
                    $results['errors'][ $order_id ] = new WP_Error(
                        'dokan_dhl_missing_vendor',
                        sprintf(
                            /* translators: %s: order number */
                            __( 'No se encontró un vendedor para el pedido #%s.', 'dokan-dhl-per-vendor' ),
                            esc_html( $this->get_order_number( $order_id ) )
                        )
                    );

                    continue;
                }

                if ( ! $this->current_user_can_manage_order( $vendor_id ) ) {
                    $results['errors'][ $order_id ] = new WP_Error(
                        'dokan_dhl_forbidden',
                        sprintf(
                            /* translators: %s: order number */
                            __( 'No tienes permiso para gestionar el pedido #%s.', 'dokan-dhl-per-vendor' ),
                            esc_html( $this->get_order_number( $order_id ) )
                        )
                    );

                    continue;
                }

                if ( $this->order_has_label( $order_id ) ) {
                    $results['skipped'][ $order_id ] = sprintf(
                        /* translators: %s: order number */
                        __( 'El pedido #%s ya tiene una etiqueta DHL.', 'dokan-dhl-per-vendor' ),
                        esc_html( $this->get_order_number( $order_id ) )
                    );

                    continue;
                }

                $response = $this->request_label_creation( $order_id );

                if ( is_wp_error( $response ) ) {
                    $results['errors'][ $order_id ] = $response;
                } else {
                    $results['created'][ $order_id ] = $response;
                }
            }

            return $results;
        }

        /**
         * Execute the REST API call to create a shipment label.
         *
         * @param int $order_id Order ID.
         *
         * @return array|WP_Error
         */
        protected function request_label_creation( $order_id ) {
            if ( ! class_exists( 'WP_REST_Request' ) ) {
                require_once ABSPATH . 'wp-includes/rest-api.php';
            }

            $request = new WP_REST_Request( 'POST', '/dokan-dhl/v1/orders/' . $order_id . '/label' );

            $response = rest_do_request( $request );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            if ( $response instanceof WP_REST_Response ) {
                $status = $response->get_status();
                $data   = $response->get_data();
            } else {
                $status = 200;
                $data   = $response;
            }

            if ( $status < 200 || $status >= 300 ) {
                $message = __( 'No se pudo crear la etiqueta DHL.', 'dokan-dhl-per-vendor' );

                if ( is_array( $data ) && isset( $data['message'] ) ) {
                    $message = (string) $data['message'];
                }

                return new WP_Error( 'dokan_dhl_rest_error', $message, array( 'status' => $status, 'data' => $data ) );
            }

            return is_array( $data ) ? $data : array();
        }

        /**
         * Persist flash messages for the current user.
         *
         * @param array  $results Processed result set.
         * @param string $context Context identifier.
         */
        protected function store_flash_messages( $results, $context ) {
            $messages = $this->prepare_flash_messages( $results );

            if ( empty( $messages ) ) {
                return;
            }

            $this->append_flash_messages( $messages, $context );
        }

        /**
         * Format messages from the bulk processing results.
         *
         * @param array $results Result set.
         *
         * @return array
         */
        protected function prepare_flash_messages( $results ) {
            $messages = array();

            if ( isset( $results['created'] ) && is_array( $results['created'] ) ) {
                foreach ( $results['created'] as $order_id => $data ) {
                    $messages[] = array(
                        'type' => 'success',
                        'text' => sprintf(
                            /* translators: %s: order number */
                            __( 'Etiqueta DHL creada para el pedido #%s.', 'dokan-dhl-per-vendor' ),
                            esc_html( $this->get_order_number( $order_id ) )
                        ),
                    );
                }
            }

            if ( isset( $results['skipped'] ) && is_array( $results['skipped'] ) ) {
                foreach ( $results['skipped'] as $order_id => $message ) {
                    $messages[] = array(
                        'type' => 'warning',
                        'text' => is_string( $message ) ? $message : sprintf(
                            /* translators: %s: order number */
                            __( 'El pedido #%s no requiere una nueva etiqueta DHL.', 'dokan-dhl-per-vendor' ),
                            esc_html( $this->get_order_number( $order_id ) )
                        ),
                    );
                }
            }

            if ( isset( $results['errors'] ) && is_array( $results['errors'] ) ) {
                foreach ( $results['errors'] as $order_id => $error ) {
                    $message = is_wp_error( $error ) ? $error->get_error_message() : (string) $error;

                    if ( '' === $message ) {
                        $message = __( 'Se produjo un error desconocido al crear la etiqueta DHL.', 'dokan-dhl-per-vendor' );
                    }

                    $messages[] = array(
                        'type' => 'error',
                        'text' => $message,
                    );
                }
            }

            return $messages;
        }

        /**
         * Store a manually crafted message.
         *
         * @param string $message Message content.
         * @param string $type    Message type.
         * @param string $context Context identifier.
         */
        protected function store_manual_message( $message, $type, $context ) {
            if ( '' === $message ) {
                return;
            }

            $this->append_flash_messages(
                array(
                    array(
                        'type' => $type,
                        'text' => $message,
                    ),
                ),
                $context
            );
        }

        /**
         * Append notices to the transient store.
         *
         * @param array  $messages Messages to append.
         * @param string $context  Context identifier.
         */
        protected function append_flash_messages( $messages, $context ) {
            $user_id = get_current_user_id();

            if ( $user_id <= 0 ) {
                return;
            }

            $key      = $this->get_flash_transient_key( $context, $user_id );
            $existing = get_transient( $key );

            if ( ! is_array( $existing ) ) {
                $existing = array();
            }

            $merged = array_merge( $existing, $messages );

            set_transient( $key, $merged, 5 * MINUTE_IN_SECONDS );
        }

        /**
         * Resolve the transient key for notices.
         *
         * @param string $context Context identifier.
         * @param int    $user_id User ID.
         *
         * @return string
         */
        protected function get_flash_transient_key( $context, $user_id ) {
            $context = sanitize_key( $context );

            return self::NOTICE_TRANSIENT_BASE . $context . '_' . $user_id;
        }

        /**
         * Map generic notice types to Dokan specific classes.
         *
         * @param string $type Notice type.
         *
         * @return string
         */
        protected function map_notice_type( $type ) {
            switch ( $type ) {
                case 'success':
                    return 'success';
                case 'error':
                    return 'danger';
                case 'warning':
                    return 'warning';
            }

            return 'info';
        }

        /**
         * Retrieve a displayable order number.
         *
         * @param int $order_id Order ID.
         *
         * @return string
         */
        protected function get_order_number( $order_id ) {
            $order = wc_get_order( $order_id );

            if ( $order instanceof WC_Order ) {
                return (string) $order->get_order_number();
            }

            return (string) $order_id;
        }

        /**
         * Resolve the order ID from the provided parameter.
         *
         * @param mixed $order Order object or ID.

         * @return int
         */
        protected function get_order_id( $order ) {
            if ( is_numeric( $order ) ) {
                $order_id = absint( $order );
            } elseif ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
                $order_id = absint( $order->get_id() );
            } else {
                $order_id = 0;
            }

            if ( $order_id <= 0 ) {
                return 0;
            }

            return $order_id;

        }

        /**
         * Retrieve the vendor ID associated with an order.
         *
         * @param int $order_id Order ID.

         * @return int
         */
        protected function get_order_vendor_id( $order_id ) {
            $vendor_id = get_post_meta( $order_id, '_dokan_vendor_id', true );

            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 ) {
                return 0;
            }

            return $vendor_id;
        }

        /**
         * Determine whether the current user can manage the order panel.
         *
         * @param int $vendor_id Vendor ID.

         * @return bool
         */
        protected function current_user_can_manage_order( $vendor_id ) {
            $user_id = get_current_user_id();

            if ( $user_id <= 0 ) {
                return false;
            }

            if ( user_can( $user_id, 'manage_woocommerce' ) || user_can( $user_id, 'manage_options' ) ) {
                return true;
            }

            if ( $vendor_id && $vendor_id === $user_id ) {
                return true;
            }

            return false;
        }

        /**
         * Prepare default package values for the panel.
         *
         * @param array $shipment_data Existing shipment data.
         * @param array $defaults      Vendor defaults.

         * @return array
         */
        protected function prepare_package_defaults( $shipment_data, $defaults ) {
            $default_values = array(
                'weight' => '',
                'length' => '',
                'width'  => '',
                'height' => '',
            );

            $map = array(
                'weight' => array( 'shipment' => 'weight', 'vendor' => 'default_weight' ),
                'length' => array( 'shipment' => 'length', 'vendor' => 'default_length' ),
                'width'  => array( 'shipment' => 'width', 'vendor' => 'default_width' ),
                'height' => array( 'shipment' => 'height', 'vendor' => 'default_height' ),
            );

            $package = isset( $shipment_data['package'] ) && is_array( $shipment_data['package'] ) ? $shipment_data['package'] : array();


            foreach ( $map as $key => $source ) {
                $value = '';

                if ( isset( $shipment_data[ $source['shipment'] ] ) && '' !== $shipment_data[ $source['shipment'] ] ) {
                    $value = (string) $shipment_data[ $source['shipment'] ];
                } elseif ( isset( $package[ $key ] ) && '' !== $package[ $key ] ) {
                    $value = (string) $package[ $key ];

                } elseif ( isset( $defaults[ $source['vendor'] ] ) && '' !== $defaults[ $source['vendor'] ] ) {
                    $value = (string) $defaults[ $source['vendor'] ];
                }

                $default_values[ $key ] = $value;
            }

            return $default_values;
        }

        /**
         * Enqueue assets required for the order panel.
         */
        protected function enqueue_assets() {
            if ( ! wp_script_is( 'dokan-dhl-vendor-orders', 'registered' ) ) {
                wp_register_script(
                    'dokan-dhl-vendor-orders',
                    DOKAN_DHL_PER_VENDOR_URL . 'assets/js/vendor-orders.js',
                    array( 'jquery', 'wp-api-fetch' ),
                    DOKAN_DHL_PER_VENDOR_VERSION,
                    true
                );
            }

            wp_enqueue_script( 'dokan-dhl-vendor-orders' );

            if ( ! $this->script_localized ) {
                $this->rest_nonce = wp_create_nonce( 'wp_rest' );

                $data = array(
                    'restBasePath' => '/dokan-dhl/v1',
                    'root'         => rest_url(),
                    'nonce'        => $this->rest_nonce,
                    'i18n'         => array(
                        'labelCreated'      => __( 'DHL label created successfully.', 'dokan-dhl-per-vendor' ),
                        'labelCreateError'  => __( 'Unable to create the DHL label.', 'dokan-dhl-per-vendor' ),
                        'trackingUpdated'   => __( 'Tracking information updated.', 'dokan-dhl-per-vendor' ),
                        'trackingError'     => __( 'Unable to refresh tracking at this time.', 'dokan-dhl-per-vendor' ),
                        'awbPending'        => __( 'Pending', 'dokan-dhl-per-vendor' ),
                        'trackingUnknown'   => __( 'Not available', 'dokan-dhl-per-vendor' ),
                    ),
                );

                wp_localize_script( 'dokan-dhl-vendor-orders', 'DokanDHLVendorOrders', $data );

                $this->script_localized = true;
            }
        }

        /**

         * Load a template file with provided data.
         *
         * @param string $template Template name.
         * @param array  $data     Data to extract for the template.
         */
        protected function load_template( $template, $data ) {

            // Asegúrate que la constante DOKAN_DHL_PER_VENDOR_PATH esté definida en tu archivo principal del plugin.
            $template_path = DOKAN_DHL_PER_VENDOR_PATH . 'templates/' . $template;

            if ( ! file_exists( $template_path ) ) {
                return;
            }

            if ( ! is_array( $data ) ) {
                $data = array();
            }

            extract( $data, EXTR_SKIP );

            require $template_path;
        }
    }
}
