<?php

/**
 * file for holding dashboard welcome page for theme
 */
if (!function_exists('clothing_apparel_shop_is_plugin_installed')) {
    function clothing_apparel_shop_is_plugin_installed($plugin_slug)
    {
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
        return file_exists($plugin_path);
    }
}
if (!function_exists('clothing_apparel_shop_is_plugin_activated')) {
    function clothing_apparel_shop_is_plugin_activated($plugin_slug)
    {
        return is_plugin_active($plugin_slug);
    }
}


// Hook into a custom action when the button is clicked
add_action('wp_ajax_clothing_apparel_shop_install_and_activate_plugins', 'clothing_apparel_shop_install_and_activate_plugins');
add_action('wp_ajax_nopriv_clothing_apparel_shop_install_and_activate_plugins', 'clothing_apparel_shop_install_and_activate_plugins');
add_action('wp_ajax_clothing_apparel_shop_rplugin_activation', 'clothing_apparel_shop_rplugin_activation');
add_action('wp_ajax_nopriv_clothing_apparel_shop_rplugin_activation', 'clothing_apparel_shop_rplugin_activation');

// Function to install and activate the plugins



function check_plugin_installed_status($pugin_slug, $plugin_file)
{
    return file_exists(ABSPATH . 'wp-content/plugins/' . $pugin_slug . '/' . $plugin_file) ? true : false;
}

/* Check if plugin is activated */


function check_plugin_active_status($pugin_slug, $plugin_file)
{
    return is_plugin_active($pugin_slug . '/' . $plugin_file) ? true : false;
}

require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/misc.php');
require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
// Helper function to check if all recommended plugins are installed and activated
function clothing_apparel_shop_all_plugins_active() {
    $recommended_plugins = array(
        array(
            'name' => __( 'Ovation Elements', 'clothing-apparel-shop' ),
            'slug' => 'ovation-elements',
            'file' => 'ovation-elements.php'
        )
    );

    foreach ($recommended_plugins as $plugin) {
        $plugin_slug = $plugin['slug'];
        $plugin_file = $plugin['file'];

        // Check if the plugin is active
        if (!is_plugin_active($plugin_slug . '/' . $plugin_file)) {
            return false; // If any plugin is not active, return false
        }
    }

    return true; // All plugins are active
}

class Silent_Skin extends WP_Upgrader_Skin {
    public function header() {}
    public function footer() {}
    public function feedback($string, ...$args) {}
    public function error($errors) {}
    public function before() {}
    public function after() {}
}

// Function to install and activate plugins
function clothing_apparel_shop_install_and_activate_plugins() {
    if (!current_user_can('manage_options')) {
        return;
    }
    check_ajax_referer('clothing_apparel_shop_welcome_nonce', 'nonce');

    // Define the recommended plugins
    $recommended_plugins = array(
        array(
            'name' => __( 'Ovation Elements', 'clothing-apparel-shop' ),
            'slug' => 'ovation-elements',
            'file' => 'ovation-elements.php'
        ),
        array(
            'name' => __( 'WooCommerce', 'clothing-apparel-shop' ),
            'slug' => 'woocommerce',
            'file' => 'woocommerce.php'
        )
    );

    set_transient('install_and_activate_progress', array(), MINUTE_IN_SECONDS * 10);

    require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/misc.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    foreach ($recommended_plugins as $plugin) {
        $plugin_slug = $plugin['slug'];
        $plugin_file = $plugin['file'];
        $plugin_name = $plugin['name'];

        // Check if the plugin is active
        if (is_plugin_active($plugin_slug . '/' . $plugin_file)) {
            update_install_and_activate_progress($plugin_name, 'Already Active');
            continue;
        }

        // Check if the plugin is installed but not active
        if (is_clothing_apparel_shop_plugin_installed($plugin_slug)) {
            $activate = activate_plugin($plugin_slug . '/' . $plugin_file);
            if (is_wp_error($activate)) {
                update_install_and_activate_progress($plugin_name, 'Error');
                continue;
            }
            update_install_and_activate_progress($plugin_name, 'Activated');
            continue;
        }

        // Plugin is not installed or activated, proceed with installation
        update_install_and_activate_progress($plugin_name, 'Installing');

        $api = plugins_api('plugin_information', array('slug' => $plugin_slug, 'fields' => array('sections' => false)));
        if (is_wp_error($api)) {
            update_install_and_activate_progress($plugin_name, 'Error');
            continue;
        }

        $upgrader = new Plugin_Upgrader(new Silent_Skin());
        $install = $upgrader->install($api->download_link);

        if ($install) {
            $activate = activate_plugin($plugin_slug . '/' . $plugin_file);
            if (is_wp_error($activate)) {
                update_install_and_activate_progress($plugin_name, 'Error');
                continue;
            }
            update_install_and_activate_progress($plugin_name, 'Activated');
            continue;
        } else {
            update_install_and_activate_progress($plugin_name, 'Error');
        }
    }
    delete_transient('install_and_activate_progress');
    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json; charset=utf-8');
    $redirect_url = admin_url('themes.php?page=clothing-apparel-shop-guide-page');
    echo json_encode([
        'success' => true,
        'data' => [
            'redirect_url' => $redirect_url,
        ],
    ]);

    wp_die();
}

