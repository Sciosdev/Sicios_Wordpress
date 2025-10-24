<?php
/**
 * Handles automatic tracking refresh tasks.
 *
 * @package Dokan_DHL_Per_Vendor
 */

if ( ! class_exists( 'Dokan_DHL_Tracking_Manager' ) ) {

    class Dokan_DHL_Tracking_Manager {

        const CRON_HOOK        = 'dokan_dhl_refresh_tracking';
        const CRON_INTERVAL    = 'dokan_dhl_tracking_interval';

        /**
         * Singleton instance.
         *
         * @var Dokan_DHL_Tracking_Manager|null
         */
        protected static $instance = null;

        /**
         * Order store instance.
         *
         * @var Dokan_DHL_Order_Store
         */
        protected $order_store;

        /**
         * DHL client instance.
         *
         * @var Dokan_DHL_Client
         */
        protected $client;

        /**
         * Boot the singleton.
         *
         * @return Dokan_DHL_Tracking_Manager
         */
        public static function boot() {
            if ( null === self::$instance ) {
                self::$instance = new self(
                    new Dokan_DHL_Order_Store(),
                    new Dokan_DHL_Client( new Dokan_DHL_Credentials_Store() )
                );
            }

            return self::$instance;
        }

        /**
         * Activation handler.
         */
        public static function activate() {
            $instance = self::boot();
            $instance->schedule_cron_event( true );
        }

        /**
         * Deactivation handler.
         */
        public static function deactivate() {
            wp_clear_scheduled_hook( self::CRON_HOOK );
        }

        /**
         * Constructor.
         *
         * @param Dokan_DHL_Order_Store $order_store Order store instance.
         * @param Dokan_DHL_Client      $client      DHL client instance.
         */
        public function __construct( Dokan_DHL_Order_Store $order_store, Dokan_DHL_Client $client ) {
            $this->order_store = $order_store;
            $this->client      = $client;

            add_filter( 'cron_schedules', array( $this, 'register_cron_schedule' ) );
            add_action( 'init', array( $this, 'maybe_schedule_event' ) );
            add_action( self::CRON_HOOK, array( $this, 'handle_cron_event' ) );
        }

        /**
         * Register the custom cron interval.
         *
         * @param array $schedules Existing schedules.
         *
         * @return array
         */
        public function register_cron_schedule( $schedules ) {
            if ( ! is_array( $schedules ) ) {
                $schedules = array();
            }

            if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
                $interval = (int) apply_filters( 'dokan_dhl_tracking_cron_interval', 6 * HOUR_IN_SECONDS );

                if ( $interval < HOUR_IN_SECONDS ) {
                    $interval = 6 * HOUR_IN_SECONDS;
                }

                $schedules[ self::CRON_INTERVAL ] = array(
                    'interval' => $interval,
                    'display'  => __( 'Every 6 hours (DHL Tracking)', 'dokan-dhl-per-vendor' ),
                );
            }

            return $schedules;
        }

        /**
         * Ensure the cron event is scheduled.
         */
        public function maybe_schedule_event() {
            $this->schedule_cron_event();
        }

        /**
         * Schedule the cron hook if necessary.
         *
         * @param bool $force Whether to reschedule even if already present.
         */
        protected function schedule_cron_event( $force = false ) {
            if ( $force ) {
                wp_clear_scheduled_hook( self::CRON_HOOK );
            }

            if ( wp_next_scheduled( self::CRON_HOOK ) ) {
                return;
            }

            $offset = (int) apply_filters( 'dokan_dhl_tracking_cron_start_offset', 10 * MINUTE_IN_SECONDS );

            if ( $offset < 0 ) {
                $offset = 0;
            }

            wp_schedule_event( time() + $offset, self::CRON_INTERVAL, self::CRON_HOOK );
        }

        /**
         * Handle the cron execution.
         */
        public function handle_cron_event() {
            $batch_size = (int) apply_filters( 'dokan_dhl_tracking_cron_batch_size', 25 );

            if ( $batch_size <= 0 ) {
                $batch_size = 25;
            }

            $paged = 1;

            do {
                $order_ids = $this->order_store->get_orders_with_awb(
                    array(
                        'posts_per_page' => $batch_size,
                        'paged'          => $paged,
                    )
                );

                if ( empty( $order_ids ) ) {
                    break;
                }

                foreach ( $order_ids as $order_id ) {
                    $this->refresh_order_tracking( $order_id );
                }

                $paged++;
            } while ( count( $order_ids ) >= $batch_size );
        }

        /**
         * Refresh tracking for a single order.
         *
         * @param int $order_id Order identifier.
         */
        protected function refresh_order_tracking( $order_id ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return;
            }

            $awb = get_post_meta( $order_id, Dokan_DHL_Order_Store::META_AWB, true );
            $awb = is_string( $awb ) ? trim( $awb ) : '';

            if ( '' === $awb ) {
                return;
            }

            $vendor_id = absint( get_post_meta( $order_id, '_dokan_vendor_id', true ) );

            if ( $vendor_id <= 0 ) {
                return;
            }

            $tracking = $this->client->get_tracking( $vendor_id, $awb );

            if ( is_wp_error( $tracking ) ) {
                do_action( 'dokan_dhl_tracking_cron_error', $order_id, $tracking );
                return;
            }

            $this->order_store->save_tracking( $order_id, $tracking );

            do_action( 'dokan_dhl_tracking_cron_updated', $order_id, $tracking );
        }
    }
}
