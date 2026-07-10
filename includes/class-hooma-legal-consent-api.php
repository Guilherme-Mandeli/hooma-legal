<?php
/**
 * Handle user consent logs API and endpoints.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hooma_Legal_Consent_API {

	/**
	 * Register REST API routes.
	 *
	 * @since    1.0.0
	 */
	public function register_rest_routes() {
		register_rest_route( 'hooma-legal/v1', '/consent', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'rest_log_consent' ),
			'permission_callback' => array( $this, 'check_api_permission' ),
		) );
	}

	/**
	 * Verify if the REST request originates from an approved domain, subdomain, or IP.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    REST Request.
	 * @return   bool|WP_Error                  True if allowed, WP_Error otherwise.
	 */
	public function check_api_permission( $request ) {
		// 1. Get client IP
		$client_ip = $this->get_ip_address();

		// Allow localhost IPs automatically (exempt from rate limit)
		if ( in_array( $client_ip, array( '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		// Rate Limiting Check (max 100 requests per hour per client IP)
		$transient_key = 'hooma_api_limit_' . md5( $client_ip );
		$request_count = get_transient( $transient_key );

		if ( false === $request_count ) {
			$request_count = 0;
		}

		if ( $request_count >= 100 ) {
			return new WP_Error(
				'rest_too_many_requests',
				__( 'Demasiadas peticiones. Por favor, inténtelo de nuevo más tarde.', 'hooma-legal' ),
				array( 'status' => 429 )
			);
		}

		// Increment count and persist for 1 hour
		$request_count++;
		set_transient( $transient_key, $request_count, HOUR_IN_SECONDS );

		// Get HTTP Referer and Origin headers
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '';
		$origin  = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( $_SERVER['HTTP_ORIGIN'] ) : '';

		// 2. Resolve request host from Referer or Origin
		$request_host = '';
		if ( ! empty( $origin ) ) {
			$request_host = wp_parse_url( $origin, PHP_URL_HOST );
		}
		if ( empty( $request_host ) && ! empty( $referer ) ) {
			$request_host = wp_parse_url( $referer, PHP_URL_HOST );
		}

		// 3. Resolve current site domain host
		$site_url  = get_home_url();
		$site_host = wp_parse_url( $site_url, PHP_URL_HOST );

		// 4. Default Allow list
		// Allow if there is no referer/origin (direct backend/internal call e.g. from local server PHP CURL)
		if ( empty( $origin ) && empty( $referer ) ) {
			$server_ip = isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : '';
			if ( $client_ip === $server_ip ) {
				return true;
			}
		}

		// Allow if request host matches current domain or any of its subdomains
		if ( ! empty( $request_host ) && ! empty( $site_host ) ) {
			if ( strtolower( $request_host ) === strtolower( $site_host ) ) {
				return true;
			}
			// Check if it is a subdomain
			if ( substr( strtolower( $request_host ), -strlen( '.' . $site_host ) ) === '.' . strtolower( $site_host ) ) {
				return true;
			}
		}

		// 5. Load settings whitelist
		$settings = get_option( 'hooma_legal_settings', array() );
		$whitelist_raw = isset( $settings['api_whitelist'] ) ? $settings['api_whitelist'] : '';

		if ( ! empty( $whitelist_raw ) ) {
			$whitelist_entries = array_filter( array_map( 'trim', explode( "\n", $whitelist_raw ) ) );
			foreach ( $whitelist_entries as $entry ) {
				if ( empty( $entry ) ) {
					continue;
				}

				// Check IP match
				if ( $client_ip === $entry ) {
					return true;
				}

				// Check domain match
				if ( ! empty( $request_host ) ) {
					$clean_entry = strtolower( $entry );
					$clean_host  = strtolower( $request_host );

					// Direct match
					if ( $clean_host === $clean_entry ) {
						return true;
					}

					// Wildcard subdomain match (e.g. *.example.com)
					if ( 0 === strpos( $clean_entry, '*.' ) ) {
						$domain_part = substr( $clean_entry, 2 );
						if ( $clean_host === $domain_part || substr( $clean_host, -strlen( '.' . $domain_part ) ) === '.' . $domain_part ) {
							return true;
						}
					}
				}
			}
		}

		// Reject request
		return new WP_Error(
			'rest_forbidden',
			__( 'Acceso denegado. Petición externa no autorizada.', 'hooma-legal' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * REST API callback to log user consent.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    REST Request.
	 * @return   WP_REST_Response|WP_Error
	 */
	public function rest_log_consent( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$result = $this->process_consent( $params );

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response( array(
			'success'    => true,
			'consent_id' => $result,
			'message'    => __( 'Consentimiento registrado exitosamente.', 'hooma-legal' ),
		), 200 );
	}

	/**
	 * Core function to process and insert consent logging into DB.
	 *
	 * @since    1.0.0
	 * @param    array    $args    Consent logging parameters.
	 * @return   int|WP_Error      Consent log ID or WP_Error on failure.
	 */
	public function process_consent( $args ) {
		global $wpdb;
		$table_logs = $wpdb->prefix . 'hooma_legal_consent_logs';

		// Self-healing check: ensure necessary columns exist
		static $schema_checked = false;
		if ( ! $schema_checked ) {
			$column_exists = $wpdb->get_results( $wpdb->prepare(
				"SHOW COLUMNS FROM $table_logs LIKE %s",
				'identifier_type'
			) );

			if ( empty( $column_exists ) ) {
				if ( ! class_exists( 'Hooma_Legal_DB' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'class-hooma-legal-db.php';
				}
				Hooma_Legal_DB::create_tables();
			}
			$schema_checked = true;
		}

		// 1. Validations: source and identifier_value are strictly required
		if ( empty( $args['source'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: process_consent failed - source is empty.' );
			}
			return new WP_Error( 'missing_source', __( 'El campo de origen (source) es obligatorio.', 'hooma-legal' ) );
		}
		if ( empty( $args['identifier_value'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: process_consent failed - identifier_value is empty.' );
			}
			return new WP_Error( 'missing_identifier', __( 'El valor identificador (identifier_value) es obligatorio.', 'hooma-legal' ) );
		}

		$identifier_type  = isset( $args['identifier_type'] ) ? sanitize_text_field( $args['identifier_type'] ) : 'email';
		$identifier_value = sanitize_text_field( $args['identifier_value'] );
		$source           = sanitize_text_field( $args['source'] );

		// Either document or document_type must be provided
		$document      = isset( $args['document'] ) ? $args['document'] : '';
		$document_type = isset( $args['document_type'] ) ? sanitize_text_field( $args['document_type'] ) : '';

		if ( empty( $document ) && empty( $document_type ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: process_consent failed - both document and document_type are empty.' );
			}
			return new WP_Error( 'missing_document', __( 'Debe proveer un identificador de documento (document o document_type).', 'hooma-legal' ) );
		}

		$doc_id = 0;

		// 2. Resolve document ID
		if ( ! empty( $document ) ) {
			if ( is_numeric( $document ) ) {
				$doc_id = intval( $document );
			} else {
				// Query CPT post by slug
				$post = get_page_by_path( sanitize_title( $document ), OBJECT, 'hooma_legal_doc' );
				if ( $post ) {
					$doc_id = $post->ID;
				}
			}
		} elseif ( ! empty( $document_type ) ) {
			// Query document by CPT meta _hooma_legal_document_type
			$posts = get_posts( array(
				'post_type'      => 'hooma_legal_doc',
				'posts_per_page' => 1,
				'meta_key'       => '_hooma_legal_document_type',
				'meta_value'     => $document_type,
				'post_status'    => array( 'publish', 'draft' ),
			) );
			if ( ! empty( $posts ) ) {
				$doc_id = $posts[0]->ID;
			}
		}

		if ( ! $doc_id || 'hooma_legal_doc' !== get_post_type( $doc_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: process_consent failed - resolved doc_id: ' . $doc_id . ' is invalid or post_type is: ' . get_post_type( $doc_id ) );
			}
			return new WP_Error( 'invalid_document', __( 'No se encontró un documento legal válido para los parámetros proporcionados.', 'hooma-legal' ) );
		}

		// 3. Resolve active version of the document
		$version = get_post_meta( $doc_id, '_hooma_legal_version', true );
		if ( empty( $version ) ) {
			$version = date( 'Y.m.d' );
		}

		// Resolve corresponding version history ID (wp_hooma_legal_versions primary key)
		$table_versions = $wpdb->prefix . 'hooma_legal_versions';
		$version_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_versions WHERE document_id = %d AND version = %s ORDER BY id DESC LIMIT 1",
			$doc_id,
			$version
		) );

		// Fallback to latest snapshot version ID if none matches exactly
		if ( ! $version_id ) {
			$version_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $table_versions WHERE document_id = %d ORDER BY id DESC LIMIT 1",
				$doc_id
			) );
			// If still none exists, create a quick snapshot for safety
			if ( ! $version_id ) {
				if ( ! class_exists( 'Hooma_Legal_Versioning' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'class-hooma-legal-versioning.php';
				}
				$post_obj = get_post( $doc_id );
				$ver_class = new Hooma_Legal_Versioning();
				$ver_class->save_document_snapshot( $doc_id, $post_obj, false );

				$version_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM $table_versions WHERE document_id = %d ORDER BY id DESC LIMIT 1",
					$doc_id
				) );
			}
		}

		// 4. Protection against rapid duplicate submissions (10 seconds rate limit)
		$table_logs = $wpdb->prefix . 'hooma_legal_consent_logs';
		$boundary_time = current_datetime()->sub( new DateInterval( 'PT10S' ) )->format( 'Y-m-d H:i:s' );
		$recent_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_logs 
			 WHERE document_id = %d 
			   AND identifier_type = %s 
			   AND identifier_value = %s 
			   AND source = %s 
			   AND timestamp > %s",
			$doc_id,
			$identifier_type,
			$identifier_value,
			$source,
			$boundary_time
		) );

		if ( $recent_id ) {
			return intval( $recent_id ); // Skip insert, return existing ID
		}

		// 5. Gather fields and insert
		$email     = isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '';
		if ( empty( $email ) && 'email' === $identifier_type ) {
			$email = $identifier_value;
		}

		$user_name = isset( $args['user_name'] ) ? sanitize_text_field( $args['user_name'] ) : '';
		if ( empty( $user_name ) && isset( $args['nombre_completo'] ) ) {
			$user_name = sanitize_text_field( $args['nombre_completo'] );
		}

		$phone        = isset( $args['phone'] ) ? sanitize_text_field( $args['phone'] ) : '';
		$user_id      = isset( $args['user_id'] ) ? intval( $args['user_id'] ) : get_current_user_id();
		$consent_type = isset( $args['consent_type'] ) ? sanitize_text_field( $args['consent_type'] ) : 'explicit';
		
		// Grab extra metadata
		$extra = isset( $args['extra_data'] ) && is_array( $args['extra_data'] ) ? $args['extra_data'] : array();
		// Add any fields that were passed but are not columns
		$known_keys = array( 'document', 'document_type', 'source', 'identifier_type', 'identifier_value', 'email', 'user_name', 'phone', 'user_id', 'consent_type' );
		foreach ( $args as $key => $val ) {
			if ( ! in_array( $key, $known_keys ) ) {
				$extra[ $key ] = is_array( $val ) ? array_map( 'sanitize_text_field', $val ) : sanitize_text_field( $val );
			}
		}

		$extra_data = ! empty( $extra ) ? wp_json_encode( $extra ) : '';

		// Get network information
		$ip_address = $this->get_ip_address();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 255 ) : 'Unknown';

		$inserted = $wpdb->insert(
			$table_logs,
			array(
				'document_id'      => $doc_id,
				'version_id'       => $version_id ? intval( $version_id ) : 0,
				'user_id'          => $user_id ? $user_id : null,
				'email'            => $email,
				'user_name'        => $user_name,
				'phone'            => $phone,
				'identifier_type'  => $identifier_type,
				'identifier_value' => $identifier_value,
				'source'           => $source,
				'extra_data'       => $extra_data,
				'consent_type'     => $consent_type,
				'ip_address'       => $ip_address,
				'user_agent'       => $user_agent,
				'timestamp'        => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal DB Error: ' . $wpdb->last_error );
			}
			return new WP_Error( 'db_insert_error', $wpdb->last_error ? $wpdb->last_error : __( 'No se pudo guardar el consentimiento en la base de datos.', 'hooma-legal' ) );
		}

		return intval( $wpdb->insert_id );
	}

	/**
	 * Retrieve client IP address securely.
	 *
	 * @since    1.0.0
	 * @return   string    IP Address.
	 */
	private function get_ip_address() {
		$ip = '127.0.0.1';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		// Clean and sanitize
		$ips = explode( ',', $ip );
		return substr( sanitize_text_field( trim( $ips[0] ) ), 0, 45 );
	}

}

/**
 * Global helper function to log legal document consent.
 *
 * @since    1.0.0
 * @param    array    $args    Consent logging parameters.
 * @return   int|WP_Error      The logged consent entry primary key ID or WP_Error.
 */
if ( ! function_exists( 'hooma_legal_log_consent' ) ) {
	function hooma_legal_log_consent( $args ) {
		$api = new Hooma_Legal_Consent_API();
		return $api->process_consent( $args );
	}
}
