<?php
/**
 * Title: Author
 * Slug: quantumshop/author
 * Categories: quantumshop
 * Keywords: author
 * Block Types: core/post-content
 * Post Types: page, wp_template
 */
?>
<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"25px","right":"25px"}}},"backgroundColor":"base","layout":{"type":"default"}} -->
<main class="wp-block-group has-base-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:25px;padding-bottom:var(--wp--preset--spacing--30);padding-left:25px"><!-- wp:image {"align":"center","id":111,"width":"120px","height":"120px","scale":"cover","sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"100%","width":"3px"}},"borderColor":"primary","className":"is-style-rounded"} -->
<figure class="wp-block-image aligncenter size-full is-resized has-custom-border is-style-rounded"><img src="<?php echo esc_url( get_template_directory_uri() );?>/assets/images/author.jpg" alt="" class="has-border-color has-primary-border-color wp-image-111" style="border-width:3px;border-radius:100%;object-fit:cover;width:120px;height:120px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"24px"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:24px;font-style:normal;font-weight:700"> <?php echo esc_html__( 'John Doe', 'quantumshop' ); ?>  </h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast-2"}}},"spacing":{"margin":{"top":"0","bottom":"0"}}},"textColor":"contrast-2","fontSize":"sml-medium"} -->
<p class="has-text-align-center has-contrast-2-color has-text-color has-link-color has-sml-medium-font-size" style="margin-top:0;margin-bottom:0"><?php echo esc_html__( ' Senior Editor', 'quantumshop' ); ?> </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<p class="has-text-align-center" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php echo esc_html__( ' Proin magna. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Proin pretium, leo ac pellentesque mollis, felis nunc ultrices eros, sed gravida augue augue mollis justo. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Praesent venenatis metus at tortor pulvinar varius.', 'quantumshop' ); ?> </p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"layout":{"type":"flex","justifyContent":"center"}} -->
<ul class="wp-block-social-links"><!-- wp:social-link {"url":"#","service":"facebook"} /-->

<!-- wp:social-link {"url":"#","service":"youtube"} /-->

<!-- wp:social-link {"url":"#","service":"linkedin"} /--></ul>
<!-- /wp:social-links --></main>
<!-- /wp:group -->