<?php
/**
 * Schema.org structured data (JSON-LD).
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'gg_optimizer_schema_output_website', static function ( $enabled ) {
	return GG_Optimizer_Feature_Toggle::is_enabled( 'schema' ) ? $enabled : false;
}, 1 );

if ( ! function_exists( 'gg_optimizer_schema_get_organization_content_sources' ) ) {
	/**
	 * Get content sources used for Organization schema extraction.
	 *
	 * Reads the current singular content plus block-template and template-part
	 * posts for the active theme so Site Editor-managed logo/social blocks are
	 * available to the schema extractor.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Array of raw block content strings.
	 */
	function gg_optimizer_schema_get_organization_content_sources( $post ) {
		$sources = array();
		$theme   = wp_get_theme()->get_stylesheet();

		if ( $post instanceof WP_Post && ! empty( $post->post_content ) ) {
			$sources[] = $post->post_content;
		}

		$template_post_types = array( 'wp_template', 'wp_template_part' );
		$query_args          = array(
			'post_type'      => $template_post_types,
			'post_status'    => array( 'publish', 'auto-draft', 'draft' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		);

		$template_posts = get_posts( $query_args );

		foreach ( $template_posts as $template_post ) {
			if ( ! ( $template_post instanceof WP_Post ) || empty( $template_post->post_content ) ) {
				continue;
			}

			if ( taxonomy_exists( 'wp_theme' ) && ! has_term( $theme, 'wp_theme', $template_post ) ) {
				continue;
			}

				$sources[] = $template_post->post_content;
		}

		return array_values( array_unique( array_filter( $sources ) ) );
	}
}


if ( ! function_exists( 'gg_optimizer_schema_extract_sameas_urls' ) ) {
	/**
	 * Extract sameAs URLs from serialized social-links block markup.
	 *
	 * This handles cases where parse_blocks() does not expose child social-link
	 * attributes consistently across template storage variants.
	 *
	 * @param string $content Serialized block content.
	 * @return array Array of URLs.
	 */
	function gg_optimizer_schema_extract_sameas_urls_from_serialized_content( $content ) {
		$urls = array();

		if ( ! is_string( $content ) || '' === $content ) {
			return $urls;
		}

		if ( ! preg_match_all( '/<!--\s+wp:social-links\s+({.*?})\s+-->(.*?)<!--\s+\/wp:social-links\s+-->/is', $content, $social_blocks, PREG_SET_ORDER ) ) {
			return $urls;
		}

		foreach ( $social_blocks as $social_block ) {
			if ( empty( $social_block[1] ) ) {
				continue;
			}

			$attrs = json_decode( $social_block[1], true );
			if ( ! is_array( $attrs ) || empty( $attrs['sameAsSchema'] ) ) {
				continue;
			}

			$inner = isset( $social_block[2] ) ? (string) $social_block[2] : '';
			if ( '' === $inner ) {
				continue;
			}

			if ( preg_match_all( '/<!--\s+wp:social-link\s+({.*?})\s*\/-->/is', $inner, $link_blocks, PREG_SET_ORDER ) ) {
				foreach ( $link_blocks as $link_block ) {
					if ( empty( $link_block[1] ) ) {
						continue;
					}

					$link_attrs = json_decode( $link_block[1], true );
					if ( is_array( $link_attrs ) && ! empty( $link_attrs['url'] ) ) {
						$urls[] = esc_url_raw( $link_attrs['url'] );
					}
				}
			}

			if ( preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\']/i', $inner, $href_matches ) ) {
				foreach ( $href_matches[1] as $href ) {
					$urls[] = esc_url_raw( $href );
				}
			}
		}

		return $urls;
	}

	/**
	 * Extract social URLs from core/social-links blocks with sameAsSchema enabled.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Array of URLs.
	 */
	function gg_optimizer_schema_extract_sameas_urls( $post ) {
		$sources = gg_optimizer_schema_get_organization_content_sources( $post );
		if ( empty( $sources ) ) {
			return array();
		}

		$urls = array();

		foreach ( $sources as $source ) {
			$urls = array_merge( $urls, gg_optimizer_schema_find_sameas_in_blocks( parse_blocks( $source ) ) );
			$urls = array_merge( $urls, gg_optimizer_schema_extract_sameas_urls_from_serialized_content( $source ) );
		}

		return array_values( array_unique( array_filter( $urls ) ) );
	}
}

