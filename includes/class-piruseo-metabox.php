<?php
/**
 * Post/Page SEO Meta Box & Admin Columns for PiruSEO.
 *
 * @package PiruSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PiruSEO_Metabox {

	/**
	 * Meta keys managed by this class.
	 *
	 * @var array
	 */
	const META_KEYS = array(
		'focus_kw'      => '_piruseo_focus_kw',
		'title'         => '_piruseo_title',
		'description'   => '_piruseo_description',
		'og_title'      => '_piruseo_og_title',
		'og_desc'       => '_piruseo_og_desc',
		'og_image'      => '_piruseo_og_image',
		'twitter_title' => '_piruseo_twitter_title',
		'twitter_desc'  => '_piruseo_twitter_desc',
		'noindex'       => '_piruseo_noindex',
		'nofollow'      => '_piruseo_nofollow',
		'canonical'     => '_piruseo_canonical',
		'seo_score'     => '_piruseo_seo_score',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_admin_columns' ) );
	}

	/**
	 * Register meta box for all supported public post types.
	 */
	public function add_meta_box() {
		$post_types = PiruSEO_Helpers::get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'piruseo_metabox',
				__( 'PiruSEO — Optimización y Análisis', 'piruseo' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Enqueue CSS and JS on post edit and list screens.
	 *
	 * @param string $hook Current admin screen hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'piruseo-admin', PIRUSEO_URL . 'assets/css/admin.css', array(), PIRUSEO_VERSION );

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'piruseo-admin', PIRUSEO_URL . 'assets/js/admin-seo.js', array( 'jquery' ), PIRUSEO_VERSION, true );

			global $post;
			$post_type = $post instanceof WP_Post ? $post->post_type : 'post';
			$tpl_key   = 'page' === $post_type ? 'page_title_template' : 'post_title_template';

			wp_localize_script(
				'piruseo-admin',
				'piruSeoData',
				array(
					'siteName'      => get_bloginfo( 'name' ),
					'siteDesc'      => get_bloginfo( 'description' ),
					'siteUrl'       => home_url( '/' ),
					'separator'     => PiruSEO_Helpers::get_option( 'separator', '|' ),
					'titleTemplate' => PiruSEO_Helpers::get_option( $tpl_key, '%%title%% %%sep%% %%sitename%%' ),
					'permalink'     => $post instanceof WP_Post ? get_permalink( $post->ID ) : home_url( '/' ),
					'postSlug'      => $post instanceof WP_Post ? $post->post_name : '',
				)
			);
		}
	}

	/**
	 * Render the meta box HTML.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'piruseo_save_meta', 'piruseo_meta_nonce' );

		$focus_kw      = get_post_meta( $post->ID, self::META_KEYS['focus_kw'], true );
		$title         = get_post_meta( $post->ID, self::META_KEYS['title'], true );
		$description   = get_post_meta( $post->ID, self::META_KEYS['description'], true );
		$og_title      = get_post_meta( $post->ID, self::META_KEYS['og_title'], true );
		$og_desc       = get_post_meta( $post->ID, self::META_KEYS['og_desc'], true );
		$og_image      = get_post_meta( $post->ID, self::META_KEYS['og_image'], true );
		$twitter_title = get_post_meta( $post->ID, self::META_KEYS['twitter_title'], true );
		$twitter_desc  = get_post_meta( $post->ID, self::META_KEYS['twitter_desc'], true );
		$noindex       = get_post_meta( $post->ID, self::META_KEYS['noindex'], true );
		$nofollow      = get_post_meta( $post->ID, self::META_KEYS['nofollow'], true );
		$canonical     = get_post_meta( $post->ID, self::META_KEYS['canonical'], true );
		$seo_score     = get_post_meta( $post->ID, self::META_KEYS['seo_score'], true );
		?>
		<div class="piruseo-metabox" id="piruseo-metabox-app">
			<input type="hidden" name="piruseo_seo_score" id="piruseo-seo-score-input" value="<?php echo esc_attr( $seo_score ); ?>" />

			<div class="piruseo-mb-tabs" role="tablist">
				<button type="button" class="piruseo-mb-tab is-active" data-mb-tab="seo">
					<span class="piruseo-dot piruseo-dot-na" id="piruseo-tab-seo-dot"></span>
					<?php esc_html_e( 'SEO', 'piruseo' ); ?>
				</button>
				<button type="button" class="piruseo-mb-tab" data-mb-tab="readability">
					<span class="piruseo-dot piruseo-dot-na" id="piruseo-tab-read-dot"></span>
					<?php esc_html_e( 'Legibilidad', 'piruseo' ); ?>
				</button>
				<button type="button" class="piruseo-mb-tab" data-mb-tab="social">
					<span class="dashicons dashicons-share"></span>
					<?php esc_html_e( 'Social', 'piruseo' ); ?>
				</button>
				<button type="button" class="piruseo-mb-tab" data-mb-tab="advanced">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Avanzado', 'piruseo' ); ?>
				</button>
			</div>

			<!-- PANEL: SEO -->
			<div class="piruseo-mb-panel is-active" id="piruseo-mb-seo">
				<div class="piruseo-field">
					<label for="piruseo-focus-kw"><strong><?php esc_html_e( 'Frase clave objetivo (Focus Keyphrase)', 'piruseo' ); ?></strong></label>
					<input type="text" id="piruseo-focus-kw" name="piruseo_focus_kw" value="<?php echo esc_attr( $focus_kw ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Ej.: diseño web en wordpress', 'piruseo' ); ?>" />
				</div>

				<!-- SERP PREVIEW -->
				<div class="piruseo-serp-box">
					<div class="piruseo-serp-toolbar">
						<strong><?php esc_html_e( 'Vista previa en Google', 'piruseo' ); ?></strong>
						<div class="piruseo-serp-toggle">
							<button type="button" class="button button-small button-primary is-active" data-serp-mode="desktop"><?php esc_html_e( 'Escritorio', 'piruseo' ); ?></button>
							<button type="button" class="button button-small" data-serp-mode="mobile"><?php esc_html_e( 'Móvil', 'piruseo' ); ?></button>
						</div>
					</div>
					<div class="piruseo-serp-preview mode-desktop" id="piruseo-serp-preview">
						<div class="piruseo-serp-site">
							<span class="piruseo-serp-site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
							<span class="piruseo-serp-url" id="piruseo-serp-url"><?php echo esc_html( get_permalink( $post->ID ) ); ?></span>
						</div>
						<div class="piruseo-serp-title" id="piruseo-serp-title"></div>
						<div class="piruseo-serp-desc" id="piruseo-serp-desc"></div>
					</div>
				</div>

				<div class="piruseo-field">
					<div class="piruseo-field-header">
						<label for="piruseo-title"><strong><?php esc_html_e( 'Título SEO', 'piruseo' ); ?></strong></label>
						<span class="piruseo-counter" id="piruseo-title-counter">0 / 60</span>
					</div>
					<input type="text" id="piruseo-title" name="piruseo_title" value="<?php echo esc_attr( $title ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Deja vacío para usar la plantilla por defecto (%%title%% %%sep%% %%sitename%%)', 'piruseo' ); ?>" />
					<div class="piruseo-meter"><div class="piruseo-meter-bar" id="piruseo-title-bar"></div></div>
					<div class="piruseo-var-chips">
						<button type="button" class="button button-small piruseo-insert-var" data-target="#piruseo-title" data-var="%%title%%">+ Título</button>
						<button type="button" class="button button-small piruseo-insert-var" data-target="#piruseo-title" data-var="%%sep%%">+ Separador</button>
						<button type="button" class="button button-small piruseo-insert-var" data-target="#piruseo-title" data-var="%%sitename%%">+ Nombre del sitio</button>
						<button type="button" class="button button-small piruseo-insert-var" data-target="#piruseo-title" data-var="%%year%%">+ Año</button>
					</div>
				</div>

				<div class="piruseo-field">
					<div class="piruseo-field-header">
						<label for="piruseo-description"><strong><?php esc_html_e( 'Meta descripción', 'piruseo' ); ?></strong></label>
						<span class="piruseo-counter" id="piruseo-desc-counter">0 / 160</span>
					</div>
					<textarea id="piruseo-description" name="piruseo_description" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'Escribe un resumen atractivo de 120 a 160 caracteres para mejorar el CTR en buscadores.', 'piruseo' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
					<div class="piruseo-meter"><div class="piruseo-meter-bar" id="piruseo-desc-bar"></div></div>
				</div>

				<div class="piruseo-analysis-section">
					<div class="piruseo-analysis-header">
						<strong><?php esc_html_e( 'Análisis SEO', 'piruseo' ); ?></strong>
						<span class="piruseo-score-badge" id="piruseo-seo-badge"><?php esc_html_e( 'Analizando…', 'piruseo' ); ?></span>
					</div>
					<ul class="piruseo-checks-list" id="piruseo-seo-checks"></ul>
				</div>
			</div>

			<!-- PANEL: READABILITY -->
			<div class="piruseo-mb-panel" id="piruseo-mb-readability">
				<div class="piruseo-analysis-section">
					<div class="piruseo-analysis-header">
						<strong><?php esc_html_e( 'Análisis de legibilidad', 'piruseo' ); ?></strong>
						<span class="piruseo-score-badge" id="piruseo-read-badge"><?php esc_html_e( 'Analizando…', 'piruseo' ); ?></span>
					</div>
					<p class="description"><?php esc_html_e( 'Evalúa la claridad de tu texto en español: longitud de párrafos, frases, uso de subtítulos y palabras de transición.', 'piruseo' ); ?></p>
					<ul class="piruseo-checks-list" id="piruseo-read-checks"></ul>
				</div>
			</div>

			<!-- PANEL: SOCIAL -->
			<div class="piruseo-mb-panel" id="piruseo-mb-social">
				<h4><?php esc_html_e( 'Open Graph (Facebook, LinkedIn, WhatsApp)', 'piruseo' ); ?></h4>
				<div class="piruseo-field">
					<label for="piruseo-og-title"><strong><?php esc_html_e( 'Título Open Graph', 'piruseo' ); ?></strong></label>
					<input type="text" id="piruseo-og-title" name="piruseo_og_title" value="<?php echo esc_attr( $og_title ); ?>" class="widefat" />
				</div>
				<div class="piruseo-field">
					<label for="piruseo-og-desc"><strong><?php esc_html_e( 'Descripción Open Graph', 'piruseo' ); ?></strong></label>
					<textarea id="piruseo-og-desc" name="piruseo_og_desc" rows="2" class="widefat"><?php echo esc_textarea( $og_desc ); ?></textarea>
				</div>
				<div class="piruseo-field">
					<label for="piruseo-og-image"><strong><?php esc_html_e( 'Imagen Open Graph (URL)', 'piruseo' ); ?></strong></label>
					<div class="piruseo-media-field">
						<input type="url" id="piruseo-og-image" name="piruseo_og_image" value="<?php echo esc_url( $og_image ); ?>" class="widefat" />
						<button type="button" class="button piruseo-media-upload" data-target="#piruseo-og-image"><?php esc_html_e( 'Elegir imagen', 'piruseo' ); ?></button>
					</div>
				</div>

				<hr />
				<h4><?php esc_html_e( 'Twitter / X Card', 'piruseo' ); ?></h4>
				<div class="piruseo-field">
					<label for="piruseo-twitter-title"><strong><?php esc_html_e( 'Título para Twitter / X', 'piruseo' ); ?></strong></label>
					<input type="text" id="piruseo-twitter-title" name="piruseo_twitter_title" value="<?php echo esc_attr( $twitter_title ); ?>" class="widefat" />
				</div>
				<div class="piruseo-field">
					<label for="piruseo-twitter-desc"><strong><?php esc_html_e( 'Descripción para Twitter / X', 'piruseo' ); ?></strong></label>
					<textarea id="piruseo-twitter-desc" name="piruseo_twitter_desc" rows="2" class="widefat"><?php echo esc_textarea( $twitter_desc ); ?></textarea>
				</div>
			</div>

			<!-- PANEL: ADVANCED -->
			<div class="piruseo-mb-panel" id="piruseo-mb-advanced">
				<div class="piruseo-field">
					<label>
						<input type="checkbox" name="piruseo_noindex" value="1" <?php checked( $noindex, '1' ); ?> />
						<strong><?php esc_html_e( 'No indexar este contenido (noindex)', 'piruseo' ); ?></strong>
					</label>
					<p class="description"><?php esc_html_e( 'Impide que los motores de búsqueda muestren esta página en los resultados y la excluye del sitemap XML.', 'piruseo' ); ?></p>
				</div>

				<div class="piruseo-field">
					<label>
						<input type="checkbox" name="piruseo_nofollow" value="1" <?php checked( $nofollow, '1' ); ?> />
						<strong><?php esc_html_e( 'No seguir los enlaces (nofollow)', 'piruseo' ); ?></strong>
					</label>
				</div>

				<div class="piruseo-field">
					<label for="piruseo-canonical"><strong><?php esc_html_e( 'URL Canónica personalizada', 'piruseo' ); ?></strong></label>
					<input type="url" id="piruseo-canonical" name="piruseo_canonical" value="<?php echo esc_url( $canonical ); ?>" class="widefat" placeholder="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Déjalo vacío para usar el enlace permanente predeterminado.', 'piruseo' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box values.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['piruseo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['piruseo_meta_nonce'] ) ), 'piruseo_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		$cap       = 'page' === $post_type ? 'edit_page' : 'edit_post';
		if ( ! current_user_can( $cap, $post_id ) ) {
			return;
		}

		$text_map = array(
			'piruseo_focus_kw'      => self::META_KEYS['focus_kw'],
			'piruseo_title'         => self::META_KEYS['title'],
			'piruseo_og_title'      => self::META_KEYS['og_title'],
			'piruseo_twitter_title' => self::META_KEYS['twitter_title'],
			'piruseo_seo_score'     => self::META_KEYS['seo_score'],
		);

		foreach ( $text_map as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$val = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				if ( '' !== $val ) {
					update_post_meta( $post_id, $meta_key, $val );
				} else {
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		$textarea_map = array(
			'piruseo_description'  => self::META_KEYS['description'],
			'piruseo_og_desc'      => self::META_KEYS['og_desc'],
			'piruseo_twitter_desc' => self::META_KEYS['twitter_desc'],
		);

		foreach ( $textarea_map as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$val = sanitize_textarea_field( wp_unslash( $_POST[ $post_key ] ) );
				if ( '' !== $val ) {
					update_post_meta( $post_id, $meta_key, $val );
				} else {
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		$url_map = array(
			'piruseo_og_image'  => self::META_KEYS['og_image'],
			'piruseo_canonical' => self::META_KEYS['canonical'],
		);

		foreach ( $url_map as $post_key => $meta_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				$val = esc_url_raw( wp_unslash( $_POST[ $post_key ] ) );
				if ( '' !== $val ) {
					update_post_meta( $post_id, $meta_key, $val );
				} else {
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		if ( ! empty( $_POST['piruseo_noindex'] ) ) {
			update_post_meta( $post_id, self::META_KEYS['noindex'], '1' );
		} else {
			delete_post_meta( $post_id, self::META_KEYS['noindex'] );
		}

		if ( ! empty( $_POST['piruseo_nofollow'] ) ) {
			update_post_meta( $post_id, self::META_KEYS['nofollow'], '1' );
		} else {
			delete_post_meta( $post_id, self::META_KEYS['nofollow'] );
		}
	}

	/**
	 * Register SEO score & keyphrase columns on post list tables.
	 */
	public function register_admin_columns() {
		$post_types = PiruSEO_Helpers::get_supported_post_types();
		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_seo_columns' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_seo_columns' ), 10, 2 );
		}
	}

	/**
	 * Add SEO columns to list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_seo_columns( $columns ) {
		$columns['piruseo_score'] = __( 'SEO', 'piruseo' );
		$columns['piruseo_kw']    = __( 'Frase clave', 'piruseo' );
		return $columns;
	}

	/**
	 * Render custom SEO column cells.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_seo_columns( $column, $post_id ) {
		if ( 'piruseo_score' === $column ) {
			$noindex = get_post_meta( $post_id, self::META_KEYS['noindex'], true );
			if ( '1' === $noindex ) {
				echo '<span class="piruseo-dot piruseo-dot-noindex" title="' . esc_attr__( 'No indexado (noindex)', 'piruseo' ) . '"></span>';
				return;
			}
			$score = get_post_meta( $post_id, self::META_KEYS['seo_score'], true );
			$map   = array(
				'good' => array( 'class' => 'piruseo-dot-good', 'label' => __( 'SEO Bueno', 'piruseo' ) ),
				'ok'   => array( 'class' => 'piruseo-dot-ok', 'label' => __( 'SEO Mejorable', 'piruseo' ) ),
				'bad'  => array( 'class' => 'piruseo-dot-bad', 'label' => __( 'Necesita mejoras', 'piruseo' ) ),
			);
			$info  = isset( $map[ $score ] ) ? $map[ $score ] : array( 'class' => 'piruseo-dot-na', 'label' => __( 'Sin analizar', 'piruseo' ) );
			echo '<span class="piruseo-dot ' . esc_attr( $info['class'] ) . '" title="' . esc_attr( $info['label'] ) . '"></span>';
		} elseif ( 'piruseo_kw' === $column ) {
			$kw = get_post_meta( $post_id, self::META_KEYS['focus_kw'], true );
			echo $kw ? esc_html( $kw ) : '<span class="piruseo-muted">—</span>';
		}
	}
}
