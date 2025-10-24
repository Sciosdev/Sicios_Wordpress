<?php
/**
 * Admin order metabox for DHL per vendor data.
 *
 * @package Dokan_DHL_Per_Vendor\Admin
 */

if ( ! class_exists( 'Dokan_DHL_Admin_Order_Metabox' ) ) {

    class Dokan_DHL_Admin_Order_Metabox {

        /**
         * Singleton instance.
         *
         * @var Dokan_DHL_Admin_Order_Metabox|null
         */
        protected static $instance = null;

        /**
         * Order store instance.
         *
         * @var Dokan_DHL_Order_Store
         */
        protected $order_store;

        /**
         * Placeholder string for empty values.
         */
        const PLACEHOLDER = '—';

        /**
         * Boot the metabox integration.
         *
         * @return Dokan_DHL_Admin_Order_Metabox
         */
        public static function boot() {
            if ( null === self::$instance ) {
                self::$instance = new self( new Dokan_DHL_Order_Store() );
            }

            return self::$instance;
        }

        /**
         * Constructor.
         *
         * @param Dokan_DHL_Order_Store $order_store Order store instance.
         */
        public function __construct( Dokan_DHL_Order_Store $order_store ) {
            $this->order_store = $order_store;

            add_action( 'add_meta_boxes_shop_order', array( $this, 'register_metabox' ) );
        }

        /**
         * Register the DHL sub-orders metabox.
         *
         * @param WP_Post $post Current order post object.
         */
        public function register_metabox( $post ) {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            add_meta_box(
                'dokan-dhl-suborders',
                esc_html__( 'DHL Vendor Shipments', 'dokan-dhl-per-vendor' ),
                array( $this, 'render_metabox' ),
                'shop_order',
                'side',
                'default'
            );
        }

        /**
         * Render the sub-orders information inside the metabox.
         *
         * @param WP_Post $post    Current post object.
         * @param array   $metabox Metabox arguments.
         */
        public function render_metabox( $post, $metabox ) {
            unset( $metabox ); // Unused but kept for compatibility with WordPress callbacks.

            $order_id = $this->get_order_id( $post );

            if ( ! $order_id ) {
                echo '<p>' . esc_html__( 'Order details are unavailable.', 'dokan-dhl-per-vendor' ) . '</p>';
                return;
            }

            $sub_order_ids = $this->get_sub_order_ids( $order_id );

            if ( empty( $sub_order_ids ) ) {
                echo '<p>' . esc_html__( 'No vendor sub-orders were found for this order.', 'dokan-dhl-per-vendor' ) . '</p>';
                return;
            }

            $rest_nonce = wp_create_nonce( 'wp_rest' );

            echo '<table class="widefat striped dokan-dhl-suborders-table">';
            echo '<thead><tr>';
            echo '<th scope="col">' . esc_html__( 'Vendor', 'dokan-dhl-per-vendor' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'AWB', 'dokan-dhl-per-vendor' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Status', 'dokan-dhl-per-vendor' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Label', 'dokan-dhl-per-vendor' ) . '</th>';
            echo '<th scope="col">' . esc_html__( 'Actions', 'dokan-dhl-per-vendor' ) . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ( $sub_order_ids as $sub_order_id ) {
                $sub_order_id = absint( $sub_order_id );

                if ( $sub_order_id <= 0 ) {
                    continue;
                }

                $vendor_id   = $this->get_vendor_id( $sub_order_id );
                $vendor_name = $this->get_vendor_name( $vendor_id );
                $order_data  = $this->order_store->get_order_data( $sub_order_id );

                $awb    = isset( $order_data['awb'] ) ? trim( (string) $order_data['awb'] ) : '';
                $status = isset( $order_data['tracking_status'] ) ? (string) $order_data['tracking_status'] : '';

                $download_link = $this->get_download_link( $sub_order_id, $order_data, $rest_nonce );
                $view_link     = $this->get_view_link( $sub_order_id );

                echo '<tr>';
                echo '<td>' . esc_html( $vendor_name ? $vendor_name : self::PLACEHOLDER ) . '</td>';
                echo '<td>' . esc_html( $awb ? $awb : self::PLACEHOLDER ) . '</td>';
                echo '<td>' . esc_html( $status ? $status : self::PLACEHOLDER ) . '</td>';
                echo '<td>' . $download_link . '</td>';
                echo '<td>' . $view_link . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }

        /**
         * Retrieve order ID from post object.
         *
         * @param mixed $post Post object or ID.
         *
         * @return int
         */
        protected function get_order_id( $post ) {
            if ( is_object( $post ) && isset( $post->ID ) ) {
                return absint( $post->ID );
            }

            if ( is_numeric( $post ) ) {
                return absint( $post );
            }

            return 0;
        }

        /**
         * Retrieve sub-order IDs for a given order.
         *
         * @param int $order_id Parent order ID.
         *
         * @return array
         */
        protected function get_sub_order_ids( $order_id ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return array();
            }

            $sub_order_ids = array();

            if ( function_exists( 'dokan_get_suborder_ids' ) ) {
                $sub_order_ids = dokan_get_suborder_ids( $order_id );

                if ( ! is_array( $sub_order_ids ) ) {
                    $sub_order_ids = array();
                }
            }

            $sub_order_ids = array_filter( array_map( 'absint', $sub_order_ids ) );

            if ( empty( $sub_order_ids ) ) {
                $vendor_id = $this->get_vendor_id( $order_id );

                if ( $vendor_id > 0 ) {
                    $sub_order_ids = array( $order_id );
                }
            }

            return array_values( array_unique( $sub_order_ids ) );
        }

        /**
         * Retrieve the vendor ID for a sub-order.
         *
         * @param int $order_id Order ID.
         *
         * @return int
         */
        protected function get_vendor_id( $order_id ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return 0;
            }

            return absint( get_post_meta( $order_id, '_dokan_vendor_id', true ) );
        }

        /**
         * Retrieve a formatted vendor name.
         *
         * @param int $vendor_id Vendor ID.
         *
         * @return string
         */
        protected function get_vendor_name( $vendor_id ) {
            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 ) {
                return '';
            }

            $user = get_userdata( $vendor_id );

            if ( ! $user instanceof WP_User ) {
                return '';
            }

            $display_name = trim( (string) $user->display_name );

            if ( '' === $display_name ) {
                $display_name = trim( (string) $user->user_login );
            }

            return $display_name;
        }

        /**
         * Get the download link HTML for an order label.
         *
         * @param int    $order_id   Order ID.
         * @param array  $order_data Stored order data.
         * @param string $rest_nonce REST API nonce.
         *
         * @return string
         */
        protected function get_download_link( $order_id, $order_data, $rest_nonce ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 || empty( $order_data['label_path'] ) ) {
                return esc_html( self::PLACEHOLDER );
            }

            $rest_base = untrailingslashit( rest_url( 'dokan-dhl/v1/orders/' . $order_id ) );
            $url       = add_query_arg( '_wpnonce', $rest_nonce, $rest_base . '/label' );

            $link_text = esc_html__( 'Download label', 'dokan-dhl-per-vendor' );

            return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), $link_text );
        }

        /**
         * Get the view link HTML for a sub-order.
         *
         * @param int $order_id Order ID.
         *
         * @return string
         */
        protected function get_view_link( $order_id ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return esc_html( self::PLACEHOLDER );
            }

            $link = get_edit_post_link( $order_id, 'edit' );

            if ( ! $link ) {
                return esc_html( self::PLACEHOLDER );
            }

            $link_text = esc_html__( 'View sub-order', 'dokan-dhl-per-vendor' );

            return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $link ), $link_text );
        }
    }
}
