<?php
namespace ReyCore\WooCommerce\LoopComponents;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SoldOutBadge extends Component {

	public function status(){
		return self::stock_display() !== 'hide';
	}

	public function get_id(){
		return 'sold_out_badge';
	}

	public function get_name(){
		return 'Sold Out Badge';
	}

	public function scheme(){

		if( in_array(self::stock_display(), ['badge_so', 'badge_is'], true) ){
			return [
				'type'          => 'action',
				'tag'           => 'reycore/loop_inside_thumbnail/top-right',
				'priority'      => 10,
			];
		}
		else if ( 'text' === self::stock_display() ){
			return [
				'type'          => 'action',
				'tag'           => 'woocommerce_shop_loop_item_title',
				'priority'      => 60,
			];
		}

	}

	public static function stock_display(){
		 return get_theme_mod('loop_stock_display', 'badge_so');
	}

	/**
	 * Item Component - NEW badge to product entry for any product added in the last 30 days.
	*
	* @since 1.0.0
	*/
	public function render() {

		if( ! $this->maybe_render() ){
			return;
		}

		if( in_array(self::stock_display(), ['badge_so', 'badge_is'], true) ){
			$this->render_badge();
		}
		else if ( 'text' === self::stock_display() ){
			$this->render_text();
		}
	}

	public function render_text(){

		if( ! ($product = reycore_wc__get_product()) ){
			return;
		}

		$stock_status = $product->get_stock_status();

		if( ($hide_statuses = get_theme_mod('loop_stock_hide_statuses', [])) && in_array($stock_status, $hide_statuses, true) ){
			return;
		}

		$availability = $product->get_availability();
		$text = '';
		$css_class = $availability['class'];

		switch( $stock_status ):
			case "instock":
				$text = $availability['availability'] ? $availability['availability'] : esc_html__( 'In stock', 'rey-core' );
				break;
			case "outofstock":
				$text = $availability['availability'] ? $availability['availability'] : esc_html__( 'Out of stock', 'rey-core' );
				break;
			case "onbackorder":
				$text = $availability['availability'] ? $availability['availability'] : esc_html__( 'On Backorder', 'rey-core' );
				break;
		endswitch;

		printf('<div class="rey-loopStock %3$s" style="%2$s">%1$s</div>', $text, self::get_css(), esc_attr($css_class) );

	}

	public function render_badge(){

		if( ! ($product = reycore_wc__get_product()) ){
			return;
		}

		$badge = '';
		$text = '';

		if( $custom_text = get_theme_mod('loop_sold_out_badge_text', '') ){
			$text = $custom_text;
		}

		if( $product->is_in_stock() ){
			if( 'onbackorder' === $product->get_stock_status() && apply_filters('reycore/woocommerce/loop/stock/onbackorder', false) ){
				$badge = apply_filters('reycore/woocommerce/loop/in_stock_text', esc_html__( 'ON BACKORDER', 'rey-core' ) );
			}
			else if( self::stock_display() === 'badge_is' ){
				$badge = $text ? $text : apply_filters('reycore/woocommerce/loop/in_stock_text', esc_html__( 'IN STOCK', 'rey-core' ) );
			}
		}
		else {
			if( self::stock_display() === 'badge_so' ) {
				$badge = $text ? $text : apply_filters('reycore/woocommerce/loop/sold_out_text', esc_html__( 'SOLD OUT', 'rey-core' ) );
			}
		}

		if( empty($badge) ){
			return;
		}

		printf('<div class="rey-itemBadge rey-soldout-badge" style="%2$s">%1$s</div>', $badge, self::get_css() );

	}

	public static function get_css(){

		if( $custom_css = get_theme_mod('loop_sold_out_badge_css', '') ){
			return esc_attr($custom_css);
		}

		return '';
	}
}