if ( ! function_exists( 'gg_optimizer_schema_find_sameas_in_blocks' ) ) {
	/**
	 * Recursively find social-links blocks with sameAsSchema enabled and collect URLs.
	 *
	 * @param array $blocks             Block array.
	 * @param array $seen_pattern_slugs Pattern slugs already visited.
	 * @param array $seen_block_refs    Synced pattern refs already visited.
	 * @return array Array of URLs.
	 */
	function gg_optimizer_schema_find_sameas_in_blocks( $blocks, $seen_pattern_slugs = array(), $seen_block_refs = array() ) {
		$urls = array();
		foreach ( $blocks as $block ) {
			if (
				isset( $block['blockName'] ) &&
				'core/pattern' === $block['blockName'] &&
				! empty( $block['attrs']['slug'] )
			) {
				$slug = (string) $block['attrs']['slug'];

				if ( ! in_array( $slug, $seen_pattern_slugs, true ) ) {
					$seen_pattern_slugs[] = $slug;

					$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
					if ( is_array( $pattern ) && ! empty( $pattern['content'] ) ) {
						$urls = array_merge(
							$urls,
							gg_optimizer_schema_find_sameas_in_blocks(
								parse_blocks( $pattern['content'] ),
								$seen_pattern_slugs,
								$seen_block_refs
							)
						);
					}
				}
			}

			if (
				isset( $block['blockName'] ) &&
				'core/block' === $block['blockName'] &&
				! empty( $block['attrs']['ref'] )
			) {
				$ref_id = (int) $block['attrs']['ref'];

				if ( $ref_id > 0 && ! in_array( $ref_id, $seen_block_refs, true ) ) {
					$seen_block_refs[] = $ref_id;
					$ref_post          = get_post( $ref_id );

					if ( $ref_post instanceof WP_Post && ! empty( $ref_post->post_content ) ) {
						$urls = array_merge(
							$urls,
							gg_optimizer_schema_find_sameas_in_blocks(
								parse_blocks( $ref_post->post_content ),
								$seen_pattern_slugs,
								$seen_block_refs
							)
						);
					}
				}
			}

			if (
				isset( $block['blockName'] ) &&
				'core/social-links' === $block['blockName'] &&
				! empty( $block['attrs']['sameAsSchema'] )
			) {
				$start_count = count( $urls );

				if ( ! empty( $block['innerBlocks'] ) ) {
					foreach ( $block['innerBlocks'] as $item ) {
						if (
							isset( $item['blockName'] ) &&
							'core/social-link' === $item['blockName'] &&
							! empty( $item['attrs']['url'] )
						) {
							$urls[] = esc_url_raw( $item['attrs']['url'] );
						}
					}
				}

				// Fallback when child URLs are present only in saved HTML.
				if ( count( $urls ) === $start_count ) {
					$html = '';
					if ( ! empty( $block['innerHTML'] ) ) {
						$html = (string) $block['innerHTML'];
					} elseif ( ! empty( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
						$html = implode( '', $block['innerContent'] );
					}

					if ( '' !== $html && preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches ) ) {
						foreach ( $matches[1] as $href ) {
							$urls[] = esc_url_raw( $href );
						}
					}
				}
			}
			// Recurse into innerBlocks for nested blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$urls = array_merge(
					$urls,
					gg_optimizer_schema_find_sameas_in_blocks( $block['innerBlocks'], $seen_pattern_slugs, $seen_block_refs )
				);
			}
		}
		return $urls;
	}
}

