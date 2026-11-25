<?php
/**
 * XML Sitemap & Robots.txt handler for PiruSEO.
 *
 * @package PiruSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PiruSEO_Sitemap {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_sitemap_request' ) );
		add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 10, 2 );
	}

	/**
	 * Register rewrite rules for XML sitemap and its XSL stylesheet.
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule( '^piruseo-sitemap\.xml$', 'index.php?piruseo_sitemap=xml', 'top' );
		add_rewrite_rule( '^piruseo-sitemap\.xsl$', 'index.php?piruseo_sitemap=xsl', 'top' );
	}

	/**
	 * Register custom query var.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'piruseo_sitemap';
		return $vars;
	}

	/**
	 * Serve XML sitemap or XSL stylesheet when requested.
	 */
	public function handle_sitemap_request() {
		$type = get_query_var( 'piruseo_sitemap' );
		if ( empty( $type ) ) {
			return;
		}

		if ( ! PiruSEO_Helpers::get_option( 'enable_sitemap', 1 ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		if ( 'xsl' === $type ) {
			$this->render_xsl();
			exit;
		}

		if ( 'xml' === $type ) {
			$this->render_xml();
			exit;
		}
	}

	/**
	 * Output the XML sitemap.
	 */
	private function render_xml() {
		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		$xsl_url    = home_url( '/piruseo-sitemap.xsl' );
		$post_types = PiruSEO_Helpers::get_supported_post_types();

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $xsl_url ) . '"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Homepage.
		echo "  <url>\n";
		echo '    <loc>' . esc_url( home_url( '/' ) ) . "</loc>\n";
		echo '    <lastmod>' . esc_html( gmdate( 'c' ) ) . "</lastmod>\n";
		echo "    <changefreq>daily</changefreq>\n";
		echo "    <priority>1.0</priority>\n";
		echo "  </url>\n";

		// Published public posts/pages/CPTs (excluding noindex).
		$query = new WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => PiruSEO_Metabox::META_KEYS['noindex'],
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => PiruSEO_Metabox::META_KEYS['noindex'],
						'value'   => '1',
						'compare' => '!=',
					),
				),
			)
		);

		foreach ( $query->posts as $post ) {
			$permalink = get_permalink( $post->ID );
			$lastmod   = get_post_modified_time( 'c', true, $post );
			$priority  = 'page' === $post->post_type ? '0.8' : '0.7';

			echo "  <url>\n";
			echo '    <loc>' . esc_url( $permalink ) . "</loc>\n";
			if ( $lastmod ) {
				echo '    <lastmod>' . esc_html( $lastmod ) . "</lastmod>\n";
			}
			echo "    <changefreq>weekly</changefreq>\n";
			echo '    <priority>' . esc_html( $priority ) . "</priority>\n";
			echo "  </url>\n";
		}

		// Public categories.
		$categories = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				$link = get_term_link( $cat );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				echo "  <url>\n";
				echo '    <loc>' . esc_url( $link ) . "</loc>\n";
				echo "    <changefreq>weekly</changefreq>\n";
				echo "    <priority>0.5</priority>\n";
				echo "  </url>\n";
			}
		}

		echo "</urlset>\n";
	}

	/**
	 * Render XSL stylesheet so the XML sitemap looks clean in browsers.
	 */
	private function render_xsl() {
		status_header( 200 );
		header( 'Content-Type: application/xslt+xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		?>
		<xsl:stylesheet version="2.0"
			xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
			xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
			<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
			<xsl:template match="/">
				<html lang="es">
				<head>
					<title>Mapa del sitio XML — PiruSEO</title>
					<style>
						body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; background: #f8fafc; margin: 0; }
						header { background: #1e1b4b; color: #fff; padding: 24px 32px; }
						header h1 { margin: 0 0 6px; font-size: 22px; }
						header p { margin: 0; opacity: .85; font-size: 14px; }
						main { max-width: 1100px; margin: 24px auto; padding: 0 20px; }
						table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
						th, td { text-align: left; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
						th { background: #f1f5f9; font-weight: 600; color: #334155; }
						a { color: #4f46e5; text-decoration: none; }
						a:hover { text-decoration: underline; }
					</style>
				</head>
				<body>
					<header>
						<h1>Mapa del sitio XML (PiruSEO)</h1>
						<p>Este sitemap contiene <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs indexables.</p>
					</header>
					<main>
						<table>
							<thead>
								<tr>
									<th>URL</th>
									<th>Prioridad</th>
									<th>Frecuencia</th>
									<th>Última modificación</th>
								</tr>
							</thead>
							<tbody>
								<xsl:for-each select="sitemap:urlset/sitemap:url">
									<tr>
										<td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
										<td><xsl:value-of select="sitemap:priority"/></td>
										<td><xsl:value-of select="sitemap:changefreq"/></td>
										<td><xsl:value-of select="sitemap:lastmod"/></td>
									</tr>
								</xsl:for-each>
							</tbody>
						</table>
					</main>
				</body>
				</html>
			</xsl:template>
		</xsl:stylesheet>
		<?php
	}

	/**
	 * Append custom rules and Sitemap directive to virtual robots.txt.
	 *
	 * @param string $output Robots.txt content.
	 * @param bool   $public Whether the site is public.
	 * @return string
	 */
	public function filter_robots_txt( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}

		$custom = trim( (string) PiruSEO_Helpers::get_option( 'custom_robots_txt', '' ) );
		if ( ! empty( $custom ) ) {
			$output .= "\n# Reglas personalizadas PiruSEO\n" . $custom . "\n";
		}

		if ( PiruSEO_Helpers::get_option( 'enable_sitemap', 1 ) ) {
			$output .= "\nSitemap: " . esc_url_raw( home_url( '/piruseo-sitemap.xml' ) ) . "\n";
		}

		return $output;
	}
}
