<?php
/**
 * Clothing Apparel Shop functions and definitions
 *
 * @package Clothing Apparel Shop
 */

if ( ! function_exists( 'clothing_apparel_shop_setup' ) ) :
function clothing_apparel_shop_setup() {
	
	if ( ! isset( $content_width ) )
		$content_width = 640; /* pixels */

    load_theme_textdomain( 'clothing-apparel-shop', get_template_directory() . '/languages' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'      => 240,
		'width'       => 240,
		'flex-height' => true,
	) );
	
	add_theme_support( 'custom-background', array(
		'default-color' => 'ffffff'
	) );
	
	// Add support for Block Styles.
	add_theme_support( 'wp-block-styles' );

	// Add support for full and wide align images.
	add_theme_support( 'align-wide' );
			
	// Add support for responsive embedded content.
	add_theme_support( 'responsive-embeds' );

    add_theme_support('woocommerce');

	// Enqueue editor styles.
	add_editor_style( array( 'assets/css/editor-style.css' ) );

    require get_parent_theme_file_path( '/inc/dashboard/dashboard.php' );

    require get_parent_theme_file_path( '/inc/customizer/customizer.php' );
	
}
endif; // clothing_apparel_shop_setup
add_action( 'after_setup_theme', 'clothing_apparel_shop_setup' );

function clothing_apparel_shop_scripts() {
	wp_enqueue_style( 'clothing-apparel-shop-basic-style', get_stylesheet_uri() );

	$clothing_apparel_shop_enable_animations = get_option( 'clothing_apparel_shop_enable_animations', true );

    if ( $clothing_apparel_shop_enable_animations ) {
        //animation
		wp_enqueue_script( 'wow-js', get_theme_file_uri( '/assets/js/wow.js' ), array( 'jquery' ), true );

		wp_enqueue_style( 'animate-css', get_template_directory_uri().'/assets/css/animate.css' );
    }

	//font-awesome
	wp_enqueue_style( 'fontawesome', get_template_directory_uri() . '/assets/font-awesome/css/all.css', array(), '5.15.3' );

	// script.js
	wp_enqueue_script('clothing-apparel-shop-main-script', get_template_directory_uri() . '/assets/js/script.js', array('jquery'), '1.0.0', true);

    wp_style_add_data( 'clothing-apparel-shop-basic-style', 'rtl', 'replace' );
}
add_action( 'wp_enqueue_scripts', 'clothing_apparel_shop_scripts' );

function clothing_apparel_shop_enqueue_admin_script($hook) {
    // Enqueue admin JS for notices
    wp_enqueue_script('clothing-apparel-shop-welcome-notice', get_template_directory_uri() . '/inc/dashboard/clothing-apparel-shop-welcome-notice.js', array('jquery'), '', true);
    
    // Localize script to pass data to JavaScript
    wp_localize_script('clothing-apparel-shop-welcome-notice', 'clothing_apparel_shop_localize', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('clothing_apparel_shop_welcome_nonce'),
        'dismiss_nonce' => wp_create_nonce('clothing_apparel_shop_welcome_nonce'), // Nonce for dismissal
        'redirect_url' => admin_url('themes.php?page=clothing-apparel-shop-guide-page')
    ));
}
add_action('admin_enqueue_scripts', 'clothing_apparel_shop_enqueue_admin_script');

function clothing_apparel_shop_admin_theme_style() {
   wp_enqueue_style('clothing-apparel-shop-custom-admin-style', esc_url(get_template_directory_uri()) . '/inc/dashboard/dashboard.css');
}
add_action('admin_enqueue_scripts', 'clothing_apparel_shop_admin_theme_style');

// Block Patterns.
require get_template_directory() . '/block-patterns.php';
require get_template_directory() . '/custom-setting.php';
require get_template_directory() .'/inc/TGM/tgm.php';
require_once get_template_directory() . '/inc/dashboard/welcome-notice.php';

//woocommerce plugin skip 
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );