<?php
/**
 * Title: Header
 * Slug: quantumshop/header
 * Categories: header, quantumshop
 * Keywords: header
 * Block Types: core/template-part/header
 */
?>
<!-- wp:group {"style":{"border":{"bottom":{"color":"var:preset|color|accent-5","width":"1px"}},"spacing":{"padding":{"top":"10px","bottom":"10px"}}},"backgroundColor":"primary","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-primary-background-color has-background" style="border-bottom-color:var(--wp--preset--color--accent-5);border-bottom-width:1px;padding-top:10px;padding-bottom:10px"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","className":"hide-on-mobile"} -->
<div class="wp-block-column is-vertically-aligned-center hide-on-mobile"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|link-color"}}},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"}},"textColor":"link-color"} -->
<p class="has-link-color-color has-text-color has-link-color" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( 'Phone Number: 956 742 455 678', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500"},"elements":{"link":{"color":{"text":"var:preset|color|link-color"}}}},"textColor":"link-color","className":"has-border-left"} -->
<p class="has-border-left has-link-color-color has-text-color has-link-color" style="font-size:14px;font-style:normal;font-weight:500"><?php echo esc_html__( 'Email:info@example.com', 'quantumshop' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:group {"className":"center-on-mobile","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group center-on-mobile"><!-- wp:social-links {"iconColor":"link-color","iconColorValue":"#fcfcfc","openInNewTab":true,"size":"has-small-icon-size","style":{"spacing":{"blockGap":{"top":"0","left":"10px"}}},"className":"is-style-logos-only","layout":{"type":"flex","justifyContent":"right"}} -->
<ul class="wp-block-social-links has-small-icon-size has-icon-color is-style-logos-only"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"youtube"} /--></ul>
<!-- /wp:social-links -->

<!-- wp:group {"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}},"typography":{"fontStyle":"normal","fontWeight":"600"}},"textColor":"contrast","className":"login-icon","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group login-icon has-contrast-color has-text-color has-link-color" style="font-style:normal;font-weight:600"><!-- wp:image {"id":87,"sizeSlug":"full","linkDestination":"none","style":{"color":{"duotone":["rgb(255, 255, 255)","rgb(255, 255, 255)"]}}} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/user-regular.png" alt="" class="wp-image-87"/><figcaption class="wp-element-caption">Login</figcaption></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"15px","bottom":"15px","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}},"border":{"bottom":{"color":"var:preset|color|accent-5","width":"1px"},"top":[],"right":[],"left":[]}},"backgroundColor":"base-2","layout":{"type":"constrained","justifyContent":"center","contentSize":"1340px"}} -->
<div class="wp-block-group alignwide has-base-2-background-color has-background" id="sticky-header" style="border-bottom-color:var(--wp--preset--color--accent-5);border-bottom-width:1px;padding-top:15px;padding-right:var(--wp--preset--spacing--20);padding-bottom:15px;padding-left:var(--wp--preset--spacing--20)"><!-- wp:columns {"verticalAlignment":"center","isStackedOnMobile":false} -->
<div class="wp-block-columns are-vertically-aligned-center is-not-stacked-on-mobile"><!-- wp:column {"verticalAlignment":"center","width":"25%","className":"mobile-50","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center mobile-50" style="flex-basis:25%"><!-- wp:site-title {"level":0,"style":{"spacing":{"margin":{"right":"var:preset|spacing|20"}},"typography":{"fontStyle":"normal","fontWeight":"700"},"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast","fontSize":"large"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"","className":"mobile-50"} -->
<div class="wp-block-column is-vertically-aligned-center mobile-50"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group alignwide"><!-- wp:navigation {"icon":"menu","layout":{"type":"flex","justifyContent":"right"},"style":{"spacing":{"blockGap":"25px"}}} /-->

<!-- wp:image {"lightbox":{"enabled":false},"id":12665,"width":"20px","height":"20px","scale":"cover","sizeSlug":"full","linkDestination":"custom","className":"wish-list"} -->
<figure class="wp-block-image size-full is-resized wish-list"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/heart.png" alt="" class="wp-image-12665" style="object-fit:cover;width:20px;height:20px"/></figure>
<!-- /wp:image -->

<!-- wp:woocommerce/mini-cart {"productCountColor":{"color":"#1ca547","name":"Primary","slug":"primary","class":"has-primary-product-count-color"}} /-->

<!-- wp:navigation {"overlayMenu":"always","icon":"menu","className":"slider-menu","layout":{"type":"flex","justifyContent":"space-between"}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->