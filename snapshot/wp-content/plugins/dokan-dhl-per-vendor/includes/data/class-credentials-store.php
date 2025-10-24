<?php
/**
 * Vendor credentials storage.
 *
 * @package Dokan_DHL_Per_Vendor\Data
 */

if ( ! class_exists( 'Dokan_DHL_Credentials_Store' ) ) {

    class Dokan_DHL_Credentials_Store {

        const META_KEY_DATA = '_dokan_dhl_credentials';
        const META_KEY_IV   = '_dokan_dhl_credentials_iv';

        /**
         * Retrieve credentials for a vendor (decrypted array).
         *
         * @param int $vendor_id Vendor ID.
         * @return array
         */
        public function get( $vendor_id ) {
            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 ) {
                return array();
            }

            if ( ! function_exists( 'openssl_decrypt' ) ) {
                return array();
            }

            $encrypted = get_user_meta( $vendor_id, self::META_KEY_DATA, true );
            $iv        = get_user_meta( $vendor_id, self::META_KEY_IV, true );

            if ( empty( $encrypted ) || empty( $iv ) ) {
                return array();
            }

            $ciphertext = base64_decode( (string) $encrypted, true );
            $iv         = base64_decode( (string) $iv, true );

            if ( false === $ciphertext || false === $iv || 16 !== strlen( $iv ) ) {
                return array();
            }

            $key = $this->get_encryption_key();

            if ( empty( $key ) ) {
                return array();
            }

            $plaintext = openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

            if ( false === $plaintext ) {
                return array();
            }

            $data = json_decode( $plaintext, true );

            if ( ! is_array( $data ) ) {
                return array();
            }

            return $data;
        }

        /**
         * Back-compat alias.
         *
         * @param int $vendor_id
         * @return array
         */
        public function get_credentials( $vendor_id ) {
            return $this->get( $vendor_id );
        }

        /**
         * Persist vendor credentials (encrypted).
         *
         * @param int   $vendor_id Vendor ID.
         * @param array $credentials Credential data.
         * @return true|WP_Error
         */
        public function save( $vendor_id, $credentials ) {
            $vendor_id = absint( $vendor_id );

            if ( $vendor_id <= 0 ) {
                return new WP_Error( 'dokan_dhl_invalid_vendor', __( 'Invalid vendor.', 'dokan-dhl-per-vendor' ) );
            }

            if ( ! function_exists( 'openssl_encrypt' ) ) {
                return new WP_Error( 'dokan_dhl_missing_openssl', __( 'The OpenSSL PHP extension is required to store DHL credentials securely.', 'dokan-dhl-per-vendor' ) );
            }

            if ( ! is_array( $credentials ) ) {
                $credentials = array();
            }


            // Remove empty values; if all empty, delete meta.
            $filtered = array_filter(
                $credentials,
                static function ( $value ) {
                    return '' !== $value && null !== $value;
                }
            );

            if ( empty( $filtered ) ) {
                delete_user_meta( $vendor_id, self::META_KEY_DATA );
                delete_user_meta( $vendor_id, self::META_KEY_IV );

                return true;
            }

            $payload = wp_json_encode( $credentials );

            if ( false === $payload ) {
                return new WP_Error( 'dokan_dhl_encoding_failed', __( 'Could not encode credentials for storage.', 'dokan-dhl-per-vendor' ) );
            }

            $key = $this->get_encryption_key();

            if ( empty( $key ) ) {
                return new WP_Error( 'dokan_dhl_missing_key', __( 'Encryption key could not be generated.', 'dokan-dhl-per-vendor' ) );
            }

            try {
                $iv = random_bytes( 16 );
            } catch ( Exception $exception ) {
                $iv = function_exists( 'openssl_random_pseudo_bytes' ) ? openssl_random_pseudo_bytes( 16 ) : '';
            }

            if ( empty( $iv ) || 16 !== strlen( $iv ) ) {
                return new WP_Error( 'dokan_dhl_missing_iv', __( 'Encryption vector could not be generated.', 'dokan-dhl-per-vendor' ) );
            }

            $ciphertext = openssl_encrypt( $payload, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

            if ( false === $ciphertext ) {
                return new WP_Error( 'dokan_dhl_encryption_failed', __( 'Unable to encrypt credentials.', 'dokan-dhl-per-vendor' ) );
            }

            update_user_meta( $vendor_id, self::META_KEY_DATA, base64_encode( $ciphertext ) );
            update_user_meta( $vendor_id, self::META_KEY_IV, base64_encode( $iv ) );

            return true;
        }

        /**
         * Back-compat alias.
         *
         * @param int   $vendor_id Vendor ID.
         * @param array $credentials Credential data.
         * @return true|WP_Error
         */
        public function set_credentials( $vendor_id, $credentials ) {
            return $this->save( $vendor_id, $credentials );
        }

        /**
         * Derive encryption key from WP salts.
         *
         * @return string Raw 32-byte key or empty string on failure.
         */
        protected function get_encryption_key() {
            $key_material = '';

            if ( defined( 'AUTH_KEY' ) ) {
                $key_material .= AUTH_KEY;
            }

            if ( defined( 'SECURE_AUTH_KEY' ) ) {
                $key_material .= SECURE_AUTH_KEY;
            }

            if ( '' === $key_material && function_exists( 'wp_salt' ) ) {
                $key_material = wp_salt( 'dokan_dhl_credentials' );
            }

            if ( '' === $key_material ) {
                return '';
            }

            return hash( 'sha256', $key_material, true );
        }
    }
}
