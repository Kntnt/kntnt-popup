<?php
/**
 * Plugin Name:         Kntnt Popup
 * Plugin URI:          https://github.com/Kntnt/kntnt-popup
 * Description:         Provides shortcode for creating popups
 * Version:             1.0.0
 * Requires at least:   6.8
 * Requires PHP:        8.2
 * Author:              TBarregren
 * Author URI:          https://www.kntnt.com/
 * License:             GPL-2.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.txt
 * Tested up to:        6.8
 * Text Domain:         kntnt-popup
 * Domain Path:         /languages
 */

namespace Kntnt\Popup;

spl_autoload_register( function ( $class ) {
	$prefix = __NAMESPACE__ . '\\';
	if ( str_starts_with( $class, $prefix ) ) {
		$base_dir = __DIR__ . '/classes/';
		$relative_class = substr( $class, strlen( $prefix ) );
		$file = $base_dir . str_replace( '\\', '/', strtolower( str_replace( '_', '-', $relative_class ) ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
} );

defined( 'ABSPATH' ) && Plugin::init(__FILE__);