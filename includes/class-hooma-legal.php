<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/includes
 * @author     Hooma Legal
 */
class Hooma_Legal {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Hooma_Legal_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'HOOMA_LEGAL_VERSION' ) ) {
			$this->version = HOOMA_LEGAL_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'hooma-legal';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_cpt_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Hooma_Legal_Loader. Orchestrates the hooks of the plugin.
	 * - Hooma_Legal_i18n. Defines internationalization functionality.
	 * - Hooma_Legal_Admin. Defines all hooks for the admin area.
	 * - Hooma_Legal_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-i18n.php';

		/**
		 * The class responsible for defining all actions and filters that occur in the
		 * admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hooma-legal-admin.php';

		/**
		 * The class responsible for defining all actions and filters that occur in the
		 * public-facing side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-hooma-legal-public.php';

		/**
		 * The class responsible for registering CPTs.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-cpt.php';

		/**
		 * The class responsible for parsing variables.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-parser.php';

		/**
		 * The class responsible for admin settings.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hooma-legal-admin-settings.php';

		/**
		 * The class responsible for Gutenberg blocks.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-blocks.php';

		/**
		 * The class responsible for document versioning.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-versioning.php';



		/**
		 * The class responsible for integrations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-integrations.php';

		/**
		 * The class responsible for admin UX actions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hooma-legal-admin-ux.php';

		/**
		 * The class responsible for User Consent API.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-consent-api.php';

		/**
		 * The class responsible for Contact Form 7 Inline document viewing.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hooma-legal-cf7-inline.php';

		$this->loader = new Hooma_Legal_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Hooma_Legal_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Hooma_Legal_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register the CPT hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_cpt_hooks() {

		$plugin_cpt = new Hooma_Legal_CPT();
		$this->loader->add_action( 'init', $plugin_cpt, 'register_cpts' );

		$plugin_blocks = new Hooma_Legal_Blocks();
		$this->loader->add_action( 'init', $plugin_blocks, 'enqueue_editor_assets' );
		$this->loader->add_action( 'init', $plugin_blocks, 'register_blocks' );
		$this->loader->add_action( 'enqueue_block_editor_assets', $plugin_blocks, 'enqueue_sidebar_assets' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Hooma_Legal_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_ajax_hooma_legal_get_snapshot', $plugin_admin, 'ajax_get_snapshot' );
		$this->loader->add_action( 'wp_ajax_hooma_legal_get_consents', $plugin_admin, 'ajax_get_consents' );
		$this->loader->add_action( 'wp_ajax_hooma_legal_delete_version', $plugin_admin, 'ajax_delete_version' );
		$this->loader->add_action( 'admin_post_hooma_legal_export_csv', $plugin_admin, 'handle_export_csv' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'check_faz_cookies_changes' );
		$this->loader->add_action( 'deactivated_plugin', $plugin_admin, 'detect_faz_deactivation', 10, 2 );
		$this->loader->add_action( 'activated_plugin', $plugin_admin, 'detect_faz_activation', 10, 2 );

		$plugin_settings = new Hooma_Legal_Admin_Settings();
		$this->loader->add_action( 'admin_menu', $plugin_settings, 'add_settings_menu' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );

		$plugin_versioning = new Hooma_Legal_Versioning();
		$this->loader->add_action( 'save_post', $plugin_versioning, 'save_document_snapshot', 10, 3 );

		$plugin_ux = new Hooma_Legal_Admin_UX();
		$this->loader->add_filter( 'post_row_actions', $plugin_ux, 'add_row_actions', 10, 2 );
		$this->loader->add_action( 'admin_action_hooma_legal_duplicate_post', $plugin_ux, 'handle_duplicate_post' );
		$this->loader->add_action( 'admin_action_hooma_legal_mark_revised', $plugin_ux, 'handle_mark_revised' );
		$this->loader->add_action( 'admin_notices', $plugin_ux, 'show_admin_notices' );
		$this->loader->add_filter( 'manage_hooma_legal_doc_posts_columns', $plugin_ux, 'set_custom_cpt_columns' );
		$this->loader->add_action( 'manage_hooma_legal_doc_posts_custom_column', $plugin_ux, 'render_custom_cpt_columns', 10, 2 );
		$this->loader->add_action( 'quick_edit_custom_box', $plugin_ux, 'display_quick_edit_fields', 10, 2 );
		$this->loader->add_action( 'save_post_hooma_legal_doc', $plugin_ux, 'save_quick_edit_fields' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Hooma_Legal_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$plugin_parser = new Hooma_Legal_Parser();
		$this->loader->add_filter( 'the_content', $plugin_parser, 'parse_variables', 9 );



		$plugin_consent_api = new Hooma_Legal_Consent_API();
		$this->loader->add_action( 'rest_api_init', $plugin_consent_api, 'register_rest_routes' );

		$plugin_integrations = new Hooma_Legal_Integrations();
		$this->loader->add_action( 'init', $plugin_integrations, 'register_integrations' );

		$plugin_cf7_inline = new Hooma_Legal_CF7_Inline();
		$this->loader->add_filter( 'wpcf7_form_elements', $plugin_cf7_inline, 'inject_inline_documents', 10, 1 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the loader class reference.
	 *
	 * @since     1.0.0
	 * @return    Hooma_Legal_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
