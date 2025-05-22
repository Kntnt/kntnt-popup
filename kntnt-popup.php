<?php
/**
 * Plugin Name: Kntnt Popup
 * Plugin URI: https://github.com/Kntnt/kntnt-popup
 * Description: Provides shortcode for creating popups
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: TBarregren
 * Author URI: https://www.kntnt.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Tested up to: 6.8
 * Text Domain: kntnt-popup
 * Domain Path: /languages
 */

declare( strict_types = 1 );

namespace Kntnt\Popup;

/**
 * Registers an autoloader for the plugin's classes.
 *
 * The autoloader loads classes from the 'classes' directory.
 * It expects class names to follow PSR-4 like conventions transformed to lowercase,
 * hyphenated file names. For example, Kntnt\Popup\My_Class would be loaded from
 * 'classes/my-class.php'.
 */
spl_autoload_register( function ( string $class_name ): void {

	// Define the plugin's namespace prefix.
	$prefix = __NAMESPACE__ . '\\';

	// Process only classes that belong to the plugin's namespace.
	if ( str_starts_with( $class_name, $prefix ) ) {

		// Construct common parts of the file path.
		$base_directory = __DIR__ . '/classes/';
		$relative_class_name = substr( $class_name, strlen( $prefix ) );

		// Convert class name to a lowercase, hyphenated file name.
		$file_path = $base_directory . str_replace( [ '_', '\\' ], [ '-', '/' ], strtolower( $relative_class_name ) ) . '.php';

		// Include the class file if it exists.
		if ( file_exists( $file_path ) ) {
			require_once $file_path; // Obvious single action, no heading needed.
		}

	}

} );

// Initialize the plugin when WordPress is loaded.
if ( defined( 'ABSPATH' ) ) {

	// Ensure WordPress environment is active before plugin initialization.
	Plugin::init( __FILE__ );

}