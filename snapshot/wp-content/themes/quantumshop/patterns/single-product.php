<?php
 /**
  * Title: Single Product
  * Slug: quantumshop/single-product
  * Inserter: no
  */
?>
<!-- wp:cover {"overlayColor":"primary","minHeight":232,"minHeightUnit":"px","tagName":"main","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<main class="wp-block-cover" style="margin-top:0;margin-bottom:0;min-height:232px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:post-title {"textAlign":"center"} /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","style":{"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}}},"textColor":"base-2","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
<div class="wp-block-group alignwide has-base-2-color has-text-color has-link-color"><!-- wp:woocommerce/breadcrumbs /--></div>
<!-- /wp:group --></div></main>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"20px","left":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-right:20px;padding-left:20px"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"75%"} -->
<div class="wp-block-column" style="flex-basis:75%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"20px","left":"20px"},"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30);padding-right:20px;padding-left:20px"><!-- wp:woocommerce/store-notices /-->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"512px"} -->
<div class="wp-block-column" style="flex-basis:512px"><!-- wp:woocommerce/product-image-gallery /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:post-title {"level":1,"style":{"typography":{"fontSize":"28px","fontStyle":"normal","fontWeight":"600"}},"__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductTemplate":true} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductTemplate":true} /-->

<!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-query/product-summary"} /-->

<!-- wp:woocommerce/add-to-cart-form /-->

<!-- wp:woocommerce/product-meta -->
<div class="wp-block-woocommerce-product-meta"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:woocommerce/product-sku {"isDescendentOfSingleProductTemplate":true} /-->

<!-- wp:post-terms {"term":"product_cat","prefix":"Category: "} /-->

<!-- wp:post-terms {"term":"product_tag","prefix":"Tags: "} /--></div>
<!-- /wp:group --></div>
<!-- /wp:woocommerce/product-meta --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:woocommerce/product-details {"align":"wide"} /-->

<!-- wp:woocommerce/related-products {"align":"wide"} -->
<div class="wp-block-woocommerce-related-products alignwide"><!-- wp:query {"queryId":0,"query":{"perPage":"4","pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"namespace":"woocommerce/related-products","lock":{"remove":true,"move":true}} -->
<div class="wp-block-query"><!-- wp:heading {"style":{"typography":{"fontSize":"32px","fontStyle":"normal","fontWeight":"600"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--30);font-size:32px;font-style:normal;font-weight:600"><?php echo esc_html__( 'Related Products', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"18px"} -->
<div style="height:18px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:post-template {"className":"products-block-post-template","layout":{"type":"grid","columnCount":4},"__woocommerceNamespace":"woocommerce/product-query/product-template"} -->
<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->

<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small","style":{"spacing":{"margin":{"bottom":"1rem"}}}} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:woocommerce/related-products -->

<!-- wp:spacer {"height":"56px"} -->
<div style="height:56px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:template-part {"slug":"woo-sidebar","theme":"quantumshop","area":"uncategorized"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->