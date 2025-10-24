<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mini Cart
 */

if ( ! class_exists('\ReyCore\WooCommerce\Tags\MiniCart') ) {
    return;
}

if( get_theme_mod('shop_catalog', false) === true ){
	return;
}

reycore_assets()->add_styles('rey-header-icon');
wp_enqueue_script( 'wc-cart-fragments' );

$args = reycore__header_cart_params();
$classes = [];

$cart_count = is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : '';
$aria_label = esc_html__('Open cart', 'rey-core');
$cart_layout = get_theme_mod('header_cart_layout', 'bag');
$cart_icon = $cart_holder = '';

if( $cart_layout !== 'disabled' ){
	$cart_icon = sprintf( '<span class="__icon rey-headerIcon-icon" aria-hidden="true">%s</span>', apply_filters('reycore/woocommerce/header/shopping_cart_icon', reycore__get_svg_icon([ 'id'=> $cart_layout ]) ) );
}

$cart_holder = $cart_icon;

if( $cart_text = get_theme_mod('header_cart_text_v2', '') ){
	$cart_text = str_replace( ['{{total}}', '{{count}}'], [\ReyCore\WooCommerce\Tags\MiniCart::get_cart_subtotal(), $cart_count], $cart_text );
	$cart_holder = sprintf('<span class="__text rey-headerCart-text rey-headerIcon-btnText %3$s">%1$s</span>%2$s', $cart_text, $cart_icon, get_theme_mod('header_cart_text_mobile', false) ? '--show-mobile' : '');
	$aria_label = sprintf('%s (%d)', $cart_text, $cart_count);
}

if( isset($args['classes']) ){
	$classes[] = $args['classes'];
}

$classes[] = esc_attr($args['hide_empty']) === 'yes' ? '--hide-empty' : '';

$tag = 'button';
$href = '';

if( is_cart() || is_checkout() || get_theme_mod('header_cart__panel_disable', false) ){
	$tag = 'a';
	$href = sprintf('href="%s"', esc_url( wc_get_cart_url() ));
} ?>

<div class="rey-headerCart-wrapper rey-headerIcon <?php echo implode(' ', $classes); ?>" data-rey-cart-count="<?php echo absint($cart_count); ?>">
	<<?php echo $tag; ?> <?php echo $href; ?> class="btn rey-headerIcon-btn rey-headerCart js-rey-headerCart" aria-label="<?php echo esc_attr($aria_label); ?>">
        <?php echo $cart_holder; ?>
        <span class="rey-headerIcon-counter --bubble"><?php \ReyCore\WooCommerce\Tags\MiniCart::get_cart_count(); ?></span>
	</<?php echo $tag; ?>>
</div>
<!-- .rey-headerCart-wrapper -->
