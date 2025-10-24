<?php

add_action( 'admin_menu', 'clothing_apparel_shop_gettingstarted' );
function clothing_apparel_shop_gettingstarted() {
	add_theme_page( esc_html__('Begin Installation', 'clothing-apparel-shop'), esc_html__('Begin Installation', 'clothing-apparel-shop'), 'edit_theme_options', 'clothing-apparel-shop-guide-page', 'clothing_apparel_shop_guide');
}

if ( ! defined( 'CLOTHING_APPAREL_SHOP_SUPPORT' ) ) {
define('CLOTHING_APPAREL_SHOP_SUPPORT',__('https://wordpress.org/support/theme/clothing-apparel-shop/','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_REVIEW' ) ) {
define('CLOTHING_APPAREL_SHOP_REVIEW',__('https://wordpress.org/support/theme/clothing-apparel-shop/reviews/','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_LIVE_DEMO' ) ) {
define('CLOTHING_APPAREL_SHOP_LIVE_DEMO',__('https://trial.ovationthemes.com/clothing-apparel-shop/','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_BUY_PRO' ) ) {
define('CLOTHING_APPAREL_SHOP_BUY_PRO',__('https://www.ovationthemes.com/products/apparel-wordpress-theme','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_PRO_DOC' ) ) {
define('CLOTHING_APPAREL_SHOP_PRO_DOC',__('https://trial.ovationthemes.com/docs/ot-clothing-apparel-shop-pro-doc/','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_FREE_DOC' ) ) {
define('CLOTHING_APPAREL_SHOP_FREE_DOC',__('https://trial.ovationthemes.com/docs/ot-clothing-apparel-shop-free-doc/','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_THEME_NAME' ) ) {
define('CLOTHING_APPAREL_SHOP_THEME_NAME',__('Premium Clothing Apparel Shop Theme','clothing-apparel-shop'));
}
if ( ! defined( 'CLOTHING_APPAREL_SHOP_BUNDLE_LINK' ) ) {
define('CLOTHING_APPAREL_SHOP_BUNDLE_LINK',__('https://www.ovationthemes.com/products/wordpress-bundle','clothing-apparel-shop'));
}
/**
 * Theme Info Page
 */
function clothing_apparel_shop_guide() {

	// Theme info
	$return = add_query_arg( array()) ;
	$theme = wp_get_theme( '' ); ?>

	<div class="getting-started__header">
		<div class="header-box-left">
			<h2><?php echo esc_html( $theme ); ?></h2>
			<p><?php esc_html_e('Version: ', 'clothing-apparel-shop'); ?><?php echo esc_html($theme['Version']);?></p>
		</div>
		<div class="header-box-right">
			<div class="btn_box">
				<a class="button-primary" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_FREE_DOC ); ?>" target="_blank"><?php esc_html_e('Documentation', 'clothing-apparel-shop'); ?></a>
				<a class="button-primary" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_SUPPORT ); ?>" target="_blank"><?php esc_html_e('Support', 'clothing-apparel-shop'); ?></a>
				<a class="button-primary" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_REVIEW ); ?>" target="_blank"><?php esc_html_e('Review', 'clothing-apparel-shop'); ?></a>
			</div>
		</div>
	</div>

	<div class="wrap getting-started">
		<div class="box-container">
			<div class="box-left-main">
				<div class="leftbox">
					<h3><?php esc_html_e('Documentation','clothing-apparel-shop'); ?></h3>
					<p><?php $theme = wp_get_theme(); 
						echo wp_kses_post( apply_filters( 'description', esc_html( $theme->get( 'Description' ) ) ) );
					?></p>

					<h4><?php esc_html_e('Edit Your Site','clothing-apparel-shop'); ?></h4>
					<p><?php esc_html_e('Now create your website with easy drag and drop powered by gutenburg.','clothing-apparel-shop'); ?></p>
					<a class="button-primary" href="<?php echo esc_url( admin_url() . 'site-editor.php' ); ?>" target="_blank"><?php esc_html_e('Edit Your Site','clothing-apparel-shop'); ?></a>

					<h4><?php esc_html_e('Visit Your Site','clothing-apparel-shop'); ?></h4>
					<p><?php esc_html_e('To check your website click here','clothing-apparel-shop'); ?></p>
					<a class="button-primary" href="<?php echo esc_url( home_url() ); ?>" target="_blank"><?php esc_html_e('Visit Your Site','clothing-apparel-shop'); ?></a>

					<h4><?php esc_html_e('Theme Documentation','clothing-apparel-shop'); ?></h4>
					<p><?php esc_html_e('Check the theme documentation to easily set up your website.','clothing-apparel-shop'); ?></p>
					<a class="button-primary" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_FREE_DOC ); ?>" target="_blank"><?php esc_html_e('Documentation','clothing-apparel-shop'); ?></a>
				</div>
       	</div>
			<div class="box-right-main">
				<h3><?php echo esc_html(CLOTHING_APPAREL_SHOP_THEME_NAME); ?></h3>
				<img class="clothing_apparel_shop_img_responsive" style="width: 100%;" src="<?php echo esc_url( $theme->get_screenshot() ); ?>" />
				<div class="pro-links">
					<div class="pro-links-inner">
						<a class="button-primary livedemo" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_LIVE_DEMO ); ?>" target="_blank"><?php esc_html_e('Live Demo', 'clothing-apparel-shop'); ?></a>
						<a class="button-primary buynow" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_BUY_PRO ); ?>" target="_blank"><?php esc_html_e('Buy Now', 'clothing-apparel-shop'); ?></a>
						<a class="button-primary docs" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_PRO_DOC ); ?>" target="_blank"><?php esc_html_e('Documentation', 'clothing-apparel-shop'); ?></a>
					</div>
						<a class="button-primary bundle-btn" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_BUNDLE_LINK ); ?>" target="_blank"><?php esc_html_e('WordPress Theme Bundle (120+ Themes at Just $89)', 'clothing-apparel-shop'); ?></a>
				</div>
				<ul style="padding-top:10px">
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Responsive Design', 'clothing-apparel-shop');?> </li>                 
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Demo Importer', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Section Reordering', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Contact Page Template', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Multiple Blog Layouts', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Unlimited Color Options', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Cross Browser Support', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Detailed Documentation Included', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('WPML Compatible (Translation Ready)', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Woo-commerce Compatible', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Full Support', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('10+ Sections', 'clothing-apparel-shop');?> </li>
					<li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('SEO Friendly', 'clothing-apparel-shop');?> </li>
               <li class="upsell-clothing_apparel_shop"> <div class="dashicons dashicons-yes"></div> <?php esc_html_e('Supper Fast', 'clothing-apparel-shop');?> </li>
            </ul>
        	</div>
		</div>
	</div>

<?php }?>