<?php
/**
 * Plugin Name: PiruSEO
 * Plugin URI:  https://example.com/piruseo
 * Description: Plugin completo de SEO para WordPress similar a Yoast SEO: análisis SEO y de legibilidad en tiempo real, vista previa SERP, metaetiquetas, Open Graph, Twitter Cards, Schema JSON-LD, sitemap XML y migas de pan.
 * Version:     2026-11-01
 * Author:      Pirulug
 * Text Domain: piruseo
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$piruseo_version_file = plugin_dir_path( __FILE__ ) . 'VERSION';
$piruseo_version      = file_exists( $piruseo_version_file ) ? trim( (string) file_get_contents( $piruseo_version_file ) ) : '2026-11-01';

define( 'PIRUSEO_VERSION', '' !== $piruseo_version ? $piruseo_version : '2026-11-01' );
define( 'PIRUSEO_FILE', __FILE__ );
define( 'PIRUSEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIRUSEO_URL', plugin_dir_url( __FILE__ ) );
define( 'PIRUSEO_BASENAME', plugin_basename( __FILE__ ) );

require_once PIRUSEO_DIR . 'includes/class-piruseo-helpers.php';
require_once PIRUSEO_DIR . 'includes/class-piruseo-settings.php';
require_once PIRUSEO_DIR . 'includes/class-piruseo-metabox.php';
require_once PIRUSEO_DIR . 'includes/class-piruseo-frontend.php';
require_once PIRUSEO_DIR . 'includes/class-piruseo-sitemap.php';
require_once PIRUSEO_DIR . 'includes/class-piruseo-breadcrumbs.php';

/**
 * Main PiruSEO plugin class.
 */
final class PiruSEO {

	/**
	 * Singleton instance.
	 *
	 * @var PiruSEO|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return PiruSEO
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		new PiruSEO_Settings();
		new PiruSEO_Metabox();
		new PiruSEO_Frontend();
		new PiruSEO_Sitemap();
		new PiruSEO_Breadcrumbs();
	}

	/**
	 * Load plugin translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'piruseo', false, dirname( PIRUSEO_BASENAME ) . '/languages' );
	}

	/**
	 * Activation hook.
	 */
	public static function activate() {
		if ( false === get_option( 'piruseo_options' ) ) {
			add_option( 'piruseo_options', PiruSEO_Helpers::get_default_options() );
		}
		PiruSEO_Sitemap::register_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'PiruSEO', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PiruSEO', 'deactivate' ) );

PiruSEO::instance();
