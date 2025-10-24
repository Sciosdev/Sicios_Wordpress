<?php
/**
 * MU-plugin para desactivar el módulo Elementor de Dokan Pro.
 */
add_filter('dokan_pro_modules', function ($modules) {
    if (is_array($modules) && isset($modules['elementor'])) {
        unset($modules['elementor']);
    }
    return $modules;
}, 0);
