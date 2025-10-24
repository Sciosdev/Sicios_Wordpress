<?php
/*
Plugin Name: Codex Deploy Test (MU)
Description: Badge discreto para verificar deploy GitHub → cPanel.
Version: 1.0.0
Author: Codex
*/

if (!defined('ABSPATH')) { exit; }

add_action('wp_footer', function () {
    $build = 'Deploy OK · ' . gmdate('Y-m-d H:i:s') . ' UTC';
    echo '<div id="codex-deploy-badge" style="position:fixed;right:12px;bottom:12px;padding:6px 10px;border-radius:8px;background:#111;color:#fff;font:12px/1.2 system-ui;z-index:999999;">'
        . esc_html($build) . '</div>';
});

add_action('admin_notices', function () {
    echo '<div class="notice notice-success"><p>Codex Deploy Test activo: ' . esc_html(gmdate('Y-m-d H:i:s')) . ' UTC</p></div>';
});
