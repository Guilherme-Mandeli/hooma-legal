<?php
/**
 * Handle Gutenberg blocks registration and rendering.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

class Hooma_Legal_Blocks {

	/**
	 * Register Gutenberg blocks in PHP.
	 *
	 * @since    1.0.0
	 */
	public function register_blocks() {
		// Register Dynamic Link Block
		register_block_type( 'hooma-legal/dynamic-link', array(
			'editor_script'   => 'hooma-legal-blocks-js',
			'render_callback' => array( $this, 'render_dynamic_link_block' ),
			'attributes'      => array(
				'documentId' => array(
					'type'    => 'string',
					'default' => '',
				),
				'linkText'   => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		) );

		// Register Reusable Legal Block Selector
		register_block_type( 'hooma-legal/reusable-block', array(
			'editor_script'   => 'hooma-legal-blocks-js',
			'render_callback' => array( $this, 'render_reusable_block' ),
			'attributes'      => array(
				'blockId' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		) );
	}

	/**
	 * Enqueue blocks script in Gutenberg Editor.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_editor_assets() {
		wp_register_script(
			'hooma-legal-blocks-js',
			plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/hooma-legal-blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-data', 'wp-editor' ),
			HOOMA_LEGAL_VERSION,
			true
		);
	}

	/**
	 * Enqueue sidebar script in Gutenberg Editor.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_sidebar_assets() {
		wp_enqueue_script(
			'hooma-legal-sidebar-js',
			plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/hooma-legal-sidebar.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' ),
			HOOMA_LEGAL_VERSION,
			true
		);
	}

	/**
	 * Render callback for Dynamic Link Block.
	 *
	 * @since    1.0.0
	 * @param    array    $attributes    Block attributes.
	 * @return   string                  HTML output.
	 */
	public function render_dynamic_link_block( $attributes ) {
		if ( empty( $attributes['documentId'] ) ) {
			return '';
		}

		$post_id = intval( $attributes['documentId'] );
		$url     = get_permalink( $post_id );

		if ( ! $url ) {
			return '';
		}

		$text = ! empty( $attributes['linkText'] ) ? $attributes['linkText'] : get_the_title( $post_id );

		return sprintf( '<a href="%1$s" class="hooma-legal-dynamic-link">%2$s</a>', esc_url( $url ), esc_html( $text ) );
	}

	/**
	 * Render callback for Reusable Block.
	 *
	 * @since    1.0.0
	 * @param    array    $attributes    Block attributes.
	 * @return   string                  HTML output.
	 */
	public function render_reusable_block( $attributes ) {
		if ( empty( $attributes['blockId'] ) ) {
			return '';
		}

		$block_id = intval( $attributes['blockId'] );
		$post     = get_post( $block_id );

		if ( ! $post || 'hooma_legal_block' !== $post->post_type ) {
			return '';
		}

		// Avoid infinite loops by temporarily unhooking the block parser if we implement one,
		// but since we are just calling the_content, it will render correctly.
		// Apply content filters (including variables parser which we hooked to the_content)
		return apply_filters( 'the_content', $post->post_content );
	}

}
