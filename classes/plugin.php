<?php

namespace Kntnt\Popup;

/**
 * Main plugin class.
 * Initializes the plugin components, manages assets, and handles popup logic.
 * Follows a singleton-like pattern for accessing static properties/methods.
 *
 * @package Kntnt\Popup
 */
class Plugin {

	/**
	 * The unique slug for the plugin, derived from the namespace.
	 * Used for identifying assets, transients, etc.
	 * Example: Kntnt\Popup -> kntnt-popup
	 *
	 * @var string|null
	 */
	private static ?string $slug = null;

	/**
	 * The filesystem directory path for the plugin.
	 *
	 * @var string|null
	 */
	private static ?string $plugin_dir = null;

	/**
	 * The plugin's version number, read from the plugin header.
	 * Cached using transients for performance.
	 *
	 * @var string|null
	 */
	private static ?string $version = null;

	/**
	 * Instance of the Assets handler class.
	 * Manages CSS and JavaScript registration and enqueuing.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * Instance of the PopupHandler class.
	 * Manages shortcode registration and popup display logic.
	 *
	 * @var Popup_Handler
	 */
	private Popup_Handler $popup_handler;

	/**
	 * Get the plugin slug.
	 * Generates the slug from the namespace on the first call.
	 * Replaces backslashes and underscores with hyphens.
	 *
	 * @return string The plugin slug (e.g., 'kntnt-popup').
	 */
	public static function slug(): string {
		if ( ! isset(self::$slug) ) {
			self::$slug = strtr( strtolower( __NAMESPACE__ ), '_\\', '--' );
		}
		return self::$slug;
	}

	/**
	 * Get the plugin's base directory path.
	 *
	 * @return string The plugin directory path with forward slashes.
	 */
	public static function plugin_dir(): string {
		if ( ! isset(self::$plugin_dir) ) {
			self::$plugin_dir = strtr( dirname( __DIR__ ), '\\', '/' );
		}
		return self::$plugin_dir;
	}

	/**
	 * Get the plugin version number.
	 * Reads the version from the plugin header on the first call.
	 *
	 * @return string Plugin version number.
	 */
	public static final function version(): string {
		if ( ! self::$version ) {
			$key = self::slug() . '-plugin-version';
			self::$version = get_transient( $key );
			if ( ! self::$version ) {
				self::$version = get_plugin_data( self::plugin_dir( self::$slug . '.php' ), false, false )['Version'];
				set_transient( $key, self::$version, DAY_IN_SECONDS );
			}
		}
		return self::$version;
	}

	/**
	 * Plugin constructor.
	 * Initializes helper classes and registers WordPress hooks.
	 */
	public function __construct() {

		$this->assets = new Assets;
		$this->popup_handler = new Popup_Handler( $this->assets );

		add_action( 'init', [ $this->popup_handler, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this->assets, 'register_assets' ] );
		add_action( 'wp_footer', [ $this->assets, 'enqueue_assets_conditionally' ], 20 );

	}

}