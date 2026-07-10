<?php
/**
 * Parse dynamic variables in legal documents content.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

/**
 * Parse dynamic variables in legal documents content.
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 * @author     Hooma Legal
 */
class Hooma_Legal_Parser {

	/**
	 * Parse dynamic variables in the content of the document.
	 *
	 * @since    1.0.0
	 * @param    string    $content    The content to be parsed.
	 * @return   string                The parsed content with variables replaced.
	 */
	public function parse_variables( $content ) {
		// Only parse if the content contains double curly braces
		if ( false === strpos( $content, '{{' ) ) {
			return $content;
		}

		$options = get_option( 'hooma_legal_settings', array() );

		// Merge with defaults to prevent empty checks
		$defaults = array(
			'company_name'     => '',
			'brand_name'       => '',
			'vat_type'         => 'NIF/CIF',
			'vat'              => '',
			'address'          => '',
			'postal_code'      => '',
			'city'             => '',
			'province'         => '',
			'country'          => '',
			'email'            => '',
			'phone'            => '',
			'website'          => get_home_url(),
			'data_controller'  => '',
			'dpo'              => '',
			'jurisdiction'     => '',
			'court'            => '',
		);

		$settings = wp_parse_args( $options, $defaults );

		// Map placeholders to values
		$replacements = array(
			'{{company_name}}'    => $settings['company_name'],
			'{{brand_name}}'      => $settings['brand_name'],
			'{{vat_type}}'        => $settings['vat_type'],
			'{{vat}}'             => $settings['vat'],
			'{{email}}'           => $settings['email'],
			'{{phone}}'           => $settings['phone'],
			'{{address}}'         => $settings['address'],
			'{{postal_code}}'     => $settings['postal_code'],
			'{{city}}'            => $settings['city'],
			'{{province}}'        => $settings['province'],
			'{{country}}'         => $settings['country'],
			'{{website}}'         => $settings['website'],
			'{{dpo}}'             => $settings['dpo'],
			'{{data_controller}}' => $settings['data_controller'],
			'{{jurisdiction}}'    => $settings['jurisdiction'],
			'{{court}}'           => $settings['court'],
		);

		// Apply filters to allow external variables or modifications
		$replacements = apply_filters( 'hooma_legal_variables_replacements', $replacements, $settings );

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	}

}
