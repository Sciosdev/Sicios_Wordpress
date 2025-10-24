(function( $ ) {
	wp.customize.bind( 'ready', function() {

		var optPrefix = '#customize-control-cake_shop_bakery_options-';
		
		// Label
		function cake_shop_bakery_customizer_label( id, title ) {

			// Colors

			if ( id === 'background_color' || id === 'background_image' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Site Identity

			if ( id === 'custom_logo' || id === 'site_icon' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// General Setting

			if ( id === 'cake_shop_bakery_preloader_hide' || id === 'cake_shop_bakery_sticky_header' || id === 'cake_shop_bakery_scroll_hide' || id === 'cake_shop_bakery_products_per_row' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Woocommerce product sale Setting

			if ( id === 'cake_shop_bakery_woocommerce_product_sale' || id === 'cake_shop_bakery_woo_product_border_radius') {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Slider

			if ( id === 'cake_shop_bakery_slider_setting' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Featured Product

			if ( id === 'cake_shop_bakery_new_product_setting' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Header Image

			if ( id === 'header_image' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Footer

			if ( id === 'cake_shop_bakery_show_hide_footer' || id === 'cake_shop_bakery_show_hide_copyright') {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Single Post Setting

			if ( id === 'cake_shop_bakery_single_post_thumb' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Single Post Comment Setting

			if ( id === 'cake_shop_bakery_single_post_comment_title' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Post Setting

			if ( id === 'cake_shop_bakery_post_page_title' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}

			// Page Setting

			if ( id === 'cake_shop_bakery_single_page_title' ) {
				$( '#customize-control-'+ id ).before('<li class="tab-title customize-control">'+ title  +'</li>');
			} else {
				$( '#customize-control-cake_shop_bakery_options-'+ id ).before('<li class="tab-title customize-control">'+ title +'</li>');
			}
			
		}

		// Colors
		cake_shop_bakery_customizer_label( 'background_color', 'Colors' );
		cake_shop_bakery_customizer_label( 'background_image', 'Background Image' );

		// Site Identity
		cake_shop_bakery_customizer_label( 'custom_logo', 'Logo Setup' );
		cake_shop_bakery_customizer_label( 'site_icon', 'Favicon' );

		// General Setting
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_preloader_hide', 'Preloader' );
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_sticky_header', 'Sticky Header' );
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_scroll_hide', 'Scroll To Top' );
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_products_per_row', 'Woocommerce Setting');
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_woocommerce_product_sale', 'Woocommerce Product Sale' );
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_woo_product_border_radius', 'Woocommerce Product Border Radius' );

		//Slider
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_slider_setting', 'Slider' );

		//Featured Product
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_new_product_setting', 'Product' );

		//Header Image
		cake_shop_bakery_customizer_label( 'header_image', 'Header Image' );

		//Footer
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_show_hide_footer', 'Footer' );
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_show_hide_copyright', 'Copyright' );

		//Single Post Setting
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_single_post_thumb', 'Single Post Setting' );
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_single_post_comment_title', 'Single Post Comment' );

		// Post Setting
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_post_page_title', 'Post Setting' );

		// Page Setting
		cake_shop_bakery_customizer_label( 'cake_shop_bakery_single_page_title', 'Page Setting' );
	

	});

})( jQuery );
