<?php
/**
 * Register Custom Post Types for Hooma Legal.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

/**
 * Register Custom Post Types for Hooma Legal.
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 * @author     Hooma Legal
 */
class Hooma_Legal_CPT {

	/**
	 * Register the CPTs and metadata.
	 *
	 * @since    1.0.0
	 */
	public function register_cpts() {
		$this->register_document_cpt();
		$this->register_block_cpt();
		$this->register_document_meta();
	}

	/**
	 * Register the Legal Documents CPT.
	 *
	 * @since    1.0.0
	 */
	private function register_document_cpt() {
		$labels = array(
			'name'                  => _x( 'Documentos Legales', 'Post Type General Name', 'hooma-legal' ),
			'singular_name'         => _x( 'Documento Legal', 'Post Type Singular Name', 'hooma-legal' ),
			'menu_name'             => __( 'Documentos Legales', 'hooma-legal' ),
			'name_admin_bar'        => __( 'Documento Legal', 'hooma-legal' ),
			'archives'              => __( 'Archivo de Documentos', 'hooma-legal' ),
			'attributes'            => __( 'Atributos del Documento', 'hooma-legal' ),
			'parent_item_colon'     => __( 'Documento Padre:', 'hooma-legal' ),
			'all_items'             => __( 'Todos los Documentos', 'hooma-legal' ),
			'add_new_item'          => __( 'Añadir Nuevo Documento Legal', 'hooma-legal' ),
			'add_new'               => __( 'Añadir Nuevo', 'hooma-legal' ),
			'new_item'              => __( 'Nuevo Documento', 'hooma-legal' ),
			'edit_item'             => __( 'Editar Documento Legal', 'hooma-legal' ),
			'update_item'           => __( 'Actualizar Documento Legal', 'hooma-legal' ),
			'view_item'             => __( 'Ver Documento', 'hooma-legal' ),
			'view_items'            => __( 'Ver Documentos', 'hooma-legal' ),
			'search_items'          => __( 'Buscar Documentos', 'hooma-legal' ),
			'not_found'             => __( 'No se encontraron documentos', 'hooma-legal' ),
			'not_found_in_trash'    => __( 'No se encontraron documentos en la papelera', 'hooma-legal' ),
			'featured_image'        => __( 'Imagen Destacada', 'hooma-legal' ),
			'set_featured_image'    => __( 'Establecer imagen destacada', 'hooma-legal' ),
			'remove_featured_image' => __( 'Borrar imagen destacada', 'hooma-legal' ),
			'use_featured_image'    => __( 'Usar como imagen destacada', 'hooma-legal' ),
			'insert_into_item'      => __( 'Insertar en documento', 'hooma-legal' ),
			'uploaded_to_this_item' => __( 'Subido a este documento', 'hooma-legal' ),
			'items_list'            => __( 'Lista de documentos', 'hooma-legal' ),
			'items_list_navigation' => __( 'Navegación de lista de documentos', 'hooma-legal' ),
			'filter_items_list'     => __( 'Filtrar lista de documentos', 'hooma-legal' ),
		);

		$args = array(
			'label'                 => __( 'Documento Legal', 'hooma-legal' ),
			'description'           => __( 'Documentos legales de Hooma Legal', 'hooma-legal' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'revisions', 'excerpt' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 25,
			'menu_icon'             => 'dashicons-category',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'publicly_queryable'    => true,
			'show_in_rest'          => true, // Enable Gutenberg editor
			'rewrite'               => array( 'slug' => 'legal', 'with_front' => false ),
		);

		register_post_type( 'hooma_legal_doc', $args );
	}

	/**
	 * Register the Reusable Legal Blocks CPT.
	 *
	 * @since    1.0.0
	 */
	private function register_block_cpt() {
		$labels = array(
			'name'                  => _x( 'Bloques Reutilizables', 'Post Type General Name', 'hooma-legal' ),
			'singular_name'         => _x( 'Bloque Reutilizable', 'Post Type Singular Name', 'hooma-legal' ),
			'menu_name'             => __( 'Bloques Reutilizables', 'hooma-legal' ),
			'all_items'             => __( 'Todos los Bloques', 'hooma-legal' ),
			'add_new_item'          => __( 'Añadir Nuevo Bloque Legal', 'hooma-legal' ),
			'add_new'               => __( 'Añadir Nuevo', 'hooma-legal' ),
			'new_item'              => __( 'Nuevo Bloque', 'hooma-legal' ),
			'edit_item'             => __( 'Editar Bloque Legal', 'hooma-legal' ),
			'update_item'           => __( 'Actualizar Bloque Legal', 'hooma-legal' ),
			'view_item'             => __( 'Ver Bloque', 'hooma-legal' ),
			'search_items'          => __( 'Buscar Bloques', 'hooma-legal' ),
			'not_found'             => __( 'No se encontraron bloques', 'hooma-legal' ),
			'not_found_in_trash'    => __( 'No se encontraron bloques en la papelera', 'hooma-legal' ),
		);

		$args = array(
			'label'                 => __( 'Bloque Reutilizable', 'hooma-legal' ),
			'description'           => __( 'Cláusulas y bloques de texto legales reutilizables', 'hooma-legal' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor' ),
			'hierarchical'          => false,
			'public'                => false, // Internal CPT
			'show_ui'               => true,
			'show_in_menu'          => 'edit.php?post_type=hooma_legal_doc', // Place under Hooma Legal CPT menu
			'show_in_rest'          => true, // Enable Gutenberg editor
		);

		register_post_type( 'hooma_legal_block', $args );
	}

	/**
	 * Register Custom Fields for Gutenberg Sidebar / REST API.
	 *
	 * @since    1.0.0
	 */
	private function register_document_meta() {
		register_post_meta( 'hooma_legal_doc', '_hooma_legal_version', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		) );

		register_post_meta( 'hooma_legal_doc', '_hooma_legal_revision_date', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		) );

		register_post_meta( 'hooma_legal_doc', '_hooma_legal_status', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		) );

		register_post_meta( 'hooma_legal_doc', '_hooma_legal_document_type', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		) );

		register_post_meta( 'hooma_legal_doc', '_hooma_legal_changelog', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		) );
	}

}
