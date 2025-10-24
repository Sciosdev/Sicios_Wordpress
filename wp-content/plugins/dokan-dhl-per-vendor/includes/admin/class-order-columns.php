<?php
/**
 * Admin order list column integration.
 *
 * @package Dokan_DHL_Per_Vendor\Admin
 */

if ( ! class_exists( 'Dokan_DHL_Admin_Order_Columns' ) ) {

    class Dokan_DHL_Admin_Order_Columns {

        const COLUMN_KEY   = 'dokan_dhl';
        const PLACEHOLDER  = '-';

        /**
         * Singleton instance.
         *
         * @var Dokan_DHL_Admin_Order_Columns|null
         */
        protected static $instance = null;

        /**
         * Order store instance.
         *
         * @var Dokan_DHL_Order_Store
         */
        protected $order_store;

        /**
         * Boot integration.
         *
         * @return Dokan_DHL_Admin_Order_Columns
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

            add_filter( 'manage_edit-shop_order_columns', array( $this, 'register_column' ), 20 );
            add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
        }

        /**
         * Register DHL column.
         *
         * @param array $columns Existing columns.
         *
         * @return array
         */
        public function register_column( $columns ) {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return $columns;
            }

            if ( isset( $columns[ self::COLUMN_KEY ] ) ) {
                return $columns;
            }

            $new_columns = array();

            foreach ( $columns as $key => $label ) {
                $new_columns[ $key ] = $label;

                if ( 'order_status' === $key ) {
                    $new_columns[ self::COLUMN_KEY ] = esc_html__( 'DHL', 'dokan-dhl-per-vendor' );
                }
            }

            if ( ! isset( $new_columns[ self::COLUMN_KEY ] ) ) {
                $new_columns[ self::COLUMN_KEY ] = esc_html__( 'DHL', 'dokan-dhl-per-vendor' );
            }

            return $new_columns;
        }

        /**
         * Render DHL column content.
         *
         * @param string $column  Column key.
         * @param int    $post_id Post ID.
         *
         * @return void
         */
        public function render_column( $column, $post_id ) {
            if ( self::COLUMN_KEY !== $column ) {
                return;
            }

            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                echo esc_html( self::PLACEHOLDER );
                return;
            }

            $post_id = absint( $post_id );

            if ( $post_id <= 0 ) {
                echo esc_html( self::PLACEHOLDER );
                return;
            }

            $order_data = $this->order_store->get_order_data( $post_id );

            if ( empty( $order_data ) || ! is_array( $order_data ) || empty( array_filter( $order_data ) ) ) {
                echo esc_html( self::PLACEHOLDER );
                return;
            }

            $badge_text = '';

            if ( ! empty( $order_data['tracking_status'] ) ) {
                $badge_text = (string) $order_data['tracking_status'];
            } elseif ( ! empty( $order_data['awb'] ) ) {
                $badge_text = (string) $order_data['awb'];
            } else {
                $badge_text = __( 'DHL', 'dokan-dhl-per-vendor' );
            }

            printf(
                '<span class="dokan-dhl-order-column-badge">%s</span>',
                esc_html( $badge_text )
            );
        }
    }
}
