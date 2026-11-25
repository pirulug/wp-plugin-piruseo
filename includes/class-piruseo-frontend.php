<?php
/**
 * Frontend SEO output: Title, Meta Tags, Open Graph, Twitter Cards & Schema.org JSON-LD.
 *
 * @package PiruSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PiruSEO_Frontend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 20 );
		add_action( 'wp_head', array( $this, 'remove_core_canonical' ), 1 );
		add_action( 'wp_head', array( $this, 'output_head_tags' ), 2 );
	}

	/**
	 * Remove WordPress core canonical tag to prevent duplicates.
	 */
	public function remove_core_canonical() {
		remove_action( 'wp_head', 'rel_canonical' );
	}

	/**
	 * Compute the resolved SEO title for the current request.
	 *
	 * @return string
	 */
	public function get_seo_title() {
		$options = PiruSEO_Helpers::get_options();

		if ( is_front_page() || is_home() ) {
			if ( is_front_page() && is_page() ) {
				$post   = get_queried_object();
				$custom = $post instanceof WP_Post ? get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['title'], true ) : '';
				if ( ! empty( $custom ) ) {
					return PiruSEO_Helpers::replace_vars( $custom, $post );
				}
			}
			return PiruSEO_Helpers::replace_vars( $options['home_title'] );
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$custom = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['title'], true );
				if ( ! empty( $custom ) ) {
					return PiruSEO_Helpers::replace_vars( $custom, $post );
				}
				$tpl_key = 'page' === $post->post_type ? 'page_title_template' : 'post_title_template';
				return PiruSEO_Helpers::replace_vars( $options[ $tpl_key ], $post );
			}
		}

		if ( is_search() ) {
			return PiruSEO_Helpers::replace_vars( $options['search_title_template'] );
		}

		if ( is_404() ) {
			return PiruSEO_Helpers::replace_vars( $options['404_title_template'] );
		}

		if ( is_archive() ) {
			return PiruSEO_Helpers::replace_vars( $options['archive_title_template'] );
		}

		return '';
	}

	/**
	 * Filter WordPress document title.
	 *
	 * @param string $title Current title.
	 * @return string
	 */
	public function filter_document_title( $title ) {
		$seo_title = $this->get_seo_title();
		return ! empty( $seo_title ) ? $seo_title : $title;
	}

	/**
	 * Compute the meta description for the current request.
	 *
	 * @return string
	 */
	public function get_meta_description() {
		$options = PiruSEO_Helpers::get_options();

		if ( is_front_page() || is_home() ) {
			if ( is_front_page() && is_page() ) {
				$post = get_queried_object();
				if ( $post instanceof WP_Post ) {
					$custom = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['description'], true );
					if ( ! empty( $custom ) ) {
						return PiruSEO_Helpers::replace_vars( $custom, $post );
					}
				}
			}
			if ( ! empty( $options['home_description'] ) ) {
				return PiruSEO_Helpers::replace_vars( $options['home_description'] );
			}
			return get_bloginfo( 'description' );
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$custom = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['description'], true );
				if ( ! empty( $custom ) ) {
					return PiruSEO_Helpers::replace_vars( $custom, $post );
				}
				return PiruSEO_Helpers::generate_excerpt( $post );
			}
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term_desc = term_description();
			if ( ! empty( $term_desc ) ) {
				return wp_strip_all_tags( $term_desc );
			}
		}

		return '';
	}

	/**
	 * Compute robots meta directives.
	 *
	 * @return string
	 */
	public function get_robots_content() {
		$options  = PiruSEO_Helpers::get_options();
		$noindex  = false;
		$nofollow = false;

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$noindex  = '1' === get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['noindex'], true );
				$nofollow = '1' === get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['nofollow'], true );
			}
		} elseif ( is_search() && ! empty( $options['noindex_search'] ) ) {
			$noindex = true;
		} elseif ( is_author() && ! empty( $options['noindex_author'] ) ) {
			$noindex = true;
		} elseif ( is_date() && ! empty( $options['noindex_date'] ) ) {
			$noindex = true;
		} elseif ( is_tag() && ! empty( $options['noindex_tag'] ) ) {
			$noindex = true;
		} elseif ( is_404() ) {
			$noindex = true;
		}

		if ( ! $noindex && ! $nofollow ) {
			return 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
		}

		$directives = array(
			$noindex ? 'noindex' : 'index',
			$nofollow ? 'nofollow' : 'follow',
		);

		return implode( ', ', $directives );
	}

	/**
	 * Compute canonical URL.
	 *
	 * @return string
	 */
	public function get_canonical_url() {
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$custom = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['canonical'], true );
				if ( ! empty( $custom ) ) {
					return $custom;
				}
				return get_permalink( $post->ID );
			}
		}

		if ( is_front_page() ) {
			return home_url( '/' );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				return is_wp_error( $link ) ? '' : $link;
			}
		}

		return '';
	}

	/**
	 * Output all PiruSEO head tags.
	 */
	public function output_head_tags() {
		$options     = PiruSEO_Helpers::get_options();
		$title       = $this->get_seo_title();
		$description = $this->get_meta_description();
		$robots      = $this->get_robots_content();
		$canonical   = $this->get_canonical_url();

		echo "\n<!-- Optimizado con PiruSEO v" . esc_html( PIRUSEO_VERSION ) . " -->\n";

		if ( ! empty( $description ) ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		}

		if ( ! empty( $robots ) ) {
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '" />' . "\n";
		}

		if ( ! empty( $canonical ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		}

		// Webmaster verification codes on front page.
		if ( is_front_page() || is_home() ) {
			if ( ! empty( $options['verify_google'] ) ) {
				echo '<meta name="google-site-verification" content="' . esc_attr( $options['verify_google'] ) . '" />' . "\n";
			}
			if ( ! empty( $options['verify_bing'] ) ) {
				echo '<meta name="msvalidate.01" content="' . esc_attr( $options['verify_bing'] ) . '" />' . "\n";
			}
			if ( ! empty( $options['verify_yandex'] ) ) {
				echo '<meta name="yandex-verification" content="' . esc_attr( $options['verify_yandex'] ) . '" />' . "\n";
			}
		}

		// Open Graph & Twitter Cards.
		if ( ! empty( $options['enable_og'] ) ) {
			$this->output_social_tags( $title, $description, $canonical, $options );
		}

		// Schema.org JSON-LD.
		$this->output_schema_json_ld( $title, $description, $canonical, $options );

		echo "<!-- / PiruSEO -->\n\n";
	}

	/**
	 * Output Open Graph and Twitter Card tags.
	 *
	 * @param string $title       SEO title.
	 * @param string $description Meta description.
	 * @param string $canonical   Canonical URL.
	 * @param array  $options     Plugin options.
	 */
	private function output_social_tags( $title, $description, $canonical, $options ) {
		$og_title      = $title;
		$og_desc       = $description;
		$og_image      = $options['default_og_image'];
		$og_type       = 'website';
		$twitter_title = $title;
		$twitter_desc  = $description;
		$post          = is_singular() ? get_queried_object() : null;

		if ( $post instanceof WP_Post ) {
			$og_type = 'post' === $post->post_type ? 'article' : 'website';

			$custom_og_title = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['og_title'], true );
			$custom_og_desc  = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['og_desc'], true );
			$custom_og_image = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['og_image'], true );
			$custom_tw_title = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['twitter_title'], true );
			$custom_tw_desc  = get_post_meta( $post->ID, PiruSEO_Metabox::META_KEYS['twitter_desc'], true );

			if ( ! empty( $custom_og_title ) ) {
				$og_title = $custom_og_title;
			}
			if ( ! empty( $custom_og_desc ) ) {
				$og_desc = $custom_og_desc;
			}
			if ( ! empty( $custom_og_image ) ) {
				$og_image = $custom_og_image;
			} elseif ( has_post_thumbnail( $post->ID ) ) {
				$thumb_url = get_the_post_thumbnail_url( $post->ID, 'full' );
				if ( $thumb_url ) {
					$og_image = $thumb_url;
				}
			}

			$twitter_title = ! empty( $custom_tw_title ) ? $custom_tw_title : $og_title;
			$twitter_desc  = ! empty( $custom_tw_desc ) ? $custom_tw_desc : $og_desc;
		}

		echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '" />' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '" />' . "\n";
		if ( ! empty( $og_title ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
		}
		if ( ! empty( $og_desc ) ) {
			echo '<meta property="og:description" content="' . esc_attr( $og_desc ) . '" />' . "\n";
		}
		if ( ! empty( $canonical ) ) {
			echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
		}
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
		if ( ! empty( $og_image ) ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		}

		if ( $post instanceof WP_Post && 'post' === $post->post_type ) {
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( DATE_W3C, $post ) ) . '" />' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( DATE_W3C, $post ) ) . '" />' . "\n";
		}

		// Twitter / X Cards.
		echo '<meta name="twitter:card" content="' . esc_attr( $options['twitter_card_type'] ) . '" />' . "\n";
		if ( ! empty( $options['twitter_site'] ) ) {
			$handle = '@' . ltrim( $options['twitter_site'], '@' );
			echo '<meta name="twitter:site" content="' . esc_attr( $handle ) . '" />' . "\n";
		}
		if ( ! empty( $twitter_title ) ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '" />' . "\n";
		}
		if ( ! empty( $twitter_desc ) ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $twitter_desc ) . '" />' . "\n";
		}
		if ( ! empty( $og_image ) ) {
			echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		}
	}

	/**
	 * Output Schema.org JSON-LD @graph.
	 *
	 * @param string $title       SEO title.
	 * @param string $description Meta description.
	 * @param string $canonical   Canonical URL.
	 * @param array  $options     Plugin options.
	 */
	private function output_schema_json_ld( $title, $description, $canonical, $options ) {
		$site_url  = home_url( '/' );
		$site_name = get_bloginfo( 'name' );
		$graph     = array();

		// Organization / Person entity.
		$publisher_id = $site_url . '#identity';
		$identity     = array(
			'@type' => $options['schema_type'],
			'@id'   => $publisher_id,
			'name'  => ! empty( $options['schema_name'] ) ? $options['schema_name'] : $site_name,
			'url'   => $site_url,
		);
		if ( ! empty( $options['schema_logo'] ) ) {
			$identity['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $options['schema_logo'],
			);
		}
		$graph[] = $identity;

		// WebSite entity with SearchAction.
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => $site_url . '#website',
			'url'             => $site_url,
			'name'            => $site_name,
			'description'     => get_bloginfo( 'description' ),
			'publisher'       => array( '@id' => $publisher_id ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => $site_url . '?s={search_term_string}',
				'query-input' => 'required name=search_term_string',
			),
		);

		// WebPage entity.
		$page_url = ! empty( $canonical ) ? $canonical : $site_url;
		$webpage  = array(
			'@type'      => 'WebPage',
			'@id'        => $page_url . '#webpage',
			'url'        => $page_url,
			'name'       => $title,
			'isPartOf'   => array( '@id' => $site_url . '#website' ),
			'inLanguage' => get_bloginfo( 'language' ),
		);
		if ( ! empty( $description ) ) {
			$webpage['description'] = $description;
		}
		$graph[] = $webpage;

		// Article entity on blog posts.
		if ( is_singular( 'post' ) ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$article = array(
					'@type'            => 'Article',
					'@id'              => $page_url . '#article',
					'isPartOf'         => array( '@id' => $page_url . '#webpage' ),
					'headline'         => get_the_title( $post ),
					'datePublished'    => get_the_date( DATE_W3C, $post ),
					'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
					'author'           => array(
						'@type' => 'Person',
						'name'  => get_the_author_meta( 'display_name', $post->post_author ),
						'url'   => get_author_posts_url( $post->post_author ),
					),
					'publisher'        => array( '@id' => $publisher_id ),
					'mainEntityOfPage' => array( '@id' => $page_url . '#webpage' ),
				);
				if ( has_post_thumbnail( $post->ID ) ) {
					$article['image'] = get_the_post_thumbnail_url( $post->ID, 'full' );
				}
				$graph[] = $article;
			}
		}

		// BreadcrumbList entity.
		$crumbs = PiruSEO_Breadcrumbs::get_items();
		if ( count( $crumbs ) > 1 ) {
			$item_list = array();
			foreach ( $crumbs as $index => $crumb ) {
				$entry = array(
					'@type'    => 'ListItem',
					'position' => $index + 1,
					'name'     => $crumb['label'],
				);
				if ( ! empty( $crumb['url'] ) ) {
					$entry['item'] = $crumb['url'];
				}
				$item_list[] = $entry;
			}
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $page_url . '#breadcrumb',
				'itemListElement' => $item_list,
			);
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		echo '<script type="application/ld+json" class="piruseo-schema">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
