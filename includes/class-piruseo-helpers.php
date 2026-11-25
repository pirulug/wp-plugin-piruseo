<?php
/**
 * Helper utilities for PiruSEO.
 *
 * @package PiruSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PiruSEO_Helpers {

	/**
	 * Default plugin options.
	 *
	 * @return array
	 */
	public static function get_default_options() {
		return array(
			'separator'              => '|',
			'home_title'             => '%%sitename%% %%sep%% %%sitedesc%%',
			'home_description'       => '',
			'post_title_template'    => '%%title%% %%sep%% %%sitename%%',
			'page_title_template'    => '%%title%% %%sep%% %%sitename%%',
			'archive_title_template' => '%%term_title%% %%sep%% %%sitename%%',
			'search_title_template'  => 'Resultados para "%%searchphrase%%" %%sep%% %%sitename%%',
			'404_title_template'     => 'Página no encontrada %%sep%% %%sitename%%',
			'noindex_author'         => 0,
			'noindex_date'           => 1,
			'noindex_search'         => 1,
			'noindex_tag'            => 0,
			'schema_type'            => 'Organization',
			'schema_name'            => get_bloginfo( 'name' ),
			'schema_logo'            => '',
			'verify_google'          => '',
			'verify_bing'            => '',
			'verify_yandex'          => '',
			'enable_og'              => 1,
			'default_og_image'       => '',
			'twitter_card_type'      => 'summary_large_image',
			'twitter_site'           => '',
			'enable_sitemap'         => 1,
			'custom_robots_txt'      => '',
		);
	}

	/**
	 * Retrieve all options merged with defaults.
	 *
	 * @return array
	 */
	public static function get_options() {
		$saved = get_option( 'piruseo_options', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::get_default_options() );
	}

	/**
	 * Retrieve a single option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get_option( $key, $default = '' ) {
		$options = self::get_options();
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Available title separators.
	 *
	 * @return array
	 */
	public static function get_separators() {
		return array(
			'|'  => '|',
			'-'  => '-',
			'–'  => '–',
			'—'  => '—',
			'•'  => '•',
			'»'  => '»',
			'/'  => '/',
			'~'  => '~',
		);
	}

	/**
	 * Replace template variables such as %%title%%, %%sitename%%, %%sep%%.
	 *
	 * @param string       $template Template string.
	 * @param WP_Post|null $post     Optional post context.
	 * @return string
	 */
	public static function replace_vars( $template, $post = null ) {
		if ( empty( $template ) ) {
			return '';
		}

		$sep      = self::get_option( 'separator', '|' );
		$sitename = get_bloginfo( 'name' );
		$sitedesc = get_bloginfo( 'description' );

		$title        = '';
		$excerpt      = '';
		$category     = '';
		$author       = '';
		$term_title   = '';
		$searchphrase = is_search() ? get_search_query() : '';

		if ( $post instanceof WP_Post ) {
			$title   = get_the_title( $post );
			$excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
			$author  = get_the_author_meta( 'display_name', $post->post_author );
			$cats    = get_the_category( $post->ID );
			if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
				$category = $cats[0]->name;
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term_title = single_term_title( '', false );
			$title      = $term_title;
		} elseif ( is_author() ) {
			$term_title = get_the_author();
			$title      = $term_title;
		} elseif ( is_archive() ) {
			$term_title = wp_strip_all_tags( get_the_archive_title() );
			$title      = $term_title;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		$page  = $paged > 1 ? sprintf( __( 'Página %d', 'piruseo' ), $paged ) : '';

		$replacements = array(
			'%%title%%'        => $title,
			'%%sitename%%'     => $sitename,
			'%%sitedesc%%'     => $sitedesc,
			'%%sep%%'          => $sep,
			'%%excerpt%%'      => $excerpt,
			'%%category%%'     => $category,
			'%%author%%'       => $author,
			'%%term_title%%'   => $term_title,
			'%%searchphrase%%' => $searchphrase,
			'%%page%%'         => $page,
			'%%year%%'         => gmdate( 'Y' ),
		);

		$result = strtr( $template, $replacements );
		$result = preg_replace( '/\s+/', ' ', trim( $result ) );

		return wp_strip_all_tags( $result );
	}

	/**
	 * Generate a clean meta description excerpt from post content.
	 *
	 * @param WP_Post $post   Post object.
	 * @param int     $length Max characters.
	 * @return string
	 */
	public static function generate_excerpt( $post, $length = 155 ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$text = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );

		if ( mb_strlen( $text ) > $length ) {
			$text = mb_substr( $text, 0, $length );
			$last_space = mb_strrpos( $text, ' ' );
			if ( false !== $last_space ) {
				$text = mb_substr( $text, 0, $last_space );
			}
			$text = rtrim( $text, '.,;:' ) . '…';
		}

		return $text;
	}

	/**
	 * Public post types supported by PiruSEO.
	 *
	 * @return array
	 */
	public static function get_supported_post_types() {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );
		return array_values( $types );
	}
}
