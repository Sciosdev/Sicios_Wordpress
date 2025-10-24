<?php
 /**
  * Title: Cart
  * Slug: quantumshop/cart
  * Inserter: no
  */
?>
<!-- wp:cover {"tagName":"main","url":"<?php echo esc_url( get_template_directory_uri() );?>/assets/images/inner-page-banner.jpg","id":201,"dimRatio":50,"focalPoint":{"x":0.37,"y":0.64},"minHeight":249,"minHeightUnit":"px","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-cover" style="margin-top:0;margin-bottom:0;min-height:249px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-201" alt="" src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/inner-page-banner.jpg" style="object-position:37% 64%" data-object-fit="cover" data-object-position="37% 64%"/><div class="wp-block-cover__inner-container"><!-- wp:post-title {"textAlign":"center","style":{"typography":{"fontSize":"32px","fontStyle":"normal","fontWeight":"600"}}} /--></div></main>
<!-- /wp:cover -->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"20px","left":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-right:20px;padding-left:20px"><!-- wp:woocommerce/cart -->
<div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block -->
<div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block -->
<div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block -->
<div class="wp-block-woocommerce-cart-line-items-block"></div>
<!-- /wp:woocommerce/cart-line-items-block -->

<!-- wp:woocommerce/cart-cross-sells-block -->
<div class="wp-block-woocommerce-cart-cross-sells-block"><!-- wp:heading {"style":{"typography":{"fontSize":"32px","fontStyle":"normal","fontWeight":"600"}}} -->
<h2 class="wp-block-heading" style="font-size:32px;font-style:normal;font-weight:600"><?php echo esc_html__( 'You may be interested in…', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/cart-cross-sells-products-block -->
<div class="wp-block-woocommerce-cart-cross-sells-products-block"></div>
<!-- /wp:woocommerce/cart-cross-sells-products-block --></div>
<!-- /wp:woocommerce/cart-cross-sells-block --></div>
<!-- /wp:woocommerce/cart-items-block -->

<!-- wp:woocommerce/cart-totals-block -->
<div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block -->
<div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-heading-block -->
<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
<!-- /wp:woocommerce/cart-order-summary-heading-block -->

<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

<!-- wp:woocommerce/cart-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

<!-- wp:woocommerce/cart-order-summary-fee-block -->
<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
<!-- /wp:woocommerce/cart-order-summary-fee-block -->

<!-- wp:woocommerce/cart-order-summary-discount-block -->
<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
<!-- /wp:woocommerce/cart-order-summary-discount-block -->

<!-- wp:woocommerce/cart-order-summary-shipping-block -->
<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

<!-- wp:woocommerce/cart-order-summary-taxes-block -->
<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/cart-order-summary-block -->

<!-- wp:woocommerce/cart-express-payment-block -->
<div class="wp-block-woocommerce-cart-express-payment-block"></div>
<!-- /wp:woocommerce/cart-express-payment-block -->

<!-- wp:woocommerce/proceed-to-checkout-block -->
<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
<!-- /wp:woocommerce/proceed-to-checkout-block -->

<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>
<!-- /wp:woocommerce/cart-totals-block --></div>
<!-- /wp:woocommerce/filled-cart-block -->

<!-- wp:woocommerce/empty-cart-block -->
<div class="wp-block-woocommerce-empty-cart-block"><!-- wp:heading {"textAlign":"center","className":"with-empty-cart-icon wc-block-cart__empty-cart__title"} -->
<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title"><?php echo esc_html__( 'Your cart is currently empty!', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-dots"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'New in store', 'quantumshop' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-new {"columns":4,"rows":1} /--></div>
<!-- /wp:woocommerce/empty-cart-block --></div>
<!-- /wp:woocommerce/cart --></div>
<!-- /wp:group -->