if ( ! function_exists( 'gg_optimizer_schema_extract_logo_url' ) ) {
	/**
	 * Extract logo image URL from core/site-logo block with organizationLogoSchema enabled.
	 *
	 * @param WP_Post $post Post object.
	 * @return string Logo image URL or empty string.
	 */
	function gg_optimizer_schema_extract_logo_url( $post ) {
		$sources = gg_optimizer_schema_get_organization_content_sources( $post );
		if ( empty( $sources ) ) {
			return '';
		}

		foreach ( $sources as $source ) {
			$logo_url = gg_optimizer_schema_find_logo_in_blocks( parse_blocks( $source ) );
			if ( '' !== $logo_url ) {
				return $logo_url;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'gg_optimizer_schema_find_logo_in_blocks' ) ) {
	/**
	 * Recursively find site-logo block with organizationLogoSchema enabled and get its image URL.
	 *
	 * @param array $blocks Block array.
	 * @return string Logo image URL or empty string.
	 */
	function gg_optimizer_schema_find_logo_in_blocks( $blocks ) {
		foreach ( $blocks as $block ) {
			if (
				isset( $block['blockName'] ) &&
				'core/site-logo' === $block['blockName'] &&
				! empty( $block['attrs']['organizationLogoSchema'] )
			) {
				// Try to get the rendered image src from the block's attrs or fallback to site logo.
				if ( ! empty( $block['attrs']['url'] ) ) {
					return esc_url_raw( $block['attrs']['url'] );
				}
				// Fallback: get_custom_logo() returns markup, extract src.
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
					if ( $logo_url ) {
						return esc_url_raw( $logo_url );
					}
				}
			}
			// Recurse into innerBlocks for nested blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$logo_url = gg_optimizer_schema_find_logo_in_blocks( $block['innerBlocks'] );
				if ( $logo_url ) {
					return $logo_url;
				}
			}
		}
		return '';
	}
}

if ( ! function_exists( 'gg_optimizer_schema_output_organization_json_ld' ) ) {
	/**
	 * Output Organization JSON-LD if logo or sameAs present.
	 *
	 * @filter gg_optimizer_schema_output_organization - Set to false to disable Organization schema output. Default true.
	 *
	 * @return void
	 */
	function gg_optimizer_schema_output_organization_json_ld() {
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_schema_output_organization', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_schema_output_organization', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		$sameas = gg_optimizer_schema_extract_sameas_urls( $post );
		$logo   = gg_optimizer_schema_extract_logo_url( $post );

		$org = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
		);

		$name = trim( (string) get_bloginfo( 'name' ) );
		if ( '' !== $name ) {
			$org['name'] = $name;
		}

		$url = home_url( '/' );
		if ( ! empty( $url ) ) {
			$org['url'] = $url;
		}

		$description = trim( (string) get_bloginfo( 'description' ) );
		if ( '' !== $description ) {
			$org['description'] = $description;
		}

		if ( ! empty( $logo ) ) {
			$org['logo'] = $logo;
		}
		if ( ! empty( $sameas ) ) {
			$org['sameAs'] = array_values( array_unique( $sameas ) );
		}

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $org, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE ) . "\n";
		echo '</script>' . "\n";
	}
}

if ( ! function_exists( 'gg_optimizer_schema_build_website_graph' ) ) {
	/**
	 * Build schema.org graph for the whole website.
	 *
	 * @return array
	 */
	function gg_optimizer_schema_build_website_graph() {
		$name        = trim( (string) get_bloginfo( 'name' ) );
		$description = trim( (string) get_bloginfo( 'description' ) );
		$url         = home_url( '/' );

		if ( '' === $name || '' === $url ) {
			return array();
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebSite',
			'url'      => $url,
			'name'     => $name,
		);

		if ( '' !== $description ) {
			$schema['description'] = $description;
		}

		$schema['inLanguage'] = get_bloginfo( 'language' );

		$schema['potentialAction'] = array(
			'@type'  => 'SearchAction',
			'target' => home_url( '/?s={search_term_string}' ),
		);

		return $schema;
	}
}

if ( ! function_exists( 'gg_optimizer_schema_output_website_json_ld' ) ) {
	/**
	 * Output WebSite JSON-LD for all front-end pages.
	 *
	 * @filter gg_optimizer_schema_output_website - Set to false to disable WebSite schema output. Default true.
	 *
	 * @return void
	 */
	function gg_optimizer_schema_output_website_json_ld() {
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_schema_output_website', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_schema_output_website', true ) ) {
			return;
		}

		$schema = gg_optimizer_schema_build_website_graph();
		if ( empty( $schema ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE ) . "\n";
		echo '</script>' . "\n";
	}

}

