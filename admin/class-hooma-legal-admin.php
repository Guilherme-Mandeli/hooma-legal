<?php
/**
 * The admin-facing functionality of the plugin.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/admin
 */

/**
 * The admin-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-facing stylesheet and JavaScript.
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/admin
 * @author     Hooma Legal
 */
class Hooma_Legal_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version          The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hooma-legal-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hooma-legal-admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'hooma_legal_admin_params', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'hooma_legal_admin_ajax_nonce' ),
		) );

	}

	/**
	 * AJAX handler to fetch a historical snapshot content.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_snapshot() {
		// Verify nonce
		check_ajax_referer( 'hooma_legal_admin_ajax_nonce', 'security' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permiso denegado.', 'hooma-legal' ) );
		}

		if ( empty( $_POST['snapshot_id'] ) ) {
			wp_send_json_error( __( 'ID de snapshot inválido.', 'hooma-legal' ) );
		}

		$snapshot_id = intval( $_POST['snapshot_id'] );

		global $wpdb;
		$table_versions = $wpdb->prefix . 'hooma_legal_versions';

		$snapshot = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_versions WHERE id = %d",
			$snapshot_id
		) );

		if ( ! $snapshot ) {
			wp_send_json_error( __( 'Snapshot no encontrado.', 'hooma-legal' ) );
		}

		$doc_title = get_the_title( $snapshot->document_id );
		if ( empty( $doc_title ) ) {
			$doc_title = __( 'Documento Eliminado', 'hooma-legal' );
		}

		// Apply wpautop and formatting to Gutenberg output content
		$formatted_content = wpautop( $snapshot->content );

		wp_send_json_success( array(
			'title'      => $doc_title,
			'version'    => $snapshot->version,
			'date'       => date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $snapshot->created_at ) ),
			'content'    => $formatted_content,
		) );
	}

	/**
	 * Detect if the FAZ Cookie Manager cookie registry has changed in the database.
	 *
	 * @since    1.0.0
	 */
	public function check_faz_cookies_changes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Throttle the check to run at most once every 10 minutes to avoid database overload on admin page loads
		if ( get_transient( 'hooma_faz_cookies_check_lock' ) ) {
			return;
		}
		set_transient( 'hooma_faz_cookies_check_lock', 1, 10 * MINUTE_IN_SECONDS );

		global $wpdb;
		$faz_table = $wpdb->prefix . 'faz_cookies';

		// Verify if FAZ table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $faz_table ) ) !== $faz_table ) {
			return;
		}

		// Calculate table checksum/hash of FAZ cookies names and descriptions
		$faz_results = $wpdb->get_results( "SELECT name, domain, duration, description FROM $faz_table ORDER BY name ASC" );
		$current_hash = md5( serialize( $faz_results ) );

		$old_hash = get_option( 'hooma_faz_cookies_checksum', '' );

		if ( ! empty( $faz_results ) && $current_hash !== $old_hash ) {
			// Update hash
			update_option( 'hooma_faz_cookies_checksum', $current_hash );

			// Only trigger if we had a previous hash and conditions are met
			if ( ! empty( $old_hash ) && $this->should_bump_on_cookie_changes() ) {
				if ( ! class_exists( 'Hooma_Legal_Versioning' ) ) {
					require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-versioning.php';
				}
				Hooma_Legal_Versioning::bump_documents_with_cookies_shortcode( __( 'Actualización automática desde FAZ Cookie Manager.', 'hooma-legal' ) );
			}
		}
	}

	/**
	 * Detect when FAZ Cookie Manager is deactivated and trigger a version bump.
	 *
	 * @since    1.0.0
	 */
	public function detect_faz_deactivation( $plugin, $network_deactivating ) {
		if ( false !== strpos( $plugin, 'faz-cookie-manager' ) ) {
			// Delete checksum
			delete_option( 'hooma_faz_cookies_checksum' );

			// Trigger version bump if conditions are met
			if ( $this->should_bump_on_cookie_changes() ) {
				if ( ! class_exists( 'Hooma_Legal_Versioning' ) ) {
					require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-versioning.php';
				}
				Hooma_Legal_Versioning::bump_documents_with_cookies_shortcode( __( 'Desactivación de FAZ Cookie Manager (Retorno a lista manual).', 'hooma-legal' ) );
			}
		}
	}

	/**
	 * Detect when FAZ Cookie Manager is activated and trigger a version bump.
	 *
	 * @since    1.0.0
	 */
	public function detect_faz_activation( $plugin, $network_activating ) {
		if ( false !== strpos( $plugin, 'faz-cookie-manager' ) ) {
			// Calculate initial checksum/hash
			global $wpdb;
			$faz_table = $wpdb->prefix . 'faz_cookies';
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $faz_table ) ) === $faz_table ) {
				$faz_results = $wpdb->get_results( "SELECT name, domain, duration, description FROM $faz_table ORDER BY name ASC" );
				$current_hash = md5( serialize( $faz_results ) );
				update_option( 'hooma_faz_cookies_checksum', $current_hash );
			}

			// Trigger version bump if conditions are met
			if ( $this->should_bump_on_cookie_changes() ) {
				if ( ! class_exists( 'Hooma_Legal_Versioning' ) ) {
					require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-versioning.php';
				}
				Hooma_Legal_Versioning::bump_documents_with_cookies_shortcode( __( 'Activación de FAZ Cookie Manager (Conexión automática de cookies).', 'hooma-legal' ) );
			}
		}
	}

	/**
	 * Check if the auto-bumping conditions for cookies are met.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	private function should_bump_on_cookie_changes() {
		// 1. Check if we have at least 1 document with the cookies shortcode
		$posts = get_posts( array(
			'post_type'      => 'hooma_legal_doc',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );

		if ( empty( $posts ) ) {
			return false;
		}

		$has_shortcode_doc = false;
		foreach ( $posts as $post ) {
			if ( has_shortcode( $post->post_content, 'faz_cookie_policy_complete' ) || has_shortcode( $post->post_content, 'faz_cookie_policy' ) ) {
				$has_shortcode_doc = true;
				break;
			}
		}

		if ( ! $has_shortcode_doc ) {
			return false;
		}

		return true;
	}

	/**
	 * AJAX handler to retrieve consent logs for a specific version.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_consents() {
		// Verify nonce
		check_ajax_referer( 'hooma_legal_admin_ajax_nonce', 'security' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permiso denegado.', 'hooma-legal' ) );
		}

		if ( empty( $_POST['version_id'] ) ) {
			wp_send_json_error( __( 'ID de versión inválido.', 'hooma-legal' ) );
		}

		$version_id = intval( $_POST['version_id'] );

		global $wpdb;
		$table_logs = $wpdb->prefix . 'hooma_legal_consent_logs';
		$table_versions = $wpdb->prefix . 'hooma_legal_versions';

		// Get version info
		$version_info = $wpdb->get_row( $wpdb->prepare(
			"SELECT title, version FROM $table_versions WHERE id = %d",
			$version_id
		) );

		if ( ! $version_info ) {
			wp_send_json_error( __( 'Versión no encontrada.', 'hooma-legal' ) );
		}

		// Build dynamic filters
		$where_conds = array( $wpdb->prepare( "version_id = %d", $version_id ) );

		// 1. Search Query (Identifiers)
		if ( ! empty( $_POST['search_query'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $_POST['search_query'] ) ) . '%';
			$where_conds[] = $wpdb->prepare(
				"(email LIKE %s OR user_name LIKE %s OR phone LIKE %s OR identifier_value LIKE %s)",
				$search, $search, $search, $search
			);
		}

		// 2. Filter Source
		if ( ! empty( $_POST['filter_source'] ) ) {
			$source_val = '%' . $wpdb->esc_like( sanitize_text_field( $_POST['filter_source'] ) ) . '%';
			$where_conds[] = $wpdb->prepare( "source LIKE %s", $source_val );
		}

		// 3. Filter IP
		if ( ! empty( $_POST['filter_ip'] ) ) {
			$ip_val = '%' . $wpdb->esc_like( sanitize_text_field( $_POST['filter_ip'] ) ) . '%';
			$where_conds[] = $wpdb->prepare( "ip_address LIKE %s", $ip_val );
		}

		// 4. Date Range
		if ( ! empty( $_POST['date_start'] ) ) {
			$date_start = sanitize_text_field( $_POST['date_start'] );
			$date_start_formatted = str_replace( 'T', ' ', $date_start );
			$where_conds[] = $wpdb->prepare( "timestamp >= %s", $date_start_formatted );
		}
		if ( ! empty( $_POST['date_end'] ) ) {
			$date_end = sanitize_text_field( $_POST['date_end'] );
			$date_end_formatted = str_replace( 'T', ' ', $date_end );
			$where_conds[] = $wpdb->prepare( "timestamp <= %s", $date_end_formatted );
		}

		$where_sql = implode( " AND ", $where_conds );

		// Count total matching logs for pagination
		$total_logs = $wpdb->get_var( "SELECT COUNT(*) FROM $table_logs WHERE $where_sql" );

		// Pagination parameters (20, 50, 100, 200 only)
		$per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20;
		if ( ! in_array( $per_page, array( 20, 50, 100, 200 ), true ) ) {
			$per_page = 20;
		}

		$paged = isset( $_POST['paged'] ) ? max( 1, intval( $_POST['paged'] ) ) : 1;
		$total_pages = ceil( $total_logs / $per_page );
		$offset = ( $paged - 1 ) * $per_page;

		// Fetch logs with limit and offset
		$logs = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_logs WHERE $where_sql ORDER BY timestamp DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		) );

		ob_start();
		?>
		<!-- FILTER BAR -->
		<div class="hooma-modal-filter-bar" style="background: #f6f7f7; border: 1px solid #dcdcde; padding: 15px; border-radius: 4px; margin-top: 10px; margin-bottom: 15px;">
			<form id="hooma-consents-filter-form" data-version-id="<?php echo esc_attr( $version_id ); ?>" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #50575e; margin-bottom: 4px;"><?php esc_html_e( 'Buscar Identificador', 'hooma-legal' ); ?></label>
					<input type="text" name="search_query" value="<?php echo esc_attr( isset($_POST['search_query']) ? $_POST['search_query'] : '' ); ?>" placeholder="Email, nombre, tel..." style="width: 100%; height: 32px; border-radius: 4px; border: 1px solid #8c8f94;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #50575e; margin-bottom: 4px;"><?php esc_html_e( 'Origen', 'hooma-legal' ); ?></label>
					<input type="text" name="filter_source" value="<?php echo esc_attr( isset($_POST['filter_source']) ? $_POST['filter_source'] : '' ); ?>" placeholder="contact_form_7..." style="width: 100%; height: 32px; border-radius: 4px; border: 1px solid #8c8f94;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #50575e; margin-bottom: 4px;"><?php esc_html_e( 'IP', 'hooma-legal' ); ?></label>
					<input type="text" name="filter_ip" value="<?php echo esc_attr( isset($_POST['filter_ip']) ? $_POST['filter_ip'] : '' ); ?>" placeholder="127.0.0.1..." style="width: 100%; height: 32px; border-radius: 4px; border: 1px solid #8c8f94;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #50575e; margin-bottom: 4px;"><?php esc_html_e( 'Desde (Fecha y Hora)', 'hooma-legal' ); ?></label>
					<input type="datetime-local" name="date_start" value="<?php echo esc_attr( isset($_POST['date_start']) ? $_POST['date_start'] : '' ); ?>" style="width: 100%; height: 32px; border-radius: 4px; border: 1px solid #8c8f94;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #50575e; margin-bottom: 4px;"><?php esc_html_e( 'Hasta (Fecha y Hora)', 'hooma-legal' ); ?></label>
					<input type="datetime-local" name="date_end" value="<?php echo esc_attr( isset($_POST['date_end']) ? $_POST['date_end'] : '' ); ?>" style="width: 100%; height: 32px; border-radius: 4px; border: 1px solid #8c8f94;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #50575e; margin-bottom: 4px;"><?php esc_html_e( 'Registros por página', 'hooma-legal' ); ?></label>
					<select name="per_page" style="width: 100%; height: 32px; border-radius: 4px; border: 1px solid #8c8f94; padding: 0 6px;">
						<?php foreach ( array( 20, 50, 100, 200 ) as $num ) : ?>
							<option value="<?php echo $num; ?>" <?php selected( $per_page, $num ); ?>><?php echo $num; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div style="grid-column: 1 / -1; display: flex; justify-content: space-between; margin-top: 5px;">
					<div>
						<button type="button" id="hooma-filter-submit-btn" class="button button-primary" style="height: 32px;"><?php esc_html_e( 'Filtrar', 'hooma-legal' ); ?></button>
						<button type="button" id="hooma-filter-clear-btn" class="button button-secondary" style="height: 32px; margin-left: 5px;"><?php esc_html_e( 'Limpiar', 'hooma-legal' ); ?></button>
					</div>
					<!-- Export CSV Button -->
					<button type="button" id="hooma-export-csv-btn" class="button button-secondary" style="height: 32px;" title="<?php esc_attr_e( 'Exportar a CSV los registros filtrados', 'hooma-legal' ); ?>">
						<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -12px; font-size: 16px; width: 16px; height: 16px;"></span>
						<?php esc_html_e( 'Exportar | CSV', 'hooma-legal' ); ?>
					</button>
				</div>
			</form>
		</div>

		<div class="hooma-legal-consent-table-wrapper" style="overflow-x: auto;">
			<table class="wp-list-table widefat fixed striped table-view-list" style="box-shadow: none; border: 1px solid #dcdcde; width: 100%;">
				<thead>
					<tr>
						<th scope="col" style="padding: 10px 15px; font-weight: 600; width: 30%;"><?php esc_html_e( 'Identificador', 'hooma-legal' ); ?></th>
						<th scope="col" style="padding: 10px 15px; font-weight: 600; width: 15%;"><?php esc_html_e( 'Origen', 'hooma-legal' ); ?></th>
						<th scope="col" style="padding: 10px 15px; font-weight: 600; width: 35%;"><?php esc_html_e( 'Metadatos Extra', 'hooma-legal' ); ?></th>
						<th scope="col" style="padding: 10px 15px; font-weight: 600; width: 20%;"><?php esc_html_e( 'Fecha', 'hooma-legal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr>
							<td colspan="4" style="padding: 20px; text-align: center; color: #646970;">
								<?php esc_html_e( 'No hay registros de consentimiento para esta versión.', 'hooma-legal' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : 
							$id_display = esc_html( $log->identifier_value );
							if ( ! empty( $log->identifier_type ) && 'email' !== $log->identifier_type ) {
								$id_display .= ' <span style="font-size:10px; color:#50575e; background:#f0f0f1; padding:2px 5px; border-radius:3px; font-weight:normal; vertical-align:middle; margin-left:5px;">' . esc_html( $log->identifier_type ) . '</span>';
							}
							?>
							<tr>
								<td style="padding: 10px 15px; vertical-align: top;">
									<strong><?php echo $id_display; ?></strong>
									<?php if ( ! empty( $log->user_name ) ) : ?>
										<div style="font-size: 11px; color: #646970; margin-top: 2px;"><strong>Nombre:</strong> <?php echo esc_html( $log->user_name ); ?></div>
									<?php endif; ?>
									<?php if ( ! empty( $log->phone ) ) : ?>
										<div style="font-size: 11px; color: #646970;"><strong>Tel:</strong> <?php echo esc_html( $log->phone ); ?></div>
									<?php endif; ?>
								</td>
								<td style="padding: 10px 15px; vertical-align: top;">
									<code style="font-size: 11px; background:#f6f7f7; padding: 0; border-radius:3px; color:#1d2327; font-family:monospace;"><?php echo esc_html( $log->source ); ?></code>
								</td>
								<td style="padding: 10px 15px; vertical-align: top;">
									<div style="font-size: 11px; color: #646970; margin-bottom: 5px;"><strong>IP:</strong> <?php echo esc_html( $log->ip_address ); ?></div>
									<?php 
									$extra = json_decode( $log->extra_data, true );
									if ( ! empty( $extra ) && is_array( $extra ) ) : ?>
										<details class="hooma-legal-consent-extra-details" style="font-size: 12px; margin-top: 5px; border: 1px solid #dcdcde; background: #fff; border-radius: 4px; padding: 4px 8px; cursor: pointer;">
											<summary style="font-weight: 500; outline: none; color: #2271b1;"><?php esc_html_e( 'Detalles adicionales', 'hooma-legal' ); ?></summary>
											<ul style="margin: 5px 0 0 15px; list-style-type: square; padding: 0;">
												<?php foreach ( $extra as $k => $v ) : ?>
													<li style="margin-bottom: 2px; font-size: 11.5px;">
														<strong><?php echo esc_html( $k ); ?>:</strong> 
														<?php echo esc_html( is_array( $v ) ? implode( ', ', $v ) : $v ); ?>
													</li>
												<?php endforeach; ?>
											</ul>
										</details>
									<?php endif; ?>
								</td>
								<td style="padding: 10px 15px; vertical-align: top; color: #50575e;">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $log->timestamp ) ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- PAGINATION BAR -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="hooma-modal-pagination" style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #dcdcde; padding-top: 15px;">
				<span style="font-size: 13px; color: #50575e;">
					<?php printf( __( 'Mostrando página %d de %d (%d registros en total)', 'hooma-legal' ), $paged, $total_pages, $total_logs ); ?>
				</span>
				<div style="display: flex; gap: 5px;">
					<?php if ( $paged > 1 ) : ?>
						<a href="#" class="button hooma-modal-paged-btn" data-page="1" title="<?php esc_attr_e( 'Primera Página', 'hooma-legal' ); ?>">&laquo;</a>
						<a href="#" class="button hooma-modal-paged-btn" data-page="<?php echo $paged - 1; ?>">&lsaquo; <?php esc_html_e( 'Anterior', 'hooma-legal' ); ?></a>
					<?php endif; ?>
					
					<?php if ( $paged < $total_pages ) : ?>
						<a href="#" class="button hooma-modal-paged-btn" data-page="<?php echo $paged + 1; ?>"><?php esc_html_e( 'Siguiente', 'hooma-legal' ); ?> &rsaquo;</a>
						<a href="#" class="button hooma-modal-paged-btn" data-page="<?php echo $total_pages; ?>" title="<?php esc_attr_e( 'Última Página', 'hooma-legal' ); ?>">&raquo;</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php
		$html_output = ob_get_clean();

		wp_send_json_success( array(
			'title'   => sprintf( __( 'Consentimientos - %s (v%s)', 'hooma-legal' ), $version_info->title, $version_info->version ),
			'content' => $html_output,
		) );
	}

	/**
	 * Export consent logs to a CSV file.
	 *
	 * @since    1.0.0
	 */
	public function handle_export_csv() {
		// Verify capability
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Permiso denegado.', 'hooma-legal' ) );
		}

		// Verify nonce
		if ( empty( $_GET['security'] ) || ! wp_verify_nonce( $_GET['security'], 'hooma_legal_admin_ajax_nonce' ) ) {
			wp_die( __( 'Enlace de descarga inválido o expirado.', 'hooma-legal' ) );
		}

		if ( empty( $_GET['version_id'] ) ) {
			wp_die( __( 'ID de versión inválido.', 'hooma-legal' ) );
		}

		$version_id = intval( $_GET['version_id'] );

		global $wpdb;
		$table_logs = $wpdb->prefix . 'hooma_legal_consent_logs';
		$table_versions = $wpdb->prefix . 'hooma_legal_versions';

		// Get version and document info
		$version_info = $wpdb->get_row( $wpdb->prepare(
			"SELECT v.title, v.version, p.post_name 
			 FROM $table_versions v 
			 LEFT JOIN {$wpdb->posts} p ON v.document_id = p.ID 
			 WHERE v.id = %d",
			$version_id
		) );

		if ( ! $version_info ) {
			wp_die( __( 'Versión no encontrada.', 'hooma-legal' ) );
		}

		// Build dynamic filters from GET parameters
		$where_conds = array( $wpdb->prepare( "version_id = %d", $version_id ) );

		// 1. Search Query (Identifiers)
		if ( ! empty( $_GET['search_query'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['search_query'] ) ) . '%';
			$where_conds[] = $wpdb->prepare(
				"(email LIKE %s OR user_name LIKE %s OR phone LIKE %s OR identifier_value LIKE %s)",
				$search, $search, $search, $search
			);
		}

		// 2. Filter Source
		if ( ! empty( $_GET['filter_source'] ) ) {
			$source_val = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['filter_source'] ) ) . '%';
			$where_conds[] = $wpdb->prepare( "source LIKE %s", $source_val );
		}

		// 3. Filter IP
		if ( ! empty( $_GET['filter_ip'] ) ) {
			$ip_val = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['filter_ip'] ) ) . '%';
			$where_conds[] = $wpdb->prepare( "ip_address LIKE %s", $ip_val );
		}

		// 4. Date Range
		if ( ! empty( $_GET['date_start'] ) ) {
			$date_start = sanitize_text_field( $_GET['date_start'] );
			$date_start_formatted = str_replace( 'T', ' ', $date_start );
			$where_conds[] = $wpdb->prepare( "timestamp >= %s", $date_start_formatted );
		}
		if ( ! empty( $_GET['date_end'] ) ) {
			$date_end = sanitize_text_field( $_GET['date_end'] );
			$date_end_formatted = str_replace( 'T', ' ', $date_end );
			$where_conds[] = $wpdb->prepare( "timestamp <= %s", $date_end_formatted );
		}

		$where_sql = implode( " AND ", $where_conds );

		// Fetch all matching logs
		$logs = $wpdb->get_results( "SELECT * FROM $table_logs WHERE $where_sql ORDER BY timestamp DESC" );

		// Generate dynamic file name
		$doc_slug = ! empty( $version_info->post_name ) ? sanitize_title( $version_info->post_name ) : 'documento';
		$version_tag = sanitize_title( $version_info->version );
		$filename = sprintf(
			'consentimientos-%s-v%s-%s.csv',
			$doc_slug,
			$version_tag,
			current_datetime()->format( 'Y-m-d-H-i-s' )
		);

		// Output CSV headers to force download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write UTF-8 BOM to make Excel read it correctly
		fwrite( $output, "\xEF\xBB\xBF" );

		// CSV Column headers
		$headers = array(
			__( 'ID', 'hooma-legal' ),
			__( 'Identificador', 'hooma-legal' ),
			__( 'Tipo de Identificador', 'hooma-legal' ),
			__( 'Nombre', 'hooma-legal' ),
			__( 'Teléfono', 'hooma-legal' ),
			__( 'Origen', 'hooma-legal' ),
			__( 'IP', 'hooma-legal' ),
			__( 'Tipo de Consentimiento', 'hooma-legal' ),
			__( 'Fecha y Hora', 'hooma-legal' ),
			__( 'Formulario ID', 'hooma-legal' ),
			__( 'Formulario Título', 'hooma-legal' ),
			__( 'Nombre del Campo', 'hooma-legal' ),
			__( 'Detalles Adicionales', 'hooma-legal' )
		);
		fputcsv( $output, $headers );

		// Loop and output rows
		foreach ( $logs as $log ) {
			// Extract extra metadata
			$form_id = '';
			$form_title = '';
			$field_name = '';
			$extra_details_array = array();

			$extra = json_decode( $log->extra_data, true );
			if ( ! empty( $extra ) && is_array( $extra ) ) {
				$inner_extra = isset( $extra['extra_data'] ) && is_array( $extra['extra_data'] ) ? $extra['extra_data'] : array();
				
				// Resolve Form ID
				if ( isset( $extra['form_id'] ) ) {
					$form_id = $extra['form_id'];
				} elseif ( isset( $inner_extra['form_id'] ) ) {
					$form_id = $inner_extra['form_id'];
				}

				// Resolve Form Title
				if ( isset( $extra['form_title'] ) ) {
					$form_title = $extra['form_title'];
				} elseif ( isset( $inner_extra['form_title'] ) ) {
					$form_title = $inner_extra['form_title'];
				}

				// Resolve Field Name
				if ( isset( $extra['field_name'] ) ) {
					$field_name = $extra['field_name'];
				} elseif ( isset( $inner_extra['field_name'] ) ) {
					$field_name = $inner_extra['field_name'];
				}

				// Build additional details string from remaining elements
				$ignored_keys = array( 'form_id', 'form_title', 'field_name', 'extra_data' );
				foreach ( $extra as $k => $v ) {
					if ( ! in_array( $k, $ignored_keys, true ) ) {
						$val_str = is_array( $v ) ? implode( ', ', $v ) : $v;
						$extra_details_array[] = $k . ': ' . $val_str;
					}
				}
				// Also check inner extra data for custom fields
				foreach ( $inner_extra as $k => $v ) {
					if ( ! in_array( $k, $ignored_keys, true ) ) {
						$val_str = is_array( $v ) ? implode( ', ', $v ) : $v;
						$extra_details_array[] = $k . ': ' . $val_str;
					}
				}
			}

			$extra_details = implode( ' | ', $extra_details_array );

			$row_data = array(
				$log->id,
				$log->identifier_value,
				$log->identifier_type,
				$log->user_name,
				$log->phone,
				$log->source,
				$log->ip_address,
				$log->consent_type,
				$log->timestamp,
				$form_id,
				$form_title,
				$field_name,
				$extra_details
			);

			fputcsv( $output, $row_data );
		}

		fclose( $output );
		exit;
	}

	/**
	 * AJAX handler to delete a version snapshot and its associated consent logs.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_version() {
		// Verify nonce
		check_ajax_referer( 'hooma_legal_admin_ajax_nonce', 'security' );

		// Only administrators can delete versions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permiso denegado. Solo administradores pueden realizar esta acción.', 'hooma-legal' ) );
		}

		if ( empty( $_POST['version_id'] ) ) {
			wp_send_json_error( __( 'ID de versión inválido.', 'hooma-legal' ) );
		}

		$version_id = intval( $_POST['version_id'] );

		global $wpdb;
		$table_versions = $wpdb->prefix . 'hooma_legal_versions';
		$table_logs     = $wpdb->prefix . 'hooma_legal_consent_logs';

		// Perform deletion
		$deleted_version = $wpdb->delete( $table_versions, array( 'id' => $version_id ), array( '%d' ) );
		$deleted_logs    = $wpdb->delete( $table_logs, array( 'version_id' => $version_id ), array( '%d' ) );

		if ( false === $deleted_version ) {
			wp_send_json_error( __( 'Error al eliminar el registro de versión de la base de datos.', 'hooma-legal' ) );
		}

		wp_send_json_success( __( 'La versión y sus consentimientos asociados han sido eliminados correctamente.', 'hooma-legal' ) );
	}

}
