<?php
/**
 * Handle database setup and custom tables.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

/**
 * Handle database setup and custom tables.
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 * @author     Hooma Legal
 */
class Hooma_Legal_DB {

	/**
	 * Create the custom tables for version history and consent logs.
	 *
	 * @since    1.0.0
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_versions = $wpdb->prefix . 'hooma_legal_versions';
		$table_logs     = $wpdb->prefix . 'hooma_legal_consent_logs';

		// We need to require upgrade.php for dbDelta
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Strict SQL syntax required by dbDelta
		$sql_versions = "CREATE TABLE $table_versions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			document_id bigint(20) unsigned NOT NULL,
			version varchar(20) NOT NULL,
			title text NOT NULL,
			content longtext NOT NULL,
			changelog text DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY document_id (document_id),
			KEY version (version)
		) $charset_collate;";

		$sql_logs = "CREATE TABLE $table_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			document_id bigint(20) unsigned NOT NULL,
			version_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			email varchar(100) DEFAULT NULL,
			user_name varchar(150) DEFAULT NULL,
			phone varchar(30) DEFAULT NULL,
			identifier_type varchar(50) NOT NULL,
			identifier_value varchar(255) NOT NULL,
			source varchar(100) NOT NULL,
			extra_data longtext DEFAULT NULL,
			consent_type varchar(50) NOT NULL,
			ip_address varchar(45) NOT NULL,
			user_agent text NOT NULL,
			timestamp datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY document_id (document_id),
			KEY email (email),
			KEY identifier (identifier_type, identifier_value),
			KEY source (source)
		) $charset_collate;";

		dbDelta( $sql_versions );
		dbDelta( $sql_logs );
	}

}
