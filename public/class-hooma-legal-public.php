<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/public
 * @author     Hooma Legal
 */
class Hooma_Legal_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version          The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hooma-legal-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hooma-legal-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Register public-facing shortcodes.
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'hooma_legal_docs_nav', array( $this, 'hooma_legal_docs_nav_shortcode' ) );
		add_shortcode( 'hooma_legal', array( $this, 'hooma_legal_shortcode' ) );
	}

	/**
	 * Shortcode: [hooma_legal_docs_nav]
	 *
	 * Muestra una navegación con todos los documentos legales.
	 *
	 * @since    1.0.0
	 * @return   string HTML output.
	 */
	public function hooma_legal_docs_nav_shortcode() {

		// Verificar que el CPT existe.
		if ( ! post_type_exists( 'hooma_legal_doc' ) ) {
			return '';
		}

		$documents = get_posts(
			array(
				'post_type'      => 'hooma_legal_doc',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $documents ) ) {
			return '';
		}

		ob_start();
		?>

		<nav class="hooma-legal-doc-nav" aria-label="<?php esc_attr_e( 'Documentos legales', 'hooma-legal' ); ?>">
			<ul class="hooma-legal-doc-list">
				<?php foreach ( $documents as $document ) : ?>
					<li class="hooma-legal-doc-item">
						<a
							class="hooma-legal-doc-link"
							href="<?php echo esc_url( get_permalink( $document ) ); ?>"
						>
							<?php echo esc_html( get_the_title( $document ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<?php

		return ob_get_clean();
	}

	/**
	 * Shortcode: [hooma_legal]
	 *
	 * Muestra el valor de una variable de configuración global.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Output value.
	 */
	public function hooma_legal_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'get' => '',
			),
			$atts,
			'hooma_legal'
		);

		$key = sanitize_key( $atts['get'] );

		if ( empty( $key ) ) {
			return '';
		}

		$options = get_option( 'hooma_legal_settings', array() );
		$defaults = array(
			'company_name'     => '',
			'brand_name'       => '',
			'vat_type'         => 'NIF/CIF',
			'vat'              => '',
			'address'          => '',
			'postal_code'      => '',
			'city'             => '',
			'province'         => '',
			'country'          => '',
			'email'            => '',
			'phone'            => '',
			'website'          => get_home_url(),
			'data_controller'  => '',
			'dpo'              => '',
			'jurisdiction'     => '',
			'court'            => '',
		);

		$settings = wp_parse_args( $options, $defaults );

		if ( isset( $settings[ $key ] ) ) {
			if ( 'website' === $key ) {
				return esc_url( $settings[ $key ] );
			}
			return esc_html( $settings[ $key ] );
		}

		return '';
	}

}
