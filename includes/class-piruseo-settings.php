<?php
/**
 * Admin settings page for PiruSEO.
 *
 * @package PiruSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PiruSEO_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . PIRUSEO_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add quick "Ajustes" link on the Plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url = admin_url( 'admin.php?page=piruseo' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ajustes', 'piruseo' ) . '</a>' );
		return $links;
	}

	/**
	 * Register top-level admin menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'PiruSEO', 'piruseo' ),
			__( 'PiruSEO', 'piruseo' ),
			'manage_options',
			'piruseo',
			array( $this, 'render_page' ),
			'dashicons-chart-line',
			80
		);
	}

	/**
	 * Enqueue styles and scripts on the PiruSEO settings page.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_piruseo' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'piruseo-admin', PIRUSEO_URL . 'assets/css/admin.css', array(), PIRUSEO_VERSION );
		wp_enqueue_script( 'piruseo-admin', PIRUSEO_URL . 'assets/js/admin-seo.js', array( 'jquery' ), PIRUSEO_VERSION, true );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'piruseo_options_group',
			'piruseo_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);
	}

	/**
	 * Sanitize submitted options.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$defaults = PiruSEO_Helpers::get_default_options();
		$existing = PiruSEO_Helpers::get_options();
		$output   = $existing;

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$text_fields = array(
			'separator',
			'home_title',
			'post_title_template',
			'page_title_template',
			'archive_title_template',
			'search_title_template',
			'404_title_template',
			'schema_name',
			'verify_google',
			'verify_bing',
			'verify_yandex',
			'twitter_site',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$output[ $field ] = sanitize_text_field( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( isset( $input['home_description'] ) ) {
			$output['home_description'] = sanitize_textarea_field( wp_unslash( $input['home_description'] ) );
		}

		if ( isset( $input['custom_robots_txt'] ) ) {
			$output['custom_robots_txt'] = sanitize_textarea_field( wp_unslash( $input['custom_robots_txt'] ) );
		}

		$url_fields = array( 'schema_logo', 'default_og_image' );
		foreach ( $url_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$output[ $field ] = esc_url_raw( wp_unslash( $input[ $field ] ) );
			}
		}

		if ( isset( $input['schema_type'] ) ) {
			$output['schema_type'] = in_array( $input['schema_type'], array( 'Organization', 'Person' ), true )
				? $input['schema_type']
				: 'Organization';
		}

		if ( isset( $input['twitter_card_type'] ) ) {
			$output['twitter_card_type'] = in_array( $input['twitter_card_type'], array( 'summary_large_image', 'summary' ), true )
				? $input['twitter_card_type']
				: 'summary_large_image';
		}

		$checkboxes = array(
			'noindex_author',
			'noindex_date',
			'noindex_search',
			'noindex_tag',
			'enable_og',
			'enable_sitemap',
		);

		foreach ( $checkboxes as $checkbox ) {
			$output[ $checkbox ] = ! empty( $input[ $checkbox ] ) ? 1 : 0;
		}

		// Flush rewrite rules if sitemap toggle changed.
		if ( (int) $existing['enable_sitemap'] !== (int) $output['enable_sitemap'] ) {
			PiruSEO_Sitemap::register_rewrite_rules();
			flush_rewrite_rules();
		}

		return wp_parse_args( $output, $defaults );
	}

	/**
	 * Render the tabbed settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options    = PiruSEO_Helpers::get_options();
		$separators = PiruSEO_Helpers::get_separators();
		$sitemap_url = home_url( '/piruseo-sitemap.xml' );
		?>
		<div class="wrap piruseo-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'PiruSEO', 'piruseo' ); ?></h1>
			<span class="piruseo-version-badge">v<?php echo esc_html( PIRUSEO_VERSION ); ?></span>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>

			<form method="post" action="options.php" class="piruseo-settings-form">
				<?php settings_fields( 'piruseo_options_group' ); ?>

				<nav class="nav-tab-wrapper piruseo-nav-tabs" role="tablist">
					<a href="#tab-general" class="nav-tab nav-tab-active" data-tab="general"><?php esc_html_e( 'General', 'piruseo' ); ?></a>
					<a href="#tab-titles" class="nav-tab" data-tab="titles"><?php esc_html_e( 'Apariencia en buscadores', 'piruseo' ); ?></a>
					<a href="#tab-social" class="nav-tab" data-tab="social"><?php esc_html_e( 'Social', 'piruseo' ); ?></a>
					<a href="#tab-sitemap" class="nav-tab" data-tab="sitemap"><?php esc_html_e( 'Sitemap y Robots', 'piruseo' ); ?></a>
					<a href="#tab-tools" class="nav-tab" data-tab="tools"><?php esc_html_e( 'Herramientas', 'piruseo' ); ?></a>
				</nav>

				<!-- TAB: GENERAL -->
				<div class="piruseo-tab-panel is-active" id="piruseo-tab-general">
					<h2><?php esc_html_e( 'Ajustes generales y Schema.org', 'piruseo' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Separador de título', 'piruseo' ); ?></th>
							<td>
								<div class="piruseo-separator-picker">
									<?php foreach ( $separators as $value => $label ) : ?>
										<label class="piruseo-sep-option">
											<input type="radio" name="piruseo_options[separator]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $options['separator'], $value ); ?> />
											<span><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="description"><?php esc_html_e( 'Se usa donde aparezca la variable %%sep%% en las plantillas de título.', 'piruseo' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-home-title"><?php esc_html_e( 'Título SEO de la portada', 'piruseo' ); ?></label></th>
							<td>
								<input type="text" id="piruseo-home-title" name="piruseo_options[home_title]" value="<?php echo esc_attr( $options['home_title'] ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Variables disponibles: %%sitename%%, %%sitedesc%%, %%sep%%, %%year%%', 'piruseo' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-home-desc"><?php esc_html_e( 'Meta descripción de la portada', 'piruseo' ); ?></label></th>
							<td>
								<textarea id="piruseo-home-desc" name="piruseo_options[home_description]" rows="3" class="large-text"><?php echo esc_textarea( $options['home_description'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-schema-type"><?php esc_html_e( 'Representación del sitio (Schema)', 'piruseo' ); ?></label></th>
							<td>
								<select id="piruseo-schema-type" name="piruseo_options[schema_type]">
									<option value="Organization" <?php selected( $options['schema_type'], 'Organization' ); ?>><?php esc_html_e( 'Organización / Empresa', 'piruseo' ); ?></option>
									<option value="Person" <?php selected( $options['schema_type'], 'Person' ); ?>><?php esc_html_e( 'Persona', 'piruseo' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-schema-name"><?php esc_html_e( 'Nombre de la organización o persona', 'piruseo' ); ?></label></th>
							<td>
								<input type="text" id="piruseo-schema-name" name="piruseo_options[schema_name]" value="<?php echo esc_attr( $options['schema_name'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-schema-logo"><?php esc_html_e( 'Logo / Avatar (URL)', 'piruseo' ); ?></label></th>
							<td>
								<div class="piruseo-media-field">
									<input type="url" id="piruseo-schema-logo" name="piruseo_options[schema_logo]" value="<?php echo esc_url( $options['schema_logo'] ); ?>" class="regular-text" />
									<button type="button" class="button piruseo-media-upload" data-target="#piruseo-schema-logo"><?php esc_html_e( 'Seleccionar imagen', 'piruseo' ); ?></button>
								</div>
							</td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Verificación de herramientas para webmasters', 'piruseo' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="piruseo-verify-google">Google Search Console</label></th>
							<td><input type="text" id="piruseo-verify-google" name="piruseo_options[verify_google]" value="<?php echo esc_attr( $options['verify_google'] ); ?>" class="regular-text" placeholder="Código de verificación (content)" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-verify-bing">Bing Webmaster Tools</label></th>
							<td><input type="text" id="piruseo-verify-bing" name="piruseo_options[verify_bing]" value="<?php echo esc_attr( $options['verify_bing'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-verify-yandex">Yandex Webmaster</label></th>
							<td><input type="text" id="piruseo-verify-yandex" name="piruseo_options[verify_yandex]" value="<?php echo esc_attr( $options['verify_yandex'] ); ?>" class="regular-text" /></td>
						</tr>
					</table>
				</div>

				<!-- TAB: TITLES & ARCHIVES -->
				<div class="piruseo-tab-panel" id="piruseo-tab-titles">
					<h2><?php esc_html_e( 'Plantillas de títulos SEO', 'piruseo' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Variables: %%title%%, %%sitename%%, %%sitedesc%%, %%sep%%, %%category%%, %%author%%, %%term_title%%, %%searchphrase%%, %%page%%, %%year%%', 'piruseo' ); ?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="piruseo-post-tpl"><?php esc_html_e( 'Entradas (Posts)', 'piruseo' ); ?></label></th>
							<td><input type="text" id="piruseo-post-tpl" name="piruseo_options[post_title_template]" value="<?php echo esc_attr( $options['post_title_template'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-page-tpl"><?php esc_html_e( 'Páginas (Pages)', 'piruseo' ); ?></label></th>
							<td><input type="text" id="piruseo-page-tpl" name="piruseo_options[page_title_template]" value="<?php echo esc_attr( $options['page_title_template'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-archive-tpl"><?php esc_html_e( 'Categorías, etiquetas y archivos', 'piruseo' ); ?></label></th>
							<td><input type="text" id="piruseo-archive-tpl" name="piruseo_options[archive_title_template]" value="<?php echo esc_attr( $options['archive_title_template'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-search-tpl"><?php esc_html_e( 'Página de búsqueda', 'piruseo' ); ?></label></th>
							<td><input type="text" id="piruseo-search-tpl" name="piruseo_options[search_title_template]" value="<?php echo esc_attr( $options['search_title_template'] ); ?>" class="regular-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-404-tpl"><?php esc_html_e( 'Página 404', 'piruseo' ); ?></label></th>
							<td><input type="text" id="piruseo-404-tpl" name="piruseo_options[404_title_template]" value="<?php echo esc_attr( $options['404_title_template'] ); ?>" class="regular-text" /></td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Indexación de archivos (noindex)', 'piruseo' ); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Ocultar de buscadores', 'piruseo' ); ?></th>
							<td>
								<fieldset>
									<label><input type="checkbox" name="piruseo_options[noindex_author]" value="1" <?php checked( $options['noindex_author'], 1 ); ?> /> <?php esc_html_e( 'Archivos de autor (noindex)', 'piruseo' ); ?></label><br />
									<label><input type="checkbox" name="piruseo_options[noindex_date]" value="1" <?php checked( $options['noindex_date'], 1 ); ?> /> <?php esc_html_e( 'Archivos por fecha (noindex)', 'piruseo' ); ?></label><br />
									<label><input type="checkbox" name="piruseo_options[noindex_search]" value="1" <?php checked( $options['noindex_search'], 1 ); ?> /> <?php esc_html_e( 'Resultados de búsqueda (noindex)', 'piruseo' ); ?></label><br />
									<label><input type="checkbox" name="piruseo_options[noindex_tag]" value="1" <?php checked( $options['noindex_tag'], 1 ); ?> /> <?php esc_html_e( 'Archivos de etiquetas / tags (noindex)', 'piruseo' ); ?></label>
								</fieldset>
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: SOCIAL -->
				<div class="piruseo-tab-panel" id="piruseo-tab-social">
					<h2><?php esc_html_e( 'Open Graph y Twitter / X Cards', 'piruseo' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Metadatos Open Graph', 'piruseo' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="piruseo_options[enable_og]" value="1" <?php checked( $options['enable_og'], 1 ); ?> />
									<?php esc_html_e( 'Activar etiquetas Open Graph (Facebook, LinkedIn, WhatsApp) y Twitter Cards', 'piruseo' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-default-og"><?php esc_html_e( 'Imagen social por defecto', 'piruseo' ); ?></label></th>
							<td>
								<div class="piruseo-media-field">
									<input type="url" id="piruseo-default-og" name="piruseo_options[default_og_image]" value="<?php echo esc_url( $options['default_og_image'] ); ?>" class="regular-text" />
									<button type="button" class="button piruseo-media-upload" data-target="#piruseo-default-og"><?php esc_html_e( 'Seleccionar imagen', 'piruseo' ); ?></button>
								</div>
								<p class="description"><?php esc_html_e( 'Se usará si la entrada no tiene imagen destacada ni imagen social personalizada (recomendado: 1200×630 px).', 'piruseo' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-twitter-card"><?php esc_html_e( 'Tipo de Twitter Card', 'piruseo' ); ?></label></th>
							<td>
								<select id="piruseo-twitter-card" name="piruseo_options[twitter_card_type]">
									<option value="summary_large_image" <?php selected( $options['twitter_card_type'], 'summary_large_image' ); ?>><?php esc_html_e( 'Resumen con imagen grande (summary_large_image)', 'piruseo' ); ?></option>
									<option value="summary" <?php selected( $options['twitter_card_type'], 'summary' ); ?>><?php esc_html_e( 'Resumen (summary)', 'piruseo' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-twitter-site"><?php esc_html_e( 'Usuario de Twitter / X', 'piruseo' ); ?></label></th>
							<td>
								<input type="text" id="piruseo-twitter-site" name="piruseo_options[twitter_site]" value="<?php echo esc_attr( $options['twitter_site'] ); ?>" class="regular-text" placeholder="@tuusuario" />
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: SITEMAP & ROBOTS -->
				<div class="piruseo-tab-panel" id="piruseo-tab-sitemap">
					<h2><?php esc_html_e( 'Mapa del sitio XML y Robots.txt', 'piruseo' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Sitemap XML', 'piruseo' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="piruseo_options[enable_sitemap]" value="1" <?php checked( $options['enable_sitemap'], 1 ); ?> />
									<?php esc_html_e( 'Activar el mapa del sitio XML de PiruSEO', 'piruseo' ); ?>
								</label>
								<?php if ( ! empty( $options['enable_sitemap'] ) ) : ?>
									<p class="description">
										<?php esc_html_e( 'URL de tu sitemap:', 'piruseo' ); ?>
										<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $sitemap_url ); ?></a>
									</p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="piruseo-robots"><?php esc_html_e( 'Reglas adicionales para robots.txt', 'piruseo' ); ?></label></th>
							<td>
								<textarea id="piruseo-robots" name="piruseo_options[custom_robots_txt]" rows="6" class="large-text code" placeholder="Disallow: /privado/"><?php echo esc_textarea( $options['custom_robots_txt'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Se añadirán al robots.txt virtual generado por WordPress junto con la directiva Sitemap.', 'piruseo' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: TOOLS / SHORTCODES -->
				<div class="piruseo-tab-panel" id="piruseo-tab-tools">
					<h2><?php esc_html_e( 'Migas de pan (Breadcrumbs)', 'piruseo' ); ?></h2>
					<p><?php esc_html_e( 'Puedes mostrar migas de pan con Schema BreadcrumbList en cualquier entrada, página o plantilla:', 'piruseo' ); ?></p>
					<ul>
						<li><strong>Shortcode:</strong> <code>[piruseo_breadcrumbs]</code></li>
						<li><strong>PHP en tu tema:</strong> <code>&lt;?php if ( function_exists( 'piruseo_breadcrumbs' ) ) { piruseo_breadcrumbs(); } ?&gt;</code></li>
					</ul>
				</div>

				<?php submit_button( __( 'Guardar cambios', 'piruseo' ) ); ?>
			</form>
		</div>
		<?php
	}
}
