<?php
 /**
  * Title: Single Post
  * Slug: quantumshop/single-post
  * Inserter: no
  */
?>
<!-- wp:cover {"overlayColor":"primary","minHeight":232,"minHeightUnit":"px","tagName":"main","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-cover" style="margin-top:0;margin-bottom:0;min-height:232px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:post-title {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"x-large"} /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:post-author-name {"isLink":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|link-color"}}}},"textColor":"link-color"} /-->

<!-- wp:post-date {"isLink":true,"style":{"elements":{"link":{"color":{"text":"var:preset|color|link-color"}}}},"textColor":"link-color"} /--></div>
<!-- /wp:group --></div></main>
<!-- /wp:cover -->

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"padding":{"right":"24px","left":"24px"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);padding-right:24px;padding-left:24px"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:post-featured-image {"style":{"border":{"radius":"0px"}}} /-->

<!-- wp:post-content /-->

<!-- wp:post-terms {"term":"category","prefix":"\u003cstrong\u003eCategories\u003c/strong\u003e: "} /-->

<!-- wp:post-terms {"term":"post_tag"} /-->

<!-- wp:post-comments-form /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","style":{"spacing":{"padding":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="padding-left:var(--wp--preset--spacing--30);flex-basis:33.33%"><!-- wp:template-part {"slug":"sidebar","theme":"quantumshop","area":"uncategorized"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></main>
<!-- /wp:group -->