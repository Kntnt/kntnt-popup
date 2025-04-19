<?php

namespace Kntnt\Popup;

/**
 * Main plugin class.
 * Initializes the plugin components.
 */
class Plugin {
    
    private Assets $assets;
    private PopupHandler $popup_handler;
    
    public function __construct() {
        // Initialize components
        $this->assets = new Assets();
        $this->popup_handler = new PopupHandler($this->assets);
        
        // Register actions and filters
        $this->register_hooks();
    }
    
    /**
     * Registers all WordPress hooks needed by the plugin.
     */
    private function register_hooks(): void {
        // Register assets
        add_action('wp_enqueue_scripts', [$this->assets, 'register_assets']);
        
        // Register shortcode
        add_action('init', [$this->popup_handler, 'register_shortcode']);
        
        // Conditionally enqueue assets in footer
        add_action('wp_footer', [$this->assets, 'enqueue_assets_conditionally'], 20);
    }
}