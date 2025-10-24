<?php
/**
 * Plugin Name: SCIOS Envíos DHL Multivendedor
 * Description: Permite a cada vendedor de Dokan gestionar sus propias etiquetas y envíos DHL de forma independiente. Desarrollado por SCIOS.
 * Version: 0.1.0
 * Author: SCIOS
 * Author URI: https://scios.club
 * Text Domain: scios-dhl-per-vendor
 * Domain Path: /languages
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'DOKAN_DHL_PER_VENDOR_FILE' ) ) {
    define( 'DOKAN_DHL_PER_VENDOR_FILE', __FILE__ );
}


if ( ! defined( 'DOKAN_DHL_PER_VENDOR_VERSION' ) ) {
    define( 'DOKAN_DHL_PER_VENDOR_VERSION', '0.1.0' );
}


if ( ! defined( 'DOKAN_DHL_PER_VENDOR_PATH' ) ) {
    define( 'DOKAN_DHL_PER_VENDOR_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DOKAN_DHL_PER_VENDOR_URL' ) ) {
    define( 'DOKAN_DHL_PER_VENDOR_URL', plugin_dir_url( __FILE__ ) );
}

require_once DOKAN_DHL_PER_VENDOR_PATH . 'includes/class-plugin.php';

register_activation_hook( DOKAN_DHL_PER_VENDOR_FILE, array( 'Dokan_DHL_Plugin', 'activate' ) );
register_deactivation_hook( DOKAN_DHL_PER_VENDOR_FILE, array( 'Dokan_DHL_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Dokan_DHL_Plugin', 'instance' ) );
