<?php
/**
 * Clothing Apparel Shop: Customizer
 *
 * @subpackage Clothing Apparel Shop
 * @since 1.0
 */

function clothing_apparel_shop_customize_register( $wp_customize ) {

	wp_enqueue_style('customizercustom_css', esc_url( get_template_directory_uri() ). '/inc/customizer/customizer.css');

	// Pro Section
 	$wp_customize->add_section('clothing_apparel_shop_pro', array(
        'title'    => __('CLOTHING APPAREL SHOP PREMIUM', 'clothing-apparel-shop'),
        'priority' => 1,
    ));
    $wp_customize->add_setting('clothing_apparel_shop_pro', array(
        'default'           => null,
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control(new Clothing_Apparel_Shop_Pro_Control($wp_customize, 'clothing_apparel_shop_pro', array(
        'label'    => __('CLOTHING APPAREL SHOP PREMIUM', 'clothing-apparel-shop'),
        'section'  => 'clothing_apparel_shop_pro',
        'settings' => 'clothing_apparel_shop_pro',
        'priority' => 1,
    )));
}
add_action( 'customize_register', 'clothing_apparel_shop_customize_register' );


define('CLOTHING_APPAREL_SHOP_PRO_LINK',__('https://www.ovationthemes.com/products/apparel-wordpress-theme','clothing-apparel-shop'));

define('CLOTHING_APPAREL_SHOP_BUNDLE_BTN',__('https://www.ovationthemes.com/products/wordpress-bundle','clothing-apparel-shop'));

/* Pro control */
if (class_exists('WP_Customize_Control') && !class_exists('Clothing_Apparel_Shop_Pro_Control')):
    class Clothing_Apparel_Shop_Pro_Control extends WP_Customize_Control{

    public function render_content(){?>
        <label style="overflow: hidden; zoom: 1;">
	        <div class="col-md upsell-btn">
                <a href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_PRO_LINK ); ?>" target="blank" class="btn btn-success btn"><?php esc_html_e('UPGRADE CLOTHING APPAREL PREMIUM','clothing-apparel-shop');?> </a>
	        </div>
            <div class="col-md">
                <img class="clothing_apparel_shop_img_responsive " src="<?php echo esc_url(get_template_directory_uri()); ?>/screenshot.png">
            </div>
	        <div class="col-md">
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
            <div class="col-md upsell-btn upsell-btn-bottom">
                <a href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_BUNDLE_BTN ); ?>" target="blank" class="btn btn-success btn"><?php esc_html_e('WP Theme Bundle (120+ Themes)','clothing-apparel-shop');?> </a>
            </div>
        </label>
    <?php } }
endif;