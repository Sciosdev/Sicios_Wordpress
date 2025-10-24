<?php
/**
 * Clothing Apparel Shop: Block Patterns
 *
 * @since Clothing Apparel Shop 1.0
 */

/**
 * Registers block patterns and categories.
 *
 * @since Clothing Apparel Shop 1.0
 *
 * @return void
 */
function clothing_apparel_shop_register_block_patterns() {
	$clothing_apparel_shop_block_pattern_categories = array(
		'clothing-apparel-shop'    => array( 'label' => __( 'Clothing Apparel Shop', 'clothing-apparel-shop' ) ),
	);

	$clothing_apparel_shop_block_pattern_categories = apply_filters( 'clothing_apparel_shop_block_pattern_categories', $clothing_apparel_shop_block_pattern_categories );

	foreach ( $clothing_apparel_shop_block_pattern_categories as $name => $properties ) {
		if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $name ) ) {
			register_block_pattern_category( $name, $properties );
		}
	}
}
add_action( 'init', 'clothing_apparel_shop_register_block_patterns', 9 );
