<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://hooma.legal
 * @since             1.0.0
 * @package           Hooma_Legal
 *
 * @wordpress-plugin
 * Plugin Name:       Hooma Legal
 * Plugin URI:        https://hooma.legal
 * Description:       Solución legal integral para tu sitio WordPress (políticas, cookies, etc.).
 * Version:           1.0.260710
 * Author:            Hooma Legal
 * Author URI:        https://hooma.legal
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hooma-legal
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'HOOMA_LEGAL_VERSION', '1.0.260710' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hooma-legal-activator.php
 */
function activate_hooma_legal() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hooma-legal-activator.php';
	Hooma_Legal_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hooma-legal-deactivator.php
 */
function deactivate_hooma_legal() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-hooma-legal-deactivator.php';
	Hooma_Legal_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_hooma_legal' );
register_deactivation_hook( __FILE__, 'deactivate_hooma_legal' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hooma-legal.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, public-facing
 * and admin-facing site hooks are registered in the loader, then executed.
 *
 * @since    1.0.0
 */
function run_hooma_legal() {
	$plugin = new Hooma_Legal();
	$plugin->run();
}
run_hooma_legal();
