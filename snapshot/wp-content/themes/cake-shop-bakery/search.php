<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package Cake Shop Bakery
 */
get_header(); ?>

    <div id="skip-content" class="container">
        <div class="row mb-5">
            <?php if (get_theme_mod('cake_shop_bakery_blog_sidebar_position','Right Side') == 'Left Side'){?>
                <?php get_sidebar(); ?>
            <?php }?>
            <div id="primary" class="content-area col-lg-9 col-md-8 ">
                <main id="main" class="site-main module-border-wrap mb-4">
                    <div class="row">
                        <?php if (have_posts()) { ?>
                            <header class="page-header">
                                <h4 class="page-title">
                                    <?php
                                        /* translators: %s: search query. */
                                        printf(esc_html__('Search Results for: %s', 'cake-shop-bakery'), '<span>' . get_search_query() . '</span>');
                                    ?>
                                </h4>
                            </header>
                            <?php
                            /* Start the Loop */
                            while (have_posts()) : the_post();

                                /**
                                 * Run the loop for the search to output the results.
                                 * If you want to overload this in a child theme then include a file
                                 * called content-search.php and that will be used instead.
                                 */
                                get_template_part( 'template-parts/content',get_post_format());

                            endwhile;

                            if( get_theme_mod('cake_shop_bakery_post_page_pagination',1) == 1){ 
                                cake_shop_bakery_blog_posts_pagination(); 
                            }

                        }else {

                            get_template_part('template-parts/content', 'none');

                        } ?>
                    </div>
                </main>
            </div>
            <?php if (get_theme_mod('cake_shop_bakery_blog_sidebar_position','Right Side') == 'Right Side'){?>
                <?php get_sidebar(); ?>
            <?php }?>
        </div>
    </div>

<?php get_footer();