/**
 * Schema.org structured data (JSON-LD).
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

if ( ! function_exists( 'gg_optimizer_schema_extract_faq_items' ) ) {
	/**
	 * Recursively extract FAQ items from core/accordion blocks.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Array of [ 'question' => ..., 'answer' => ... ]
	 */
	function gg_optimizer_schema_extract_faq_items( $post ) {
		if ( empty( $post->post_content ) ) {
			return array();
		}
		$blocks = parse_blocks( $post->post_content );
		return gg_optimizer_schema_find_faq_in_blocks( $blocks );
	}
}

if ( ! function_exists( 'gg_optimizer_schema_find_faq_in_blocks' ) ) {
	/**
	 * Helper: Recursively search blocks for FAQ accordions.
	 *
	 * @param array $blocks Block array.
	 * @return array Array of [ 'question' => ..., 'answer' => ... ]
	 */
	function gg_optimizer_schema_find_faq_in_blocks( $blocks ) {
			$faq = array();
		foreach ( $blocks as $block ) {
			if (
				isset( $block['blockName'] ) &&
				'core/accordion' === $block['blockName'] &&
				! empty( $block['innerBlocks'] ) &&
				! empty( $block['attrs']['faqSchema'] )
			) {
				foreach ( $block['innerBlocks'] as $item ) {
					if ( ! isset( $item['blockName'] ) || 'core/accordion-item' !== $item['blockName'] || empty( $item['innerBlocks'] ) ) {
						continue;
					}
					$question = '';
					$answer   = '';
					foreach ( $item['innerBlocks'] as $sub ) {
						if ( isset( $sub['blockName'] ) && 'core/accordion-heading' === $sub['blockName'] ) {
							$html = render_block( $sub );
							if ( preg_match( '/<span[^>]*class="[^"]*toggle-title[^"]*"[^>]*>(.*?)<\/span>/is', $html, $m ) ) {
								$question = trim( wp_strip_all_tags( $m[1] ) );
							} else {
								$question = trim( wp_strip_all_tags( $html ) );
							}
						}
						if ( isset( $sub['blockName'] ) && 'core/accordion-panel' === $sub['blockName'] && ! empty( $sub['innerBlocks'] ) ) {
							foreach ( $sub['innerBlocks'] as $panel_sub ) {
								if ( isset( $panel_sub['blockName'] ) && 'core/paragraph' === $panel_sub['blockName'] ) {
									$answer .= ' ' . trim( wp_strip_all_tags( render_block( $panel_sub ) ) );
								}
							}
						}
					}
					$question = trim( $question );
					$answer   = trim( $answer );
					if ( $question && $answer ) {
						$faq[] = array(
							'question' => $question,
							'answer'   => $answer,
						);
					}
				}
			}
			// Recurse into innerBlocks for nested accordions or other blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$faq = array_merge( $faq, gg_optimizer_schema_find_faq_in_blocks( $block['innerBlocks'] ) );
			}
		}
			return $faq;
	}
}
/**
 * Schema.org structured data (JSON-LD).
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

if ( ! function_exists( 'gg_optimizer_schema_get_description' ) ) {
	/**
	 * Extract description for schema from post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	function gg_optimizer_schema_get_description( $post ) {
		// Delegates to canonical meta description resolver — ensures schema parity with head and OG tags.
		return gg_optimizer_get_meta_description( $post );
	}
}

if ( ! function_exists( 'gg_optimizer_schema_get_image' ) ) {
	/**
	 * Get featured image URL for schema.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function gg_optimizer_schema_get_image( $post_id ) {
		// Delegates to canonical image resolver — ensures schema parity with social card tags.
		if ( ! function_exists( 'gg_optimizer_get_social_image_data' ) ) {
			return '';
		}
		$data = gg_optimizer_get_social_image_data( $post_id );
		if ( ! is_array( $data ) || empty( $data['url'] ) ) {
			return '';
		}
		return $data['url'];
	}
}

if ( ! function_exists( 'gg_optimizer_schema_build_graph' ) ) {
	/**
	 * Build schema.org graph for singular content.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	function gg_optimizer_schema_build_graph( $post ) {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => apply_filters( 'gg_optimizer_schema_article_type', is_page( $post ) ? 'WebPage' : 'BlogPosting', $post ),
		);

		// Use canonical URL resolver so schema url matches <link rel="canonical">.
		$canonical = function_exists( 'gg_optimizer_get_canonical_url' )
			? gg_optimizer_get_canonical_url( $post )
			: get_permalink( $post );
		if ( ! empty( $canonical ) ) {
			$schema['url'] = $canonical;
		}

		$headline = trim( (string) get_the_title( $post ) );
		if ( '' !== $headline ) {
			$schema['headline'] = $headline;
		}

		$description = gg_optimizer_schema_get_description( $post );
		if ( '' !== $description ) {
			$schema['description'] = $description;
		}

		$schema['inLanguage'] = get_bloginfo( 'language' );

		if ( ! empty( $canonical ) ) {
			$schema['mainEntityOfPage'] = array(
				'@type' => 'WebPage',
				'@id'   => $canonical,
			);
		}

		// Publisher.
		$publisher_name = trim( (string) get_bloginfo( 'name' ) );
		if ( '' !== $publisher_name ) {
			$schema['publisher'] = array(
				'@type' => 'Organization',
				'name'  => $publisher_name,
			);
		}

		// Image.
		$image = gg_optimizer_schema_get_image( $post->ID );
		if ( '' !== $image ) {
			$schema['image'] = $image;
		}

		// Date modified (all types).
		$date_modified = get_post_modified_time( 'c', true, $post );
		if ( ! empty( $date_modified ) ) {
			$schema['dateModified'] = $date_modified;
		}

		$date_published = get_post_time( 'c', true, $post );
		if ( ! empty( $date_published ) ) {
			$schema['datePublished'] = $date_published;
		}

		// BlogPosting-specific.
		if ( ! is_page( $post ) ) {
			// Author.
			$author_id = (int) $post->post_author;
			if ( $author_id > 0 ) {
				$author_name = (string) get_the_author_meta( 'display_name', $author_id );
				if ( '' !== $author_name ) {
					$schema['author'] = array(
						'@type' => 'Person',
						'name'  => $author_name,
					);
				}
			}
		}

		if ( 2 === count( $schema ) ) {
			return array();
		}

		return $schema;
	}
}

if ( ! function_exists( 'gg_optimizer_schema_get_breadcrumb_items' ) ) {
	/**
	 * Build breadcrumb items for singular content.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	function gg_optimizer_schema_get_breadcrumb_items( $post ) {
		$items = array();

		if ( ! ( $post instanceof WP_Post ) ) {
			return $items;
		}

		$items[] = array(
			'name' => __( 'Home', 'gregius-optimizer' ),
			'item' => home_url( '/' ),
		);

		$post_type = get_post_type( $post );
		if ( $post_type && is_post_type_hierarchical( $post_type ) ) {
			$ancestors = get_post_ancestors( $post );
			if ( ! empty( $ancestors ) ) {
				$ancestors = array_reverse( $ancestors );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor_title = get_the_title( $ancestor_id );
					$ancestor_url   = get_permalink( $ancestor_id );

					if ( '' !== $ancestor_title && $ancestor_url ) {
						$items[] = array(
							'name' => $ancestor_title,
							'item' => $ancestor_url,
						);
					}
				}
			}
		}

		$current_title = get_the_title( $post );
		if ( '' === $current_title ) {
			$post_type_object = get_post_type_object( $post->post_type );
			if ( $post_type_object && ! empty( $post_type_object->labels->singular_name ) ) {
				$current_title = (string) $post_type_object->labels->singular_name;
			}
		}

		$current_item = array();

		if ( '' !== trim( $current_title ) ) {
			$current_item['name'] = $current_title;
		}

		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id > 0 && $post->ID === $front_page_id ) {
			return $items;
		}

		if ( ! empty( $current_item['name'] ) ) {
			$items[] = $current_item;
		}

		return $items;
	}
}

if ( ! function_exists( 'gg_optimizer_schema_build_breadcrumb_graph' ) ) {
	/**
	 * Build BreadcrumbList schema graph for singular views.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	function gg_optimizer_schema_build_breadcrumb_graph( $post ) {
		if ( ! is_singular() || is_home() ) {
			return array();
		}

		$items = gg_optimizer_schema_get_breadcrumb_items( $post );
		if ( count( $items ) < 1 ) {
			return array();
		}

		$list_items    = array();
		$total_items   = count( $items );
		$items_indexed = array_values( $items );

		foreach ( $items_indexed as $index => $item ) {
			$name = isset( $item['name'] ) ? trim( (string) $item['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}

			$list_item = array(
				'@type'    => 'ListItem',
				'position' => count( $list_items ) + 1,
				'name'     => $name,
			);

			$is_last = ( $index === $total_items - 1 );
			if ( ! empty( $item['item'] ) && ! $is_last ) {
				$list_item['item'] = $item['item'];
			}

			$list_items[] = $list_item;
		}

		if ( count( $list_items ) < 1 ) {
			return array();
		}

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list_items,
		);
	}
}

if ( ! function_exists( 'gg_optimizer_schema_output_breadcrumb_json_ld' ) ) {
	/**
	 * Output BreadcrumbList JSON-LD for singular views.
	 *
	 * @filter gg_optimizer_schema_output_breadcrumb - Set to false to disable BreadcrumbList schema output. Default true.
	 *
	 * @return void
	 */
	function gg_optimizer_schema_output_breadcrumb_json_ld() {
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_schema_output_breadcrumb', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_schema_output_breadcrumb', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		$breadcrumb_schema = gg_optimizer_schema_build_breadcrumb_graph( $post );
		if ( empty( $breadcrumb_schema ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $breadcrumb_schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE ) . "\n";
		echo '</script>' . "\n";
	}
}

