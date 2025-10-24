<?php
if (! defined('GROCEFYCART_VERSION')) {
	// Replace the version number of the theme on each release.
	define('GROCEFYCART_VERSION', wp_get_theme()->get('Version'));
}
define('GROCEFYCART_DEBUG', defined('WP_DEBUG') && WP_DEBUG === true);
define('GROCEFYCART_DIR', trailingslashit(get_template_directory()));
define('GROCEFYCART_URL', trailingslashit(get_template_directory_uri()));

if (! function_exists('grocefycart_support')) :

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since walker_fse 1.0.0
	 *
	 * @return void
	 */
	function grocefycart_support()
	{
		// Add default posts and comments RSS feed links to head.
		add_theme_support('automatic-feed-links');
		// Add support for block styles.
		add_theme_support('wp-block-styles');
		add_theme_support('post-thumbnails');
		// Enqueue editor styles.
		add_editor_style('style.css');
		// Removing default patterns.
		remove_theme_support('core-block-patterns');
	}

endif;
add_action('after_setup_theme', 'grocefycart_support');

/*
----------------------------------------------------------------------------------
Enqueue Styles
-----------------------------------------------------------------------------------*/
if (! function_exists('grocefycart_styles')) :
	function grocefycart_styles()
	{
		// registering style for theme
		wp_enqueue_style('grocefycart-style', get_stylesheet_uri(), array(), GROCEFYCART_VERSION);
		wp_enqueue_style('grocefycart-blocks-style', get_template_directory_uri() . '/assets/css/blocks.css');
		wp_enqueue_style('grocefycart-aos-style', get_template_directory_uri() . '/assets/css/aos.css');
		if (is_rtl()) {
			wp_enqueue_style('grocefycart-rtl-css', get_template_directory_uri() . '/assets/css/rtl.css', 'rtl_css');
		}
		wp_enqueue_script('grocefycart-aos-scripts', get_template_directory_uri() . '/assets/js/aos.js', array(), GROCEFYCART_VERSION, true);
		wp_enqueue_script('grocefycart-scripts', get_template_directory_uri() . '/assets/js/grocefycart-scripts.js', array(), GROCEFYCART_VERSION, true);
	}
endif;

add_action('wp_enqueue_scripts', 'grocefycart_styles');

/**
 * Enqueue scripts for admin area
 */
function grocefycart_admin_style()
{
	$hello_notice_current_screen = get_current_screen();
	if (! empty($_GET['page']) && 'about-grocefycart' === $_GET['page'] || $hello_notice_current_screen->id === 'themes' || $hello_notice_current_screen->id === 'dashboard') {
		wp_enqueue_style('grocefycart-admin-style', get_template_directory_uri() . '/assets/css/admin-style.css', array(), GROCEFYCART_VERSION, 'all');
		wp_enqueue_script('grocefycart-admin-scripts', get_template_directory_uri() . '/assets/js/grocefycart-admin-scripts.js', array(), GROCEFYCART_VERSION, true);
		wp_localize_script(
			'grocefycart-admin-scripts',
			'grocefycart_admin_localize',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('grocefycart_admin_nonce'),
			)
		);
		wp_enqueue_script('grocefycart-welcome-notice', get_template_directory_uri() . '/inc/admin/js/grocefycart-welcome-notice.js', array('jquery'), GROCEFYCART_VERSION, true);
		wp_localize_script(
			'grocefycart-welcome-notice',
			'grocefycart_localize',
			array(
				'ajax_url'     => admin_url('admin-ajax.php'),
				'nonce'        => wp_create_nonce('grocefycart_welcome_nonce'),
				'redirect_url' => admin_url('themes.php?page=_cozy_companions'),
			)
		);
	}
}
add_action('admin_enqueue_scripts', 'grocefycart_admin_style');

/**
 * Enqueue assets scripts for both backend and frontend
 */
function grocefycart_block_assets()
{
	wp_enqueue_style('grocefycart-blocks-style', get_template_directory_uri() . '/assets/css/blocks.css');
}
add_action('enqueue_block_assets', 'grocefycart_block_assets');

/**
 * Load core file.
 */
require_once get_template_directory() . '/inc/core/init.php';

/**
 * Load welcome page file.
 */
require_once get_template_directory() . '/inc/admin/welcome-notice.php';

function grocefycart_add_woocommerce_support()
{
	add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'grocefycart_add_woocommerce_support');
