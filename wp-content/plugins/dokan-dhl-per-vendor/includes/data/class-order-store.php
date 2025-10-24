<?php
/**
 * Order metadata storage.
 *
 * @package Dokan_DHL_Per_Vendor\Data
 */

if ( ! class_exists( 'Dokan_DHL_Order_Store' ) ) {

    class Dokan_DHL_Order_Store {

        const META_KEY              = '_dokan_dhl_order_data';
        const META_AWB              = '_dhl_awb';
        const META_LABEL_PATH       = '_dhl_label_path';
        const META_TRACKING_STATUS  = '_dhl_tracking_status';
        const META_TRACKING_EVENTS  = '_dhl_tracking_events';
        const META_AUDIT            = '_dhl_audit_trail';

        /**
         * Get DHL data for an order.
         *
         * @param int $order_id Order ID.

         * @return array
         */
        public function get_order_data( $order_id ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return array();
            }

            $data = get_post_meta( $order_id, self::META_KEY, true );

            if ( ! is_array( $data ) ) {
                $data = array();
            }

            $mapped_meta = array(
                'awb'            => get_post_meta( $order_id, self::META_AWB, true ),
                'label_path'     => get_post_meta( $order_id, self::META_LABEL_PATH, true ),
                'tracking_status'=> get_post_meta( $order_id, self::META_TRACKING_STATUS, true ),
            );

            foreach ( $mapped_meta as $key => $value ) {
                if ( '' !== $value && null !== $value ) {
                    $data[ $key ] = $value;
                }
            }

            $events = get_post_meta( $order_id, self::META_TRACKING_EVENTS, true );

            if ( is_array( $events ) ) {
                $data['tracking_events'] = $events;
            } elseif ( ! isset( $data['tracking_events'] ) ) {
                $data['tracking_events'] = array();
            }

            $audit = get_post_meta( $order_id, self::META_AUDIT, true );

            if ( is_string( $audit ) && '' !== $audit ) {
                $decoded = json_decode( $audit, true );

                if ( null !== $decoded ) {
                    $data['audit'] = $decoded;
                } else {
                    $data['audit_raw'] = $audit;
                }
            }

            return $data;
        }

        /**
         * Save DHL data for an order.
         *
         * @param int   $order_id Order ID.
         * @param array $data     DHL data.
         * @return void
         */
        public function set_order_data( $order_id, $data ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 || ! is_array( $data ) ) {
                return;
            }

            if ( empty( array_filter( $data ) ) ) {
                delete_post_meta( $order_id, self::META_KEY );
                return;
            }

            update_post_meta( $order_id, self::META_KEY, $data );
        }

        /**
         * Retrieve order IDs by airway bill number.
         *
         * @param string $awb Airway bill.
         *
         * @return array
         */
        public function get_order_ids_by_awb( $awb ) {
            $awb = trim( (string) $awb );

            if ( '' === $awb ) {
                return array();
            }

            $post_status = 'any';

            if ( function_exists( 'wc_get_order_statuses' ) ) {
                $post_status = array_keys( wc_get_order_statuses() );
            }

            $query = new WP_Query(
                array(
                    'post_type'      => 'shop_order',
                    'post_status'    => $post_status,
                    'fields'         => 'ids',
                    'posts_per_page' => -1,
                    'no_found_rows'  => true,
                    'orderby'        => 'ID',
                    'order'          => 'ASC',
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'   => self::META_AWB,
                            'value' => $awb,
                        ),
                        array(
                            'key'     => '_dokan_vendor_id',
                            'value'   => 0,
                            'compare' => '>',
                            'type'    => 'NUMERIC',
                        ),
                    ),
                    'suppress_filters' => true,
                )
            );

            if ( empty( $query->posts ) ) {
                return array();
            }

            $order_ids = array();

            foreach ( $query->posts as $order_id ) {
                $order_id = absint( $order_id );

                if ( $order_id <= 0 ) {
                    continue;
                }

                $order_ids[] = $order_id;
            }

            return array_values( array_unique( $order_ids ) );
        }

        /**
         * Retrieve vendor order IDs that have an associated airway bill.
         *
         * @param array $args Additional query arguments.
         *
         * @return array
         */
        public function get_orders_with_awb( $args = array() ) {
            $defaults = array(
                'posts_per_page' => 20,
                'paged'          => 1,
            );

            $args = wp_parse_args( $args, $defaults );

            $post_status = 'any';

            if ( function_exists( 'wc_get_order_statuses' ) ) {
                $post_status = array_keys( wc_get_order_statuses() );
            }

            $query_args = array(
                'post_type'      => 'shop_order',
                'post_status'    => $post_status,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'no_found_rows'  => true,
                'suppress_filters' => true,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => self::META_AWB,
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key'     => '_dokan_vendor_id',
                        'value'   => 0,
                        'compare' => '>',
                        'type'    => 'NUMERIC',
                    ),
                ),
            );

            $query_args = array_merge( $query_args, $args );

            if ( isset( $query_args['paged'] ) ) {
                $query_args['paged'] = max( 1, (int) $query_args['paged'] );
            }

            $query = new WP_Query( $query_args );

            if ( empty( $query->posts ) ) {
                return array();
            }

            $order_ids = array();

            foreach ( $query->posts as $order_id ) {
                $order_id = absint( $order_id );

                if ( $order_id <= 0 ) {
                    continue;
                }

                $awb = get_post_meta( $order_id, self::META_AWB, true );
                $awb = is_string( $awb ) ? trim( $awb ) : '';

                if ( '' === $awb ) {
                    continue;
                }

                $order_ids[] = $order_id;
            }

            return array_values( array_unique( $order_ids ) );
        }

        /**
         * Save shipment information and label to disk.
         *
         * @param int   $order_id  Order ID.
         * @param int   $vendor_id Vendor ID.
         * @param array $shipment  Shipment payload.
         *
         * @return array|WP_Error Stored shipment summary or error.
         */
        public function save_shipment( $order_id, $vendor_id, $shipment ) {
            $order_id  = absint( $order_id );
            $vendor_id = absint( $vendor_id );

            if ( $order_id <= 0 || $vendor_id <= 0 ) {
                return new WP_Error( 'dokan_dhl_invalid_identifiers', __( 'Invalid order or vendor.', 'dokan-dhl-per-vendor' ) );
            }

            if ( ! is_array( $shipment ) ) {
                return new WP_Error( 'dokan_dhl_invalid_shipment', __( 'Shipment data is invalid.', 'dokan-dhl-per-vendor' ) );
            }

            $awb = isset( $shipment['awb'] ) ? trim( (string) $shipment['awb'] ) : '';

            if ( '' === $awb ) {
                return new WP_Error( 'dokan_dhl_missing_awb', __( 'Shipment response is missing an airway bill.', 'dokan-dhl-per-vendor' ) );
            }

            $label = isset( $shipment['label'] ) && is_array( $shipment['label'] ) ? $shipment['label'] : array();
            $label_content = isset( $label['content'] ) ? $label['content'] : '';

            if ( '' === $label_content ) {
                return new WP_Error( 'dokan_dhl_missing_label', __( 'Shipment did not include a label.', 'dokan-dhl-per-vendor' ) );
            }

            $binary = base64_decode( (string) $label_content, true );

            if ( false === $binary ) {
                return new WP_Error( 'dokan_dhl_invalid_label', __( 'Unable to decode label content.', 'dokan-dhl-per-vendor' ) );
            }

            $extension  = isset( $label['file_extension'] ) ? sanitize_key( $label['file_extension'] ) : 'pdf';
            $extension  = $extension ? $extension : 'pdf';
            $safe_awb   = sanitize_file_name( $awb );
            $filename   = sprintf( 'order-%d-%s.%s', $order_id, $safe_awb, $extension );
            $label_path = $this->persist_label_file( $vendor_id, $filename, $binary );

            if ( is_wp_error( $label_path ) ) {
                return $label_path;
            }

            update_post_meta( $order_id, self::META_AWB, $awb );
            update_post_meta( $order_id, self::META_LABEL_PATH, $label_path );

            $tracking = isset( $shipment['tracking'] ) && is_array( $shipment['tracking'] ) ? $shipment['tracking'] : array();
            $audit_payload = isset( $shipment['meta'] ) ? $shipment['meta'] : $shipment;
            $encoded_audit = wp_json_encode( $audit_payload );

            if ( false !== $encoded_audit ) {
                update_post_meta( $order_id, self::META_AUDIT, $encoded_audit );
            }

            $package = isset( $shipment['package'] ) && is_array( $shipment['package'] ) ? $shipment['package'] : array();
            $summary = $this->save_tracking(
                $order_id,
                $tracking,
                array(
                    'awb'        => $awb,
                    'label_path' => $label_path,
                    'package'    => $package,
                )
            );

            if ( empty( $summary ) ) {
                $summary = array(
                    'awb'        => $awb,
                    'label_path' => $label_path,
                    'package'    => $package,
                );
            }

            return $summary;
        }

        /**
         * Persist tracking data for an order.
         *
         * @param int   $order_id Order ID.
         * @param array $tracking Tracking payload.
         * @param array $extra    Extra data to merge into stored payload.
         *
         * @return array Updated tracking data.
         */
        public function save_tracking( $order_id, $tracking, $extra = array() ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return array();
            }

            if ( ! is_array( $tracking ) ) {
                $tracking = array();
            }

            if ( ! is_array( $extra ) ) {
                $extra = array();
            }

            $existing        = $this->get_order_data( $order_id );
            $previous_status = isset( $existing['tracking_status'] ) ? (string) $existing['tracking_status'] : '';

            $existing = array_merge( $existing, $extra );

            $status = isset( $tracking['status'] ) ? sanitize_text_field( (string) $tracking['status'] ) : '';
            $events = isset( $tracking['events'] ) && is_array( $tracking['events'] ) ? $tracking['events'] : array();

            if ( ! empty( $events ) ) {
                $events = array_values(
                    array_filter(
                        array_map( array( $this, 'sanitize_tracking_event' ), $events )
                    )
                );
            }

            if ( '' !== $status ) {
                update_post_meta( $order_id, self::META_TRACKING_STATUS, $status );
            } else {
                delete_post_meta( $order_id, self::META_TRACKING_STATUS );
            }

            if ( ! empty( $events ) ) {
                update_post_meta( $order_id, self::META_TRACKING_EVENTS, $events );
            } else {
                delete_post_meta( $order_id, self::META_TRACKING_EVENTS );
            }

            if ( isset( $tracking['meta'] ) ) {
                $encoded = wp_json_encode( $tracking['meta'] );

                if ( false !== $encoded ) {
                    update_post_meta( $order_id, self::META_AUDIT, $encoded );
                }
            }

            $existing['tracking_status'] = $status;
            $existing['tracking_events'] = $events;
            $existing['updated_at']      = current_time( 'mysql', true );

            $this->set_order_data( $order_id, $existing );

            $this->maybe_add_tracking_note( $order_id, $previous_status, $status, $existing );

            do_action( 'dokan_dhl_tracking_saved', $order_id, $existing, $tracking );

            return $existing;
        }

        /**
         * Retrieve the label path for an order.
         *
         * @param int $order_id Order ID.
         *
         * @return string
         */
        public function get_label_path( $order_id ) {
            $order_id = absint( $order_id );

            if ( $order_id <= 0 ) {
                return '';
            }

            return (string) get_post_meta( $order_id, self::META_LABEL_PATH, true );
        }

        /**
         * Persist a label file to the uploads directory.
         *
         * @param int    $vendor_id Vendor ID.
         * @param string $filename  Filename.
         * @param string $contents  File contents.
         *
         * @return string|WP_Error Absolute file path or error.
         */
        protected function persist_label_file( $vendor_id, $filename, $contents ) {
            $uploads = wp_upload_dir();

            if ( empty( $uploads['basedir'] ) ) {
                return new WP_Error( 'dokan_dhl_upload_dir', __( 'Upload directory is not available.', 'dokan-dhl-per-vendor' ) );
            }

            $year      = gmdate( 'Y' );
            $directory = trailingslashit( $uploads['basedir'] ) . 'dokan-dhl/' . $vendor_id . '/' . $year . '/';

            if ( ! wp_mkdir_p( $directory ) ) {
                return new WP_Error( 'dokan_dhl_directory_unwritable', __( 'Unable to create the DHL label directory.', 'dokan-dhl-per-vendor' ) );
            }

            $path = $directory . $filename;

            $bytes_written = file_put_contents( $path, $contents );

            if ( false === $bytes_written ) {
                return new WP_Error( 'dokan_dhl_write_failed', __( 'Could not write the DHL label file.', 'dokan-dhl-per-vendor' ) );
            }

            return $path;
        }

        /**
         * Sanitize a single tracking event entry.
         *
         * @param mixed $event Raw event data.
         *
         * @return array
         */
        protected function sanitize_tracking_event( $event ) {
            if ( ! is_array( $event ) ) {
                return array();
            }

            $allowed_keys = array( 'description', 'location', 'timestamp', 'code' );
            $sanitized    = array();

            foreach ( $allowed_keys as $key ) {
                if ( isset( $event[ $key ] ) && '' !== $event[ $key ] ) {
                    $sanitized[ $key ] = sanitize_text_field( (string) $event[ $key ] );
                }
            }

            return $sanitized;
        }

        /**
         * Add an order note when the tracking status changes.
         *
         * @param int    $order_id        Order identifier.
         * @param string $previous_status Previous tracking status.
         * @param string $new_status      New tracking status.
         * @param array  $data            Stored order data snapshot.
         */
        protected function maybe_add_tracking_note( $order_id, $previous_status, $new_status, $data = array() ) {
            $previous_status = sanitize_text_field( (string) $previous_status );
            $new_status      = sanitize_text_field( (string) $new_status );

            if ( $new_status === $previous_status ) {
                return;
            }

            if ( '' === $new_status && '' === $previous_status ) {
                return;
            }

            if ( ! function_exists( 'wc_get_order' ) ) {
                return;
            }

            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                return;
            }

            $awb = '';

            if ( isset( $data['awb'] ) && $data['awb'] ) {
                $awb = (string) $data['awb'];
            } else {
                $meta_awb = get_post_meta( $order_id, self::META_AWB, true );
                $awb      = is_string( $meta_awb ) ? trim( $meta_awb ) : '';
            }

            $message = '';

            if ( '' === $new_status ) {
                if ( '' === $previous_status ) {
                    return;
                }

                if ( '' !== $awb ) {
                    $message = sprintf(
                        /* translators: 1: airway bill, 2: previous status */
                        __( 'DHL tracking status for AWB %1$s was cleared (previously "%2$s").', 'dokan-dhl-per-vendor' ),
                        $awb,
                        $previous_status
                    );
                } else {
                    $message = sprintf(
                        /* translators: %s: previous status */
                        __( 'DHL tracking status was cleared (previously "%s").', 'dokan-dhl-per-vendor' ),
                        $previous_status
                    );
                }
            } elseif ( '' === $previous_status ) {
                if ( '' !== $awb ) {
                    $message = sprintf(
                        /* translators: 1: airway bill, 2: new status */
                        __( 'DHL tracking status for AWB %1$s updated to "%2$s".', 'dokan-dhl-per-vendor' ),
                        $awb,
                        $new_status
                    );
                } else {
                    $message = sprintf(
                        /* translators: %s: new status */
                        __( 'DHL tracking status updated to "%s".', 'dokan-dhl-per-vendor' ),
                        $new_status
                    );
                }
            } else {
                if ( '' !== $awb ) {
                    $message = sprintf(
                        /* translators: 1: airway bill, 2: previous status, 3: new status */
                        __( 'DHL tracking status for AWB %1$s changed from "%2$s" to "%3$s".', 'dokan-dhl-per-vendor' ),
                        $awb,
                        $previous_status,
                        $new_status
                    );
                } else {
                    $message = sprintf(
                        /* translators: 1: previous status, 2: new status */
                        __( 'DHL tracking status changed from "%1$s" to "%2$s".', 'dokan-dhl-per-vendor' ),
                        $previous_status,
                        $new_status
                    );
                }
            }

            if ( '' === $message ) {
                return;
            }

            $order->add_order_note( $message );
        }

    }
}
