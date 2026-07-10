<?php
/**
 * Handle document snapshots and version history logs.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

class Hooma_Legal_Versioning {

	/**
	 * Log a static snapshot of a document upon publication or updates.
	 *
	 * @since    1.0.0
	 * @param    int       $post_id    Post ID.
	 * @param    WP_Post   $post       Post object.
	 * @param    bool      $update     Whether this is an existing post being updated.
	 */
	public function save_document_snapshot( $post_id, $post, $update ) {
		// Only track legal documents
		if ( 'hooma_legal_doc' !== $post->post_type ) {
			return;
		}

		// Detect if post content contains the cookies shortcode and save metadata flag
		$has_shortcode = ( has_shortcode( $post->post_content, 'faz_cookie_policy_complete' ) || has_shortcode( $post->post_content, 'faz_cookie_policy' ) ) ? '1' : '0';
		update_post_meta( $post_id, '_hooma_has_cookies_shortcode', $has_shortcode );

		// Only track published documents
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Prevent infinite loops during save processes
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'hooma_legal_versions';

		// Get the current version meta
		$version = get_post_meta( $post_id, '_hooma_legal_version', true );
		if ( empty( $version ) ) {
			$version = date( 'Y.m.d' );
			update_post_meta( $post_id, '_hooma_legal_version', $version );
		}

		// Require parser class to resolve all variables statically
		if ( ! class_exists( 'Hooma_Legal_Parser' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-hooma-legal-parser.php';
		}

		$parser = new Hooma_Legal_Parser();
		$parsed_content = $parser->parse_variables( $post->post_content );
		
		// Render Gutenberg blocks and shortcodes into static HTML for compliance tracking
		$parsed_content = do_blocks( $parsed_content );
		$parsed_content = do_shortcode( $parsed_content );

		// Fetch the latest snapshot to verify if changes were actually made
		$last_snapshot = $wpdb->get_row( $wpdb->prepare(
			"SELECT version, content FROM $table_name WHERE document_id = %d ORDER BY id DESC LIMIT 1",
			$post_id
		) );

		// If nothing changed, do not store a duplicate snapshot
		if ( $last_snapshot && $last_snapshot->version === $version && $last_snapshot->content === $parsed_content ) {
			return;
		}

		// If content changed and the version wasn't manually bumped in this save, bump it automatically
		if ( $last_snapshot && $last_snapshot->content !== $parsed_content && $last_snapshot->version === $version ) {
			$version = self::bump_version_string( $version );
			update_post_meta( $post_id, '_hooma_legal_version', $version );
			update_post_meta( $post_id, '_hooma_legal_revision_date', date( 'Y-m-d' ) );
		}

		// Get optional changelog meta
		$changelog = get_post_meta( $post_id, '_hooma_legal_changelog', true );

		// Insert the historic record
		$wpdb->insert(
			$table_name,
			array(
				'document_id' => $post_id,
				'version'     => $version,
				'title'       => $post->post_title,
				'content'     => $parsed_content,
				'changelog'   => $changelog ? sanitize_text_field( $changelog ) : '',
				'created_by'  => get_current_user_id(),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Clean up the temporary changelog meta after snapshotting
		delete_post_meta( $post_id, '_hooma_legal_changelog' );
	}

	/**
	 * Automatically bump versions and capture new snapshots for all documents containing the cookies shortcode.
	 *
	 * @since    1.0.0
	 * @param    string    $changelog    Changelog note.
	 */
	public static function bump_documents_with_cookies_shortcode( $changelog ) {
		// Query all published documents
		$posts = get_posts( array(
			'post_type'      => 'hooma_legal_doc',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );

		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post ) {
			// Dynamic on-the-fly verification to ensure backwards compatibility
			if ( has_shortcode( $post->post_content, 'faz_cookie_policy_complete' ) || has_shortcode( $post->post_content, 'faz_cookie_policy' ) ) {
				$post_id = $post->ID;

				// Save/sync meta flag
				update_post_meta( $post_id, '_hooma_has_cookies_shortcode', '1' );

				// Get current version
				$version = get_post_meta( $post_id, '_hooma_legal_version', true );
				if ( empty( $version ) ) {
					$version = date( 'Y.m.d' );
				}

				// Bump version string
				$new_version = self::bump_version_string( $version );

				// Update metadata
				update_post_meta( $post_id, '_hooma_legal_version', $new_version );
				update_post_meta( $post_id, '_hooma_legal_revision_date', date( 'Y-m-d' ) );

				// Save a historic snapshot with the new version and changelog note
				global $wpdb;
				$table_name = $wpdb->prefix . 'hooma_legal_versions';

				// Require parser to resolve variables
				if ( ! class_exists( 'Hooma_Legal_Parser' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'class-hooma-legal-parser.php';
				}

			$parser = new Hooma_Legal_Parser();
			$parsed_content = $parser->parse_variables( $post->post_content );
			$parsed_content = do_blocks( $parsed_content );
			$parsed_content = do_shortcode( $parsed_content );

			$wpdb->insert(
				$table_name,
				array(
					'document_id' => $post_id,
					'version'     => $new_version,
					'title'       => $post->post_title,
					'content'     => $parsed_content,
					'changelog'   => sanitize_text_field( $changelog ),
					'created_by'  => get_current_user_id() ? get_current_user_id() : 1,
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
			);
			}
		}
	}

	/**
	 * Increments a version string (supports YYYY.MM.DD date strings and semantic X.Y.Z versioning).
	 *
	 * @since    1.0.0
	 * @param    string    $version    Current version.
	 * @return   string                New version.
	 */
	public static function bump_version_string( $version ) {
		// If it is a date format YYYY.MM.DD
		if ( preg_match( '/^\d{4}\.\d{2}\.\d{2}$/', $version ) ) {
			$today = date( 'Y.m.d' );
			if ( $version === $today ) {
				return $today . '.1';
			}
			return $today;
		}
		
		// If it is a date format YYYY.MM.DD.X (with revision number)
		if ( preg_match( '/^\d{4}\.\d{2}\.\d{2}\.(\d+)$/', $version, $matches ) ) {
			$today = date( 'Y.m.d' );
			$parts = explode( '.', $version );
			if ( $parts[0] . '.' . $parts[1] . '.' . $parts[2] === $today ) {
				$rev = intval( $parts[3] ) + 1;
				return $today . '.' . $rev;
			}
			return $today;
		}
		
		// If standard semantic versioning X.Y.Z, bump Z (patch)
		$parts = explode( '.', $version );
		if ( count( $parts ) >= 3 ) {
			$parts[2] = intval( $parts[2] ) + 1;
			return implode( '.', $parts );
		}
		if ( count( $parts ) == 2 ) {
			return $version . '.1';
		}
		return $version . '.1';
	}

	/**
	 * Automatically bump versions and capture new snapshots for all documents containing any of the changed variables.
	 *
	 * @since    1.0.0
	 * @param    array    $changed_variables    List of placeholder strings that changed.
	 */
	public static function bump_documents_using_variables( $changed_variables ) {
		if ( empty( $changed_variables ) ) {
			return;
		}

		// Query all published documents
		$posts = get_posts( array(
			'post_type'      => 'hooma_legal_doc',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );

		if ( empty( $posts ) ) {
			return;
		}

		$changelog_variables = implode( ', ', $changed_variables );
		$changelog = sprintf( __( 'Cambio en variable(s) global(es): %s', 'hooma-legal' ), $changelog_variables );

		foreach ( $posts as $post ) {
			$uses_variable = false;
			foreach ( $changed_variables as $placeholder ) {
				if ( false !== strpos( $post->post_content, $placeholder ) ) {
					$uses_variable = true;
					break;
				}
			}

			if ( $uses_variable ) {
				$post_id = $post->ID;

				// Get current version
				$version = get_post_meta( $post_id, '_hooma_legal_version', true );
				if ( empty( $version ) ) {
					$version = date( 'Y.m.d' );
				}

				// Bump version string
				$new_version = self::bump_version_string( $version );

				// Update metadata
				update_post_meta( $post_id, '_hooma_legal_version', $new_version );
				update_post_meta( $post_id, '_hooma_legal_revision_date', date( 'Y-m-d' ) );

				// Save a historic snapshot with the new version and changelog note
				global $wpdb;
				$table_name = $wpdb->prefix . 'hooma_legal_versions';

				// Require parser to resolve variables
				if ( ! class_exists( 'Hooma_Legal_Parser' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'class-hooma-legal-parser.php';
				}

				$parser = new Hooma_Legal_Parser();
				$parsed_content = $parser->parse_variables( $post->post_content );
				$parsed_content = do_blocks( $parsed_content );
				$parsed_content = do_shortcode( $parsed_content );

				$wpdb->insert(
					$table_name,
					array(
						'document_id' => $post_id,
						'version'     => $new_version,
						'title'       => $post->post_title,
						'content'     => $parsed_content,
						'changelog'   => sanitize_text_field( $changelog ),
						'created_by'  => get_current_user_id() ? get_current_user_id() : 1,
						'created_at'  => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
				);
			}
		}
	}

}
