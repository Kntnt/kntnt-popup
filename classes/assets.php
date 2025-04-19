<?php

namespace Kntnt\Popup;

/**
 * Class Assets.
 * Handles registration and conditional enqueueing of CSS and JavaScript assets.
 */
final class Assets {

    private const VERSION = '1.0.0'; // Plugin version for cache busting
    private const HANDLE_PREFIX = 'kntnt-popup-';

    private bool $assets_needed = false;
    private array $popup_configs = []; // Store configs for localization

    /**
     * Registers CSS and JS files.
     */
    public function register_assets(): void {
        $base_url = plugin_dir_url(dirname(__FILE__)); // URL to the plugin directory

        // Register Micromodal JS (installed with npm)
        wp_register_script(
            self::HANDLE_PREFIX . 'micromodal',
            $base_url . 'js/micromodal.min.js',
            [],
            '0.4.10', // Micromodal version
            true // Load in footer
        );

        // Register Plugin CSS
        wp_register_style(
            self::HANDLE_PREFIX . 'style',
            $base_url . 'css/kntnt-popup.css',
            [], // Dependencies
            self::VERSION
        );

        // Register Plugin JS
        wp_register_script(
            self::HANDLE_PREFIX . 'script',
            $base_url . 'js/kntnt-popup.js',
            [self::HANDLE_PREFIX . 'micromodal'], // Depend on Micromodal
            self::VERSION,
            true // Load in footer
        );

        // Add defer attribute to plugin script for performance
        wp_script_add_data(self::HANDLE_PREFIX . 'script', 'defer', true);
    }

    /**
     * Marks that assets should be enqueued for the current page.
     */
    public function mark_assets_needed(): void {
        $this->assets_needed = true;
    }

    /**
     * Adds configuration for a specific popup instance to be localized.
     * @param array $config Popup configuration data.
     */
    public function add_popup_config(array $config): void {
        $this->popup_configs[] = $config;
    }

    /**
     * Enqueues assets in the footer if mark_assets_needed() was called.
     * Localizes data for the JavaScript.
     */
    public function enqueue_assets_conditionally(): void {
        if ($this->assets_needed) {
            // Enqueue Styles
            wp_enqueue_style(self::HANDLE_PREFIX . 'style');

            // Localize data BEFORE enqueuing the script that uses it
            wp_localize_script(
                self::HANDLE_PREFIX . 'script', // Handle of the script to attach data to
                'kntntPopupData',               // JavaScript object name
                [                               // Data to pass
                    'popups' => $this->popup_configs,
                    'ajax_url' => admin_url('admin-ajax.php'), // Example if needed for AJAX
                ]
            );

            // Enqueue Scripts (Micromodal will be loaded as a dependency)
            wp_enqueue_script(self::HANDLE_PREFIX . 'script');
        }
    }
}