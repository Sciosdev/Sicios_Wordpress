<?php
/**
 * Plugin Name:       Scios Git Bridge
 * Plugin URI:        https://example.com/scios-git-bridge
 * Description:       Provides integration between WordPress and Git services for Scios projects.
 * Version:           0.1.0
 * Author:            Scios
 * Author URI:        https://example.com
 * Text Domain:       scios-git-bridge
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

const SCIOS_GIT_BRIDGE_MIN_PHP_VERSION = '7.4';
const SCIOS_GIT_BRIDGE_MIN_WP_VERSION  = '5.9';

/**
 * Holds the requirement error message when the environment does not meet the plugin needs.
 *
 * @var string|null
 */
$GLOBALS['scios_git_bridge_requirement_error'] = null;

add_action('plugins_loaded', 'scios_git_bridge_bootstrap', 0);

/**
 * Bootstraps the plugin after ensuring the environment meets the minimum requirements.
 *
 * @return void
 */
function scios_git_bridge_bootstrap()
{
    if (!scios_git_bridge_requirements_met()) {
        add_action('admin_notices', 'scios_git_bridge_requirement_notice');

        if (is_admin()) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            deactivate_plugins(plugin_basename(__FILE__));
        }

        return;
    }

    scios_git_bridge_register_autoloader();

    $pull_service = new \Scios\GitBridge\Services\SCIOS_Pull_Service();
    add_action('scios_git_bridge_refresh_status', [$pull_service, 'dry_run']);
    add_action('scios_git_bridge_trigger_deploy', [$pull_service, 'deploy']);

    $snapshot_service = new \Scios\GitBridge\Services\SCIOS_Snapshot_Service();
    add_action('scios_git_bridge_trigger_snapshot', [$snapshot_service, 'trigger_snapshot'], 10, 1);

    load_plugin_textdomain(
        'scios-git-bridge',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );

    if (is_admin()) {
        $admin = new \Scios\GitBridge\Admin\SCIOS_Admin();
        $admin->register();
    }
}

/**
 * Checks whether the environment satisfies the minimum PHP and WordPress version requirements.
 *
 * @return bool
 */
function scios_git_bridge_requirements_met()
{
    if (version_compare(PHP_VERSION, SCIOS_GIT_BRIDGE_MIN_PHP_VERSION, '<')) {
        $GLOBALS['scios_git_bridge_requirement_error'] = sprintf(
            /* translators: 1: plugin name, 2: PHP version number */
            esc_html__(
                '%1$s requires PHP version %2$s or higher. Please update PHP to continue using the plugin.',
                'scios-git-bridge'
            ),
            'Scios Git Bridge',
            SCIOS_GIT_BRIDGE_MIN_PHP_VERSION
        );

        return false;
    }

    if (version_compare(get_bloginfo('version'), SCIOS_GIT_BRIDGE_MIN_WP_VERSION, '<')) {
        $GLOBALS['scios_git_bridge_requirement_error'] = sprintf(
            /* translators: 1: plugin name, 2: WordPress version number */
            esc_html__(
                '%1$s requires WordPress version %2$s or higher. Please update WordPress to continue using the plugin.',
                'scios-git-bridge'
            ),
            'Scios Git Bridge',
            SCIOS_GIT_BRIDGE_MIN_WP_VERSION
        );

        return false;
    }

    return true;
}

/**
 * Displays an admin notice when the environment does not meet the plugin requirements.
 *
 * @return void
 */
function scios_git_bridge_requirement_notice()
{
    if (empty($GLOBALS['scios_git_bridge_requirement_error'])) {
        return;
    }

    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html($GLOBALS['scios_git_bridge_requirement_error'])
    );
}

/**
 * Registers the autoloader for plugin classes.
 *
 * @return void
 */
function scios_git_bridge_register_autoloader()
{
    spl_autoload_register(
        static function ($class) {
            $prefix = 'Scios\\GitBridge\\';

            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative_class = substr($class, strlen($prefix));
            $relative_path  = str_replace('\\', '/', $relative_class);
            $file           = __DIR__ . '/includes/' . $relative_path . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        }
    );
}
