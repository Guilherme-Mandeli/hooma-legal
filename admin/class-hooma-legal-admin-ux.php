<?php
/**
 * Handle admin UX extensions like row actions.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/admin
 */

class Hooma_Legal_Admin_UX {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function register_ux_hooks() {
		// Row actions for hooma_legal_doc
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );

		// Custom columns for hooma_legal_doc
		add_filter( 'manage_hooma_legal_doc_posts_columns', array( $this, 'set_custom_cpt_columns' ) );
		add_action( 'manage_hooma_legal_doc_posts_custom_column', array( $this, 'render_custom_cpt_columns' ), 10, 2 );

		// Quick Edit hooks
		add_action( 'quick_edit_custom_box', array( $this, 'display_quick_edit_fields' ), 10, 2 );
		add_action( 'save_post_hooma_legal_doc', array( $this, 'save_quick_edit_fields' ) );

		// Hook admin URL action endpoints
		add_action( 'admin_action_hooma_legal_duplicate_post', array( $this, 'handle_duplicate_post' ) );
		add_action( 'admin_action_hooma_legal_mark_revised', array( $this, 'handle_mark_revised' ) );

		// Show notices if redirect query arguments are present
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Add "Duplicar" and "Marcar Revisado" row actions.
	 *
	 * @since    1.0.0
	 */
	public function add_row_actions( $actions, $post ) {
		if ( 'hooma_legal_doc' !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		// Nonce URLs
		$duplicate_nonce = wp_create_nonce( 'hooma_legal_duplicate_' . $post->ID );
		$duplicate_url   = admin_url( 'admin.php?action=hooma_legal_duplicate_post&post=' . $post->ID . '&nonce=' . $duplicate_nonce );

		$revised_nonce = wp_create_nonce( 'hooma_legal_revised_' . $post->ID );
		$revised_url   = admin_url( 'admin.php?action=hooma_legal_mark_revised&post=' . $post->ID . '&nonce=' . $revised_nonce );

		$actions['duplicate']    = sprintf( '<a href="%s" title="%s">%s</a>', esc_url( $duplicate_url ), esc_attr__( 'Duplicar este documento', 'hooma-legal' ), esc_html__( 'Duplicar', 'hooma-legal' ) );
		$actions['mark_revised'] = sprintf( '<a href="%s" title="%s">%s</a>', esc_url( $revised_url ), esc_attr__( 'Marcar revisión como hecha hoy', 'hooma-legal' ), esc_html__( 'Marcar Revisado', 'hooma-legal' ) );

		return $actions;
	}

	/**
	 * Handle post duplication request.
	 *
	 * @since    1.0.0
	 */
	public function handle_duplicate_post() {
		if ( empty( $_GET['post'] ) || empty( $_GET['nonce'] ) ) {
			wp_die( __( 'Petición inválida.', 'hooma-legal' ) );
		}

		$post_id = intval( $_GET['post'] );
		$nonce   = sanitize_text_field( $_GET['nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'hooma_legal_duplicate_' . $post_id ) || ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Permiso denegado.', 'hooma-legal' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( __( 'Documento no encontrado.', 'hooma-legal' ) );
		}

		// Create duplicate post array
		$new_post = array(
			'post_title'   => $post->post_title . ' (' . __( 'Copia', 'hooma-legal' ) . ')',
			'post_content' => $post->post_content,
			'post_status'  => 'draft',
			'post_type'    => $post->post_type,
			'post_excerpt' => $post->post_excerpt,
			'post_author'  => get_current_user_id(),
		);

		$new_post_id = wp_insert_post( $new_post );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( __( 'Error al duplicar el documento.', 'hooma-legal' ) );
		}

		// Copy post meta fields
		$version       = get_post_meta( $post_id, '_hooma_legal_version', true );
		$revision_date = get_post_meta( $post_id, '_hooma_legal_revision_date', true );

		update_post_meta( $new_post_id, '_hooma_legal_version', $version ? $version : '1.0.0' );
		update_post_meta( $new_post_id, '_hooma_legal_revision_date', $revision_date ? $revision_date : date( 'Y-m-d' ) );

		// Redirect back with success flag
		wp_redirect( admin_url( 'edit.php?post_type=hooma_legal_doc&hooma_notice=duplicated' ) );
		exit;
	}

	/**
	 * Handle mark revised request.
	 *
	 * @since    1.0.0
	 */
	public function handle_mark_revised() {
		if ( empty( $_GET['post'] ) || empty( $_GET['nonce'] ) ) {
			wp_die( __( 'Petición inválida.', 'hooma-legal' ) );
		}

		$post_id = intval( $_GET['post'] );
		$nonce   = sanitize_text_field( $_GET['nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'hooma_legal_revised_' . $post_id ) || ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Permiso denegado.', 'hooma-legal' ) );
		}

		// Update last revision date metadata
		update_post_meta( $post_id, '_hooma_legal_revision_date', date( 'Y-m-d' ) );

		wp_redirect( admin_url( 'edit.php?post_type=hooma_legal_doc&hooma_notice=revised' ) );
		exit;
	}

