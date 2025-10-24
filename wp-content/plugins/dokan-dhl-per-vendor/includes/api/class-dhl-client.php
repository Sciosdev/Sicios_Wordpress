<?php
/**
 * DHL API client.
 *
 * @package Dokan_DHL_Per_Vendor\API
 */

if ( ! class_exists( 'Dokan_DHL_Client' ) ) {

    class Dokan_DHL_Client {

        const DEFAULT_API_BASE = 'https://express.api.dhl.com/mydhlapi/';

        const TOKEN_TRANSIENT_PREFIX = 'dokan_dhl_token_';

        /**
         * Credentials store.
         *
         * @var Dokan_DHL_Credentials_Store
         */
        protected $credentials_store;

        /**
         * Constructor.
         *
         * @param Dokan_DHL_Credentials_Store $credentials_store Credentials store instance.
         */
        public function __construct( Dokan_DHL_Credentials_Store $credentials_store ) {
            $this->credentials_store = $credentials_store;
        }

        /**
         * Create a shipment.
         *
         * @param int      $vendor_id Vendor ID.
         * @param WC_Order $order     Order instance.
         * @param array    $package   Package details.
         *
         * @return array|WP_Error
         */
        public function create_shipment( $vendor_id, $order, $package ) {
            if ( ! $order instanceof WC_Order ) {
                return new WP_Error( 'dokan_dhl_invalid_order', __( 'Invalid order provided.', 'dokan-dhl-per-vendor' ) );
            }

            $credentials = $this->credentials_store->get( $vendor_id );

            if ( empty( $credentials ) ) {
                return new WP_Error( 'dokan_dhl_missing_credentials', __( 'DHL credentials are not configured for this vendor.', 'dokan-dhl-per-vendor' ) );
            }

            if ( empty( $credentials['account'] ) ) {
                return new WP_Error( 'dokan_dhl_missing_credentials', __( 'The DHL account number is not configured for this vendor.', 'dokan-dhl-per-vendor' ) );
            }

            $token = $this->get_access_token( $vendor_id, $credentials );

            if ( is_wp_error( $token ) ) {
                return $token;
            }

            $api_base = $this->get_api_base( $vendor_id );
            $label_format = apply_filters( 'dokan_dhl_label_format', 'PDF', $vendor_id, $order, $package, $credentials );
            $label_format = strtoupper( preg_replace( '/[^A-Z]/', '', (string) $label_format ) );
            $label_format = $label_format ? $label_format : 'PDF';

            $headers = $this->get_request_headers( $vendor_id, $credentials, $token );

            $payload = $this->prepare_shipment_payload( $order, $package, $credentials, $label_format );

            $encoded_body = wp_json_encode( $payload );

            if ( false === $encoded_body ) {
                return new WP_Error( 'dokan_dhl_http', __( 'Unable to encode the DHL shipment payload.', 'dokan-dhl-per-vendor' ), array( 'status' => 500 ) );
            }

            /**
             * Filters the DHL create shipment payload before making the API request.
             *
             * @param array    $payload     Request payload.
             * @param WC_Order $order       Order instance.
             * @param array    $package     Package data.
             * @param array    $credentials Vendor credentials.
             */
            $payload = apply_filters( 'dokan_dhl_create_shipment_payload', $payload, $order, $package, $credentials );

            $url  = $this->build_endpoint( $api_base, 'shipments' );
            $args = array(
                'method'      => 'POST',
                'timeout'     => 20,
                'headers'     => $headers,
                'body'        => $encoded_body,
                'data_format' => 'body',
            );

            $response = $this->request_with_retry( $vendor_id, $url, $args, $credentials );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );

            if ( 401 === $code ) {
                $this->clear_access_token( $vendor_id );
                $token = $this->get_access_token( $vendor_id, $credentials, true );

                if ( is_wp_error( $token ) ) {
                    return $token;
                }

                $headers['Authorization'] = 'Bearer ' . $token;
                $args['headers']          = $headers;

                $response = $this->request_with_retry( $vendor_id, $url, $args, $credentials );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }

                $code = wp_remote_retrieve_response_code( $response );
            }

            if ( $code < 200 || $code >= 300 ) {
                return $this->error_from_response( $response );
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( null === $body ) {
                return new WP_Error( 'dokan_dhl_http', __( 'DHL returned an unexpected response when creating the shipment.', 'dokan-dhl-per-vendor' ), array( 'status' => $code ) );
            }

            $shipment = $this->parse_shipment_response( $body, $package, $label_format );

            if ( is_wp_error( $shipment ) ) {
                return $shipment;
            }

            $shipment['meta'] = array(
                'api_base'  => $api_base,
                'status'    => $code,
                'requestId' => wp_remote_retrieve_header( $response, 'x-message-id' ),
            );

            return $shipment;
        }

        /**
         * Retrieve tracking information.
         *
         * @param int    $vendor_id Vendor ID.
         * @param string $awb       Airway bill number.
         *
         * @return array|WP_Error
         */
        public function get_tracking( $vendor_id, $awb ) {
            $awb = trim( (string) $awb );

            if ( '' === $awb ) {
                return new WP_Error( 'dokan_dhl_missing_awb', __( 'Tracking number is missing.', 'dokan-dhl-per-vendor' ) );
            }

            $credentials = $this->credentials_store->get( $vendor_id );

            if ( empty( $credentials ) ) {
                return new WP_Error( 'dokan_dhl_missing_credentials', __( 'DHL credentials are not configured for this vendor.', 'dokan-dhl-per-vendor' ) );
            }

            $token = $this->get_access_token( $vendor_id, $credentials );

            if ( is_wp_error( $token ) ) {
                return $token;
            }

            $api_base = $this->get_api_base( $vendor_id );
            $headers  = $this->get_request_headers( $vendor_id, $credentials, $token );
            $url      = add_query_arg( array( 'trackingNumber' => $awb ), $this->build_endpoint( $api_base, 'track/shipments' ) );

            $args = array(
                'method'  => 'GET',
                'timeout' => 20,
                'headers' => $headers,
            );

            $response = $this->request_with_retry( $vendor_id, $url, $args, $credentials );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );

            if ( 401 === $code ) {
                $this->clear_access_token( $vendor_id );
                $token = $this->get_access_token( $vendor_id, $credentials, true );

                if ( is_wp_error( $token ) ) {
                    return $token;
                }

                $headers['Authorization'] = 'Bearer ' . $token;
                $args['headers']          = $headers;

                $response = $this->request_with_retry( $vendor_id, $url, $args, $credentials );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }

                $code = wp_remote_retrieve_response_code( $response );
            }

            if ( $code < 200 || $code >= 300 ) {
                return $this->error_from_response( $response );
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( null === $body ) {
                return new WP_Error( 'dokan_dhl_http', __( 'DHL returned an unexpected response when retrieving the tracking status.', 'dokan-dhl-per-vendor' ), array( 'status' => $code ) );
            }

            $tracking = $this->parse_tracking_response( $body, $awb );

            if ( is_wp_error( $tracking ) ) {
                return $tracking;
            }

            $tracking['meta'] = array(
                'api_base'  => $api_base,
                'status'    => $code,
                'requestId' => wp_remote_retrieve_header( $response, 'x-message-id' ),
            );

            return $tracking;
        }

        /**
         * Test the API connection for a vendor without creating shipments.
         *
         * @param int $vendor_id Vendor identifier.
         *
         * @return true|WP_Error
         */
        public function test_connection( $vendor_id ) {
            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 ) {
                return new WP_Error( 'dokan_dhl_missing_vendor', __( 'Unable to determine which vendor to test.', 'dokan-dhl-per-vendor' ) );
            }

            $credentials = $this->credentials_store->get( $vendor_id );

            if ( empty( $credentials ) ) {
                return new WP_Error( 'dokan_dhl_missing_credentials', __( 'DHL credentials are not configured for this vendor.', 'dokan-dhl-per-vendor' ) );
            }

            $token = $this->get_access_token( $vendor_id, $credentials );

            if ( is_wp_error( $token ) ) {
                return $token;
            }

            $api_base = $this->get_api_base( $vendor_id );
            $headers  = $this->get_request_headers( $vendor_id, $credentials, $token );
            $url      = $this->build_endpoint( $api_base, 'monitoring/ping' );
            $args     = array(
                'method'  => 'GET',
                'timeout' => 10,
                'headers' => $headers,
            );

            $response = $this->request_with_retry( $vendor_id, $url, $args, $credentials );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );

            if ( 401 === $code ) {
                $this->clear_access_token( $vendor_id );
                $token = $this->get_access_token( $vendor_id, $credentials, true );

                if ( is_wp_error( $token ) ) {
                    return $token;
                }

                $headers['Authorization'] = 'Bearer ' . $token;
                $args['headers']          = $headers;

                $response = $this->request_with_retry( $vendor_id, $url, $args, $credentials );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }

                $code = wp_remote_retrieve_response_code( $response );
            }

            if ( $code >= 200 && $code < 300 ) {
                return true;
            }

            if ( in_array( $code, array( 404, 405 ), true ) ) {
                // The endpoint may not be available for all accounts, but authentication succeeded.
                return true;
            }

            return $this->error_from_response( $response );
        }

        /**
         * Retrieve the API base URL.
         *
         * @param int $vendor_id Vendor ID.
         *
         * @return string
         */
        protected function get_api_base( $vendor_id ) {
            $base = apply_filters( 'dokan_dhl_api_base', self::DEFAULT_API_BASE, $vendor_id );

            if ( ! is_string( $base ) || '' === $base ) {
                $base = self::DEFAULT_API_BASE;
            }

            return $base;
        }

        /**
         * Build default request headers.
         *
         * @param int    $vendor_id    Vendor ID.
         * @param array  $credentials  Vendor credentials.
         * @param string $token        Bearer token.
         *
         * @return array
         */
        protected function get_request_headers( $vendor_id, $credentials, $token = '' ) {
            $headers = array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            );

            if ( $token ) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            return apply_filters( 'dokan_dhl_api_headers', $headers, $vendor_id, $credentials );
        }

        /**
         * Prepare a shipment payload for DHL.
         *
         * @param WC_Order $order        Order instance.
         * @param array    $package      Package details.
         * @param array    $credentials  Vendor credentials.
         * @param string   $label_format Requested label format.
         *
         * @return array
         */
        protected function prepare_shipment_payload( $order, $package, $credentials, $label_format ) {
            $shipper_country  = strtoupper( isset( $credentials['country'] ) ? $credentials['country'] : '' );
            $receiver_country = strtoupper( $order->get_shipping_country() );
            $is_international = $shipper_country && $receiver_country && $shipper_country !== $receiver_country;

            $package_weight = isset( $package['weight'] ) ? (float) $package['weight'] : 0;
            $package_length = isset( $package['length'] ) ? (float) $package['length'] : 0;
            $package_width  = isset( $package['width'] ) ? (float) $package['width'] : 0;
            $package_height = isset( $package['height'] ) ? (float) $package['height'] : 0;

            $reference_value = sanitize_text_field( (string) $order->get_order_number() );

            $packages = array(
                array(
                    'weight'     => array(
                        'value' => max( 0, $package_weight ),
                        'unit'  => 'KG',
                    ),
                    'dimensions' => array(
                        'length' => max( 0, $package_length ),
                        'width'  => max( 0, $package_width ),
                        'height' => max( 0, $package_height ),
                        'unit'   => 'CM',
                    ),
                    'customerReferences' => array(
                        array(
                            'typeCode' => 'CU',
                            'value'    => $reference_value,
                        ),
                    ),
                ),
            );

            $shipper_details = array(
                'name'         => isset( $credentials['shipper_name'] ) ? $credentials['shipper_name'] : '',
                'companyName'  => isset( $credentials['company'] ) ? $credentials['company'] : '',
                'addressLine1' => isset( $credentials['address1'] ) ? $credentials['address1'] : '',
                'addressLine2' => isset( $credentials['address2'] ) ? $credentials['address2'] : '',
                'postalCode'   => isset( $credentials['postcode'] ) ? $credentials['postcode'] : '',
                'cityName'     => isset( $credentials['city'] ) ? $credentials['city'] : '',
                'countyName'   => isset( $credentials['state'] ) ? $credentials['state'] : '',
                'countryCode'  => $shipper_country,
                'emailAddress' => isset( $credentials['email'] ) ? $credentials['email'] : '',
                'phoneNumber'  => isset( $credentials['phone'] ) ? $credentials['phone'] : '',
            );

            $receiver_details = array(
                'name'         => $order->get_formatted_shipping_full_name(),
                'companyName'  => $order->get_shipping_company(),
                'addressLine1' => $order->get_shipping_address_1(),
                'addressLine2' => $order->get_shipping_address_2(),
                'postalCode'   => $order->get_shipping_postcode(),
                'cityName'     => $order->get_shipping_city(),
                'countyName'   => $order->get_shipping_state(),
                'countryCode'  => $receiver_country,
                'emailAddress' => $order->get_billing_email(),
                'phoneNumber'  => $order->get_billing_phone(),
            );

            $export_items = array();

            foreach ( $order->get_items() as $item ) {
                if ( ! $item instanceof WC_Order_Item_Product ) {
                    continue;
                }

                $product      = $item->get_product();
                $hs_code      = $product ? $product->get_meta( 'hs_code', true ) : '';
                $country_orig = $product ? $product->get_meta( 'country_of_origin', true ) : '';

                $name_fragment = $item->get_name();

                if ( function_exists( 'mb_substr' ) ) {
                    $name_fragment = mb_substr( $name_fragment, 0, 45 );
                } else {
                    $name_fragment = substr( $name_fragment, 0, 45 );
                }

                $line_item = array(
                    'description' => $name_fragment,
                    'quantity'    => (int) $item->get_quantity(),
                    'value'       => max( 0, (float) $item->get_total() ),
                    'weight'      => array(
                        'unit'  => 'KG',
                        'value' => $packages[0]['weight']['value'] / max( 1, $item->get_quantity() ),
                    ),
                );

                if ( $country_orig ) {
                    $line_item['manufacturerCountry'] = strtoupper( $country_orig );
                }

                if ( $hs_code ) {
                    $line_item['harmonizedSystemCode'] = preg_replace( '/[^0-9A-Za-z]/', '', $hs_code );
                }

                $export_items[] = $line_item;
            }

            $account_number = trim( (string) $credentials['account'] );
            $incoterm       = isset( $credentials['incoterm'] ) ? strtoupper( trim( $credentials['incoterm'] ) ) : 'DAP';
            $incoterm       = $incoterm ? $incoterm : 'DAP';

            $payload = array(
                'plannedShippingDateAndTime' => gmdate( 'Y-m-d\TH:i:s\Z' ),
                'pickup'                     => array(
                    'isRequested' => false,
                ),
                'productCode'                => isset( $credentials['service'] ) && $credentials['service'] ? $credentials['service'] : 'P',
                'accounts'                   => array(
                    array(
                        'typeCode' => 'shipper',
                        'number'   => $account_number,
                    ),
                ),
                'customerDetails'            => array(
                    'shipperDetails'  => $shipper_details,
                    'receiverDetails' => $receiver_details,
                ),
                'content'                    => array(
                    'isCustomsDeclarable' => $is_international,
                    'declaredValue'       => max( 0, (float) $order->get_total() ),
                    'declaredCurrency'    => $order->get_currency(),
                    'packages'            => $packages,
                ),
                'outputImageProperties'      => array(
                    'printerDPI'     => 300,
                    'encodingFormat' => $label_format,
                ),
                'references'                 => array(
                    array(
                        'typeCode' => 'CU',
                        'value'    => $reference_value,
                    ),
                ),
            );

            if ( $is_international ) {
                if ( empty( $export_items ) ) {
                    $export_items[] = array(
                        'description' => __( 'Merchandise', 'dokan-dhl-per-vendor' ),
                        'quantity'    => 1,
                        'value'       => max( 0, (float) $order->get_total() ),
                        'weight'      => array(
                            'unit'  => 'KG',
                            'value' => $packages[0]['weight']['value'],
                        ),
                    );
                }

                $payload['content']['exportDeclaration'] = array(
                    'lineItems'   => $export_items,
                    'invoice'     => array(
                        'number' => (string) $order->get_order_number(),
                        'date'   => gmdate( 'Y-m-d' ),
                    ),
                    'termsOfTrade' => $incoterm,
                );
            }

            return $payload;
        }

        /**
         * Build a fully qualified endpoint URL.
         *
         * @param string $base API base.
         * @param string $path Endpoint path.
         *
         * @return string
         */
        protected function build_endpoint( $base, $path ) {
            $base = trailingslashit( $base );
            $path = ltrim( $path, '/' );

            return $base . $path;
        }

        /**
         * Parse the shipment response into the expected structure.
         *
         * @param array  $body         Response body.
         * @param array  $package      Package request data.
         * @param string $label_format Requested label format.
         *
         * @return array|WP_Error
         */
        protected function parse_shipment_response( $body, $package, $label_format ) {
            $awb = '';

            if ( isset( $body['shipmentTrackingNumber'] ) && is_string( $body['shipmentTrackingNumber'] ) ) {
                $awb = $body['shipmentTrackingNumber'];
            } elseif ( isset( $body['shipmentIdentificationNumber'] ) && is_string( $body['shipmentIdentificationNumber'] ) ) {
                $awb = $body['shipmentIdentificationNumber'];
            }

            if ( '' === $awb && isset( $body['packages'] ) && is_array( $body['packages'] ) ) {
                foreach ( $body['packages'] as $package_data ) {
                    if ( isset( $package_data['trackingNumber'] ) && is_string( $package_data['trackingNumber'] ) ) {
                        $awb = $package_data['trackingNumber'];
                        break;
                    }
                }
            }

            $awb = trim( (string) $awb );

            if ( '' === $awb ) {
                return new WP_Error( 'dokan_dhl_http', __( 'DHL did not return a tracking number for this shipment.', 'dokan-dhl-per-vendor' ), array( 'status' => 200 ) );
            }

            $label = $this->extract_label_document( $body, $label_format );

            if ( is_wp_error( $label ) ) {
                return $label;
            }

            $tracking = $this->parse_tracking_response( $body, $awb );

            if ( is_wp_error( $tracking ) ) {
                $tracking = array(
                    'status' => 'created',
                    'events' => array(),
                );
            }

            $package_summary = array(
                'weight' => isset( $package['weight'] ) ? $package['weight'] : '',
                'length' => isset( $package['length'] ) ? $package['length'] : '',
                'width'  => isset( $package['width'] ) ? $package['width'] : '',
                'height' => isset( $package['height'] ) ? $package['height'] : '',
                'weight_unit' => 'KG',
                'dimension_unit' => 'CM',
            );

            return array(
                'awb'      => $awb,
                'label'    => $label,
                'package'  => $package_summary,
                'tracking' => $tracking,
            );
        }

        /**
         * Parse tracking response data.
         *
         * @param array  $body Response body.
         * @param string $awb  Tracking number.
         *
         * @return array|WP_Error
         */
        protected function parse_tracking_response( $body, $awb ) {
            $shipments = array();

            if ( isset( $body['shipments'] ) && is_array( $body['shipments'] ) ) {
                $shipments = $body['shipments'];
            } elseif ( isset( $body['shipment'] ) && is_array( $body['shipment'] ) ) {
                $shipments = array( $body['shipment'] );
            } else {
                $shipments = array( $body );
            }

            $shipment_data = array();

            foreach ( $shipments as $shipment ) {
                $number = isset( $shipment['id'] ) ? $shipment['id'] : ( isset( $shipment['awbNumber'] ) ? $shipment['awbNumber'] : '' );
                $number = $number ? $number : ( isset( $shipment['trackingNumber'] ) ? $shipment['trackingNumber'] : '' );
                $number = trim( (string) $number );

                if ( '' === $number || strcasecmp( $number, $awb ) === 0 ) {
                    $shipment_data = $shipment;
                    break;
                }
            }

            if ( empty( $shipment_data ) && ! empty( $shipments ) ) {
                $first = reset( $shipments );
                if ( is_array( $first ) ) {
                    $shipment_data = $first;
                }
            }

            if ( empty( $shipment_data ) || ! is_array( $shipment_data ) ) {
                return new WP_Error( 'dokan_dhl_http', __( 'DHL did not return tracking data for this shipment.', 'dokan-dhl-per-vendor' ), array( 'status' => 200 ) );
            }

            $status = '';

            if ( isset( $shipment_data['status'] ) && is_string( $shipment_data['status'] ) ) {
                $status = $shipment_data['status'];
            } elseif ( isset( $shipment_data['status']['status'] ) && is_string( $shipment_data['status']['status'] ) ) {
                $status = $shipment_data['status']['status'];
            } elseif ( isset( $shipment_data['statusCode'] ) && is_string( $shipment_data['statusCode'] ) ) {
                $status = $shipment_data['statusCode'];
            }

            $events = array();

            if ( isset( $shipment_data['events'] ) && is_array( $shipment_data['events'] ) ) {
                foreach ( $shipment_data['events'] as $event ) {
                    $description = '';

                    if ( isset( $event['description'] ) && is_string( $event['description'] ) ) {
                        $description = $event['description'];
                    } elseif ( isset( $event['description']['value'] ) && is_string( $event['description']['value'] ) ) {
                        $description = $event['description']['value'];
                    }

                    $location = '';

                    if ( isset( $event['location']['address'] ) && is_array( $event['location']['address'] ) ) {
                        $address = $event['location']['address'];
                        $parts   = array();

                        foreach ( array( 'addressLocality', 'countyCode', 'countryCode' ) as $field ) {
                            if ( isset( $address[ $field ] ) && $address[ $field ] ) {
                                $parts[] = $address[ $field ];
                            }
                        }

                        $location = implode( ', ', $parts );
                    } elseif ( isset( $event['location'] ) && is_string( $event['location'] ) ) {
                        $location = $event['location'];
                    }

                    $timestamp = '';

                    if ( isset( $event['timestamp'] ) && is_string( $event['timestamp'] ) ) {
                        $timestamp = $event['timestamp'];
                    } elseif ( isset( $event['date'] ) && isset( $event['time'] ) ) {
                        $timestamp = trim( $event['date'] . ' ' . $event['time'] );
                    }

                    $events[] = array(
                        'description' => $description,
                        'location'    => $location,
                        'timestamp'   => $timestamp,
                    );
                }
            }

            return array(
                'awb'    => $awb,
                'status' => $status ? $status : 'created',
                'events' => $events,
            );
        }

        /**
         * Extract the label document from the response.
         *
         * @param array  $body         Response body.
         * @param string $label_format Requested label format.
         *
         * @return array|WP_Error
         */
        protected function extract_label_document( $body, $label_format ) {
            if ( isset( $body['documents'] ) && is_array( $body['documents'] ) ) {
                foreach ( $body['documents'] as $document ) {
                    if ( isset( $document['content'] ) && is_string( $document['content'] ) ) {
                        $extension = isset( $document['typeCode'] ) && is_string( $document['typeCode'] ) ? sanitize_key( strtolower( $document['typeCode'] ) ) : strtolower( $label_format );
                        $extension = $extension ? $extension : strtolower( $label_format );

                        return array(
                            'content'        => $document['content'],
                            'format'         => $label_format,
                            'mime_type'      => $this->mime_type_from_extension( $extension ),
                            'file_extension' => $extension,
                        );
                    }
                }
            }

            if ( isset( $body['labelImage'] ) && is_string( $body['labelImage'] ) ) {
                return array(
                    'content'        => $body['labelImage'],
                    'format'         => $label_format,
                    'mime_type'      => $this->mime_type_from_extension( strtolower( $label_format ) ),
                    'file_extension' => strtolower( $label_format ),
                );
            }

            return new WP_Error( 'dokan_dhl_http', __( 'DHL did not include a label in the response.', 'dokan-dhl-per-vendor' ), array( 'status' => 200 ) );
        }

        /**
         * Determine mime type for a file extension.
         *
         * @param string $extension File extension.
         *
         * @return string
         */
        protected function mime_type_from_extension( $extension ) {
            $extension = strtolower( $extension );

            switch ( $extension ) {
                case 'pdf':
                    return 'application/pdf';
                case 'zpl':
                    return 'application/zpl';
                case 'png':
                    return 'image/png';
                case 'jpeg':
                case 'jpg':
                    return 'image/jpeg';
                default:
                    return 'application/octet-stream';
            }
        }

        /**
         * Execute an HTTP request with retry support.
         *
         * @param int    $vendor_id   Vendor identifier.
         * @param string $url         Request URL.
         * @param array  $args        Request arguments.
         * @param array  $credentials Vendor credentials.
         *
         * @return array|WP_Error
         */
        protected function request_with_retry( $vendor_id, $url, $args, $credentials ) {
            $attempts    = 0;
            $max_attempt = 2;
            $last_error  = null;

            while ( $attempts < $max_attempt ) {
                $request_args = apply_filters( 'dokan_dhl_request_args', $args, $vendor_id, $url, $credentials, isset( $args['method'] ) ? $args['method'] : 'GET' );

                $response = wp_remote_request( $url, $request_args );

                if ( is_wp_error( $response ) ) {
                    $message    = $response->get_error_message();
                    $message    = $message ? wp_strip_all_tags( $message ) : __( 'Unable to communicate with DHL.', 'dokan-dhl-per-vendor' );
                    $last_error = new WP_Error( 'dokan_dhl_http', $message, array( 'status' => 500 ) );
                    break;
                }

                $code = wp_remote_retrieve_response_code( $response );

                if ( $code >= 200 && $code < 300 ) {
                    return $response;
                }

                if ( $attempts + 1 >= $max_attempt ) {
                    return $response;
                }

                if ( 429 !== $code && ( $code < 500 || $code >= 600 ) ) {
                    return $response;
                }

                $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
                $sleep_for   = $this->parse_retry_after_header( $retry_after );

                if ( ! $sleep_for ) {
                    $sleep_for = pow( 2, $attempts );
                }

                $sleep_for = (int) max( 1, min( 60, $sleep_for ) );

                if ( function_exists( 'wp_sleep' ) ) {
                    wp_sleep( $sleep_for );
                } else {
                    sleep( $sleep_for );
                }

                $attempts++;
            }

            if ( $last_error ) {
                return $last_error;
            }

            return new WP_Error( 'dokan_dhl_http', __( 'Unexpected error communicating with DHL.', 'dokan-dhl-per-vendor' ), array( 'status' => 500 ) );
        }

        /**
         * Parse Retry-After header value.
         *
         * @param string $header Header value.
         *
         * @return int
         */
        protected function parse_retry_after_header( $header ) {
            if ( ! $header ) {
                return 0;
            }

            if ( is_numeric( $header ) ) {
                return (int) $header;
            }

            $timestamp = strtotime( $header );

            if ( false === $timestamp ) {
                return 0;
            }

            $diff = $timestamp - time();

            return $diff > 0 ? $diff : 0;
        }

        /**
         * Build a WP_Error from an HTTP response.
         *
         * @param array $response HTTP response array.
         *
         * @return WP_Error
         */
        protected function error_from_response( $response ) {
            $code    = wp_remote_retrieve_response_code( $response );
            $message = __( 'DHL API request failed.', 'dokan-dhl-per-vendor' );

            $body          = wp_remote_retrieve_body( $response );
            $error_message = $this->extract_error_message( $body );

            if ( $error_message ) {
                $message = sprintf( __( 'DHL API request failed: %s', 'dokan-dhl-per-vendor' ), $error_message );
            }

            return new WP_Error( 'dokan_dhl_http', $message, array( 'status' => $code ? $code : 500 ) );
        }

        /**
         * Extract an error message from the response body.
         *
         * @param string $body Response body.
         *
         * @return string
         */
        protected function extract_error_message( $body ) {
            if ( ! $body ) {
                return '';
            }

            $decoded = json_decode( $body, true );

            if ( is_array( $decoded ) ) {
                $candidates = array(
                    'detail',
                    'message',
                    'title',
                    'description',
                    'error',
                );

                foreach ( $candidates as $candidate ) {
                    if ( isset( $decoded[ $candidate ] ) && is_string( $decoded[ $candidate ] ) ) {
                        return wp_strip_all_tags( $decoded[ $candidate ] );
                    }
                }

                if ( isset( $decoded['errors'] ) && is_array( $decoded['errors'] ) ) {
                    $first = reset( $decoded['errors'] );

                    if ( is_array( $first ) ) {
                        foreach ( $candidates as $candidate ) {
                            if ( isset( $first[ $candidate ] ) && is_string( $first[ $candidate ] ) ) {
                                return wp_strip_all_tags( $first[ $candidate ] );
                            }
                        }
                    } elseif ( is_string( $first ) ) {
                        return wp_strip_all_tags( $first );
                    }
                }
            }

            return wp_strip_all_tags( $body );
        }

        /**
         * Retrieve a bearer token for the vendor.
         *
         * @param int   $vendor_id   Vendor identifier.
         * @param array $credentials Vendor credentials.
         * @param bool  $force       Force refresh.
         *
         * @return string|WP_Error
         */
        protected function get_access_token( $vendor_id, $credentials, $force = false ) {
            $cache_key = $this->get_token_transient_key( $vendor_id );

            if ( ! $force ) {
                $cached = get_transient( $cache_key );

                if ( is_string( $cached ) && '' !== $cached ) {
                    return $cached;
                }
            }

            $client_id     = isset( $credentials['api_key'] ) ? $credentials['api_key'] : '';
            $client_secret = isset( $credentials['api_secret'] ) ? $credentials['api_secret'] : '';

            if ( '' === $client_id || '' === $client_secret ) {
                return new WP_Error( 'dokan_dhl_missing_credentials', __( 'DHL credentials are not configured for this vendor.', 'dokan-dhl-per-vendor' ) );
            }

            $api_base = $this->get_api_base( $vendor_id );
            $url      = $this->build_endpoint( $api_base, 'oauth/token' );

            $headers = array(
                'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => 'application/json',
            );

            $args = array(
                'method'      => 'POST',
                'timeout'     => 20,
                'headers'     => $headers,
                'body'        => array(
                    'grant_type' => 'client_credentials',
                ),
                'data_format' => 'body',
            );

            $response = wp_remote_post( $url, $args );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'dokan_dhl_http', __( 'Unable to authenticate with DHL. Please verify your credentials.', 'dokan-dhl-per-vendor' ), array( 'status' => 500 ) );
            }

            $code = wp_remote_retrieve_response_code( $response );

            if ( $code < 200 || $code >= 300 ) {
                return $this->error_from_response( $response );
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! isset( $body['access_token'] ) || ! is_string( $body['access_token'] ) ) {
                return new WP_Error( 'dokan_dhl_http', __( 'DHL did not return an access token.', 'dokan-dhl-per-vendor' ), array( 'status' => $code ) );
            }

            $token      = $body['access_token'];
            $expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3500;
            $ttl        = max( 60, $expires_in - 60 );

            set_transient( $cache_key, $token, $ttl );

            return $token;
        }

        /**
         * Clear the cached access token for a vendor.
         *
         * @param int $vendor_id Vendor identifier.
         */
        protected function clear_access_token( $vendor_id ) {
            delete_transient( $this->get_token_transient_key( $vendor_id ) );
        }

        /**
         * Build the transient key for the vendor token cache.
         *
         * @param int $vendor_id Vendor identifier.
         *
         * @return string
         */
        protected function get_token_transient_key( $vendor_id ) {
            return self::TOKEN_TRANSIENT_PREFIX . absint( $vendor_id );
        }
    }
}
