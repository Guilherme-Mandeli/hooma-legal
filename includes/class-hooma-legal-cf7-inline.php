<?php
/**
 * Handle Contact Form 7 inline legal document viewing.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

/**
 * Handle Contact Form 7 inline legal document viewing.
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 * @author     Hooma Legal
 */
class Hooma_Legal_CF7_Inline {


	/**
	 * Log debug messages to a local file.
	 *
	 * @since    1.0.0
	 * @param    string    $message    The message.
	 */
	private function log_debug( $message ) {
		error_log( '[Hooma CF7 Debug] ' . $message );
	}

	/**
	 * Scan form HTML and inject inline legal document viewers.
	 *
	 * @since    1.0.0
	 * @param    string    $html           The form HTML.
	 * @return   string                    Modified form HTML.
	 */
	public function inject_inline_documents( $html ) {
		$this->log_debug( '--- START INJECT INLINE DOCUMENTS ---' );
		if ( empty( $html ) ) {
			$this->log_debug( 'HTML is empty.' );
			return $html;
		}

		$this->log_debug( 'Form HTML sample: ' . substr( $html, 0, 300 ) );

		// 1. Scan form HTML for all hooma acceptance name attributes to know which documents are active
		preg_match_all( '/name="hooma_legal_(?:accept|consent)-([^"\[\]]+)(?:\[\])?"/i', $html, $name_matches );
		
		$this->log_debug( 'Name matches: ' . print_r( $name_matches, true ) );

		if ( empty( $name_matches[1] ) ) {
			$this->log_debug( 'No active hooma name matches found in HTML.' );
			return $html;
		}

		$active_identifiers = array_unique( $name_matches[1] );
		$resolved_docs = array();

		foreach ( $active_identifiers as $identifier ) {
			$doc_id = $this->resolve_document_id( $identifier );
			$this->log_debug( "Identifier '{$identifier}' resolved to Post ID: {$doc_id}" );
			if ( $doc_id ) {
				$resolved_docs[ $identifier ] = $doc_id;
			}
		}

		if ( empty( $resolved_docs ) ) {
			$this->log_debug( 'No active documents resolved.' );
			return $html;
		}

		// We will accumulate the inline viewers to append them at the end
		$inline_viewers = array();

		// 2. Scan the form HTML for all <a> tags and check if their URL points to one of the active documents (or their translations)
		$pattern = '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>.*?<\/a>/is';
		
		$html = preg_replace_callback( $pattern, function( $link_matches ) use ( $resolved_docs, &$inline_viewers ) {
			$full_link = $link_matches[0];
			$href      = $link_matches[1];

			$this->log_debug( "Found link: href='{$href}', outer HTML='{$full_link}'" );

			// Resolve post ID from href
			$link_doc_id = $this->resolve_doc_id_from_url( $href );
			$this->log_debug( "Resolved link href '{$href}' to Post ID: {$link_doc_id}" );

			if ( ! $link_doc_id ) {
				return $full_link;
			}

			// Check if this resolved post ID matches any of our active checkbox documents
			$matched_identifier = false;
			foreach ( $resolved_docs as $identifier => $doc_id ) {
				$translated_doc_id = $this->get_translated_post_id( $doc_id );
				$translated_link_doc_id = $this->get_translated_post_id( $link_doc_id );
				
				$this->log_debug( "Comparing Checkbox ID: {$doc_id} (translated: {$translated_doc_id}) vs Link Target ID: {$link_doc_id} (translated: {$translated_link_doc_id})" );

				if ( $doc_id === $link_doc_id || $translated_doc_id === $link_doc_id || $doc_id === $translated_link_doc_id ) {
					$matched_identifier = $identifier;
					break;
				}
			}

			$this->log_debug( "Match result: matched_identifier='{$matched_identifier}'" );

			if ( ! $matched_identifier ) {
				return $full_link;
			}

			// Add data-hooma-target attribute to the <a> tag
			$modified_link = preg_replace( '/<a\s+/is', '<a data-hooma-target="' . esc_attr( $matched_identifier ) . '" ', $full_link );

			// Render the inline viewer HTML
			$post = get_post( $link_doc_id );
			if ( ! $post ) {
				$this->log_debug( "Failed to load post for ID: {$link_doc_id}" );
				return $full_link;
			}

			// Load parser to render variables
			if ( ! class_exists( 'Hooma_Legal_Parser' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'class-hooma-legal-parser.php';
			}

			$parser         = new Hooma_Legal_Parser();
			$parsed_content = $parser->parse_variables( $post->post_content, $link_doc_id );
			$doc_content    = apply_filters( 'the_content', $parsed_content );

			ob_start();
			?>
			<div id="hooma-inline-doc-<?php echo esc_attr( $matched_identifier ); ?>" class="hooma-inline-doc-container">
				<div class="hooma-inline-doc-header">
					<span class="hooma-inline-doc-title"><?php echo esc_html( $post->post_title ); ?></span>
					<button type="button" class="hooma-inline-doc-close-btn" data-target="<?php echo esc_attr( $matched_identifier ); ?>" title="<?php esc_attr_e( 'Cerrar', 'hooma-legal' ); ?>">&times;</button>
				</div>
				<div class="hooma-inline-doc-content">
					<?php echo $doc_content; ?>
				</div>
			</div>
			<?php
			$inline_viewers[] = ob_get_clean();

			return $modified_link;
		}, $html );

		// Append all generated inline viewers to the bottom of the form HTML
		if ( ! empty( $inline_viewers ) ) {
			$html .= implode( '', $inline_viewers );
		}

		$this->log_debug( '--- END INJECT INLINE DOCUMENTS ---' );
		return $html;
	}

	/**
	 * Resolve a legal document ID from a post ID, slug, or Gutenberg key.
	 *
	 * @since    1.0.0
	 * @param    string    $identifier    The identifier from checkbox name suffix.
	 * @return   int                      The resolved document post ID or 0 if not found.
	 */
	private function resolve_document_id( $identifier ) {
		if ( empty( $identifier ) ) {
			return 0;
		}

		// 1. Check if identifier is a numeric Post ID
		if ( is_numeric( $identifier ) ) {
			$doc_id = intval( $identifier );
			if ( 'hooma_legal_doc' === get_post_type( $doc_id ) && 'publish' === get_post_status( $doc_id ) ) {
				return $doc_id;
			}
		}

		// 2. Check if identifier is a Gutenberg document type key
		$posts_by_type = get_posts( array(
			'post_type'      => 'hooma_legal_doc',
			'posts_per_page' => 1,
			'meta_key'       => '_hooma_legal_document_type',
			'meta_value'     => $identifier,
			'post_status'    => 'publish',
		) );
		if ( ! empty( $posts_by_type ) ) {
			return $posts_by_type[0]->ID;
		}

		// 3. Check if identifier is a Post Slug
		$posts_by_slug = get_posts( array(
			'post_type'      => 'hooma_legal_doc',
			'name'           => sanitize_title( $identifier ),
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		) );
		if ( ! empty( $posts_by_slug ) ) {
			return $posts_by_slug[0]->ID;
		}

		return 0;
	}

	/**
	 * Resolve a document post ID from a URL (supporting drafts).
	 *
	 * @since    1.0.0
	 * @param    string    $url    The target URL.
	 * @return   int               The resolved document ID or 0.
	 */
	private function resolve_doc_id_from_url( $url ) {
		if ( empty( $url ) ) {
			return 0;
		}

		// 1. Try native WordPress url_to_postid
		$post_id = url_to_postid( $url );
		if ( $post_id && 'hooma_legal_doc' === get_post_type( $post_id ) && 'publish' === get_post_status( $post_id ) ) {
			return $post_id;
		}

		// 2. Try parsing URL query parameters (e.g. ?p=540 or ?post=540)
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			parse_str( $query, $query_vars );
			if ( isset( $query_vars['p'] ) && is_numeric( $query_vars['p'] ) ) {
				$p_val = intval( $query_vars['p'] );
				if ( 'hooma_legal_doc' === get_post_type( $p_val ) && 'publish' === get_post_status( $p_val ) ) {
					return $p_val;
				}
			}
			if ( isset( $query_vars['post'] ) && is_numeric( $query_vars['post'] ) ) {
				$post_val = intval( $query_vars['post'] );
				if ( 'hooma_legal_doc' === get_post_type( $post_val ) && 'publish' === get_post_status( $post_val ) ) {
					return $post_val;
				}
			}
		}

		// 3. Try parsing slug from the path
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! empty( $path ) ) {
			$path_segments = array_filter( explode( '/', $path ) );
			if ( ! empty( $path_segments ) ) {
				$slug = end( $path_segments );
				$posts_by_slug = get_posts( array(
					'post_type'      => 'hooma_legal_doc',
					'name'           => sanitize_title( $slug ),
					'posts_per_page' => 1,
					'post_status'    => 'publish',
				) );
				if ( ! empty( $posts_by_slug ) ) {
					return $posts_by_slug[0]->ID;
				}
			}
		}

		return 0;
	}

	/**
	 * Get the translated post ID if WPML or Polylang is active.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id    The post ID.
	 * @return   int                The translated or original post ID.
	 */
	private function get_translated_post_id( $post_id ) {
		if ( empty( $post_id ) ) {
			return $post_id;
		}

		// WPML support
		if ( function_exists( 'wpml_object_id_filter' ) ) {
			$translated = wpml_object_id_filter( $post_id, 'hooma_legal_doc', true );
			if ( $translated ) {
				return $translated;
			}
		} elseif ( has_filter( 'wpml_object_id' ) ) {
			$translated = apply_filters( 'wpml_object_id', $post_id, 'hooma_legal_doc', true );
			if ( $translated ) {
				return $translated;
			}
		}

		// Polylang support
		if ( function_exists( 'pll_get_post' ) ) {
			$translated = pll_get_post( $post_id );
			if ( $translated ) {
				return $translated;
			}
		}

		return $post_id;
	}

}
