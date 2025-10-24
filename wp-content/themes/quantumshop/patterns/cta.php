<?php
/**
 * Title: Call to Action
 * Slug: quantumshop/cta
 * Categories: quantumshop
 * Keywords: call to action
 * Block Types: core/post-content
 * Post Types: page, wp_template
 */
?>
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() );?>/assets/images/featured-image.jpg","id":343,"dimRatio":90,"overlayColor":"contrast","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="margin-top:0;margin-bottom:0"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-90 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-343" alt="" src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/featured-image.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"38px"},"spacing":{"margin":{"top":"0","bottom":"0"}},"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2"} -->
<h2 class="wp-block-heading has-base-2-color has-text-color has-link-color" style="margin-top:0;margin-bottom:0;font-size:38px;font-style:normal;font-weight:600"><?php echo esc_html__( 'Sales Up to 50%', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base"} -->
<p class="has-base-color has-text-color has-link-color"><?php echo esc_html__( 'Cras ultricies mi eu turpis hendrerit fringilla. Vivamus quis mi. Ut tincidunt tincidunt erat. Maecenas vestibulum mollis diam. Sed lectus.', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:button {"style":{"spacing":{"padding":{"left":"var:preset|spacing|30","right":"var:preset|spacing|30","top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--30)"><?php echo esc_html__( 'Shop Now', 'quantumshop' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"base-2","textColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"}}},"border":{"width":"1px"}},"borderColor":"primary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-base-2-background-color has-text-color has-background has-link-color has-border-color has-primary-border-color wp-element-button" style="border-width:1px"><?php echo esc_html__( 'Know More', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"align":"right","id":175,"width":"343px","height":"auto","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image alignright size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/cta-banner.png" alt="" class="wp-image-175" style="width:343px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->