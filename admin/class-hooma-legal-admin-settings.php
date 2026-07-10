<?php
/**
 * Register settings and admin settings page.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/admin
 */

class Hooma_Legal_Admin_Settings {

	/**
	 * Register settings page submenu.
	 *
	 * @since    1.0.0
	 */
	public function add_settings_menu() {
		add_submenu_page(
			'edit.php?post_type=hooma_legal_doc',
			__( 'Ajustes Globales de Hooma Legal', 'hooma-legal' ),
			__( 'Ajustes Globales', 'hooma-legal' ),
			'manage_options',
			'hooma-legal-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'edit.php?post_type=hooma_legal_doc',
			__( 'Historial de Versiones de Hooma Legal', 'hooma-legal' ),
			__( 'Historial de Versiones', 'hooma-legal' ),
			'manage_options',
			'hooma-legal-versions',
			array( $this, 'render_versions_page' )
		);
	}

	/**
	 * Register option setting.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting(
			'hooma_legal_settings_group',
			'hooma_legal_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize fields in the option group.
	 *
	 * @since    1.0.0
	 * @param    array    $input    Raw input fields.
	 * @return   array              Sanitized input fields.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		$sanitized['company_name']     = isset( $input['company_name'] ) ? sanitize_text_field( $input['company_name'] ) : '';
		$sanitized['brand_name']       = isset( $input['brand_name'] ) ? sanitize_text_field( $input['brand_name'] ) : '';
		$sanitized['vat_type']         = isset( $input['vat_type'] ) ? sanitize_text_field( $input['vat_type'] ) : 'NIF/CIF';
		$sanitized['vat']              = isset( $input['vat'] ) ? sanitize_text_field( $input['vat'] ) : '';
		$sanitized['address']          = isset( $input['address'] ) ? sanitize_text_field( $input['address'] ) : '';
		$sanitized['postal_code']      = isset( $input['postal_code'] ) ? sanitize_text_field( $input['postal_code'] ) : '';
		$sanitized['city']             = isset( $input['city'] ) ? sanitize_text_field( $input['city'] ) : '';
		$sanitized['province']         = isset( $input['province'] ) ? sanitize_text_field( $input['province'] ) : '';
		$sanitized['country']          = isset( $input['country'] ) ? sanitize_text_field( $input['country'] ) : '';
		$sanitized['email']            = isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '';
		$sanitized['phone']            = isset( $input['phone'] ) ? sanitize_text_field( $input['phone'] ) : '';
		$sanitized['website']          = isset( $input['website'] ) ? esc_url_raw( $input['website'] ) : '';
		$sanitized['data_controller']  = isset( $input['data_controller'] ) ? sanitize_text_field( $input['data_controller'] ) : '';
		$sanitized['dpo']              = isset( $input['dpo'] ) ? sanitize_text_field( $input['dpo'] ) : '';
		$sanitized['jurisdiction']     = isset( $input['jurisdiction'] ) ? sanitize_text_field( $input['jurisdiction'] ) : '';
		$sanitized['court']            = isset( $input['court'] ) ? sanitize_text_field( $input['court'] ) : '';
		$sanitized['active_services']  = isset( $input['active_services'] ) && is_array( $input['active_services'] ) ? array_map( 'sanitize_text_field', $input['active_services'] ) : array();
		$sanitized['api_whitelist']    = isset( $input['api_whitelist'] ) ? sanitize_textarea_field( $input['api_whitelist'] ) : '';

		$old_settings = get_option( 'hooma_legal_settings', array() );

		// Temporarily filter get_option to return the new sanitized settings during the bumping process
		$filter_callback = function() use ( $sanitized ) {
			return $sanitized;
		};
		add_filter( 'pre_option_hooma_legal_settings', $filter_callback );

		// Detect global settings variables changes and trigger version bumps on documents using those variables
		$variables_to_check = array(
			'company_name'     => '{{company_name}}',
			'brand_name'       => '{{brand_name}}',
			'vat_type'         => '{{vat_type}}',
			'vat'              => '{{vat}}',
			'address'          => '{{address}}',
			'postal_code'      => '{{postal_code}}',
			'city'             => '{{city}}',
			'province'         => '{{province}}',
			'country'          => '{{country}}',
			'email'            => '{{email}}',
			'phone'            => '{{phone}}',
			'website'          => '{{website}}',
			'data_controller'  => '{{data_controller}}',
			'dpo'              => '{{dpo}}',
			'jurisdiction'     => '{{jurisdiction}}',
			'court'            => '{{court}}',
		);

		$changed_variables = array();
		foreach ( $variables_to_check as $key => $placeholder ) {
			$old_val = isset( $old_settings[ $key ] ) ? $old_settings[ $key ] : '';
			$new_val = isset( $sanitized[ $key ] ) ? $sanitized[ $key ] : '';
			if ( trim( $old_val ) !== trim( $new_val ) ) {
				$changed_variables[] = $placeholder;
			}
		}

		if ( ! empty( $changed_variables ) ) {
			if ( ! class_exists( 'Hooma_Legal_Versioning' ) ) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-versioning.php';
			}
			Hooma_Legal_Versioning::bump_documents_using_variables( $changed_variables );
		}

		remove_filter( 'pre_option_hooma_legal_settings', $filter_callback );

		return $sanitized;
	}

	/**
	 * Render the settings page template.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/hooma-legal-admin-display.php';
	}

	/**
	 * Render the versions list page.
	 *
	 * @since    1.0.0
	 */
	public function render_versions_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/hooma-legal-admin-versions.php';
	}

}
