<?php
/**
 * Template Name: Home Template
 */

get_header(); ?>

<main id="skip-content" slider-loop="<?php echo esc_html(get_theme_mod('cake_shop_bakery_slider_loop')); ?>">
  <section id="top-slider">
    <?php if(get_theme_mod('cake_shop_bakery_slider_setting') != ''){ ?>
    <?php $cake_shop_bakery_slide_pages = array();
      for ( $cake_shop_bakery_count = 1; $cake_shop_bakery_count <= 3; $cake_shop_bakery_count++ ) {
        $cake_shop_bakery_mod = intval( get_theme_mod( 'cake_shop_bakery_top_slider_page' . $cake_shop_bakery_count ));
        if ( 'page-none-selected' != $cake_shop_bakery_mod ) {
          $cake_shop_bakery_slide_pages[] = $cake_shop_bakery_mod;
        }
      }
      if( !empty($cake_shop_bakery_slide_pages) ) :
        $cake_shop_bakery_args = array(
          'post_type' => 'page',
          'post__in' => $cake_shop_bakery_slide_pages,
          'orderby' => 'post__in'
        );
        $cake_shop_bakery_query = new WP_Query( $cake_shop_bakery_args );
        if ( $cake_shop_bakery_query->have_posts() ) :
          $i = 1;
    ?>
    <div class="owl-carousel" role="listbox">
      <?php  while ( $cake_shop_bakery_query->have_posts() ) : $cake_shop_bakery_query->the_post(); ?>
        <div class="slider-box">
          <?php if(has_post_thumbnail()){
            the_post_thumbnail();
            } else{?>
            <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/slider.png" alt="" />
          <?php } ?>
          <div class="slider-inner-box">
            <?php if(get_theme_mod('cake_shop_bakery_slider_title_setting',1) == 1){ ?>
              <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
            <?php }?>
            <?php if(get_theme_mod('cake_shop_bakery_slider_content_setting',1) == 1){ ?>
              <p><?php echo wp_trim_words( get_the_content(), esc_attr(get_theme_mod('cake_shop_bakery_slider_excerpt_length', 30)) ); ?></p>
            <?php }?>
            <?php if(get_theme_mod('cake_shop_bakery_slider_button_setting',1) == 1 && get_theme_mod('cake_shop_bakery_slider_button_text','Order Now') != ''){ ?>
              <div class="slider-box-btn mt-4">
                <a href="<?php the_permalink(); ?>"><?php esc_html_e(get_theme_mod('cake_shop_bakery_slider_button_text','Order Now')); ?><i class="fas fa-angle-right ms-2"></i></a>
              </div>
            <?php }?>
          </div>
        </div>
      <?php $i++; endwhile;
      wp_reset_postdata();?>
    </div>
    <?php else : ?>
      <div class="no-postfound"></div>
    <?php endif;
    endif;?>
    <?php } ?>
  </section>

<?php if(get_theme_mod('cake_shop_bakery_new_product_setting') != ''){ ?>
  <section id="new-products" class="py-5">
    <div class="container">
      <?php if(get_theme_mod('cake_shop_bakery_new_product_title') != ''){ ?>
        <h3 class="text-center"><?php echo esc_html(get_theme_mod('cake_shop_bakery_new_product_title','')); ?></h3>
      <?php }?>
      <?php if(get_theme_mod('cake_shop_bakery_new_product_text') != ''){ ?>
        <p class="text-center"><?php echo esc_html(get_theme_mod('cake_shop_bakery_new_product_text','')); ?></p>
      <?php }?>
      <div class="row mt-5">
        <?php
        if ( class_exists( 'WooCommerce' ) ) {
          $cake_shop_bakery_args = array(
            'post_type' => 'product',
            'posts_per_page' => get_theme_mod('cake_shop_bakery_new_product_number'),
            'product_cat' => get_theme_mod('cake_shop_bakery_new_product_category'),
            'order' => 'ASC'
          );
          $loop = new WP_Query( $cake_shop_bakery_args );
          while ( $loop->have_posts() ) : $loop->the_post(); global $product; ?>
            <div class="col-lg-3 col-md-4 col-sm-4">
              <div class="product-box mb-4">
                <div class="product-image mb-3">
                  <?php if (has_post_thumbnail( $loop->post->ID )) echo get_the_post_thumbnail($loop->post->ID, 'shop_catalog'); else echo '<img src="'.esc_url(woocommerce_placeholder_img_src()).'" />'; ?>
                </div>
                <?php woocommerce_show_product_sale_flash( $post, $product ); ?>
                <p class="my-2 product-title"><a href="<?php echo esc_url(get_permalink( $loop->post->ID )); ?>"><?php the_title(); ?></a></p>
                <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?> mb-0"><?php echo $product->get_price_html(); ?></p>
                <div class="addtocart mt-3">
                  <?php if( $product->is_type( 'simple' ) ) { woocommerce_template_loop_add_to_cart(  $loop->post, $product );} ?>
                </div>
              </div>
            </div>
          <?php endwhile; wp_reset_query(); ?>
        <?php } ?>
      </div>
    </div>
  </section>
  <?php } ?>

  <section id="page-content">
    <div class="container">
      <div class="py-5">
        <?php
          if ( have_posts() ) :
            while ( have_posts() ) : the_post();
              the_content();
            endwhile;
          endif;
        ?>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
