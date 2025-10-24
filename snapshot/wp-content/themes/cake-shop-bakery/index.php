<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Cake Shop Bakery
 */

get_header(); ?>

    <div id="skip-content" class="container">
        <div class="row mb-5">
            <?php if (get_theme_mod('cake_shop_bakery_blog_sidebar_position','Right Side') == 'Left Side'){?>
                <?php get_sidebar(); ?>
            <?php }?>
            <div id="primary" class="content-area col-lg-9 col-md-8">
                <main id="main" class="site-main">
                    
                    <?php if (have_posts()) { 
                        if (is_home() && !is_front_page()) : ?>
                            <header>
                                <h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
                            </header>
                        <?php endif; ?>

                    <div class="row">
                        <?php /* Start the Loop */
                        while (have_posts()) :
                            the_post();

                            get_template_part( 'template-parts/content',get_post_format());

                        endwhile;

                        if( get_theme_mod('cake_shop_bakery_post_page_pagination',1) == 1){ 
                            cake_shop_bakery_blog_posts_pagination(); 
                        }

                        } else {

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