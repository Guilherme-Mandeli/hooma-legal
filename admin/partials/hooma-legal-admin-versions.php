<?php
/**
 * Provide a admin area view for the plugin versions history list
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_versions = $wpdb->prefix . 'hooma_legal_versions';

// Fetch all documents for filter dropdown
$documents = get_posts( array(
	'post_type'      => 'hooma_legal_doc',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft' ),
) );

// Retrieve current filter
$filter_doc = isset( $_GET['filter_document'] ) ? intval( $_GET['filter_document'] ) : 0;

// Pagination variables
$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page     = isset( $_GET['per_page'] ) ? intval( $_GET['per_page'] ) : 20;
if ( ! in_array( $per_page, array( 20, 50, 100, 200 ), true ) ) {
	$per_page = 20;
}

// Resolve active documents and their latest snapshot IDs
$active_args = array(
	'post_type'      => 'hooma_legal_doc',
	'posts_per_page' => -1,
	'post_status'    => array( 'publish', 'draft' ),
);
if ( $filter_doc > 0 ) {
	$active_args['include'] = array( $filter_doc );
}
$active_docs = get_posts( $active_args );

$active_snapshots_by_version = array();
$active_snapshots_latest = array();
$exclude_snapshot_ids = array();

if ( ! empty( $active_docs ) ) {
	// Pre-fetch all latest snapshot IDs to prevent N+1 queries in loop
	$active_doc_ids = wp_list_pluck( $active_docs, 'ID' );

	if ( ! empty( $active_doc_ids ) ) {
		$ids_placeholder = implode( ',', array_map( 'intval', $active_doc_ids ) );
		// Get all versions for the active documents to parse in memory
		$snapshot_rows = $wpdb->get_results( "
			SELECT id, document_id, version 
			FROM $table_versions 
			WHERE document_id IN ($ids_placeholder) 
			ORDER BY id DESC
		" );

		foreach ( $snapshot_rows as $row ) {
			$doc_id  = intval( $row->document_id );
			$snap_id = intval( $row->id );
			$ver     = $row->version;

			// Store the first one we find for a specific version (latest due to DESC ordering)
			if ( ! isset( $active_snapshots_by_version[ $doc_id ][ $ver ] ) ) {
				$active_snapshots_by_version[ $doc_id ][ $ver ] = $snap_id;
			}
			// Store the absolute latest snapshot for this document
			if ( ! isset( $active_snapshots_latest[ $doc_id ] ) ) {
				$active_snapshots_latest[ $doc_id ] = $snap_id;
			}
		}

		// Identify the active snapshot IDs to exclude them from the historic versions list
		foreach ( $active_docs as $doc ) {
			$version = get_post_meta( $doc->ID, '_hooma_legal_version', true );
			if ( empty( $version ) ) {
				$version = date( 'Y.m.d' );
			}
			$active_snapshot_id = isset( $active_snapshots_by_version[ $doc->ID ][ $version ] ) ? $active_snapshots_by_version[ $doc->ID ][ $version ] : 0;
			if ( ! $active_snapshot_id ) {
				$active_snapshot_id = isset( $active_snapshots_latest[ $doc->ID ] ) ? $active_snapshots_latest[ $doc->ID ] : 0;
			}
			if ( $active_snapshot_id ) {
				$exclude_snapshot_ids[] = $active_snapshot_id;
			}
		}
	}
}

// Query versions
$query = "SELECT * FROM $table_versions";
$where = array();
if ( $filter_doc > 0 ) {
	$where[] = $wpdb->prepare( "document_id = %d", $filter_doc );
}
if ( ! empty( $exclude_snapshot_ids ) ) {
	$exclude_placeholders = implode( ',', array_map( 'intval', $exclude_snapshot_ids ) );
	$where[] = "id NOT IN ($exclude_placeholders)";
}

if ( ! empty( $where ) ) {
	$query .= " WHERE " . implode( " AND ", $where );
}

// Get total items for pagination calculations
$count_query = "SELECT COUNT(*) FROM $table_versions";
if ( ! empty( $where ) ) {
	$count_query .= " WHERE " . implode( " AND ", $where );
}
$total_items = $wpdb->get_var( $count_query );
$total_pages = ceil( $total_items / $per_page );

// Query with limit and offset
$offset = ( $current_page - 1 ) * $per_page;
$query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
$versions = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

// Pre-fetch all consent counts for both active snapshots and historic versions
$version_ids_to_count = array();
if ( ! empty( $active_snapshots_latest ) ) {
	$version_ids_to_count = array_merge( $version_ids_to_count, array_values( $active_snapshots_latest ) );
}
if ( ! empty( $active_snapshots_by_version ) ) {
	foreach ( $active_snapshots_by_version as $doc_snaps ) {
		$version_ids_to_count = array_merge( $version_ids_to_count, array_values( $doc_snaps ) );
	}
}
if ( ! empty( $versions ) ) {
	$version_ids_to_count = array_merge( $version_ids_to_count, wp_list_pluck( $versions, 'id' ) );
}

$version_ids_to_count = array_unique( array_map( 'intval', $version_ids_to_count ) );

$consent_counts = array();
if ( ! empty( $version_ids_to_count ) ) {
	$table_logs = $wpdb->prefix . 'hooma_legal_consent_logs';
	$placeholders = implode( ',', $version_ids_to_count );
	$count_rows = $wpdb->get_results( "
		SELECT version_id, COUNT(*) as count 
		FROM $table_logs 
		WHERE version_id IN ($placeholders) 
		GROUP BY version_id
	" );
	foreach ( $count_rows as $row ) {
		$consent_counts[ intval( $row->version_id ) ] = intval( $row->count );
	}
}
?>

<div class="wrap hooma-legal-admin-container">
	<h1 class="hooma-legal-title"><?php esc_html_e( 'Historial de Versiones Históricas', 'hooma-legal' ); ?></h1>
	<p class="hooma-legal-subtitle"><?php esc_html_e( 'Consulta y audita las instantáneas de documentos legales guardadas en la base de datos tras las aceptaciones de los usuarios.', 'hooma-legal' ); ?></p>

	<!-- FILTRO -->
	<div class="tablenav top" style="margin-bottom: 20px; background: #fff; padding: 15px; border: 1px solid #dcdcde; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
		<form method="get" action="">
			<input type="hidden" name="post_type" value="hooma_legal_doc">
			<input type="hidden" name="page" value="hooma-legal-versions">
			
			<label for="filter_document" style="font-weight: 500; margin-right: 10px;"><?php esc_html_e( 'Filtrar por Documento:', 'hooma-legal' ); ?></label>
			<select name="filter_document" id="filter_document" style="min-height: 30px; font-size: 13.5px; border-radius: 4px; padding: 2px 8px;">
				<option value="0"><?php esc_html_e( '— Todos los documentos —', 'hooma-legal' ); ?></option>
				<?php foreach ( $documents as $doc ) : ?>
					<option value="<?php echo esc_attr( $doc->ID ); ?>" <?php selected( $filter_doc, $doc->ID ); ?>>
						<?php echo esc_html( $doc->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			
			<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Filtrar', 'hooma-legal' ); ?>" style="margin-left: 10px; vertical-align: middle;">
			
			<label for="per_page" style="font-weight: 500; margin-left: 20px; margin-right: 10px;"><?php esc_html_e( 'Mostrar:', 'hooma-legal' ); ?></label>
			<select name="per_page" id="per_page" style="min-height: 30px; font-size: 13.5px; border-radius: 4px; padding: 2px 8px;" onchange="this.form.submit();">
				<?php foreach ( array( 20, 50, 100, 200 ) as $num ) : ?>
					<option value="<?php echo $num; ?>" <?php selected( $per_page, $num ); ?>>
						<?php printf( _n( '%d registro', '%d registros', $num, 'hooma-legal' ), $num ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php if ( $filter_doc > 0 || $per_page != 20 ) : ?>
				<a href="?post_type=hooma_legal_doc&page=hooma-legal-versions" class="button button-link" style="margin-left: 10px; line-height: 28px; text-decoration: none;"><?php esc_html_e( 'Restablecer', 'hooma-legal' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<!-- TABLA DE VERSIONES -->
	<div class="hooma-legal-card" style="padding: 0; border: 1px solid #dcdcde; border-radius: 6px;">
		<table class="wp-list-table widefat fixed striped table-view-list" style="border: none; border-radius: 6px; box-shadow: none;">
			<thead>
				<tr>
					<th scope="col" style="padding: 15px 20px; font-weight: 600; width: 35%;"><?php esc_html_e( 'Documento', 'hooma-legal' ); ?></th>
					<th scope="col" style="padding: 15px 20px; font-weight: 600; width: 20%;"><?php esc_html_e( 'Versión', 'hooma-legal' ); ?></th>
					<th scope="col" style="padding: 15px 20px; font-weight: 600; width: 15%;"><?php esc_html_e( 'Consentimientos', 'hooma-legal' ); ?></th>
					<th scope="col" style="padding: 15px 20px; font-weight: 600; width: 15%;"><?php esc_html_e( 'Fecha de Registro', 'hooma-legal' ); ?></th>
					<th scope="col" style="padding: 15px 20px; font-weight: 600; width: 15%; text-align: right;"><?php esc_html_e( 'Acciones', 'hooma-legal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<!-- ACTIVE VERSIONS FIRST -->
				<?php 
				if ( ! empty( $active_docs ) ) : 
					?>
					<?php foreach ( $active_docs as $doc ) : 
						$version = get_post_meta( $doc->ID, '_hooma_legal_version', true );
						if ( empty( $version ) ) {
							$version = date( 'Y.m.d' );
						}
						
						$active_snapshot_id = isset( $active_snapshots_by_version[ $doc->ID ][ $version ] ) ? $active_snapshots_by_version[ $doc->ID ][ $version ] : 0;

						if ( ! $active_snapshot_id ) {
							$active_snapshot_id = isset( $active_snapshots_latest[ $doc->ID ] ) ? $active_snapshots_latest[ $doc->ID ] : 0;
						}
						
						$rev_date = get_post_meta( $doc->ID, '_hooma_legal_revision_date', true );
						$date_display = ! empty( $rev_date ) ? date_i18n( get_option( 'date_format' ), strtotime( $rev_date ) ) : date_i18n( get_option( 'date_format' ) );
						?>
						<tr style="background: #f0f6fc;">
							<td style="padding: 15px 20px; font-weight: 500;">
								<strong>
									<a href="<?php echo esc_url( get_edit_post_link( $doc->ID ) ); ?>" title="<?php esc_attr_e( 'Editar documento original', 'hooma-legal' ); ?>">
										<?php echo esc_html( $doc->post_title ); ?>
									</a>
								</strong>
								<span style="margin-left: 10px; background: #e7f5ec; color: #1f7842; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 11px; text-transform: uppercase; border: 1px solid #1f7842; display: inline-block; vertical-align: middle;">
									<?php esc_html_e( 'Versión Activa', 'hooma-legal' ); ?>
								</span>
							</td>
							<td style="padding: 15px 20px;">
								<code style="font-size: 13px; font-weight: 600; background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 4px; font-family: monospace;">
									<?php echo esc_html( $version ); ?>
								</code>
							</td>
							<td style="padding: 15px 20px;">
								<?php $active_consent_count = isset( $consent_counts[ $active_snapshot_id ] ) ? $consent_counts[ $active_snapshot_id ] : 0; ?>
								<span class="hooma-consent-count-badge" style="background: #2271b1; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block;">
									<?php echo number_format_i18n( $active_consent_count ); ?>
								</span>
							</td>
							<td style="padding: 15px 20px; color: #1d2327; font-weight: 500;">
								<?php echo esc_html( $date_display ); ?>
							</td>
							<td style="padding: 15px 20px; text-align: right; white-space: nowrap; position: relative;">
								<?php if ( $active_snapshot_id ) : ?>
									<div class="hooma-dropdown-wrapper">
										<button type="button" class="hooma-dropdown-trigger-btn" aria-haspopup="true" aria-expanded="false" title="<?php esc_attr_e( 'Acciones', 'hooma-legal' ); ?>">
											<span class="dashicons dashicons-ellipsis"></span>
										</button>
										<div class="hooma-dropdown-menu" style="display: none;">
											<ul>
												<li>
													<a href="#" class="hooma-view-snapshot-btn" data-id="<?php echo esc_attr( $active_snapshot_id ); ?>">
														<span class="dashicons dashicons-visibility" style="font-size: 16px; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;"></span>
														<?php esc_html_e( 'Ver Documento', 'hooma-legal' ); ?>
													</a>
												</li>
												<li>
													<a href="#" class="hooma-view-consents-btn" data-id="<?php echo esc_attr( $active_snapshot_id ); ?>">
														<span class="dashicons dashicons-groups" style="font-size: 16px; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;"></span>
														<?php esc_html_e( 'Ver Consentimientos', 'hooma-legal' ); ?>
													</a>
												</li>
												<?php if ( current_user_can( 'manage_options' ) ) : ?>
													<li>
														<a href="#" class="hooma-delete-version-btn" data-id="<?php echo esc_attr( $active_snapshot_id ); ?>" style="color: #b32d2e;">
															<span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle; color: #b32d2e;"></span>
															<?php esc_html_e( 'Borrar Versión', 'hooma-legal' ); ?>
														</a>
													</li>
												<?php endif; ?>
											</ul>
										</div>
									</div>
								<?php else : ?>
									<span style="font-size:12px; color:#646970; font-style:italic;"><?php esc_html_e( 'Sin cambios guardados', 'hooma-legal' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>

				<!-- HISTORIC SNAPSHOTS -->
				<?php if ( empty( $versions ) && empty( $active_docs ) ) : ?>
					<tr>
						<td colspan="5" style="padding: 30px 20px; text-align: center; color: #646970;">
							<?php esc_html_e( 'No se encontraron versiones registradas en el historial.', 'hooma-legal' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $versions as $ver ) : 
						$doc_title = get_the_title( $ver->document_id );
						if ( empty( $doc_title ) ) {
							$doc_title = __( '(Documento eliminado)', 'hooma-legal' );
						}
						?>
						<tr>
							<td style="padding: 15px 20px; font-weight: 500;">
								<strong>
									<?php if ( get_post( $ver->document_id ) ) : ?>
										<a href="<?php echo esc_url( get_edit_post_link( $ver->document_id ) ); ?>" title="<?php esc_attr_e( 'Editar documento original', 'hooma-legal' ); ?>">
											<?php echo esc_html( $doc_title ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $doc_title ); ?>
									<?php endif; ?>
								</strong>
							</td>
							<td style="padding: 15px 20px;">
								<code style="font-size: 13px; font-weight: 600; background: #f0f6fc; color: #2271b1; padding: 3px 8px; border-radius: 4px; font-family: monospace;">
									<?php echo esc_html( $ver->version ); ?>
								</code>
							</td>
							<td style="padding: 15px 20px;">
								<?php $consent_count = isset( $consent_counts[ $ver->id ] ) ? $consent_counts[ $ver->id ] : 0; ?>
								<span class="hooma-consent-count-badge" style="background: #2271b1; color: #fff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block;">
									<?php echo number_format_i18n( $consent_count ); ?>
								</span>
							</td>
							<td style="padding: 15px 20px; color: #646970;">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $ver->created_at ) ) ); ?>
							</td>
							<td style="padding: 15px 20px; text-align: right; white-space: nowrap; position: relative;">
								<div class="hooma-dropdown-wrapper">
									<button type="button" class="hooma-dropdown-trigger-btn" aria-haspopup="true" aria-expanded="false" title="<?php esc_attr_e( 'Acciones', 'hooma-legal' ); ?>">
										<span class="dashicons dashicons-ellipsis"></span>
									</button>
									<div class="hooma-dropdown-menu" style="display: none;">
										<ul>
											<li>
												<a href="#" class="hooma-view-snapshot-btn" data-id="<?php echo esc_attr( $ver->id ); ?>">
													<span class="dashicons dashicons-visibility" style="font-size: 16px; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;"></span>
													<?php esc_html_e( 'Ver Documento', 'hooma-legal' ); ?>
												</a>
											</li>
											<li>
												<a href="#" class="hooma-view-consents-btn" data-id="<?php echo esc_attr( $ver->id ); ?>">
													<span class="dashicons dashicons-groups" style="font-size: 16px; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;"></span>
													<?php esc_html_e( 'Ver Consentimientos', 'hooma-legal' ); ?>
												</a>
											</li>
											<?php if ( current_user_can( 'manage_options' ) ) : ?>
												<li>
													<a href="#" class="hooma-delete-version-btn" data-id="<?php echo esc_attr( $ver->id ); ?>" style="color: #b32d2e;">
														<span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin-right: 8px; vertical-align: middle; color: #b32d2e;"></span>
														<?php esc_html_e( 'Borrar Versión', 'hooma-legal' ); ?>
													</a>
												</li>
											<?php endif; ?>
										</ul>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- PAGINACIÓN -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom" style="margin: 20px 0; display: flex; justify-content: flex-end; align-items: center;">
			<div class="tablenav-pages" style="display: inline-block;">
				<?php
				echo paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => __( '&laquo; Anterior', 'hooma-legal' ),
					'next_text' => __( 'Siguiente &raquo;', 'hooma-legal' ),
					'total'     => $total_pages,
					'current'   => $current_page,
					'type'      => 'plain',
				) );
				?>
			</div>
		</div>
	<?php endif; ?>

	<!-- VISOR MODAL (AUDITORÍA HISTÓRICA) -->
	<div id="hooma-legal-snapshot-modal" class="hooma-modal-overlay" style="display: none;">
		<div class="hooma-modal-container">
			<div class="hooma-modal-header">
				<h2 id="hooma-modal-title"><?php esc_html_e( 'Visor de Documento Histórico', 'hooma-legal' ); ?></h2>
				<button type="button" class="hooma-modal-close-btn" id="hooma-modal-close-x">&times;</button>
			</div>
			<div class="hooma-modal-meta-bar" style="background: #f0f6fc; padding: 12px 20px; border-bottom: 1px solid #dcdcde; font-size: 13px; color: #1d2327;">
				<span style="margin-right: 20px;"><strong><?php esc_html_e( 'Versión:', 'hooma-legal' ); ?></strong> <span id="hooma-modal-meta-version"></span></span>
				<span><strong><?php esc_html_e( 'Fecha de Registro:', 'hooma-legal' ); ?></strong> <span id="hooma-modal-meta-date"></span></span>
			</div>
			<div class="hooma-modal-body" id="hooma-modal-content-body">
				<!-- Content loaded via AJAX -->
			</div>
			<div class="hooma-modal-footer">
				<button type="button" class="button button-secondary" id="hooma-modal-close-btn"><?php esc_html_e( 'Cerrar', 'hooma-legal' ); ?></button>
			</div>
		</div>
	</div>

	<!-- MODAL DE CONFIRMACIÓN DE BORRADO -->
	<div id="hooma-legal-delete-modal" class="hooma-modal-overlay" style="display: none;">
		<div class="hooma-modal-container" style="max-width: 450px;">
			<div class="hooma-modal-header" style="background: #d63638;">
				<h2><?php esc_html_e( 'Confirmar Eliminación', 'hooma-legal' ); ?></h2>
				<button type="button" class="hooma-modal-close-btn" id="hooma-delete-modal-close-x">&times;</button>
			</div>
			<div class="hooma-modal-body" style="padding: 20px; font-size: 14px; line-height: 1.5; background: #fafafa;">
				<p><strong><?php esc_html_e( '¿Estás seguro de que deseas eliminar permanentemente esta versión?', 'hooma-legal' ); ?></strong></p>
				<p style="color: #646970; font-size: 13px; margin-bottom: 0;">
					<?php esc_html_e( 'Esta acción no se puede deshacer. Se borrará el registro de la instantánea histórica y todos los consentimientos de usuarios asociados a ella.', 'hooma-legal' ); ?>
				</p>
			</div>
			<div class="hooma-modal-footer" style="background: #fafafa; border-top: 1px solid #dcdcde;">
				<button type="button" class="button button-secondary" id="hooma-delete-modal-cancel-btn" style="margin-right: 8px;"><?php esc_html_e( 'Cancelar', 'hooma-legal' ); ?></button>
				<button type="button" class="button button-primary" id="hooma-delete-modal-confirm-btn" style="background: #d63638; border-color: #d63638; color: #fff;"><?php esc_html_e( 'Confirmar (Enter)', 'hooma-legal' ); ?></button>
			</div>
		</div>
	</div>
</div>
