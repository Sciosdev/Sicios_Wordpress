<?php
 /**
  * Title: Woo Sidebar
  * Slug: quantumshop/woo-sidebar
  * Categories: quantumshop
  */
?>
<!-- wp:group {"tagName":"main","layout":{"type":"default"}} -->
<main class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"16px","padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"14px","right":"14px"}},"border":{"radius":"5px"}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group has-base-background-color has-background" style="border-radius:5px;padding-top:var(--wp--preset--spacing--10);padding-right:14px;padding-bottom:var(--wp--preset--spacing--10);padding-left:14px"><!-- wp:heading {"level":3,"fontSize":"mdm-large"} -->
<h3 class="wp-block-heading has-mdm-large-font-size"><?php echo esc_html__( 'Search ', 'quantumshop' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search products…","buttonText":"Search","buttonPosition":"button-inside","buttonUseIcon":true,"query":{"post_type":"product"},"style":{"border":{"width":"0px","style":"none"}}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"14px","right":"14px"}},"border":{"radius":"5px"}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group has-base-background-color has-background" style="border-radius:5px;padding-top:var(--wp--preset--spacing--10);padding-right:14px;padding-bottom:var(--wp--preset--spacing--10);padding-left:14px"><!-- wp:heading {"level":3,"className":"is-style-default","fontSize":"mdm-large"} -->
<h3 class="wp-block-heading is-style-default has-mdm-large-font-size"><?php echo esc_html__( 'Categories', 'quantumshop' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-categories {"hasCount":false,"style":{"typography":{"lineHeight":"1.5"}},"fontSize":"sml-medium"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"14px","right":"14px"}},"border":{"radius":"5px"}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group has-base-background-color has-background" style="border-radius:5px;padding-top:var(--wp--preset--spacing--10);padding-right:14px;padding-bottom:var(--wp--preset--spacing--10);padding-left:14px"><!-- wp:woocommerce/filter-wrapper {"filterType":"price-filter","heading":"Filter by price"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3,"fontSize":"mdm-large"} -->
<h3 class="wp-block-heading has-mdm-large-font-size"><?php echo esc_html__( 'Filter by price', 'quantumshop' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/price-filter {"heading":"","lock":{"remove":true},"textColor":"black","style":{"elements":{"link":{"color":{"text":"var:preset|color|black"}}}}} -->
<div class="wp-block-woocommerce-price-filter is-loading has-black-color has-text-color has-link-color"><span aria-hidden="true" class="wc-block-product-categories__placeholder"></span></div>
<!-- /wp:woocommerce/price-filter --></div>
<!-- /wp:woocommerce/filter-wrapper --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"14px","right":"14px"}},"border":{"radius":"5px"}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group has-base-background-color has-background" style="border-radius:5px;padding-top:var(--wp--preset--spacing--10);padding-right:14px;padding-bottom:var(--wp--preset--spacing--10);padding-left:14px"><!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":3,"fontSize":"mdm-large"} -->
<h3 class="wp-block-heading has-mdm-large-font-size"><?php echo esc_html__( 'Filter by color', 'quantumshop' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:woocommerce/attribute-filter {"attributeId":1,"heading":"","lock":{"remove":true},"textColor":"black","style":{"elements":{"link":{"color":{"text":"var:preset|color|black"}}}}} -->
<div class="wp-block-woocommerce-attribute-filter is-loading has-black-color has-text-color has-link-color"></div>
<!-- /wp:woocommerce/attribute-filter --></div>
<!-- /wp:woocommerce/filter-wrapper --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"14px","right":"14px"}},"border":{"radius":"5px"}},"backgroundColor":"base","layout":{"type":"default"}} -->
<div class="wp-block-group has-base-background-color has-background" style="border-radius:5px;padding-top:var(--wp--preset--spacing--10);padding-right:14px;padding-bottom:var(--wp--preset--spacing--10);padding-left:14px"><!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"Filter by attribute"} -->
<div class="wp-block-woocommerce-filter-wrapper"><!-- wp:heading {"level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast"} -->
<h4 class="wp-block-heading has-contrast-color has-text-color has-link-color"><?php echo esc_html__( 'Filter by size', 'quantumshop' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:woocommerce/attribute-filter {"attributeId":2,"heading":"","lock":{"remove":true},"textColor":"black","style":{"elements":{"link":{"color":{"text":"var:preset|color|black"}}}}} -->
<div class="wp-block-woocommerce-attribute-filter is-loading has-black-color has-text-color has-link-color"></div>
<!-- /wp:woocommerce/attribute-filter --></div>
<!-- /wp:woocommerce/filter-wrapper --></div>
<!-- /wp:group --></main>
<!-- /wp:group -->