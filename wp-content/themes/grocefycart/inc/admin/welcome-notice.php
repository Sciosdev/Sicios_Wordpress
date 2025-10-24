<?php

/**
 * file for holding dashboard welcome page for theme
 */
if ( ! function_exists( 'shopmighty_is_plugin_installed' ) ) {
	function shopmighty_is_plugin_installed( $plugin_slug ) {
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
		return file_exists( $plugin_path );
	}
}
if ( ! function_exists( 'shopmighty_is_plugin_activated' ) ) {
	function shopmighty_is_plugin_activated( $plugin_slug ) {
		return is_plugin_active( $plugin_slug );
	}
}
if ( ! function_exists( 'shopmighty_welcome_notice' ) ) :
	function shopmighty_welcome_notice() {
		if ( get_option( 'shopmighty_dismissed_custom_notice' ) ) {
			return;
		}
		global $pagenow;
		$current_screen = get_current_screen();

		if ( is_admin() ) {
			if ( $current_screen->id !== 'dashboard' && $current_screen->id !== 'themes' ) {
				return;
			}
			if ( is_network_admin() ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$theme = wp_get_theme();

			if ( is_child_theme() ) {
				$theme = wp_get_theme()->parent();
			}
			$shopmighty_version = $theme->get( 'Version' );

			?>
			<div class="grocefycart-admin-notice notice notice-info is-dismissible content-install-plugin theme-info-notice" id="grocefycart-dismiss-notice">
				<div class="info-content">
					<div class="notice-holder">
						<h5><span class="theme-name"><span><?php echo __( 'Welcome to Grocefycart', 'grocefycart' ); ?></span></h5>
						<h2><?php echo __( 'Launch Your Online Store Effortlessly with Grocefycart! 🚀 ', 'grocefycart' ); ?></h2>
						</h3>
						<div class="notice-text">
							<p><?php echo __( 'Please install and activate all recommended plugins to access 40+ advanced blocks, pre-built starter demos, and the one-click demo importer. Launch your online store in minutes with just a few clicks! - Cozy Addons, Cozy Essential Addons, Advanced Import, QuiqOwl-Ajax Search for WooCommerce!', 'grocefycart' ); ?></p>
						</div>
						<a href="#" id="install-activate-button" class="button admin-button info-button"><?php echo __( 'Getting started with a single click', 'grocefycart' ); ?></a>
						<a href="<?php echo admin_url(); ?>themes.php?page=about-grocefycart" class="button admin-button info-button"><?php echo __( 'Explore Grocefycart', 'grocefycart' ); ?></a>

					</div>
				</div>
				<div class="theme-hero-screens">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/inc/admin/images/admin-panel-img.png' ); ?>" />
				</div>

			</div>
			<?php
		}
	}
endif;
add_action( 'admin_notices', 'shopmighty_welcome_notice' );
function shopmighty_dismissble_notice() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'shopmighty_admin_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
		return;
	}

	$result = update_option( 'shopmighty_dismissed_custom_notice', 1 );

	if ( $result ) {
		wp_send_json_success();
	} else {
		wp_send_json_error( array( 'message' => 'Failed to update option' ) );
	}
}
add_action( 'wp_ajax_shopmighty_dismissble_notice', 'shopmighty_dismissble_notice' );
// Hook into a custom action when the button is clicked
add_action( 'wp_ajax_shopmighty_install_and_activate_plugins', 'shopmighty_install_and_activate_plugins' );
add_action( 'wp_ajax_nopriv_shopmighty_install_and_activate_plugins', 'shopmighty_install_and_activate_plugins' );
add_action( 'wp_ajax_shopmighty_rplugin_activation', 'shopmighty_rplugin_activation' );
add_action( 'wp_ajax_nopriv_shopmighty_rplugin_activation', 'shopmighty_rplugin_activation' );

// Function to install and activate the plugins



function check_plugin_installed_status( $pugin_slug, $plugin_file ) {
	return file_exists( ABSPATH . 'wp-content/plugins/' . $pugin_slug . '/' . $plugin_file ) ? true : false;
}

/* Check if plugin is activated */