if ( ! function_exists( 'gg_optimizer_schema_build_json_ld' ) ) {
	/**
	 * Build the schema.org JSON-LD @graph array.
	 *
	 * Accepts an optional post object. When passed, generates the full
	 * singular-content graph. When null, falls back to the current query context.
	 *
	 * @filter gg_optimizer_schema_output_website      - Set to false to disable WebSite schema output. Default true.
	 * @filter gg_optimizer_schema_output_organization - Set to false to disable Organization schema output. Default true.
	 * @filter gg_optimizer_schema_output_article - Set to false to disable BlogPosting/WebPage schema output. Default true.
	 * @filter gg_optimizer_schema_output_faq     - Set to false to disable FAQPage schema output. Default true.
	 * @filter gg_optimizer_schema_output_breadcrumb - Set to false to disable BreadcrumbList schema output. Default true.
	 *
	 * @param WP_Post|null $post Optional post object.
	 * @return array Full schema payload with @context and @graph.
	 */
	function gg_optimizer_schema_build_json_ld( $post = null ) {
		$graph        = array();
		$home_url     = trailingslashit( home_url( '/' ) );
		$home_base_id = untrailingslashit( $home_url );
		$site_id      = $home_base_id . '#website';
		$org_id       = $home_base_id . '#organization';

		$is_single = false;
		if ( $post instanceof WP_Post ) {
			$is_single = true;
		} else {
			if ( is_admin() ) {
				return array(
					'@context' => 'https://schema.org',
					'@graph'   => array(),
				);
			}
			$post      = get_queried_object();
			$is_single = is_singular() && ( $post instanceof WP_Post );
		}

		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_schema_output_website', '__return_false' );
		$output_website = (bool) apply_filters( 'gg_optimizer_schema_output_website', true );
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_schema_output_organization', '__return_false' );
		$output_organization = (bool) apply_filters( 'gg_optimizer_schema_output_organization', true );
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_schema_output_breadcrumb', '__return_false' );
		$output_breadcrumb = (bool) apply_filters( 'gg_optimizer_schema_output_breadcrumb', true );

		if ( $output_organization ) {
			$sameas = array();
			$logo   = '';

			$sameas = gg_optimizer_schema_extract_sameas_urls( $post instanceof WP_Post ? $post : null );
			$logo   = gg_optimizer_schema_extract_logo_url( $post instanceof WP_Post ? $post : null );

			$organization = array(
				'@type' => apply_filters( 'gg_optimizer_schema_organization_type', 'Organization' ),
				'@id'   => $org_id,
			);

			$name = trim( (string) get_bloginfo( 'name' ) );
			if ( '' !== $name ) {
				$organization['name'] = $name;
			}

			if ( ! empty( $home_url ) ) {
				$organization['url'] = $home_url;
			}

			$description = trim( (string) get_bloginfo( 'description' ) );
			if ( '' !== $description ) {
				$organization['description'] = $description;
			}

			if ( ! empty( $logo ) ) {
				$logo_image_id = $home_base_id . '#logo';
				$organization['logo'] = array( '@id' => $logo_image_id );
				$graph[] = array(
					'@type'      => 'ImageObject',
					'@id'        => $logo_image_id,
					'url'        => $logo,
					'contentUrl' => $logo,
					'caption'    => $name,
				);
			}

			if ( ! empty( $sameas ) ) {
				$organization['sameAs'] = array_values( array_unique( $sameas ) );
			}

			$graph[] = $organization;
		}

		if ( $output_website ) {
			$website = gg_optimizer_schema_build_website_graph();
			if ( ! empty( $website ) ) {
				unset( $website['@context'] );
				$website['@id'] = $site_id;

				if ( $output_organization ) {
					$website['publisher'] = array( '@id' => $org_id );
				}

				$graph[] = $website;
			}
		}

		if ( $is_single ) {
			$page_url      = (string) get_permalink( $post );
			$page_base_id  = untrailingslashit( $page_url );
			$page_id       = $page_base_id . '#webpage';
			$breadcrumb_id = $page_base_id . '#breadcrumb';
			$faq_id        = $page_base_id . '#faq';
			$breadcrumb    = array();

			if ( $output_breadcrumb ) {
				$breadcrumb = gg_optimizer_schema_build_breadcrumb_graph( $post );
				if ( ! empty( $breadcrumb ) ) {
					unset( $breadcrumb['@context'] );
					$breadcrumb['@id'] = $breadcrumb_id;
				}
			}

			// phpcs:ignore -- Example usage documentation.
			// Usage: add_filter( 'gg_optimizer_schema_output_article', '__return_false' );
			if ( apply_filters( 'gg_optimizer_schema_output_article', true ) ) {
				$article = gg_optimizer_schema_build_graph( $post );
				if ( ! empty( $article ) ) {
					unset( $article['@context'] );
					$article['@id'] = $page_id;

					if ( isset( $article['publisher'] ) && is_array( $article['publisher'] ) && $output_organization ) {
						$article['publisher'] = array( '@id' => $org_id );
					}

					if ( $output_website ) {
						$article['isPartOf'] = array( '@id' => $site_id );
					}

					if ( ! empty( $breadcrumb ) ) {
						$article['breadcrumb'] = array( '@id' => $breadcrumb_id );
					}

					if ( ! empty( $article['image'] ) && is_string( $article['image'] ) ) {
						$image_url                  = $article['image'];
						$image_id                   = $page_base_id . '#primaryimage';
						$article['image']            = array( '@id' => $image_id );
						$article['primaryImageOfPage'] = array( '@id' => $image_id );
						$graph[] = array(
							'@type'      => 'ImageObject',
							'@id'        => $image_id,
							'url'        => $image_url,
							'contentUrl' => $image_url,
						);
					}

					$graph[] = $article;
				}
			}

			if ( ! empty( $breadcrumb ) ) {
				$graph[] = $breadcrumb;
			}

			// phpcs:ignore -- Example usage documentation.
			// Usage: add_filter( 'gg_optimizer_schema_output_faq', '__return_false' );
			if ( apply_filters( 'gg_optimizer_schema_output_faq', true ) ) {
				$faq_items = gg_optimizer_schema_extract_faq_items( $post );

				if ( ! empty( $faq_items ) ) {
					$faq_schema = array(
						'@type'      => 'FAQPage',
						'@id'        => $faq_id,
						'isPartOf'   => array( '@id' => $page_id ),
						'mainEntity' => array(),
					);

					foreach ( $faq_items as $item ) {
						$faq_schema['mainEntity'][] = array(
							'@type'          => 'Question',
							'name'           => $item['question'],
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $item['answer'],
							),
						);
					}

					$graph[] = $faq_schema;
				}
			}
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}
}

if ( ! function_exists( 'gg_optimizer_schema_output_json_ld' ) ) {
	/**
	 * Output schema.org JSON-LD in document head as a single @graph script.
	 *
	 * Wrapper that echoes the result of gg_optimizer_schema_build_json_ld().
	 *
	 * @return void
	 */
	function gg_optimizer_schema_output_json_ld() {
		if ( ! GG_Optimizer_Feature_Toggle::is_enabled( 'schema' ) ) {
			return;
		}

		$payload = gg_optimizer_schema_build_json_ld();

		if ( empty( $payload['@graph'] ) ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $payload, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE ) . "\n";
		echo '</script>' . "\n";
	}
}
