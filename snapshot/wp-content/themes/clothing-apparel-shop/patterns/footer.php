<?php
/**
 * Title: Footer
 * Slug: clothing-apparel-shop/footer
 * Categories: clothing-apparel-shop, footer
 */
?>

<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","right":"20px","bottom":"60px","left":"20px"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"backgroundColor":"footer-bg","fontSize":"small","layout":{"type":"constrained","contentSize":"90%"}} -->
<div class="wp-block-group has-footer-bg-background-color has-background has-small-font-size" style="margin-top:0;margin-bottom:0;padding-top:60px;padding-right:20px;padding-bottom:60px;padding-left:20px"><!-- wp:group {"align":"wide","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"textColor":"white","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide has-white-color has-text-color has-link-color" style="margin-top:0px;margin-bottom:0px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:site-logo /-->

<!-- wp:site-title {"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white","fontSize":"extra-large"} /--></div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"id":1993,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/telephone.png'); ?>" alt="" class="wp-image-1993"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>(316) 555 0116</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"id":1990,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/envelope.png'); ?>" alt="" class="wp-image-1990"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e('mail@example.com','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:social-links {"iconColor":"white","iconColorValue":"#ffffff","iconBackgroundColor":"primary","iconBackgroundColorValue":"#ab7aff","size":"has-normal-icon-size","className":"is-style-default","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color has-icon-background-color is-style-default"><!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"twitter"} /-->

<!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"linkedin"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":5,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","letterSpacing":"1px"}},"textColor":"white"} -->
<h5 class="wp-block-heading has-white-color has-text-color" style="font-style:normal;font-weight:600;letter-spacing:1px"><?php esc_html_e('Recent Posts','clothing-apparel-shop'); ?></h5>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-wide","style":{"spacing":{"margin":{"top":"10px"}}},"backgroundColor":"primary"} -->
<hr class="wp-block-separator has-text-color has-primary-color has-alpha-channel-opacity has-primary-background-color has-background is-style-wide" style="margin-top:10px"/>
<!-- /wp:separator -->

<!-- wp:query {"queryId":22,"query":{"perPage":"3","pages":"1","offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"default","columnCount":3}} -->
<!-- wp:post-title {"level":6,"isLink":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"textColor":"white","fontSize":"small"} /-->

<!-- wp:post-excerpt {"showMoreOnNewLine":false,"className":"footer-post-excerpt","style":{"spacing":{"margin":{"top":"5px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"extra-small"} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p><?php esc_html_e('There is no posts to display','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top"} -->
<div class="wp-block-column is-vertically-aligned-top"><!-- wp:heading {"level":5,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","letterSpacing":"1px"}},"textColor":"white"} -->
<h5 class="wp-block-heading has-white-color has-text-color" style="font-style:normal;font-weight:600;letter-spacing:1px"><?php esc_html_e('Quick Links','clothing-apparel-shop'); ?></h5>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-wide","style":{"spacing":{"margin":{"top":"10px"}}},"backgroundColor":"primary"} -->
<hr class="wp-block-separator has-text-color has-primary-color has-alpha-channel-opacity has-primary-background-color has-background is-style-wide" style="margin-top:10px"/>
<!-- /wp:separator -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('Home','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('About Us','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('Careers','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('Gallery','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('Wholesale','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('Corporate Information','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e('Contact Us','clothing-apparel-shop'); ?></a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":5,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","letterSpacing":"1px"}},"textColor":"white"} -->
<h5 class="wp-block-heading has-white-color has-text-color" style="font-style:normal;font-weight:600;letter-spacing:1px"><?php esc_html_e('Gallery','clothing-apparel-shop'); ?></h5>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-wide","style":{"spacing":{"margin":{"top":"10px"}}},"backgroundColor":"primary"} -->
<hr class="wp-block-separator has-text-color has-primary-color has-alpha-channel-opacity has-primary-background-color has-background is-style-wide" style="margin-top:10px"/>
<!-- /wp:separator -->

<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped"><!-- wp:image {"id":117,"sizeSlug":"thumbnail","linkDestination":"none"} -->
<figure class="wp-block-image size-thumbnail"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img1.png'); ?>" alt="" class="wp-image-117"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":118,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img2.png'); ?>" alt="" class="wp-image-118"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":119,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img3.png'); ?>" alt="" class="wp-image-119"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":120,"sizeSlug":"thumbnail","linkDestination":"none"} -->
<figure class="wp-block-image size-thumbnail"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img4.png'); ?>" alt="" class="wp-image-120"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":121,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img5.png'); ?>" alt="" class="wp-image-121"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":122,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img6.png'); ?>" alt="" class="wp-image-122"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":123,"sizeSlug":"thumbnail","linkDestination":"none"} -->
<figure class="wp-block-image size-thumbnail"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img7.png'); ?>" alt="" class="wp-image-123"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":124,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/insta-img8.png'); ?>" alt="" class="wp-image-124"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":127,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() . '/images/offer4.png'); ?>" alt="" class="wp-image-127"/></figure>
<!-- /wp:image --></figure>
<!-- /wp:gallery --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}},"backgroundColor":"primary","textColor":"white","layout":{"type":"constrained","contentSize":"90%"}} -->
<div class="wp-block-group has-white-color has-primary-background-color has-text-color has-background" style="padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"className":"copyright-text","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group copyright-text"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|Background"}}},"layout":{"selfStretch":"fit","flexSize":null}},"textColor":"Background"} -->
<p class="has-background-color has-text-color has-link-color"> <a rel="noreferrer noopener" href="https://www.ovationthemes.com/products/free-clothing-wordpress-theme" target="_blank"><?php esc_html_e('Clothing Apparel Shop WordPress Theme','clothing-apparel-shop'); ?></a>.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|Background"}}},"layout":{"selfStretch":"fit","flexSize":null}},"textColor":"Background"} -->
<p class="has-background-color has-text-color has-link-color"><?php esc_html_e('Proudly powered by ','clothing-apparel-shop'); ?><a rel="noreferrer noopener" href="https://www.ovationthemes.com/" target="_blank"><?php esc_html_e('Ovation Themes','clothing-apparel-shop'); ?></a> <?php esc_html_e('and','clothing-apparel-shop'); ?> <a href="https://wordpress.org"><?php esc_html_e('WordPress','clothing-apparel-shop'); ?></a>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"scroll-top"} -->
<p class="scroll-top"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->