	/**
	 * Show admin notices.
	 *
	 * @since    1.0.0
	 */
	public function show_admin_notices() {
		if ( empty( $_GET['hooma_notice'] ) ) {
			return;
		}

		$notice = sanitize_text_field( $_GET['hooma_notice'] );

		if ( 'duplicated' === $notice ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'El documento legal ha sido duplicado con éxito como borrador.', 'hooma-legal' ) );
		} elseif ( 'revised' === $notice ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__( 'La fecha de revisión del documento ha sido actualizada al día de hoy.', 'hooma-legal' ) );
		}
	}

	/**
	 * Define custom columns for the CPT table.
	 *
	 * @since    1.0.0
	 */
	public function set_custom_cpt_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			$new_columns[$key] = $title;
			if ( 'title' === $key ) {
				$new_columns['hooma_version']       = __( 'Versión', 'hooma-legal' );
				$new_columns['hooma_revision_date'] = __( 'Fecha de Revisión', 'hooma-legal' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render the custom columns content.
	 *
	 * @since    1.0.0
	 */
	public function render_custom_cpt_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'hooma_version':
				$version = get_post_meta( $post_id, '_hooma_legal_version', true );
				if ( empty( $version ) ) {
					$version = date( 'Y.m.d' );
					update_post_meta( $post_id, '_hooma_legal_version', $version );
				}
				echo '<span id="hooma_version_' . esc_attr( $post_id ) . '">' . esc_html( $version ) . '</span>';
				break;

			case 'hooma_revision_date':
				$date = get_post_meta( $post_id, '_hooma_legal_revision_date', true );
				echo esc_html( $date ? $date : '—' );
				break;
		}
	}

	/**
	 * Output the custom field inside the WordPress Quick Edit box.
	 *
	 * @since    1.0.0
	 */
	public function display_quick_edit_fields( $column_name, $post_type ) {
		if ( 'hooma_version' !== $column_name || 'hooma_legal_doc' !== $post_type ) {
			return;
		}

		wp_nonce_field( 'hooma_legal_quick_edit_action', 'hooma_legal_quick_edit_nonce' );
		?>
		<fieldset class="inline-edit-col-right inline-edit-hooma-legal">
			<div class="inline-edit-col">
				<div class="inline-edit-group wp-clearfix">
					<label class="alignleft">
						<span class="title"><?php esc_html_e( 'Versión', 'hooma-legal' ); ?></span>
						<span class="input-text-wrap">
							<input type="text" name="hooma_version" class="hooma-version-input" value="">
						</span>
					</label>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Save Quick Edit field.
	 *
	 * @since    1.0.0
	 */
	public function save_quick_edit_fields( $post_id ) {
		if ( 'hooma_legal_doc' !== get_post_type( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['hooma_legal_quick_edit_nonce'] ) || ! wp_verify_nonce( $_POST['hooma_legal_quick_edit_nonce'], 'hooma_legal_quick_edit_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['hooma_version'] ) ) {
			update_post_meta( $post_id, '_hooma_legal_version', sanitize_text_field( $_POST['hooma_version'] ) );
		}
	}

}
