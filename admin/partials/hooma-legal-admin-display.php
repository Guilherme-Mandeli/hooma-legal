<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://hooma.legal
 * @since      1.0.0
 *
 * @package    Hooma_Legal
 * @subpackage Hooma_Legal/admin/partials
 */

// Retrieve currently stored options
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
	'active_services'  => array(),
	'api_whitelist'    => '',
);

$settings = wp_parse_args( $options, $defaults );
?>

<div class="wrap hooma-legal-admin-container">
	<h1 class="hooma-legal-title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p class="hooma-legal-subtitle"><?php esc_html_e( 'Configura los datos globales de la empresa. Estos datos sustituirán automáticamente las variables como {{company_name}} en tus documentos legales.', 'hooma-legal' ); ?></p>

	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper hooma-legal-tabs">
		<a href="#tab-company" class="nav-tab nav-tab-active" data-tab="company"><?php esc_html_e( 'Datos de Empresa', 'hooma-legal' ); ?></a>
		<a href="#tab-location" class="nav-tab" data-tab="location"><?php esc_html_e( 'Contacto y Ubicación', 'hooma-legal' ); ?></a>
		<a href="#tab-legal" class="nav-tab" data-tab="legal"><?php esc_html_e( 'Protección y Jurisdicción', 'hooma-legal' ); ?></a>
		<a href="#tab-services" class="nav-tab" data-tab="services"><?php esc_html_e( 'Servicios', 'hooma-legal' ); ?></a>
		<a href="#tab-integrations" class="nav-tab" data-tab="integrations"><?php esc_html_e( 'Integración API', 'hooma-legal' ); ?></a>
	</h2>

	<form method="post" action="options.php" class="hooma-legal-form">
		<?php
		settings_fields( 'hooma_legal_settings_group' );
		?>

		<!-- TAB 1: COMPANY -->
		<div id="tab-company" class="hooma-legal-tab-content hooma-legal-active">
			<div class="hooma-legal-card">
				<h3><?php esc_html_e( 'Información General', 'hooma-legal' ); ?></h3>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="company_name"><?php esc_html_e( 'Nombre de la Empresa / Razón Social', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="company_name" name="hooma_legal_settings[company_name]" value="<?php echo esc_attr( $settings['company_name'] ); ?>" class="regular-text" placeholder="Ej: Hooma Legal S.L.">
								<p class="description"><?php esc_html_e( 'Nombre legal de la entidad.', 'hooma-legal' ); ?> <code>{{company_name}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="brand_name"><?php esc_html_e( 'Nombre Comercial', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="brand_name" name="hooma_legal_settings[brand_name]" value="<?php echo esc_attr( $settings['brand_name'] ); ?>" class="regular-text" placeholder="Ej: Hooma">
								<p class="description"><?php esc_html_e( 'Nombre de marca o comercial.', 'hooma-legal' ); ?> <code>{{brand_name}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="vat_type"><?php esc_html_e( 'Tipo de Identificador Fiscal', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="vat_type" name="hooma_legal_settings[vat_type]" value="<?php echo esc_attr( $settings['vat_type'] ); ?>" class="regular-text" placeholder="Ej: NIF, CIF, VAT, RFC">
								<p class="description"><?php esc_html_e( 'Tipo de documento de identificación fiscal.', 'hooma-legal' ); ?> <code>{{vat_type}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="vat"><?php esc_html_e( 'Número de Identificador Fiscal', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="vat" name="hooma_legal_settings[vat]" value="<?php echo esc_attr( $settings['vat'] ); ?>" class="regular-text" placeholder="Ej: B12345678">
								<p class="description"><?php esc_html_e( 'Número de documento de identificación.', 'hooma-legal' ); ?> <code>{{vat}}</code></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- TAB 2: LOCATION & CONTACT -->
		<div id="tab-location" class="hooma-legal-tab-content">
			<div class="hooma-legal-card">
				<h3><?php esc_html_e( 'Contacto y Dirección Física', 'hooma-legal' ); ?></h3>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="address"><?php esc_html_e( 'Dirección Completa', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="address" name="hooma_legal_settings[address]" value="<?php echo esc_attr( $settings['address'] ); ?>" class="large-text" placeholder="Ej: Calle Gran Vía 12, Piso 3">
								<p class="description"><code>{{address}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="postal_code"><?php esc_html_e( 'Código Postal', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="postal_code" name="hooma_legal_settings[postal_code]" value="<?php echo esc_attr( $settings['postal_code'] ); ?>" placeholder="Ej: 28013">
								<p class="description"><code>{{postal_code}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="city"><?php esc_html_e( 'Ciudad / Municipio', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="city" name="hooma_legal_settings[city]" value="<?php echo esc_attr( $settings['city'] ); ?>" class="regular-text" placeholder="Ej: Madrid">
								<p class="description"><code>{{city}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="province"><?php esc_html_e( 'Provincia / Estado', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="province" name="hooma_legal_settings[province]" value="<?php echo esc_attr( $settings['province'] ); ?>" class="regular-text" placeholder="Ej: Madrid">
								<p class="description"><code>{{province}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="country"><?php esc_html_e( 'País', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="country" name="hooma_legal_settings[country]" value="<?php echo esc_attr( $settings['country'] ); ?>" class="regular-text" placeholder="Ej: España">
								<p class="description"><code>{{country}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="email"><?php esc_html_e( 'Email de Contacto', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="email" id="email" name="hooma_legal_settings[email]" value="<?php echo esc_attr( $settings['email'] ); ?>" class="regular-text" placeholder="Ej: contacto@empresa.com">
								<p class="description"><code>{{email}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="phone"><?php esc_html_e( 'Teléfono de Contacto', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="phone" name="hooma_legal_settings[phone]" value="<?php echo esc_attr( $settings['phone'] ); ?>" class="regular-text" placeholder="Ej: +34 900 000 000">
								<p class="description"><code>{{phone}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="website"><?php esc_html_e( 'Dominio Principal / Sitio Web', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="url" id="website" name="hooma_legal_settings[website]" value="<?php echo esc_url( $settings['website'] ); ?>" class="regular-text" placeholder="Ej: https://empresa.com">
								<p class="description"><code>{{website}}</code></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- TAB 3: LEGAL & JURISDICTION -->
		<div id="tab-legal" class="hooma-legal-tab-content">
			<div class="hooma-legal-card">
				<h3><?php esc_html_e( 'Protección de Datos y Ámbito Judicial', 'hooma-legal' ); ?></h3>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="data_controller"><?php esc_html_e( 'Responsable del Tratamiento', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="data_controller" name="hooma_legal_settings[data_controller]" value="<?php echo esc_attr( $settings['data_controller'] ); ?>" class="large-text" placeholder="Ej: Hooma Legal S.L.">
								<p class="description"><?php esc_html_e( 'La persona jurídica o física que decide los fines y medios del tratamiento de datos.', 'hooma-legal' ); ?> <code>{{data_controller}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="dpo"><?php esc_html_e( 'DPO (Delegado de Protección de Datos)', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="dpo" name="hooma_legal_settings[dpo]" value="<?php echo esc_attr( $settings['dpo'] ); ?>" class="large-text" placeholder="Ej: dpo@empresa.com o nombre del delegado">
								<p class="description"><?php esc_html_e( 'Identificación o contacto de la figura DPO, en caso de existir.', 'hooma-legal' ); ?> <code>{{dpo}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="jurisdiction"><?php esc_html_e( 'Legislación / Jurisdicción Aplicable', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="jurisdiction" name="hooma_legal_settings[jurisdiction]" value="<?php echo esc_attr( $settings['jurisdiction'] ); ?>" class="regular-text" placeholder="Ej: Española (RGPD)">
								<p class="description"><?php esc_html_e( 'Normativa aplicable a los términos legales.', 'hooma-legal' ); ?> <code>{{jurisdiction}}</code></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="court"><?php esc_html_e( 'Tribunal Competente', 'hooma-legal' ); ?></label></th>
							<td>
								<input type="text" id="court" name="hooma_legal_settings[court]" value="<?php echo esc_attr( $settings['court'] ); ?>" class="regular-text" placeholder="Ej: Tribunales de Madrid">
								<p class="description"><?php esc_html_e( 'Los juzgados o tribunales acordados para la resolución de conflictos.', 'hooma-legal' ); ?> <code>{{court}}</code></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- TAB 4: SERVICES & COOKIES -->
		<div id="tab-services" class="hooma-legal-tab-content">
			<!-- AUTODETECCIÓN DE PLUGINS (Servicios Externos Activos) -->
			<div class="hooma-legal-card">
				<h3><?php esc_html_e( 'Servicios Externos Activos', 'hooma-legal' ); ?></h3>
				<p class="description" style="margin-bottom: 20px;">
					<?php esc_html_e( 'Escaneamos automáticamente las tecnologías y plugins activos en tu instalación de WordPress para detallarte su implicación legal y las acciones recomendadas.', 'hooma-legal' ); ?>
				</p>

				<?php
				$active_plugins = (array) get_option( 'active_plugins', array() );
				if ( is_multisite() ) {
					$active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
				}

				// WooCommerce Detection
				if ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) : ?>
					<details class="hooma-legal-recommendation" open>
						<summary>✓ <?php esc_html_e( 'WooCommerce Detectado', 'hooma-legal' ); ?></summary>
						<div class="hooma-legal-recommendation-content">
							<p><?php esc_html_e( 'Se ha detectado WooCommerce activo en tu sitio. Al procesar transacciones comerciales, estás obligado a recopilar datos sensibles de tus clientes (nombres, direcciones físicas, correos y pasarelas de pago).', 'hooma-legal' ); ?></p>
							<p><strong><?php esc_html_e( 'Qué debes hacer:', 'hooma-legal' ); ?></strong></p>
							<ul style="list-style-type:disc; margin-left:20px;">
								<li><?php esc_html_e( 'Incluye cláusulas de comercio electrónico en tus Condiciones Generales de Venta (Términos y Condiciones).', 'hooma-legal' ); ?></li>
								<li><?php esc_html_e( 'Inserta los bloques legales de WooCommerce correspondientes y detalla el uso de cookies técnicas de carrito de compras.', 'hooma-legal' ); ?></li>
							</ul>
						</div>
					</details>
				<?php endif;

				// Contact Form 7 Detection
				if ( in_array( 'contact-form-7/wp-contact-form-7.php', $active_plugins ) ) : ?>
					<details class="hooma-legal-recommendation" open>
						<summary>✓ <?php esc_html_e( 'Contact Form 7 Detectado', 'hooma-legal' ); ?></summary>
						<div class="hooma-legal-recommendation-content">
							<p><?php esc_html_e( 'Se ha detectado Contact Form 7 activo en tu sitio. El plugin de Hooma Legal registrará automáticamente el consentimiento de los envíos exitosos vinculando los datos al documento legal correspondiente.', 'hooma-legal' ); ?></p>
							<p><strong><?php esc_html_e( 'Cómo asociar el formulario al documento legal:', 'hooma-legal' ); ?></strong></p>
							<p><?php esc_html_e( 'Añade una casilla de aceptación (checkbox) al diseño de tu formulario indicando el ID, el slug o la clave del documento legal tras un guion en el nombre del campo.', 'hooma-legal' ); ?></p>
							
							<p style="margin-bottom: 5px;"><strong><?php esc_html_e( 'Ejemplos de configuración:', 'hooma-legal' ); ?></strong></p>
							<ul style="list-style-type:disc; margin-left:20px; margin-bottom: 15px; font-size: 13px;">
								<li><code>[acceptance hooma_legal_accept-<strong>politica-de-privacidad</strong>]Acepto la &lt;a href="/legal/politica-de-privacidad/" target="_blank"&gt;Política de Privacidad&lt;/a&gt;[/acceptance]</code> — <?php esc_html_e( 'Asociación por el slug del documento.', 'hooma-legal' ); ?></li>
								<li><code>[acceptance hooma_legal_accept-<strong>privacy_policy</strong>]Acepto la &lt;a href="/legal/politica-de-privacidad/" target="_blank"&gt;Política de Privacidad&lt;/a&gt;[/acceptance]</code> — <?php esc_html_e( 'Asociación por el tipo de documento configurado en la barra lateral de Gutenberg.', 'hooma-legal' ); ?></li>
								<li><code>[acceptance hooma_legal_accept-<strong>124</strong>]Acepto la &lt;a href="/legal/politica-de-privacidad/" target="_blank"&gt;Política de Privacidad&lt;/a&gt;[/acceptance]</code> — <?php esc_html_e( 'Asociación directa por el ID de post del documento legal.', 'hooma-legal' ); ?></li>
							</ul>

							<p><strong><?php esc_html_e( 'Mapeo Inteligente de Datos del Usuario:', 'hooma-legal' ); ?></strong></p>
							<p><?php esc_html_e( 'El plugin mapea automáticamente los campos del formulario buscando el correo, nombre y teléfono del remitente. Funciona en inglés, español y portugués de Brasil (buscando campos como "correo", "email", "seu-email", "nombre", "seu-nome", "telefone", "celular", etc.). Si no encuentra un campo explícito de correo, analizará el formulario completo para registrar la primera entrada con formato de email.', 'hooma-legal' ); ?></p>
						</div>
					</details>
				<?php endif;

				// FAZ Cookie Manager Detection
				$has_faz = false;
				foreach ( $active_plugins as $plugin_path ) {
					if ( false !== strpos( $plugin_path, 'faz-cookie-manager' ) ) {
						$has_faz = true;
						break;
					}
				}

				if ( $has_faz ) : ?>
					<details class="hooma-legal-recommendation" open>
						<summary>✓ <?php esc_html_e( 'FAZ Cookie Manager Detectado', 'hooma-legal' ); ?></summary>
						<div class="hooma-legal-recommendation-content">
							<p><?php esc_html_e( '¡Conexión establecida con éxito! Hemos detectado el plugin FAZ Cookie Manager activo en tu instalación.', 'hooma-legal' ); ?></p>
							<p><strong><?php esc_html_e( 'Funcionamiento:', 'hooma-legal' ); ?></strong></p>
							<p><?php esc_html_e( 'Las políticas de cookies y su tabla correspondiente se sincronizan automáticamente con FAZ Cookie Manager utilizando sus shortcodes oficiales.', 'hooma-legal' ); ?></p>
						</div>
					</details>
				<?php else : ?>
					<details class="hooma-legal-recommendation" open>
						<summary>⚠ <?php esc_html_e( 'FAZ Cookie Manager no detectado', 'hooma-legal' ); ?></summary>
						<div class="hooma-legal-recommendation-content">
							<p><?php esc_html_e( 'No hemos detectado el plugin FAZ Cookie Manager activo. Asegúrate de activarlo para habilitar la sincronización de cookies y políticas automáticas.', 'hooma-legal' ); ?></p>
						</div>
					</details>
				<?php endif; ?>
			</div>

			<div class="hooma-legal-card" style="margin-top: 20px;">
				<h3><?php esc_html_e( 'Shortcodes Disponibles', 'hooma-legal' ); ?></h3>
				<p class="description" style="margin-bottom: 20px;">
					<?php esc_html_e( 'Puedes utilizar los siguientes shortcodes en tus páginas o entradas para mostrar información legal de forma dinámica.', 'hooma-legal' ); ?>
				</p>

				<details class="hooma-legal-recommendation" open>
					<summary><code>[hooma_legal_docs_nav]</code> — <?php esc_html_e( 'Navegación de Documentos Legales', 'hooma-legal' ); ?></summary>
					<div class="hooma-legal-recommendation-content">
						<p><?php esc_html_e( 'Muestra una navegación con todos los documentos legales publicados, ordenados según su orden de menú y título.', 'hooma-legal' ); ?></p>
						<p><strong><?php esc_html_e( 'Uso:', 'hooma-legal' ); ?></strong></p>
						<p><?php printf( esc_html__( 'Añade el shortcode %s en cualquier editor de texto o bloque de shortcode en tu sitio.', 'hooma-legal' ), '<code>[hooma_legal_docs_nav]</code>' ); ?></p>
					</div>
				</details>

				<details class="hooma-legal-recommendation" open style="margin-top: 15px;">
					<summary><code>[hooma_legal]</code> — <?php esc_html_e( 'Mostrar Variable de Ajustes Globales', 'hooma-legal' ); ?></summary>
					<div class="hooma-legal-recommendation-content">
						<p><?php esc_html_e( 'Muestra el valor de cualquiera de los campos de datos globales de la empresa.', 'hooma-legal' ); ?></p>
						<p><strong><?php esc_html_e( 'Uso:', 'hooma-legal' ); ?></strong></p>
						<p><?php printf( esc_html__( 'Usa el parámetro %1$s con la clave de la variable, por ejemplo: %2$s para mostrar la razón social, o %3$s para la dirección física.', 'hooma-legal' ), '<code>get</code>', '<code>[hooma_legal get="company_name"]</code>', '<code>[hooma_legal get="address"]</code>' ); ?></p>
						<p><strong><?php esc_html_e( 'Variables disponibles:', 'hooma-legal' ); ?></strong></p>
						<ul style="list-style-type:disc; margin-left:20px; font-size:13px; color:#50575e;">
							<li><code>company_name</code>, <code>brand_name</code>, <code>vat_type</code>, <code>vat</code> (<?php esc_html_e( 'Datos de Empresa', 'hooma-legal' ); ?>)</li>
							<li><code>address</code>, <code>postal_code</code>, <code>city</code>, <code>province</code>, <code>country</code>, <code>email</code>, <code>phone</code>, <code>website</code> (<?php esc_html_e( 'Contacto y Ubicación', 'hooma-legal' ); ?>)</li>
							<li><code>data_controller</code>, <code>dpo</code>, <code>jurisdiction</code>, <code>court</code> (<?php esc_html_e( 'Protección y Jurisdicción', 'hooma-legal' ); ?>)</li>
						</ul>
					</div>
				</details>
			</div>
		</div>

		<!-- TAB 5: INTEGRATIONS -->
		<div id="tab-integrations" class="hooma-legal-tab-content">
			<!-- SECURITY AND WHITELIST SETTING -->
			<div class="hooma-legal-card" style="padding: 25px; margin-bottom: 25px;">
				<h3><?php esc_html_e( 'Seguridad de la API y Restricciones de Origen', 'hooma-legal' ); ?></h3>
				<p class="description" style="margin-bottom: 20px;">
					<?php esc_html_e( 'Por seguridad, la API de consentimiento de Hooma Legal solo procesará peticiones desde el mismo dominio o sus subdominios por defecto. Si necesitas recibir peticiones de otros servidores o IPs externas, agrégalos a la lista blanca a continuación.', 'hooma-legal' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="api_whitelist"><?php esc_html_e( 'Lista Blanca de Dominios e IPs', 'hooma-legal' ); ?></label></th>
							<td>
								<textarea id="api_whitelist" name="hooma_legal_settings[api_whitelist]" rows="5" class="large-text" placeholder="ejemplo.com&#10;192.168.1.50&#10;*.otrodominio.com" style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea( $settings['api_whitelist'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Ingresa un dominio o dirección IP por línea (ej: ejemplo.com o 192.168.1.1). Puedes usar asterisco (*) como comodín para subdominios (ej: *.midominio.com). Por defecto, el dominio de este sitio está permitido automáticamente.', 'hooma-legal' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="hooma-legal-card" style="padding: 25px;">
				<h3><?php esc_html_e( 'Documentación de Integración para Desarrolladores', 'hooma-legal' ); ?></h3>
				<p class="description" style="margin-bottom: 30px;">
					<?php esc_html_e( 'Hooma Legal ofrece una API unificada para que cualquier plugin, formulario o herramienta externa pueda registrar de manera automatizada el consentimiento de los usuarios a tus documentos legales.', 'hooma-legal' ); ?>
				</p>

				<!-- 1. EXPLANATION OF PARAMETERS -->
				<div style="background: #fafafa; border: 1px solid #dcdcde; padding: 20px; border-radius: 6px; margin-bottom: 30px;">
					<h4 style="border-bottom: 2px solid #dcdcde; padding-bottom: 8px; font-weight: 600; font-size: 14.5px; color: #1d2327; margin-top: 0; margin-bottom: 15px;">
						<?php esc_html_e( 'Parámetros Disponibles', 'hooma-legal' ); ?>
					</h4>
					<ul style="list-style-type: disc; padding-left: 20px; margin-bottom: 0; font-size: 13.5px; line-height: 1.6; color: #3c434a;">
						<li style="margin-bottom: 10px;">
							<strong><code>document</code> / <code>document_type</code>:</strong> 
							Identifican el documento legal a aceptar. Funcionan de forma independiente: 
							<code>document</code> acepta el ID numérico o el slug (ej. <code>'politica-privacidad'</code>). 
							<code>document_type</code> busca el documento por la clave asignada en Gutenberg (ej. <code>'privacy_policy'</code>).
						</li>
						<li style="margin-bottom: 10px;">
							<strong><code>identifier_type</code> & <code>identifier_value</code>:</strong> 
							Identificador libre del usuario. Si no se especifica <code>identifier_type</code>, se asume <code>'email'</code> por defecto. Puedes utilizar otros tipos como <code>'dni'</code>, <code>'user_id'</code>, <code>'session_id'</code>, etc.
						</li>
						<li style="margin-bottom: 10px;">
							<strong><code>source</code>:</strong> 
							Obligatorio. Indica qué formulario, plugin o webhook remite el consentimiento para facilitar las auditorías (ej: <code>'contact_form_7'</code>, <code>'woocommerce'</code>).
						</li>
						<li style="margin-bottom: 0;">
							<strong><code>extra_data</code>:</strong> 
							Cualquier campo adicional enviado (como Apellidos, DNI, o campos personalizados) se guardará automáticamente formateado en JSON en la base de datos.
						</li>
					</ul>
				</div>

				<!-- 2. PHP GLOBAL FUNCTION -->
				<div style="margin-bottom: 30px;">
					<h4 style="border-bottom: 2px solid #f0f0f1; padding-bottom: 8px; font-weight: 600; font-size: 14.5px; color: #1d2327; margin-top: 0; margin-bottom: 10px;">
						<?php esc_html_e( '1. Función Global de PHP', 'hooma-legal' ); ?>
					</h4>
					<p style="font-size: 13px; margin-top: 0; color: #50575e; line-height: 1.4;">
						<?php esc_html_e( 'Llama a esta función desde cualquier parte del código de tu WordPress (ej. al procesar compras de WooCommerce o registros personalizados).', 'hooma-legal' ); ?>
					</p>
					<pre style="background: #faf9f5; border: 1px solid #e2e1dc; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; color: #23282d; line-height: 1.5;">hooma_legal_log_consent( array(
    'document_type'    => 'privacy_policy',         // Identifica el documento por tipo
    'identifier_type'  => 'email',                  // Tipo de identificador
    'identifier_value' => 'correo@ejemplo.com',      // Valor único del usuario
    'source'           => 'nombre_mi_plugin',       // Origen del consentimiento (Obligatorio)
    'user_name'        => 'Juan Pérez',             // Opcional
    'phone'            => '+34600112233',           // Opcional
    'extra_data'       => array(                    // Datos adicionales libre en JSON
        'apellidos'    => 'Pérez Gómez',
        'ip_consent'   => 'Aceptada en checkbox'
    )
) );</pre>
				</div>

				<!-- 3. REST API ENDPOINT -->
				<div style="margin-bottom: 30px;">
					<h4 style="border-bottom: 2px solid #f0f0f1; padding-bottom: 8px; font-weight: 600; font-size: 14.5px; color: #1d2327; margin-top: 0; margin-bottom: 10px;">
						<?php esc_html_e( '2. Endpoint REST API (POST)', 'hooma-legal' ); ?>
					</h4>
					<p style="font-size: 13px; margin-top: 0; color: #50575e; line-height: 1.4;">
						<?php esc_html_e( 'Realiza llamadas asíncronas HTTP desde frontends externos, aplicaciones desacopladas o banners de consentimiento de cookies.', 'hooma-legal' ); ?>
					</p>
					<div style="background: #fafafb; border: 1px solid #dcdcde; padding: 12px; border-radius: 4px; font-family: monospace; font-size: 12.5px; margin-bottom: 12px; color: #2c3338; font-weight: 600;">
						POST <span style="color: #2271b1;"><?php echo esc_url( get_rest_url( null, 'hooma-legal/v1/consent' ) ); ?></span>
					</div>
					<pre style="background: #fcfcfc; border: 1px solid #dcdcde; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; color: #23282d; line-height: 1.5;">{
  "document": "privacy-policy",
  "identifier_type": "email",
  "identifier_value": "user@domain.com",
  "source": "cookie_banner",
  "nombre_completo": "Juan Pérez",
  "apellidos": "Pérez Gómez"
}</pre>
				</div>

				<!-- 4. CF7 EXAMPLE -->
				<div style="background: #f0f6fc; border: 1px solid #c8d9e6; padding: 20px; border-radius: 6px;">
					<h4 style="font-weight: 600; font-size: 14.5px; color: #0c1c2b; margin-top: 0; margin-bottom: 10px; display: flex; align-items: center;">
						<span class="dashicons dashicons-code-standards" style="margin-right: 8px;"></span>
						<?php esc_html_e( 'Ejemplo Práctico para Contact Form 7', 'hooma-legal' ); ?>
					</h4>
					<p style="font-size: 13px; color: #2c3338; line-height: 1.5; margin-top: 0; margin-bottom: 15px;">
						<?php esc_html_e( 'Añade este bloque de código al archivo functions.php de tu tema para automatizar el registro cuando los usuarios envíen un formulario aceptando la política:', 'hooma-legal' ); ?>
					</p>
					<pre style="background: #ffffff; border: 1px solid #dcdcde; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; color: #23282d; line-height: 1.5; margin-bottom: 0;">add_action( 'wpcf7_mail_sent', 'hooma_cf7_log_privacy_consent' );
function hooma_cf7_log_privacy_consent( $contact_form ) {
    $submission = WPCF7_Submission::get_instance();
    if ( $submission ) {
        $data = $submission->get_posted_data();
        
        // Registrar consentimiento del usuario automáticamente
        hooma_legal_log_consent( array(
            'document_type'    => 'privacy_policy',
            'identifier_type'  => 'email',
            'identifier_value' => $data['your-email'],
            'user_name'        => $data['your-name'],
            'source'           => 'contact_form_7_id_' . $contact_form->id(),
            'extra_data'       => array(
                'subject'      => $data['your-subject'],
                'accepted_box' => isset( $data['hooma_legal_accept'] ) ? 'Aceptado' : 'No Aceptado'
            )
        ) );
    }
}</pre>
				</div>

			</div>
		</div>

		<?php submit_button( __( 'Guardar Ajustes', 'hooma-legal' ), 'primary', 'submit_hooma_legal' ); ?>
	</form>
</div>
