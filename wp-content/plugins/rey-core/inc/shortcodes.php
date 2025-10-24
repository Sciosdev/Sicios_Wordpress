<?php
namespace ReyCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	public function __construct(){

		add_shortcode('site_info', [$this, 'site_info']);
		add_shortcode('user_name', [$this, 'username']);
		add_shortcode('current_year', [$this, 'current_year']);
		add_shortcode('enqueue_asset', [$this, 'enqueue_asset']);

	}

	/**
	 * Display site info through shortcodes.
	 * show = name / email / url
	 *
	 * @since 1.0.0
	 **/
	public function site_info($atts) {

		$content = '';
		if( isset($atts['show']) && $show = $atts['show'] ){
			switch ($show):
				case"name":
					$content = get_bloginfo( 'name' );
					break;
				case"email":
					$content = get_bloginfo( 'admin_email' );
					break;
				case"url":
					$content = sprintf('<a href="%1$s">%1%s</a>', get_bloginfo( 'url' ));
					break;
			endswitch;
		}
		return $content;
	}

	/**
	 * Enqueue a script or style;
	 *
	 * @since 1.9.7
	 **/
	public function enqueue_asset($atts)
	{
		$content = '';

		if( isset($atts['type']) && ($type = $atts['type']) && isset($atts['name']) && ($name = $atts['name']) ){

			if( $type === 'style' ){
				wp_enqueue_style($name);
			}
			else if( $type === 'script' ){
				wp_enqueue_script($name);
			}

		}

		return $content;
	}

	public function username($atts){

		if( ! ($current_user = wp_get_current_user()) ){
			return;
		}
		return $current_user->user_firstname ?? $current_user->user_login;

	}

	public function current_year(){
		return date('Y');
	}
}