function check_plugin_active_status( $pugin_slug, $plugin_file ) {
	return is_plugin_active( $pugin_slug . '/' . $plugin_file ) ? true : false;
}

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
function shopmighty_install_and_activate_plugins() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_ajax_referer( 'shopmighty_welcome_nonce', 'nonce' );
	// Define the plugins to be installed and activated
	$recommended_plugins = array(
		array(
			'slug' => 'cozy-addons',
			'file' => 'cozy-addons.php',
			'name' => __( 'Cozy Blocks', 'grocefycart' ),
		),
		array(
			'slug' => 'advanced-import',
			'file' => 'advanced-import.php',
			'name' => __( 'Advanced Imporrt', 'grocefycart' ),
		),
		array(
			'slug' => 'cozy-essential-addons',
			'file' => 'cozy-essential-addons.php',
			'name' => __( 'Cozy Essential Addons', 'grocefycart' ),
		),
		array(
			'slug' => 'quiqowl',
			'file' => 'quiqowl.php',
			'name' => __( 'QuiqOwl Ajax Search for WooCommerce', 'grocefycart' ),
		),
		// Add more plugins here as needed
	);

	// Include the necessary WordPress functions

	// Set up a transient to store the installation progress
	set_transient( 'install_and_activate_progress', array(), MINUTE_IN_SECONDS * 10 );

	// Loop through each plugin
	foreach ( $recommended_plugins as $plugin ) {
		$plugin_slug = $plugin['slug'];
		$plugin_file = $plugin['file'];
		$plugin_name = $plugin['name'];

		// Check if the plugin is active
		if ( is_plugin_active( $plugin_slug . '/' . $plugin_file ) ) {
			update_install_and_activate_progress( $plugin_name, 'Already Active' );
			continue; // Skip to the next plugin
		}

		// Check if the plugin is installed but not active
		if ( is_shopmighty_plugin_installed( $plugin_slug . '/' . $plugin_file ) ) {
			$activate = activate_plugin( $plugin_slug . '/' . $plugin_file );
			if ( is_wp_error( $activate ) ) {
				update_install_and_activate_progress( $plugin_name, 'Error' );
				continue; // Skip to the next plugin
			}
			update_install_and_activate_progress( $plugin_name, 'Activated' );
			continue; // Skip to the next plugin
		}

		// Plugin is not installed or activated, proceed with installation
		update_install_and_activate_progress( $plugin_name, 'Installing' );

		// Fetch plugin information
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array( 'sections' => false ),
			)
		);

		// Check if plugin information is fetched successfully
		if ( is_wp_error( $api ) ) {
			update_install_and_activate_progress( $plugin_name, 'Error' );
			continue; // Skip to the next plugin
		}

		// Set up the plugin upgrader
		$upgrader = new Plugin_Upgrader();
		$install  = $upgrader->install( $api->download_link );

		// Check if installation is successful
		if ( $install ) {
			// Activate the plugin
			$activate = activate_plugin( $plugin_slug . '/' . $plugin_file );

			// Check if activation is successful
			if ( is_wp_error( $activate ) ) {
				update_install_and_activate_progress( $plugin_name, 'Error' );
				continue; // Skip to the next plugin
			}
			update_install_and_activate_progress( $plugin_name, 'Activated' );
		} else {
			update_install_and_activate_progress( $plugin_name, 'Error' );
		}
	}

	// Delete the progress transient
	$redirect_url = admin_url( 'themes.php?page=advanced-import' );

	// Delete the progress transient
	delete_transient( 'install_and_activate_progress' );
	// Return JSON response
	wp_send_json_success( array( 'redirect_url' => $redirect_url ) );
}

// Function to check if a plugin is installed but not active
function is_shopmighty_plugin_installed( $plugin_slug ) {
	$plugins = get_plugins();
	return isset( $plugins[ $plugin_slug ] );
}

// Function to update the installation and activation progress
function update_install_and_activate_progress( $plugin_name, $status ) {
	$progress   = get_transient( 'install_and_activate_progress' );
	$progress[] = array(
		'plugin' => $plugin_name,
		'status' => $status,
	);
	set_transient( 'install_and_activate_progress', $progress, MINUTE_IN_SECONDS * 10 );
}


