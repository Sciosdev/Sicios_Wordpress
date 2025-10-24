<?php
/**
 * Title: Header
 * Slug: clothing-apparel-shop/header
 * Categories: clothing-apparel-shop, header
 */
?>

<!-- wp:group {"className":"upper-header","style":{"spacing":{"padding":{"top":"0px","right":"20px","bottom":"0px","left":"20px"}}},"backgroundColor":"background","layout":{"type":"constrained","contentSize":"90%"}} -->
<div class="wp-block-group upper-header has-background-background-color has-background" style="padding-top:0px;padding-right:20px;padding-bottom:0px;padding-left:20px"><!-- wp:columns {"className":"wow flash","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns wow flash" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:column {"verticalAlignment":"center","width":"32%","className":"logo-block"} -->
<div class="wp-block-column is-vertically-aligned-center logo-block" style="flex-basis:32%"><!-- wp:group {"className":"logodiv","textColor":"primary","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group logodiv has-primary-color has-text-color"><!-- wp:site-logo {"width":43,"shouldSyncIcon":true} /-->

<!-- wp:site-title {"style":{"elements":{"link":{"color":{"text":"#41305c"}}},"color":{"text":"#41305c"},"typography":{"textTransform":"capitalize"}},"fontSize":"extra-large"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"33%","className":"dropdwon-div"} -->
<div class="wp-block-column is-vertically-aligned-center dropdwon-div" style="flex-basis:33%"><!-- wp:columns {"className":"dropdown-column","style":{"border":{"radius":"5px","color":"#eeeef5","width":"1px"}}} -->
<div class="wp-block-columns dropdown-column has-border-color" style="border-color:#eeeef5;border-width:1px;border-radius:5px"><!-- wp:column {"verticalAlignment":"center","width":"40%","className":"header_dropdown","style":{"border":{"right":{"color":"#eeeef5","width":"1px"}},"color":{"background":"#f4f6fa"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
<div class="wp-block-column is-vertically-aligned-center header_dropdown has-background" style="border-right-color:#eeeef5;border-right-width:1px;background-color:#f4f6fa;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);flex-basis:40%"><!-- wp:woocommerce/product-categories {"isDropdown":true,"fontSize":"medium"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%"><!-- wp:search {"label":"","showLabel":false,"placeholder":"Search Here..","buttonText":"Search","buttonUseIcon":true,"query":{"post_type":"product"},"style":{"elements":{"link":{"color":{"text":"var:preset|color|button"}}},"border":{"width":"0px","style":"none"},"typography":{"fontStyle":"normal","fontWeight":"500"}},"backgroundColor":"white","textColor":"button","fontSize":"small"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"13%","className":"bell-block"} -->
<div class="wp-block-column is-vertically-aligned-center bell-block" style="flex-basis:13%"><!-- wp:image {"lightbox":{"enabled":false},"id":19,"sizeSlug":"full","linkDestination":"custom","align":"center","className":"is-style-default"} -->
<figure class="wp-block-image aligncenter size-full is-style-default"><a href="#"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/bell.png'); ?>" alt="" class="wp-image-19"/></a></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"25%","className":"mail-block"} -->
<div class="wp-block-column is-vertically-aligned-center mail-block" style="flex-basis:25%"><!-- wp:paragraph {"className":"contact-text","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"secondary","fontSize":"medium"} -->
<p class="contact-text has-secondary-color has-text-color has-link-color has-medium-font-size" style="font-style:normal;font-weight:500"><?php esc_html_e('Leslie Alexander','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"contact-info","style":{"elements":{"link":{"color":{"text":"var:preset|color|body-text"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"body-text","fontSize":"small"} -->
<p class="contact-info has-body-text-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:500"><?php esc_html_e('support@example.com','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"menu-header","style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}},"border":{"top":{"color":"#eeeef5","width":"1px"},"right":[],"bottom":[],"left":[]}},"backgroundColor":"background","layout":{"type":"constrained","contentSize":"90%"}} -->
<div class="wp-block-group menu-header has-background-background-color has-background" style="border-top-color:#eeeef5;border-top-width:1px;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"verticalAlignment":"center","width":"57%","className":"header-nav wow slideInLeft"} -->
<div class="wp-block-column is-vertically-aligned-center header-nav wow slideInLeft" style="flex-basis:57%"><!-- wp:navigation {"textColor":"heading","overlayBackgroundColor":"white","overlayTextColor":"black","metadata":{"ignoredHookedBlocks":["woocommerce/customer-account"]},"style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"layout":{"type":"flex","justifyContent":"left"}} -->
<!-- wp:navigation-link {"label":"Home","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-submenu {"label":"Men","type":"","url":"#","kind":"custom"} -->
	<!-- wp:navigation-link {"label":"Top Wear","type":"","url":"#","kind":"custom","className":""} /-->

	<!-- wp:navigation-link {"label":"Lower Wear","type":"","url":"#","kind":"custom","className":""} /-->
<!-- /wp:navigation-submenu -->

<!-- wp:navigation-link {"label":"Women","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Baby \u0026 Kids","type":"","url":"#","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Buy Now","type":"link","opensInNewTab":true,"url":"https://www.ovationthemes.com/products/apparel-wordpress-theme","kind":"custom","className":"buynow"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"18%","className":"head-info-outer wow slideInRight"} -->
<div class="wp-block-column is-vertically-aligned-center head-info-outer wow slideInRight" style="flex-basis:18%"><!-- wp:columns {"verticalAlignment":"center","className":"head-info"} -->
<div class="wp-block-columns are-vertically-aligned-center head-info"><!-- wp:column {"verticalAlignment":"center","width":"33.33%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%"><!-- wp:image {"id":47,"width":"38px","sizeSlug":"full","linkDestination":"none","align":"right"} -->
<figure class="wp-block-image alignright size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/mail.png'); ?>" alt="" class="wp-image-47" style="width:38px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"66.66%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%"><!-- wp:paragraph {"className":"contact-text","style":{"elements":{"link":{"color":{"text":"var:preset|color|button"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"button","fontSize":"small"} -->
<p class="contact-text has-button-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:500"><?php esc_html_e('Mail Us','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"contact-info","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"secondary","fontSize":"small"} -->
<p class="contact-info has-secondary-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:500"><?php esc_html_e('mail@example.com','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"15%","className":"head-info-outer wow slideInRight"} -->
<div class="wp-block-column is-vertically-aligned-center head-info-outer wow slideInRight" style="flex-basis:15%"><!-- wp:columns {"verticalAlignment":"center","className":"head-info"} -->
<div class="wp-block-columns are-vertically-aligned-center head-info"><!-- wp:column {"verticalAlignment":"center","width":"33.33%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%"><!-- wp:image {"id":206,"width":"38px","sizeSlug":"full","linkDestination":"none","align":"right"} -->
<figure class="wp-block-image alignright size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/call.png'); ?>" alt="" class="wp-image-206" style="width:38px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"66.66%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%"><!-- wp:paragraph {"className":"contact-text","style":{"elements":{"link":{"color":{"text":"var:preset|color|button"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"button","fontSize":"small"} -->
<p class="contact-text has-button-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:500"><?php esc_html_e('Call Us','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"contact-info","style":{"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}},"typography":{"fontStyle":"normal","fontWeight":"500"}},"textColor":"secondary","fontSize":"small"} -->
<p class="contact-info has-secondary-color has-text-color has-link-color has-small-font-size" style="font-style:normal;font-weight:500">(316) 555 0116</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"10%","className":"cart-block wow slideInRight"} -->
<div class="wp-block-column is-vertically-aligned-center cart-block wow slideInRight" style="flex-basis:10%"><!-- wp:woocommerce/mini-cart {"priceColor":{"color":"#132c3b","name":"Secondary","slug":"secondary","class":"has-secondary-product-count-color"},"iconColor":{"color":"#6622da","name":"Button","slug":"button","class":"has-button-icon-color"},"productCountColor":{"color":"#132c3b","name":"Secondary","slug":"secondary","class":"has-secondary-product-count-color"},"fontSize":"medium"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->