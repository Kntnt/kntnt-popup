<?php

namespace Kntnt\Popup;

/**
 * Main plugin class.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private static ?string $slug = null;

	private static ?string $plugin_dir = null;

	private static ?string $plugin_url = null;

	private static ?string $version = null;

	private Assets $assets;

	private Popup $popup;

	public static function init( string $plugin_path ): void {
		if ( self::$instance === null ) {
			self::$slug = strtr( strtolower( __NAMESPACE__ ), '_\\', '--' );
			self::$plugin_dir = plugin_dir_path( $plugin_path );
			self::$plugin_url = plugin_dir_url( $plugin_path );
			self::$version = get_plugin_data( $plugin_path, true, false )['Version'];
			self::$instance = new self;
		}
	}

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			throw new \LogicException;
		}
		return self::$instance;
	}

	public static function slug(): string {
		return self::$slug;
	}

	public static function plugin_dir(): string {
		return self::$plugin_dir;
	}

	public static function plugin_url(): string {
		return self::$plugin_url;
	}

	public static final function version(): string {
		return self::$version;
	}

	private function __construct() {

		$this->assets = new Assets;
		$this->popup = new Popup;

		add_action( 'wp_enqueue_scripts', [ $this->assets, 'register_assets' ] );
		add_action( 'wp_footer', [ $this->assets, 'enqueue_assets_conditionally' ], 20 );
		add_action( 'init', [ $this->popup, 'register_shortcode' ] );
		add_action( 'init', [ $this, 'load_textdomain' ] );

	}

	private function __clone(): void {}

	public function __wakeup(): void {
		throw new \LogicException;
	}

	public function assets(): Assets {
		return $this->assets;
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'kntnt-popup', false, self::$plugin_dir . '/languages/' );
	}

}