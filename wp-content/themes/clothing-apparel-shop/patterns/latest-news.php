<?php
/**
 * Title: Latest News
 * Slug: clothing-apparel-shop/latest-news
 * Categories: clothing-apparel-shop, latest-news
 */
?>

<!-- wp:group {"metadata":{"categories":["clothing-apparel-shop","latest-news"],"patternName":"clothing-apparel-shop/latest-news","name":"Latest News"},"className":"latest-news","style":{"spacing":{"margin":{"top":"0px","bottom":"0px"},"padding":{"top":"60px","right":"20px","bottom":"60px","left":"20px"}}},"layout":{"type":"constrained","contentSize":"90%"}} -->
<div class="wp-block-group latest-news" style="margin-top:0px;margin-bottom:0px;padding-top:60px;padding-right:20px;padding-bottom:60px;padding-left:20px"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"align":"wide","className":"section_head","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide section_head"><!-- wp:group {"className":"offer-zone-text","style":{"border":{"width":"0px","style":"none"}},"layout":{"type":"constrained","contentSize":"10%"}} -->
<div class="wp-block-group offer-zone-text" style="border-style:none;border-width:0px"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"textTransform":"capitalize"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"textColor":"white","gradient":"ternary-to-button","fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-center has-white-color has-ternary-to-button-gradient-background has-text-color has-background has-link-color has-medium-font-size" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20);text-transform:capitalize"><?php esc_html_e('Our Recent News','clothing-apparel-shop'); ?></h3>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"800","textTransform":"capitalize"}},"textColor":"secondary","fontSize":"extra-large"} -->
<h2 class="wp-block-heading has-text-align-center has-secondary-color has-text-color has-extra-large-font-size" style="font-style:normal;font-weight:800;text-transform:capitalize"><?php esc_html_e('Our Latest News','clothing-apparel-shop'); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"right":"0","left":"0"}}}} -->
<p class="has-text-align-center" style="margin-right:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><?php esc_html_e('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:query {"queryId":3,"query":{"perPage":3,"pages":"1","offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"className":"wow swing","layout":{"type":"constrained"}} -->
<div class="wp-block-group wow swing"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-featured-image {"isLink":true,"className":"news-thumb-wrap"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:post-title {"level":5,"isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.2"}},"fontSize":"regular"} /-->

<!-- wp:group {"style":{"border":{"top":{"color":"var:preset|color|primary","width":"1px"},"bottom":{"color":"var:preset|color|primary","width":"1px"},"right":{},"left":{}},"spacing":{"padding":{"top":"7px","bottom":"7px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--primary);border-top-width:1px;border-bottom-color:var(--wp--preset--color--primary);border-bottom-width:1px;padding-top:7px;padding-bottom:7px"><!-- wp:post-author {"showAvatar":false} /-->

<!-- wp:post-terms {"term":"category"} /--></div>
<!-- /wp:group -->

<!-- wp:post-excerpt {"moreText":"","showMoreOnNewLine":false} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p><?php esc_html_e('There is no post found','clothing-apparel-shop'); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->