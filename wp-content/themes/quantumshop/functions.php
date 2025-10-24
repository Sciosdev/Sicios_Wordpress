<?php


/**
 * Quantumshop functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @subpackage Quantumshop
 * @since Quantumshop 1.0
 */

function quantumshop_theme_scripts() {
    // Enqueue theme stylesheet for the front-end.
    wp_enqueue_style( 'quantumshop-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_style( 'quantumshop-fontawesome', get_template_directory_uri() . '/assets/font-awesome/css/all.css', array(), '5.15.3' );
	wp_enqueue_script('quantumshop-main-script', get_template_directory_uri() . '/assets/js/script.js', array('jquery'), '1.0.0', true);
	wp_enqueue_script('quantumshop-jquery-sticky', get_template_directory_uri() . '/assets/js/jquery-sticky.js', array('jquery') );    
}

add_action( 'wp_enqueue_scripts', 'quantumshop_theme_scripts' );
add_editor_style( 'style.css' );




// register own theme pattern

function quantumshop_register_pattern_category() {

	$patterns = array();

	$block_pattern_categories = array(
		'quantumshop' => array( 'label' => __( 'Quantumshop', 'quantumshop' ) )
	);

	$block_pattern_categories = apply_filters( 'quantumshop_block_pattern_categories', $block_pattern_categories );

	foreach ( $block_pattern_categories as $name => $properties ) {
		if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $name ) ) {
			register_block_pattern_category( $name, $properties );
		}
	}
}

add_action( 'init', 'quantumshop_register_pattern_category');

//recommend plugins
require get_theme_file_path( '/inc/tgm-plugin/tgmpa-hook.php' );

// Admin Info
require get_template_directory() . '/class/admin-info.php';