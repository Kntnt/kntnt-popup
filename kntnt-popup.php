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
 */

namespace Kntnt\Popup;

// Prevent direct access
defined( 'ABSPATH' ) && new Plugin;

// Simple autoloader adhering to project structure
spl_autoload_register(function ($class) {

    $prefix = __NAMESPACE__ . '\\';
    $base_dir = __DIR__ . '/classes/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        // Not a class from this namespace
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators
    // Replace underscores with hyphens and convert to lowercase for the file name
    $file = $base_dir . str_replace('\\', '/', strtolower(str_replace('_', '-', $relative_class))) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }

});

/**
 * Initializes the plugin.
 *
 * @return Plugin The main plugin instance.
 */
function init(): Plugin {
    static $instance = null;
    if ($instance === null) {
        $instance = new Plugin();
    }
    return $instance;
}

// Load the plugin
init();