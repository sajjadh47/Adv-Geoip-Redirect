<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @package           Adv_Geoip_Redirect
 * @author            Sajjad Hossain Sagor <sagorh672@gmail.com>
 *
 * Plugin Name:       Advanced GeoIP Redirect
 * Plugin URI:        https://wordpress.org/plugins/adv-geoip-redirect/
 * Description:       Redirect your visitors according to their geographical (country) location. Using the Maxmind GeoIP (Lite) Database.
 * Version:           2.1.4
 * Requires at least: 5.6
 * Requires PHP:      8.1
 * Author:            Sajjad Hossain Sagor
 * Author URI:        https://sajjadhsagor.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       adv-geoip-redirect
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'ADV_GEOIP_REDIRECT_PLUGIN_VERSION', '2.1.4' );

/**
 * Define Plugin Folders Path
 */
define( 'ADV_GEOIP_REDIRECT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'ADV_GEOIP_REDIRECT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'ADV_GEOIP_REDIRECT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-adv-geoip-redirect-activator.php
 *
 * @since    2.0.0
 */
function on_activate_adv_geoip_redirect() {
	require_once ADV_GEOIP_REDIRECT_PLUGIN_PATH . 'includes/class-adv-geoip-redirect-activator.php';

	Adv_Geoip_Redirect_Activator::on_activate();
}

register_activation_hook( __FILE__, 'on_activate_adv_geoip_redirect' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-adv-geoip-redirect-deactivator.php
 *
 * @since    2.0.0
 */
function on_deactivate_adv_geoip_redirect() {
	require_once ADV_GEOIP_REDIRECT_PLUGIN_PATH . 'includes/class-adv-geoip-redirect-deactivator.php';

	Adv_Geoip_Redirect_Deactivator::on_deactivate();
}

register_deactivation_hook( __FILE__, 'on_deactivate_adv_geoip_redirect' );

/**
 * The core plugin class that is used to define admin-specific and public-facing hooks.
 *
 * @since    2.0.0
 */
require ADV_GEOIP_REDIRECT_PLUGIN_PATH . 'includes/class-adv-geoip-redirect.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_adv_geoip_redirect() {
	$plugin = new Adv_Geoip_Redirect();

	$plugin->run();
}

run_adv_geoip_redirect();
