<?php
/**
 * Fired during plugin activation
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 * @author     Hooma Legal
 */
class Hooma_Legal_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-db.php';
		Hooma_Legal_DB::create_tables();

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-cpt.php';
		$cpt = new Hooma_Legal_CPT();
		$cpt->register_cpts();
		flush_rewrite_rules();
	}

}