// Function to check if a plugin is installed
function is_clothing_apparel_shop_plugin_installed($plugin_slug) {
    $installed_plugins = get_plugins();
    foreach ($installed_plugins as $path => $details) {
        if (strpos($path, $plugin_slug) === 0) {
            return true;
        }
    }
    return false;
}

// Function to update the installation progress
function update_install_and_activate_progress($plugin_name, $status) {
    $progress = get_transient('install_and_activate_progress');
    $progress[] = array('plugin' => $plugin_name, 'status' => $status);
    set_transient('install_and_activate_progress', $progress, MINUTE_IN_SECONDS * 10);
}

// Dismiss function for AJAX request
add_action('wp_ajax_clothing_apparel_shop_dismissed_notice_handler', 'clothing_apparel_shop_ajax_notice_dismiss_function');

function clothing_apparel_shop_ajax_notice_dismiss_function() {
    if (!wp_verify_nonce($_POST['wpnonce'], 'clothing_apparel_shop_welcome_nonce')) {
        wp_send_json_error('Invalid nonce');
        exit;
    }
    
    if (isset($_POST['type'])) {
        $type = sanitize_text_field(wp_unslash($_POST['type']));
        update_option('dismissed-' . $type, true);
        wp_send_json_success('Notice dismissed');
    } else {
        wp_send_json_error('Type not set');
    }
}

// Function to show admin notice conditionally
function clothing_apparel_shop_custom_admin_notice() {
    if (!get_option('dismissed-get_started_notice', false)) {
        $clothing_apparel_shop_current_screen = get_current_screen();
        $clothing_apparel_shop_theme = wp_get_theme();
        if ($clothing_apparel_shop_current_screen && $clothing_apparel_shop_current_screen->id !== 'appearance_page_clothing-apparel-shop-guide-page') {
            ?>

            <div class="clothing-apparel-shop-admin-notice notice notice-info is-dismissible content-install-plugin theme-info-notice" id="clothing-apparel-shop-dismiss-notice" data-notice="get_started_notice">
                <div class="notice-div">
                    <div>
                        <p class="theme-name"><?php echo esc_html($clothing_apparel_shop_theme->get('Name')); ?></p>
                        <p><?php _e('For information and detailed instructions, check out our theme documentation.', 'clothing-apparel-shop'); ?></p>
                    </div>
                    <div class="notice-buttons-box">
                        <a class="button-primary livedemo" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_LIVE_DEMO ); ?>" target="_blank"><?php esc_html_e('Live Demo', 'clothing-apparel-shop'); ?></a>
                        <a class="button-primary buynow" href="<?php echo esc_url( CLOTHING_APPAREL_SHOP_BUY_PRO ); ?>" target="_blank"><?php esc_html_e('Buy Now', 'clothing-apparel-shop'); ?></a>
                        <a class="button-primary theme-install" id="install-activate-button" href="#"><?php _e('Begin Installation', 'clothing-apparel-shop'); ?></a>`
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
add_action('admin_notices', 'clothing_apparel_shop_custom_admin_notice');

// After switching theme, reset dismissed notice option
add_action('after_switch_theme', 'clothing_apparel_shop_after_switch_theme');
function clothing_apparel_shop_after_switch_theme() {
    update_option('dismissed-get_started_notice', FALSE);
} ?>