function shopmighty_dashboard_menu() {
	add_theme_page( esc_html__( 'About Grocefycart', 'grocefycart' ), esc_html__( 'About Grocefycart', 'grocefycart' ), 'edit_theme_options', 'about-grocefycart', 'shopmighty_theme_info_display' );
}
add_action( 'admin_menu', 'shopmighty_dashboard_menu' );
function shopmighty_theme_info_display() {

	?>
	<div class="dashboard-about-grocefycart">
		<div class="grocefycart-dashboard">
			<ul id="grocefycart-dashboard-tabs-nav">
				<li><a href="#grocefycart-welcome"><?php echo __( 'Get Started', 'grocefycart' ); ?></a></li>
				<li><a href="#grocefycart-setup"><?php echo __( 'Setup Instruction', 'grocefycart' ); ?></a></li>
				<li><a href="#grocefycart-comparision"><?php echo __( 'FREE VS PRO', 'grocefycart' ); ?></a></li>
			</ul> <!-- END tabs-nav -->
			<div id="tabs-content">
				<div id="grocefycart-welcome" class="tab-content">
					<h1> <?php echo __( 'Welcome to the Grocefycart', 'grocefycart' ); ?></h1>
					<span>
						<p><?php echo __( 'Grocefycart is a minimal, clean, and versatile WooCommerce theme designed to launch your online store effortlessly. Despite being a Full Site Editing (FSE) theme, it allows you to customize every corner of your website without limitations, making it easy to achieve your brand’s perfect fit and design layout—whether it’s the header, footer, blog, shop catalog, or the entire site. It is suitable for various online stores, including fashion and clothing, beauty and skincare, electronics and smart gadgets, home decor and furniture, hardware and tools, grocery and food, fitness and sports gear, jewelry and accessories, pet supplies, books and stationery, automotive parts, baby and kid’s products, and any other eCommerce niche. Grocefycart offers ready-to-use pre-built sections, templates, and starter site demos with a one-click demo import option, enabling you to set up your store effortlessly. Discover all features and live demos at https://cozythemes.com/grocefycart-woocommerce-theme/.', 'grocefycart' ); ?></p>
					</span>
					<h3><?php echo __( 'Required Plugins', 'grocefycart' ); ?></h3>
					<p class="notice-text"><?php echo __( 'Please install and activate all recommended pluign to import the demo with "one click demo import" feature and unlock premium features!(for pro version)', 'grocefycart' ); ?></p>
					<ul class="grocefycart-required-plugin">
						<li>

							<h4><?php echo __( '1. Cozy Addons', 'grocefycart' ); ?>
								<?php
								if ( shopmighty_is_plugin_activated( 'cozy-addons/cozy-addons.php' ) ) {
									echo __( ': Plugin has been already activated!', 'grocefycart' );
								} elseif ( shopmighty_is_plugin_installed( 'cozy-addons/cozy-addons.php' ) ) {
									echo __( ': Plugin does not activated, Activate the plugin to use one click demo import and unlock premium features.', 'grocefycart' );
								} else {
									echo ': <a href="' . get_admin_url() . 'plugin-install.php?tab=plugin-information&plugin=cozy-addons&TB_iframe=true&width=600&height=550">' . esc_html__( 'Install and Activate', 'grocefycart' ) . '</a>';
								}
								?>
							</h4>
						</li>
						<li>

							<h4><?php echo __( '2. Cozy Essential Addons', 'grocefycart' ); ?>
								<?php
								if ( shopmighty_is_plugin_activated( 'cozy-essential-addons/cozy-essential-addons.php' ) ) {
									echo __( ': Plugin has been already activated!', 'grocefycart' );
								} elseif ( shopmighty_is_plugin_installed( 'cozy-essential-addons/cozy-essential-addons.php' ) ) {
									echo __( ': Plugin does not activated, Activate the plugin to use one click demo import and unlock premium features.', 'grocefycart' );
								} else {
									echo ': <a href="' . get_admin_url() . 'plugin-install.php?tab=plugin-information&plugin=cozy-essential-addons&TB_iframe=true&width=600&height=550">' . esc_html__( 'Install and Activate', 'grocefycart' ) . '</a>';
								}
								?>
							</h4>
						</li>
						<li>
							<h4><?php echo __( '3. Advanced Import - Need only to use "one click demo import" features', 'grocefycart' ); ?>
								<?php
								if ( shopmighty_is_plugin_activated( 'advanced-import/advanced-import.php' ) ) {
									echo __( ': Plugin has been already activated!', 'grocefycart' );
								} elseif ( shopmighty_is_plugin_installed( 'advanced-import/advanced-import.php' ) ) {
									echo __( ': Plugin does not activated, Activate the plugin to use one click demo import feature.', 'grocefycart' );
								} else {
									echo ': <a href="' . get_admin_url() . 'plugin-install.php?tab=plugin-information&plugin=advanced-import&TB_iframe=true&width=600&height=550">' . esc_html__( 'Install and Activate', 'grocefycart' ) . '</a>';
								}
								?>
							</h4>
						</li>
						<li>
							<h4><?php echo __( '4. QuiqOwl - Ajax Search for WooCommerce', 'grocefycart' ); ?>
								<?php
								if ( shopmighty_is_plugin_activated( 'quiqowl/quiqowl.php' ) ) {
									echo __( ': Plugin has been already activated!', 'grocefycart' );
								} elseif ( shopmighty_is_plugin_installed( 'quiqowl/quiqowl.php' ) ) {
									echo __( ': Plugin does not activated, Activate the plugin to use one click demo import feature.', 'grocefycart' );
								} else {
									echo ': <a href="' . get_admin_url() . 'plugin-install.php?tab=plugin-information&plugin=quiqowl&TB_iframe=true&width=600&height=550">' . esc_html__( 'Install and Activate', 'grocefycart' ) . '</a>';
								}
								?>
							</h4>
						</li>
					</ul>
					<a href="#" id="install-activate-button" class="button admin-button info-button"><?php echo __( 'Getting started with a single click', 'grocefycart' ); ?></a>
				</div>
				<div id="grocefycart-setup" class="tab-content">
					<h3 class="grocefycart-baisc-guideline-header"><?php echo __( 'Basic Theme Setup', 'grocefycart' ); ?></h3>
					<div class="grocefycart-baisc-guideline">
						<div class="featured-box">
							<ul>
								<li><strong><?php echo __( 'Setup Header Layout:', 'grocefycart' ); ?></strong>
									<ul>
										<li> - <?php echo __( 'Go to Appearance -> Editor -> Patterns -> Template Parts -> Header:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'click on Header > Click on Edit (Icon) -> Add or Remove Requirend block/content as your requirement.:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'Click on save to update your layout', 'grocefycart' ); ?></li>
									</ul>
								</li>
							</ul>
						</div>
						<div class="featured-box">
							<ul>
								<li><strong><?php echo __( 'Setup Footer Layout:', 'grocefycart' ); ?></strong>
									<ul>
										<li> - <?php echo __( 'Go to Appearance -> Editor -> Patterns -> Template Parts -> Footer:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'click on Footer > Click on Edit (Icon) > Add or Remove Requirend block/content as your requirement.:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'Click on save to update your layout', 'grocefycart' ); ?></li>
									</ul>
								</li>
							</ul>
						</div>
						<div class="featured-box">
							<ul>
								<li><strong><?php echo __( 'Setup Templates like Homepage/404/Search/Page/Single and more templates Layout:', 'grocefycart' ); ?></strong>
									<ul>
										<li> - <?php echo __( 'Go to Appearance -> Editor -> Templates:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'click on Template(You need to edit/update) > Click on Edit (Icon) > Add or Remove Requirend block/content as your requirement.:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'Click on save to update your layout', 'grocefycart' ); ?></li>
									</ul>
								</li>
							</ul>
						</div>
						<div class="featured-box">
							<ul>
								<li><strong><?php echo __( 'Restore/Reset Default Content layout of Template(Like: Frontpage/Blog/Archive etc.)', 'grocefycart' ); ?></strong>
									<ul>
										<li> - <?php echo __( 'Go to Appearance -> Editor -> Templates:', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'Click on Manage all Templates', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'Click on 3 Dots icon ( ⋮ ) at right side of respective Template', 'grocefycart' ); ?></li>
										<li> - <?php echo __( 'Click on Reset', 'grocefycart' ); ?></li>
									</ul>
								</li>
							</ul>
						</div>
						<div class="featured-box">
							<ul>
								<li><strong><?php echo __( 'Restore/Reset Default Content layout of Template Parts(Header/Footer/Sidebar)', 'grocefycart' ); ?></strong>
								<ul>
									<li> - <?php echo __( 'Go to Appearance -> Editor -> Patterns:', 'grocefycart' ); ?></li>
									<li> - <?php echo __( 'Click on Manage All Template Parts', 'grocefycart' ); ?></li>
									<li> - <?php echo __( 'Click on 3 Dots icon ( ⋮ ) at right side of respective Template parts', 'grocefycart' ); ?></li>
									<li> - <?php echo __( 'Click on Reset', 'grocefycart' ); ?></li>
									</ul>
								</li>
							</ul>
						</div>
					</div>
				</div>
				<div id="grocefycart-comparision" class="tab-content">
					<div class="featured-list">
						<div class="half-col free-features">
							<h3><?php echo __( 'Grocefycart Features (Free)', 'grocefycart' ); ?></h3>
							<ul>
								<li>
									<h4><?php echo __( 'Grocefycart offers 20+ pre-built sections to help you launch your store effortlessly.', 'grocefycart' ); ?></h4>
									<ul>
										<li><?php echo __( '404 Page Not Found Section', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Marquee Section', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Latest Blogs Section - 2', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Call To Action Section - 2', 'grocefycart' ); ?></li>
										<li><?php echo __( 'WooCommerce Product Showcase Section - 9', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Testimonial Grid Section', 'grocefycart' ); ?></li>
										<li><?php echo __( 'About Us Section', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Header Section', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Footer Section - 2', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Sitemap Section', 'grocefycart' ); ?></li>
										<li><?php echo __( 'Sidebar Section - 2', 'grocefycart' ); ?></li>
									</ul>
								</li>

								<li> <strong>- <?php echo __( 'FSE Templates Ready', 'grocefycart' ); ?></strong>
									<ul>
										<li> <?php echo __( '404 Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Archive Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Product Archive Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Blank Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Blank Template with Header and Footer', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'WooCommerce Cart Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'WooCommerce Checkout Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Front Page Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Blog Home Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Index Page Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Search Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Sitemap Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Page Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Page Template with Left Sidebar', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Page  Template with Right Sidebar', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Single Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Single Template with Left Sidebar', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Single Template with Right Sidebar', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Single Product Template', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Single Product Template with Left Sidebar', 'grocefycart' ); ?></li>
										<li> <?php echo __( 'Single Product Template with Right Sidebar', 'grocefycart' ); ?></li>

									</ul>
								<li>
								<li><strong> - <?php echo __( 'Fully Customizable Header Layout', 'grocefycart' ); ?></strong></li>
								<li> <strong>- <?php echo __( 'Fully Customizable Footer Layout ', 'grocefycart' ); ?></strong></li>
								<li> <strong>- <?php echo __( 'On Scroll Animation option', 'grocefycart' ); ?></strong></li>
								<li> <strong>- <?php echo __( 'One Click Demo Import Features', 'grocefycart' ); ?></strong></li>
								<li> <strong>- <?php echo __( 'Access Cozy Blocks with upto 25+ Advanced Blocks for FSE and Gutenberg Editor', 'grocefycart' ); ?></strong></li>
							</ul>
						</div>
						<div class="half-col pro-features">
							<h3><?php echo __( 'Premium Features', 'grocefycart' ); ?></h3>
							<p><?php echo __( 'Grocefycart seamlessly integrates with Cozy Blocks, offering 10 advanced WooCommerce blocks and 40+ total blocks to enhance your store. Build a high-performance, conversion-focused shop effortlessly with powerful features and ready-to-use patterns for a stunning, professional look.', 'grocefycart' ); ?></p>
							<h4><?php echo __( '50+ Advanced Blocks', 'grocefycart' ); ?></h4>
							<ul>
								<li><?php echo __( '10 WooCommerce Blocks', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Product Slider Blocks', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Product Grid/Carousel Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Product Categories Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Product Quickview Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Product Wishlist Blocks', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Related Product Blocks', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Featured Products Tab Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Categories Products Tab Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'All Product Reviews Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Featured Product Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Timer Countdown Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'After Before Image Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Slider Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Counter Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Progress Bar Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Advanced Gallery with Lightbox, filterable and multiple layout', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Portfolio Block with Custom Post Type with lightbox, category filterable and multiple layout', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Team Block with grid and carousel', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Testimonial Block with grid and carousel', 'grocefycart' ); ?></li>
								<li><?php echo __( '15 Post and Magazine Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Social Shares Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Social Icons Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Breadcrumbs Block', 'grocefycart' ); ?></li>
								<li><?php echo __( 'Popup buidler Block to display offer and flash sale', 'grocefycart' ); ?>
									<?php echo __( 'and access', 'grocefycart' ); ?> <a href="<?php echo __( 'https://cozythemes.com/cozy-addons/', 'grocefycart' ); ?>" target="_blank"><?php echo __( 'Cozy Blocks with more than 40+ advanced block.', 'grocefycart' ); ?></a>
								</li>

							</ul>
							<br />
							<a href="<?php echo __( 'https://cozythemes.com/pricing-and-plans/', 'grocefycart' ); ?>" class="upgrade-btn button" target="_blank"><?php echo __( 'Upgrade to Pro', 'grocefycart' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
