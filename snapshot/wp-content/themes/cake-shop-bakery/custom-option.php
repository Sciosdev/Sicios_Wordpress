<?php

    $cake_shop_bakery_theme_css= "";

    /*--------------------------- Scroll To Top Positions -------------------*/

    $cake_shop_bakery_scroll_position = get_theme_mod( 'cake_shop_bakery_scroll_top_position','Right');
    if($cake_shop_bakery_scroll_position == 'Right'){
        $cake_shop_bakery_theme_css .='#button{';
            $cake_shop_bakery_theme_css .='right: 20px;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_scroll_position == 'Left'){
        $cake_shop_bakery_theme_css .='#button{';
            $cake_shop_bakery_theme_css .='left: 20px; right: auto;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_scroll_position == 'Center'){
        $cake_shop_bakery_theme_css .='#button{';
            $cake_shop_bakery_theme_css .='right: auto;left: 50%; transform:translateX(-50%);';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Scroll To Top Border Radius -------------------*/

    $cake_shop_bakery_scroll_to_top_border_radius = get_theme_mod('cake_shop_bakery_scroll_to_top_border_radius');
    $cake_shop_bakery_scroll_bg_color = get_theme_mod('cake_shop_bakery_scroll_bg_color');
    $cake_shop_bakery_scroll_color = get_theme_mod('cake_shop_bakery_scroll_color');
    $cake_shop_bakery_scroll_font_size = get_theme_mod('cake_shop_bakery_scroll_font_size');
    if($cake_shop_bakery_scroll_to_top_border_radius != false || $cake_shop_bakery_scroll_bg_color != false || $cake_shop_bakery_scroll_color != false || $cake_shop_bakery_scroll_font_size != false){
        $cake_shop_bakery_theme_css .='#colophon a#button{';
            $cake_shop_bakery_theme_css .='border-radius: '.esc_attr($cake_shop_bakery_scroll_to_top_border_radius).'px; background-color: '.esc_attr($cake_shop_bakery_scroll_bg_color).'; color: '.esc_attr($cake_shop_bakery_scroll_color).' !important; font-size: '.esc_attr($cake_shop_bakery_scroll_font_size).'px;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Slider Image Opacity -------------------*/
    $cake_shop_bakery_slider_opacity_setting = get_theme_mod( 'cake_shop_bakery_slider_opacity_setting',true);
    $cake_shop_bakery_image_opacity_color = get_theme_mod( 'cake_shop_bakery_image_opacity_color');
    $cake_shop_bakery_slider_opacity = get_theme_mod( 'cake_shop_bakery_slider_opacity');
    if( $cake_shop_bakery_slider_opacity_setting != false) {
        $cake_shop_bakery_theme_css .='#top-slider .slider-box img{';
            $cake_shop_bakery_theme_css .='opacity: '. $cake_shop_bakery_slider_opacity. ';';
        $cake_shop_bakery_theme_css .='}';
        if( $cake_shop_bakery_image_opacity_color != '') {
            $cake_shop_bakery_theme_css .='#top-slider .slider-box {';
                $cake_shop_bakery_theme_css .='background-color: '. $cake_shop_bakery_image_opacity_color. ';';
            $cake_shop_bakery_theme_css .='}';
        }
    } else {
        $cake_shop_bakery_theme_css .='#top-slider .slider-box img{';
            $cake_shop_bakery_theme_css .='opacity: 1;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*---------------------------Slider Height ------------*/

    $cake_shop_bakery_slider_img_height = get_theme_mod('cake_shop_bakery_slider_img_height');
    if($cake_shop_bakery_slider_img_height != false){
        $cake_shop_bakery_theme_css .='#top-slider .owl-carousel .owl-item img{';
            $cake_shop_bakery_theme_css .='height: '.esc_attr($cake_shop_bakery_slider_img_height).';';
        $cake_shop_bakery_theme_css .='}';
    }

    /*---------------- Single post Settings ------------------*/

    $cake_shop_bakery_single_post_navigation_show_hide = get_theme_mod('cake_shop_bakery_single_post_navigation_show_hide',true);
    if($cake_shop_bakery_single_post_navigation_show_hide != true){
        $cake_shop_bakery_theme_css .='.nav-links{';
            $cake_shop_bakery_theme_css .='display: none;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Woocommerce Product Sale Positions -------------------*/

    $cake_shop_bakery_product_sale = get_theme_mod( 'cake_shop_bakery_woocommerce_product_sale','Right');
    if($cake_shop_bakery_product_sale == 'Right'){
        $cake_shop_bakery_theme_css .='.woocommerce ul.products li.product .onsale{';
            $cake_shop_bakery_theme_css .='left: auto; right: 15px;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_product_sale == 'Left'){
        $cake_shop_bakery_theme_css .='.woocommerce ul.products li.product .onsale{';
            $cake_shop_bakery_theme_css .='left: 15px; right: auto;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_product_sale == 'Center'){
        $cake_shop_bakery_theme_css .='.woocommerce ul.products li.product .onsale{';
            $cake_shop_bakery_theme_css .='right: 50%;left: 50%;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Woocommerce Product Sale Border Radius -------------------*/

    $cake_shop_bakery_woo_product_sale_border_radius = get_theme_mod('cake_shop_bakery_woo_product_sale_border_radius');
    if($cake_shop_bakery_woo_product_sale_border_radius != false){
        $cake_shop_bakery_theme_css .='.woocommerce ul.products li.product .onsale{';
            $cake_shop_bakery_theme_css .='border-radius: '.esc_attr($cake_shop_bakery_woo_product_sale_border_radius).'px;';
        $cake_shop_bakery_theme_css .='}';
    }

     /*--------------------------- Woocommerce Product Border Radius -------------------*/

    $cake_shop_bakery_woo_product_border_radius = get_theme_mod('cake_shop_bakery_woo_product_border_radius', 0);
    if($cake_shop_bakery_woo_product_border_radius != false){
        $cake_shop_bakery_theme_css .='.woocommerce ul.products li.product a img{';
            $cake_shop_bakery_theme_css .='border-radius: '.esc_attr($cake_shop_bakery_woo_product_border_radius).'px;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Single Post Page Image Box Shadow -------------------*/

    $cake_shop_bakery_single_post_page_image_box_shadow = get_theme_mod('cake_shop_bakery_single_post_page_image_box_shadow',0);
    if($cake_shop_bakery_single_post_page_image_box_shadow != false){
        $cake_shop_bakery_theme_css .='.single-post .entry-header img{';
            $cake_shop_bakery_theme_css .='box-shadow: '.esc_attr($cake_shop_bakery_single_post_page_image_box_shadow).'px '.esc_attr($cake_shop_bakery_single_post_page_image_box_shadow).'px '.esc_attr($cake_shop_bakery_single_post_page_image_box_shadow).'px #cccccc;';
        $cake_shop_bakery_theme_css .='}';
    }

     /*--------------------------- Single Post Page Image Border Radius -------------------*/

    $cake_shop_bakery_single_post_page_image_border_radius = get_theme_mod('cake_shop_bakery_single_post_page_image_border_radius', 0);
    if($cake_shop_bakery_single_post_page_image_border_radius != false){
        $cake_shop_bakery_theme_css .='.single-post .entry-header img{';
            $cake_shop_bakery_theme_css .='border-radius: '.esc_attr($cake_shop_bakery_single_post_page_image_border_radius).'px;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Footer Background Image Position -------------------*/

    $cake_shop_bakery_footer_bg_image_position = get_theme_mod( 'cake_shop_bakery_footer_bg_image_position','scroll');
    if($cake_shop_bakery_footer_bg_image_position == 'fixed'){
        $cake_shop_bakery_theme_css .='#colophon, .footer-widgets{';
            $cake_shop_bakery_theme_css .='background-attachment: fixed !important; background-position: center !important;';
        $cake_shop_bakery_theme_css .='}';
    }elseif ($cake_shop_bakery_footer_bg_image_position == 'scroll'){
        $cake_shop_bakery_theme_css .='#colophon, .footer-widgets{';
            $cake_shop_bakery_theme_css .='background-attachment: scroll !important; background-position: center !important;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Footer Widget Heading Alignment -------------------*/

    $cake_shop_bakery_footer_widget_heading_alignment = get_theme_mod( 'cake_shop_bakery_footer_widget_heading_alignment','Left');
    if($cake_shop_bakery_footer_widget_heading_alignment == 'Left'){
        $cake_shop_bakery_theme_css .='#colophon h5, h5.footer-column-widget-title{';
        $cake_shop_bakery_theme_css .='text-align: left;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_footer_widget_heading_alignment == 'Center'){
        $cake_shop_bakery_theme_css .='#colophon h5, h5.footer-column-widget-title{';
            $cake_shop_bakery_theme_css .='text-align: center;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_footer_widget_heading_alignment == 'Right'){
        $cake_shop_bakery_theme_css .='#colophon h5, h5.footer-column-widget-title{';
            $cake_shop_bakery_theme_css .='text-align: right;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Footer background image -------------------*/

    $cake_shop_bakery_footer_bg_image = get_theme_mod('cake_shop_bakery_footer_bg_image');
    if($cake_shop_bakery_footer_bg_image != false){
        $cake_shop_bakery_theme_css .='#colophon, .footer-widgets{';
            $cake_shop_bakery_theme_css .='background: url('.esc_attr($cake_shop_bakery_footer_bg_image).');';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Copyright Background Color -------------------*/

    $cake_shop_bakery_copyright_background_color = get_theme_mod('cake_shop_bakery_copyright_background_color');
    if($cake_shop_bakery_copyright_background_color != false){
        $cake_shop_bakery_theme_css .='.footer_info{';
            $cake_shop_bakery_theme_css .='background-color: '.esc_attr($cake_shop_bakery_copyright_background_color).' !important;';
        $cake_shop_bakery_theme_css .='}';
    } 

    /*--------------------------- Site Title And Tagline Color -------------------*/

    $cake_shop_bakery_logo_title_color = get_theme_mod('cake_shop_bakery_logo_title_color');
    if($cake_shop_bakery_logo_title_color != false){
        $cake_shop_bakery_theme_css .='p.site-title a, .navbar-brand a{';
            $cake_shop_bakery_theme_css .='color: '.esc_attr($cake_shop_bakery_logo_title_color).' !important;';
        $cake_shop_bakery_theme_css .='}';
    }

    $cake_shop_bakery_logo_tagline_color = get_theme_mod('cake_shop_bakery_logo_tagline_color');
    if($cake_shop_bakery_logo_tagline_color != false){
        $cake_shop_bakery_theme_css .='.logo p.site-description, .navbar-brand p{';
            $cake_shop_bakery_theme_css .='color: '.esc_attr($cake_shop_bakery_logo_tagline_color).'  !important;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Footer Widget Content Alignment -------------------*/

    $cake_shop_bakery_footer_widget_content_alignment = get_theme_mod( 'cake_shop_bakery_footer_widget_content_alignment','Left');
    if($cake_shop_bakery_footer_widget_content_alignment == 'Left'){
        $cake_shop_bakery_theme_css .='#colophon ul, #colophon p, .tagcloud, .widget{';
        $cake_shop_bakery_theme_css .='text-align: left;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_footer_widget_content_alignment == 'Center'){
        $cake_shop_bakery_theme_css .='#colophon ul, #colophon p, .tagcloud, .widget{';
            $cake_shop_bakery_theme_css .='text-align: center;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_footer_widget_content_alignment == 'Right'){
        $cake_shop_bakery_theme_css .='#colophon ul, #colophon p, .tagcloud, .widget{';
            $cake_shop_bakery_theme_css .='text-align: right;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*--------------------------- Copyright Content Alignment -------------------*/

    $cake_shop_bakery_copyright_content_alignment = get_theme_mod( 'cake_shop_bakery_copyright_content_alignment','Center');
    if($cake_shop_bakery_copyright_content_alignment == 'Left'){
        $cake_shop_bakery_theme_css .='.footer-menu-left{';
        $cake_shop_bakery_theme_css .='text-align: left;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_copyright_content_alignment == 'Center'){
        $cake_shop_bakery_theme_css .='.footer-menu-left{';
            $cake_shop_bakery_theme_css .='text-align: center;';
        $cake_shop_bakery_theme_css .='}';
    }else if($cake_shop_bakery_copyright_content_alignment == 'Right'){
        $cake_shop_bakery_theme_css .='.footer-menu-left{';
            $cake_shop_bakery_theme_css .='text-align: right;';
        $cake_shop_bakery_theme_css .='}';
    }

    /*------------------ Nav Menus -------------------*/

    $cake_shop_bakery_nav_menu = get_theme_mod( 'cake_shop_bakery_nav_menu_text_transform','Capitalize');
    if($cake_shop_bakery_nav_menu == 'Capitalize'){
        $cake_shop_bakery_theme_css .='.main-navigation .menu > li > a{';
            $cake_shop_bakery_theme_css .='text-transform:Capitalize;';
        $cake_shop_bakery_theme_css .='}';
    }
    if($cake_shop_bakery_nav_menu == 'Lowercase'){
        $cake_shop_bakery_theme_css .='.main-navigation .menu > li > a{';
            $cake_shop_bakery_theme_css .='text-transform:Lowercase;';
        $cake_shop_bakery_theme_css .='}';
    }
    if($cake_shop_bakery_nav_menu == 'Uppercase'){
        $cake_shop_bakery_theme_css .='.main-navigation .menu > li > a{';
            $cake_shop_bakery_theme_css .='text-transform:Uppercase;';
        $cake_shop_bakery_theme_css .='}';
    }

    $cake_shop_bakery_menu_font_size = get_theme_mod( 'cake_shop_bakery_menu_font_size');
    if($cake_shop_bakery_menu_font_size != ''){
        $cake_shop_bakery_theme_css .='.main-navigation .menu > li > a{';
            $cake_shop_bakery_theme_css .='font-size: '.esc_attr($cake_shop_bakery_menu_font_size).'px;';
        $cake_shop_bakery_theme_css .='}';
    }

    $cake_shop_bakery_nav_menu_font_weight = get_theme_mod( 'cake_shop_bakery_nav_menu_font_weight',500);
    if($cake_shop_bakery_menu_font_size != ''){
        $cake_shop_bakery_theme_css .='.main-navigation .menu > li > a{';
            $cake_shop_bakery_theme_css .='font-weight: '.esc_attr($cake_shop_bakery_nav_menu_font_weight).';';
        $cake_shop_bakery_theme_css .='}';
    }

    /*------------------ Slider CSS -------------------*/

    $cake_shop_bakery_slider_content_layout = get_theme_mod( 'cake_shop_bakery_slider_content_layout','Left');
    if($cake_shop_bakery_slider_content_layout == 'Left'){
        $cake_shop_bakery_theme_css .='.slider-inner-box, #top-slider .slider-inner-box p{';
            $cake_shop_bakery_theme_css .='text-align : left;';
        $cake_shop_bakery_theme_css .='}';
    }
    if($cake_shop_bakery_slider_content_layout == 'Center'){
        $cake_shop_bakery_theme_css .='.slider-inner-box, #top-slider .slider-inner-box p{';
            $cake_shop_bakery_theme_css .='text-align : center;';
        $cake_shop_bakery_theme_css .='}';
    }
    if($cake_shop_bakery_slider_content_layout == 'Right'){
        $cake_shop_bakery_theme_css .='.slider-inner-box, #top-slider .slider-inner-box p{';
            $cake_shop_bakery_theme_css .='text-align : right;';
        $cake_shop_bakery_theme_css .='}';
    }