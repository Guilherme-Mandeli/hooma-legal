<?php
/**
 * Handle integrations with third-party plugins (CF7, Cookie Managers).
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

class Hooma_Legal_Integrations {

	/**
	 * Register hooks for third-party integrations.
	 *
	 * @since    1.0.0
	 */
	public function register_integrations() {
		// Hook into Contact Form 7 submission
		add_action( 'wpcf7_before_send_mail', array( $this, 'handle_cf7_submit' ), 10, 3 );

	}

	/**
	 * Handle CF7 successful submissions to log legal consent.
	 *
	 * @since    1.0.0
	 * @param    object    $contact_form    WPCF7_ContactForm object.
	 * @param    bool      $abort           Whether to abort email sending.
	 * @param    object    $submission      WPCF7_Submission object.
	 */
	public function handle_cf7_submit( $contact_form, $abort = false, $submission = null ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Hooma Legal: handle_cf7_submit hook triggered.' );
		}

		if ( ! class_exists( 'WPCF7_Submission' ) ) {
			return;
		}

		if ( ! $submission ) {
			$submission = WPCF7_Submission::get_instance();
		}

		if ( ! $submission ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: WPCF7_Submission instance is null.' );
			}
			return;
		}

		$posted_data = $submission->get_posted_data();

		// Scan for acceptance checkboxes and resolve document target from suffix
		$consent_field = '';
		$document_identifier = '';
		foreach ( $posted_data as $key => $value ) {
			if ( 0 === strpos( $key, 'hooma_legal_accept' ) || 0 === strpos( $key, 'hooma_legal_consent' ) ) {
				if ( ! empty( $value ) ) {
					$consent_field = $key;
					
					// Check if there is a suffix (after a hyphen)
					$parts = explode( '-', $key );
					if ( count( $parts ) > 1 ) {
						// Reconstruct suffix in case it had multiple hyphens
						array_shift( $parts );
						$document_identifier = implode( '-', $parts );
					}
					break;
				}
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Hooma Legal: Consent field detected: ' . $consent_field . ', Identifier: ' . $document_identifier );
		}

		// If no legal acceptance checkbox was processed, exit
		if ( empty( $consent_field ) ) {
			return;
		}

		$document      = '';
		$document_type = '';

		if ( ! empty( $document_identifier ) ) {
			if ( is_numeric( $document_identifier ) ) {
				$document = intval( $document_identifier );
			} else {
				// Could be a slug or a document type key
				// Check if this matches a document type key
				$doc_by_type = get_posts( array(
					'post_type'      => 'hooma_legal_doc',
					'posts_per_page' => 1,
					'meta_key'       => '_hooma_legal_document_type',
					'meta_value'     => $document_identifier,
					'post_status'    => array( 'publish', 'draft' ),
				) );
				if ( ! empty( $doc_by_type ) ) {
					$document_type = $document_identifier;
				} else {
					$document = $document_identifier;
				}
			}
		}

		// If no target was specified in the checkbox name, look at specific fields
		if ( empty( $document ) && empty( $document_type ) ) {
			if ( ! empty( $posted_data['hooma_legal_document'] ) ) {
				$document = sanitize_text_field( $posted_data['hooma_legal_document'] );
			} elseif ( ! empty( $posted_data['hooma_legal_doc_id'] ) ) {
				$document = intval( $posted_data['hooma_legal_doc_id'] );
			} elseif ( ! empty( $posted_data['hooma_legal_document_type'] ) ) {
				$document_type = sanitize_text_field( $posted_data['hooma_legal_document_type'] );
			} else {
				// Fallback to the current page ID ONLY if that page is actually a legal document post
				$container_post_id = $submission->get_meta( 'container_post_id' );
				if ( $container_post_id && 'hooma_legal_doc' === get_post_type( $container_post_id ) ) {
					$document = $container_post_id;
				}
			}
		}

		// If we still have no document identifier, we cannot log consent
		if ( empty( $document ) && empty( $document_type ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: Document identifier is empty.' );
			}
			return;
		}

		// Extract user details using smart mapping (English, Spanish, Brazilian Portuguese)
		$email_keys = array(
			'your-email', 'email', 'mail', 'correo', 'e-mail', 'email-address', 'email_address', 'correo_electronico', 'correo-electronico',
			'seu-email', 'seu-e-mail', 'endereco-de-email', 'endereco-de-e-mail', 'email-principal', 'email_principal', 'correio'
		);
		$name_keys = array(
			'your-name', 'name', 'nombre', 'first_name', 'last_name', 'fullname', 'nombre_completo', 'nombre-completo',
			'seu-nome', 'primeiro-nome', 'sobrenome', 'nome-completo', 'nome_completo'
		);
		$phone_keys = array(
			'your-tel', 'tel', 'telefono', 'phone', 'your-phone', 'mobile', 'celular', 'telefono-contacto', 'telefono_contacto',
			'seu-telefone', 'fone', 'contato'
		);

		$email     = '';
		$user_name = '';
		$phone     = '';

		// Search email
		foreach ( $email_keys as $k ) {
			if ( ! empty( $posted_data[ $k ] ) ) {
				$val = sanitize_email( $posted_data[ $k ] );
				if ( is_email( $val ) ) {
					$email = $val;
					break;
				}
			}
		}

		// Search name
		foreach ( $name_keys as $k ) {
			if ( ! empty( $posted_data[ $k ] ) ) {
				// If first_name and last_name are separate
				if ( 'first_name' === $k && ! empty( $posted_data['last_name'] ) ) {
					$user_name = sanitize_text_field( $posted_data['first_name'] ) . ' ' . sanitize_text_field( $posted_data['last_name'] );
					break;
				}
				if ( 'primeiro-nome' === $k && ! empty( $posted_data['sobrenome'] ) ) {
					$user_name = sanitize_text_field( $posted_data['primeiro-nome'] ) . ' ' . sanitize_text_field( $posted_data['sobrenome'] );
					break;
				}
				$user_name = sanitize_text_field( $posted_data[ $k ] );
				break;
			}
		}

		// Search phone
		foreach ( $phone_keys as $k ) {
			if ( ! empty( $posted_data[ $k ] ) ) {
				$phone = sanitize_text_field( $posted_data[ $k ] );
				break;
			}
		}

		// If no email was found, check if any value in posted data looks like an email address
		if ( empty( $email ) ) {
			foreach ( $posted_data as $key => $val ) {
				if ( is_string( $val ) && is_email( trim( $val ) ) ) {
					$email = sanitize_email( trim( $val ) );
					break;
				}
			}
		}

		// Determine identifier type and value
		$identifier_value = '';
		$identifier_type  = 'email';

		if ( ! empty( $email ) ) {
			$identifier_value = $email;
		} elseif ( ! empty( $user_name ) ) {
			$identifier_value = $user_name;
			$identifier_type  = 'name';
		} elseif ( ! empty( $phone ) ) {
			$identifier_value = $phone;
			$identifier_type  = 'phone';
		} else {
			$identifier_value = 'CF7-' . $contact_form->id() . '-' . time();
			$identifier_type  = 'session_id';
		}

		$extra_data = array(
			'form_id'    => $contact_form->id(),
			'form_title' => $contact_form->title(),
			'field_name' => $consent_field,
		);

		$log_args = array(
			'source'           => 'contact_form_7',
			'identifier_type'  => $identifier_type,
			'identifier_value' => $identifier_value,
			'email'            => $email,
			'user_name'        => $user_name,
			'phone'            => $phone,
			'consent_type'     => 'explicit',
			'extra_data'       => $extra_data,
		);

		if ( ! empty( $document ) ) {
			$log_args['document'] = $document;
		}
		if ( ! empty( $document_type ) ) {
			$log_args['document_type'] = $document_type;
		}

		if ( function_exists( 'hooma_legal_log_consent' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Hooma Legal: Logging consent with args: ' . print_r( $log_args, true ) );
			}
			$res = hooma_legal_log_consent( $log_args );
			if ( is_wp_error( $res ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Hooma Legal: Consent log failed: ' . $res->get_error_message() );
				}
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Hooma Legal: Consent logged successfully. ID: ' . $res );
				}
			}
		}
	}


}
