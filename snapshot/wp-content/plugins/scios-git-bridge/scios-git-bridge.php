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
    add_action('scios_git_bridge_trigger_dry_run', [$pull_service, 'dry_run']);
    add_action('scios_git_bridge_trigger_deploy', [$pull_service, 'deploy']);

    $snapshot_service = new \Scios\GitBridge\Services\SCIOS_Snapshot_Service();
    add_action('scios_git_bridge_trigger_snapshot', [$snapshot_service, 'trigger_snapshot'], 10, 1);

    $smoke_test_service = new \Scios\GitBridge\Services\SCIOS_Smoke_Test_Service();
    add_action('scios_git_bridge_trigger_smoke_test', [$smoke_test_service, 'run'], 10, 1);

    $rollback_service = new \Scios\GitBridge\Services\SCIOS_Rollback_Service();
    add_action('scios_git_bridge_trigger_rollback', [$rollback_service, 'rollback'], 10, 2);

    $cache_service = new \Scios\GitBridge\Services\SCIOS_Cache_Service();
    add_action('scios_git_bridge_trigger_cache_purge', [$cache_service, 'purge'], 10, 1);

    add_action(
        'upgrader_process_complete',
        static function ($upgrader, $hook_extra) {
            scios_git_bridge_handle_upgrader_process_complete($hook_extra);
        },
        20,
        2
    );

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

/**
 * Handles upgrader completion events to trigger automated tasks.
 *
 * @param array<string, mixed>|mixed $hook_extra Context provided by the upgrader.
 *
 * @return void
 */
function scios_git_bridge_handle_upgrader_process_complete($hook_extra)
{
    if (!is_array($hook_extra)) {
        return;
    }

    $action = isset($hook_extra['action']) ? (string) $hook_extra['action'] : '';
    $type   = isset($hook_extra['type']) ? (string) $hook_extra['type'] : '';

    if ($action !== 'update' && $action !== 'install') {
        return;
    }

    $supported_types = ['plugin', 'theme', 'core'];

    if (!in_array($type, $supported_types, true)) {
        return;
    }

    $items = [];

    if ($type === 'plugin' && !empty($hook_extra['plugins'])) {
        $items = array_map('strval', (array) $hook_extra['plugins']);
    } elseif ($type === 'theme' && !empty($hook_extra['themes'])) {
        $items = array_map('strval', (array) $hook_extra['themes']);
    } elseif ($type === 'core') {
        $items[] = isset($hook_extra['version']) ? (string) $hook_extra['version'] : get_bloginfo('version');
    }

    $items = array_filter(array_map('trim', $items));

    $reason = sprintf(
        /* translators: 1: update type, 2: items list. */
        __('Actualización detectada (%1$s): %2$s', 'scios-git-bridge'),
        $type,
        $items !== [] ? implode(', ', $items) : __('elemento no identificado', 'scios-git-bridge')
    );

    /**
     * Fires when a snapshot should be triggered after an upgrader operation.
     */
    do_action('scios_git_bridge_trigger_snapshot', $reason);

    /**
     * Fires when a smoke test should be executed after an upgrader operation.
     */
    do_action('scios_git_bridge_trigger_smoke_test', $reason);
}
