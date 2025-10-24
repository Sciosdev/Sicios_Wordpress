<?php
/**
 * Title: Featured Products
 * Slug: quantumshop/featured-product
 * Categories: quantumshop
 * Keywords: featured-product 
 */
$pluginsList = get_option( 'active_plugins' );
$quantumshop_plugin = 'woocommerce/woocommerce.php';
 $results = in_array( $quantumshop_plugin , $pluginsList);
 if ( $results )  {
?>
<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<main class="wp-block-group has-base-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"38px"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<h2 class="wp-block-heading" style="margin-top:0;margin-bottom:0;font-size:38px;font-style:normal;font-weight:600"><?php echo esc_html__( 'Featured Products', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Cras ultricies mi eu turpis hendrerit fringilla. Vivamus quis mi.', 'quantumshop' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"0"}}}} -->
<div class="wp-block-buttons" style="margin-top:0"><!-- wp:button {"backgroundColor":"base-2","textColor":"primary","style":{"spacing":{"padding":{"left":"var:preset|spacing|30","right":"var:preset|spacing|30","top":"10px","bottom":"10px"}},"border":{"width":"1px"},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"borderColor":"primary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-base-2-background-color has-text-color has-background has-link-color has-border-color has-primary-border-color wp-element-button" style="border-width:1px;padding-top:10px;padding-right:var(--wp--preset--spacing--30);padding-bottom:10px;padding-left:var(--wp--preset--spacing--30)"><?php echo esc_html__( 'View all', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20)"><!-- wp:query {"queryId":34,"query":{"perPage":"8","pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"__woocommerceAttributes":[],"__woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":null,"parents":[]},"namespace":"woocommerce/product-query"} -->
<div class="wp-block-query"><!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"className":"products-block-post-template","layout":{"type":"grid","columnCount":4},"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} /-->

<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"500","fontSize":"18px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|10","right":"var:preset|spacing|10"},"margin":{"top":"var:preset|spacing|10","bottom":"8px","left":"0","right":"0"}}},"__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:woocommerce/product-rating {"isDescendentOfQueryLoop":true,"textAlign":"center","style":{"spacing":{"margin":{"top":"5px","bottom":"5px","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->
<?php  }  else{ ?>
<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"base","layout":{"type":"constrained"}} -->
<main class="wp-block-group has-base-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"38px"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<h2 class="wp-block-heading" style="margin-top:0;margin-bottom:0;font-size:38px;font-style:normal;font-weight:600"><?php echo esc_html__( 'Featured Products', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Cras ultricies mi eu turpis hendrerit fringilla. Vivamus quis mi.', 'quantumshop' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"0"}}}} -->
<div class="wp-block-buttons" style="margin-top:0"><!-- wp:button {"backgroundColor":"link-color","textColor":"primary","style":{"spacing":{"padding":{"left":"var:preset|spacing|30","right":"var:preset|spacing|30","top":"10px","bottom":"10px"}},"border":{"width":"1px"},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"borderColor":"primary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-link-color-background-color has-text-color has-background has-link-color has-border-color has-primary-border-color wp-element-button" style="border-width:1px;padding-top:10px;padding-right:var(--wp--preset--spacing--30);padding-bottom:10px;padding-left:var(--wp--preset--spacing--30)"><?php echo esc_html__( 'View all', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12644,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-5.jpg" alt="" class="wp-image-12644"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Chairs', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 120', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12645,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-4.jpg" alt="" class="wp-image-12645"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Couches', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 140', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12646,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-3.jpg" alt="" class="wp-image-12646"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Tables', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 120', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12647,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-2.jpg" alt="" class="wp-image-12647"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'lights', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 782', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12644,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-5.jpg" alt="" class="wp-image-12644"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Chairs', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 120', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12645,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-4.jpg" alt="" class="wp-image-12645"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Couches', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 140', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12646,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-3.jpg" alt="" class="wp-image-12646"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Tables', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 120', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"9px","left":"9px","right":"9px","bottom":"20px"}}},"backgroundColor":"base-2","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:9px;padding-right:9px;padding-bottom:20px;padding-left:9px"><!-- wp:image {"id":12647,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/banner-product-2.jpg" alt="" class="wp-image-12647"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","level":4} -->
<h4 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'lights', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( '$ 782', 'quantumshop' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button"><?php echo esc_html__( 'Add to Cart', 'quantumshop' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></main>
<!-- /wp:group -->
<?php } ; ?>