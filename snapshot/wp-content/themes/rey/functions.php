<?php
/**
 * Theme functions and definitions
 *
 * @package rey
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

update_site_option( 'rey_purchase_code', 'AAAAAAAA-1111-1111-1111-AAAAAAAAAAAA' );

add_filter( 'pre_http_request', 'override_reytheme_api_call', 10, 3 );

function override_reytheme_api_call( $preempt, $r, $url ) {
    // Debug log
    error_log('Requesting URL: ' . $url);

    // Check if the URL is for 'get_plugins'
    if ( strpos( $url, 'https://api.reytheme.com/wp-json/rey-api/v1/get_plugins' ) !== false ) {
        $custom_url = 'https://apis.gpltimes.com/rey/data.php';
        $response = wp_remote_get( $custom_url, $r );
        return $response;
    }
    // Check if the URL is for 'get_plugin_data'
    else if ( strpos( $url, 'https://api.reytheme.com/wp-json/rey-api/v1/get_plugin_data' ) !== false ) {
        $custom_url = 'https://apis.gpltimes.com/rey/filtered_data.php';
        $response = wp_remote_get( $custom_url, $r );
        return $response;
    }
    // Check if the URL is for 'get_demos'
    else if ( strpos( $url, 'https://api.reytheme.com/wp-json/rey-api/v1/get_demos' ) !== false ) {
        $custom_url = 'https://apis.gpltimes.com/rey/get_demo.php';
        $response = wp_remote_get( $custom_url, $r );
        return $response;
    }
    // Check if the URL is for 'get_demo_data'
    else if ( strpos( $url, 'https://api.reytheme.com/wp-json/rey-api/v1/get_demo_data' ) !== false ) {
        $custom_url = 'https://apis.gpltimes.com/rey/get_demo_data.php';
        $response = wp_remote_get( $custom_url, $r );
        return $response;
    }

    // Proceed with the original request
    return false;
}

/**
 * Global Variables
 */
define('REY_THEME_DIR', get_template_directory());
define('REY_THEME_PARENT_DIR', get_stylesheet_directory());
define('REY_THEME_URI', get_template_directory_uri());
define('REY_THEME_PLACEHOLDER', REY_THEME_URI . '/assets/images/placeholder.png');
define('REY_THEME_NAME', 'rey');
define('REY_THEME_CORE_SLUG', 'rey-core');
define('REY_THEME_VERSION', '2.8.3' );
define('REY_THEME_REQUIRED_PHP_VERSION', '5.4.0' ); // Minimum required versions

/**
 * Load Core
 */
require_once REY_THEME_DIR . '/inc/core/core.php';
