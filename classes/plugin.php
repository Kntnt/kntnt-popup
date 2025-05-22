<?php

declare( strict_types = 1 );

namespace Kntnt\Popup;

use LogicException;

/**
 * Main plugin class.
 *
 * Handles the initialization of the plugin, including setting up
 * metadata, loading assets, registering shortcodes, and managing localization.
 * Implements a singleton pattern to ensure only one instance exists.
 */
final class Plugin {

	/**
	 * The singleton instance of the Plugin.
	 *
	 * @var ?Plugin
	 */
	private static ?Plugin $s_instance = null;

	/**
	 * The plugin's unique slug.
	 * Automatically generated from the namespace.
	 *
	 * @var ?string
	 */
	private static ?string $s_slug = null;

	/**
	 * The absolute file path to the plugin's directory.
	 *
	 * @var ?string
	 */
	private static ?string $s_plugin_directory = null;

	/**
	 * The URL to the plugin's directory.
	 *
	 * @var ?string
	 */
	private static ?string $s_plugin_url = null;

	/**
	 * The version number of the plugin.
	 *
	 * @var ?string
	 */
	private static ?string $s_version = null;

	/**
	 * Manages plugin assets (CSS and JavaScript).
	 *
	 * @var Assets
	 */
	private readonly Assets $assets_manager;

	/**
	 * Manages the popup shortcode.
	 *
	 * @var Shortcode
	 */
	private readonly Shortcode $shortcode_handler;

	/**
	 * Initializes the plugin.
	 *
	 * Sets up plugin metadata (slug, paths, version), creates the singleton instance,
	 * and registers initial WordPress hooks. This method should be called once.
	 *
	 * @param string $plugin_path The full path to the main plugin file.
	 *
	 * @return void
	 */
	public static function init( string $plugin_path ): void {
		if ( self::$s_instance === null ) {

			// Generate and store the plugin slug from its namespace.
			self::$s_slug = strtr( strtolower( __NAMESPACE__ ), '_\\', '--' );

			// Determine and store the plugin's directory path.
			self::$s_plugin_directory = plugin_dir_path( $plugin_path );

			// Determine and store the plugin's directory URL.
			self::$s_plugin_url = plugin_dir_url( $plugin_path );

			// Retrieve plugin metadata, specifically the version number.
			$plugin_data = get_plugin_data( $plugin_path, true, false );
			self::$s_version = $plugin_data['Version'] ?? '0.0.0'; // Default if not found

			// Create and store the singleton instance of this plugin.
			self::$s_instance = new self;

		}
	}

	/**
	 * Returns the singleton instance of the Plugin.
	 *
	 * @return Plugin The plugin instance.
	 * @throws LogicException If the plugin has not been initialized via init().
	 */
	public static function get_instance(): Plugin {
		if ( self::$s_instance === null ) {
			throw new LogicException( 'Plugin has not been initialized. Call init() first.' );
		}
		return self::$s_instance;
	}

	/**
	 * Gets the plugin slug.
	 *
	 * @return string The plugin slug.
	 * @throws LogicException If the plugin slug has not been initialized.
	 */
	public static function get_slug(): string {
		if ( self::$s_slug === null ) {
			throw new LogicException( 'Plugin slug has not been initialized.' );
		}
		return self::$s_slug;
	}

	/**
	 * Gets the plugin directory path.
	 *
	 * @return string The plugin directory path.
	 * @throws LogicException If the plugin directory has not been initialized.
	 */
	public static function get_plugin_directory(): string {
		if ( self::$s_plugin_directory === null ) {
			throw new LogicException( 'Plugin directory has not been initialized.' );
		}
		return self::$s_plugin_directory;
	}

	/**
	 * Gets the plugin directory URL.
	 *
	 * @return string The plugin directory URL.
	 * @throws LogicException If the plugin URL has not been initialized.
	 */
	public static function get_plugin_url(): string {
		if ( self::$s_plugin_url === null ) {
			throw new LogicException( 'Plugin URL has not been initialized.' );
		}
		return self::$s_plugin_url;
	}

	/**
	 * Gets the plugin version.
	 *
	 * @return string The plugin version.
	 * @throws LogicException If the plugin version has not been initialized.
	 */
	public static final function get_version(): string {
		if ( self::$s_version === null ) {
			throw new LogicException( 'Plugin version has not been initialized.' );
		}
		return self::$s_version;
	}

	/**
	 * Private constructor to prevent direct instantiation and ensure singleton pattern.
	 * Initializes Assets and Shortcode classes and registers WordPress hooks.
	 */
	private function __construct() {

		// Instantiate the assets manager for handling CSS and JavaScript.
		$this->assets_manager = new Assets;

		// Instantiate the shortcode handler for the [popup] shortcode.
		$this->shortcode_handler = new Shortcode;

		// Register action to set up plugin assets.
		add_action( 'wp_enqueue_scripts', [ $this->assets_manager, 'register_assets' ], 10 );

		// Register action to check for shortcode presence on the page.
		add_action( 'wp_enqueue_scripts', [ $this->assets_manager, 'check_for_shortcode_presence' ], 15 );

		// Register action to conditionally enqueue assets if shortcode is found.
		add_action( 'wp_enqueue_scripts', [ $this->assets_manager, 'enqueue_assets_conditionally' ], 20 );

		// Register action to initialize the popup shortcode.
		add_action( 'init', [ $this->shortcode_handler, 'register_shortcode' ] );

		// Register action to load the plugin's text domain for localization.
		add_action( 'init', [ $this, 'load_textdomain' ] );

	}

	/**
	 * Prevents cloning of the instance to maintain singleton pattern.
	 *
	 * @return void
	 */
	private function __clone(): void {
		// This class is a singleton; cloning is not allowed to preserve its integrity.
	}

	/**
	 * Prevents unserialization of the instance to maintain singleton pattern.
	 *
	 * @return void
	 * @throws LogicException Always, as unserializing a singleton is not allowed.
	 */
	public function __wakeup(): void {
		throw new LogicException( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Gets the Assets manager instance.
	 *
	 * @return Assets The assets manager.
	 */
	public function get_assets_manager(): Assets {
		return $this->assets_manager;
	}

	/**
	 * Loads the plugin text domain for localization.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'kntnt-popup', false, self::get_plugin_directory() . '/languages/' );
	}

}