<?php
/**
 * Breadcrumbs shortcode and helper for PiruSEO.
 *
 * @package PiruSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PiruSEO_Breadcrumbs {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'piruseo_breadcrumbs', array( $this, 'shortcode' ) );
	}

	/**
	 * Build breadcrumb trail items for the current page.
	 *
	 * @return array List of ['label' => string, 'url' => string].
	 */
	public static function get_items() {
		$items = array(
			array(
				'label' => __( 'Inicio', 'piruseo' ),
				'url'   => home_url( '/' ),
			),
		);

		if ( is_front_page() ) {
			return $items;
		}

		if ( is_singular( 'post' ) ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$cats = get_the_category( $post->ID );
				if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
					$primary = $cats[0];
					$items[] = array(
						'label' => $primary->name,
						'url'   => get_category_link( $primary->term_id ),
					);
				}
				$items[] = array(
					'label' => get_the_title( $post ),
					'url'   => '',
				);
			}
		} elseif ( is_page() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$ancestors = array_reverse( get_post_ancestors( $post->ID ) );
				foreach ( $ancestors as $ancestor_id ) {
					$items[] = array(
						'label' => get_the_title( $ancestor_id ),
						'url'   => get_permalink( $ancestor_id ),
					);
				}
				$items[] = array(
					'label' => get_the_title( $post ),
					'url'   => '',
				);
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$items[] = array(
				'label' => single_term_title( '', false ),
				'url'   => '',
			);
		} elseif ( is_search() ) {
			$items[] = array(
				'label' => sprintf( __( 'Búsqueda: %s', 'piruseo' ), get_search_query() ),
				'url'   => '',
			);
		} elseif ( is_404() ) {
			$items[] = array(
				'label' => __( 'Error 404', 'piruseo' ),
				'url'   => '',
			);
		} elseif ( is_archive() ) {
			$items[] = array(
				'label' => wp_strip_all_tags( get_the_archive_title() ),
				'url'   => '',
			);
		}

		return $items;
	}

	/**
	 * Render HTML breadcrumbs.
	 *
	 * @param string $separator Separator string.
	 * @return string
	 */
	public static function render( $separator = '»' ) {
		$items = self::get_items();
		if ( count( $items ) <= 1 ) {
			return '';
		}

		$parts = array();
		foreach ( $items as $index => $item ) {
			$is_last = ( $index === count( $items ) - 1 );
			if ( ! $is_last && ! empty( $item['url'] ) ) {
				$parts[] = '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			} else {
				$parts[] = '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
			}
		}

		$sep_html = ' <span class="piruseo-bc-sep" aria-hidden="true">' . esc_html( $separator ) . '</span> ';
		return '<nav class="piruseo-breadcrumbs" aria-label="' . esc_attr__( 'Migas de pan', 'piruseo' ) . '">' . implode( $sep_html, $parts ) . '</nav>';
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'separator' => '»',
			),
			$atts,
			'piruseo_breadcrumbs'
		);

		return self::render( $atts['separator'] );
	}
}

if ( ! function_exists( 'piruseo_breadcrumbs' ) ) {
	/**
	 * Template helper function to echo PiruSEO breadcrumbs.
	 *
	 * @param string $separator Separator string.
	 */
	function piruseo_breadcrumbs( $separator = '»' ) {
		echo wp_kses_post( PiruSEO_Breadcrumbs::render( $separator ) );
	}
}
