<?php

namespace Scios\GitBridge\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detects and triggers common cache purge hooks exposed by popular plugins.
 */
class SCIOS_Cache_Purger
{
    /**
     * Executes detected purge callbacks and returns their results.
     *
     * @return array<int, array<string, mixed>>
     */
    public function purge(): array
    {
        $callbacks = $this->detect_callbacks();
        $callbacks = apply_filters('scios_git_bridge_cache_purge_callbacks', $callbacks);

        $results = [];

        foreach ($callbacks as $slug => $callback) {
            if (!is_callable($callback)) {
                continue;
            }

            $slug = (string) $slug;

            try {
                call_user_func($callback);
                $results[] = [
                    'slug'     => $slug,
                    'success'  => true,
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'slug'     => $slug,
                    'success'  => false,
                    'message'  => $exception->getMessage(),
                ];
            }
        }

        do_action('scios_git_bridge_cache_purge_complete', $results);

        return $results;
    }

    /**
     * Builds a list of purge callbacks for the current environment.
     *
     * @return array<string, callable>
     */
    private function detect_callbacks(): array
    {
        $callbacks = [];

        if (function_exists('wp_cache_clear_cache')) {
            $callbacks['wp-super-cache'] = static function (): void {
                wp_cache_clear_cache();
            };
        }

        if (function_exists('w3tc_flush_all')) {
            $callbacks['w3-total-cache'] = static function (): void {
                w3tc_flush_all();
            };
        }

        if (function_exists('rocket_clean_domain')) {
            $callbacks['wp-rocket'] = static function (): void {
                rocket_clean_domain();
            };
        }

        if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            $callbacks['litespeed-cache'] = static function (): void {
                \LiteSpeed_Cache_API::purge_all();
            };
        } elseif (has_action('litespeed_purge_all')) {
            $callbacks['litespeed-cache'] = static function (): void {
                do_action('litespeed_purge_all');
            };
        }

        if (function_exists('sg_cachepress_purge_cache')) {
            $callbacks['siteground-cachepress'] = static function (): void {
                sg_cachepress_purge_cache();
            };
        }

        if (function_exists('cache_enabler_clear_total_cache')) {
            $callbacks['cache-enabler'] = static function (): void {
                cache_enabler_clear_total_cache();
            };
        }

        if (function_exists('autoptimize_flush_cache')) {
            $callbacks['autoptimize'] = static function (): void {
                autoptimize_flush_cache();
            };
        }

        if (function_exists('wp_cache_flush')) {
            $callbacks['wp-object-cache'] = static function (): void {
                wp_cache_flush();
            };
        }

        return $callbacks;
    }
}
