<?php
defined( 'ABSPATH' ) || exit;
/**
 * Search-facing metadata output.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

if ( ! function_exists( 'gg_optimizer_output_meta_description' ) ) {
	/**
	 * Output meta description in document head.
	 *
	 * @filter gg_optimizer_meta_output_description - Set to false to disable meta description output. Default true.
	 *
	 * @return void
	 */
	function gg_optimizer_output_meta_description() {
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_meta_output_description', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_meta_output_description', true ) ) {
			return;
		}

		// Use canonical description provenance shared across meta, OG, and Twitter tags.
		$description = gg_optimizer_get_meta_description();
		if ( '' === $description ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
}

if ( ! function_exists( 'gg_optimizer_get_canonical_url' ) ) {
	/**
	 * Resolve canonical URL for the current request.
	 *
	 * @param WP_Post|null $post Post object.
	 * @return string
	 */
	function gg_optimizer_get_canonical_url( $post = null ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		if ( $post instanceof WP_Post ) {
			$canonical = wp_get_canonical_url( $post );
			if ( '' !== trim( (string) $canonical ) ) {
				return (string) $canonical;
			}

			$permalink = get_permalink( $post );
			if ( '' !== trim( (string) $permalink ) ) {
				return (string) $permalink;
			}
		}

		if ( is_front_page() || is_home() ) {
			return home_url( '/' );
		}

		if ( is_post_type_archive() ) {
			$post_type = (string) get_query_var( 'post_type' );
			$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
			$archive   = get_post_type_archive_link( (string) $post_type );

			if ( $archive ) {
				return (string) $archive;
			}
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$term_link = get_term_link( $term );
				if ( ! is_wp_error( $term_link ) ) {
					return (string) $term_link;
				}
			}
		}

		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof WP_User ) {
				return get_author_posts_url( $author->ID );
			}
		}

		if ( is_search() ) {
			return get_search_link();
		}

		return home_url( '/' );
	}
}

if ( ! function_exists( 'gg_optimizer_output_canonical_link' ) ) {
	/**
	 * Output canonical link tag in document head.
	 *
	 * @filter gg_optimizer_meta_output_canonical - Set to false to disable canonical output. Default true.
	 * @filter gg_optimizer_meta_canonical_url    - Override canonical URL string.
	 *
	 * @return void
	 */
	function gg_optimizer_output_canonical_link() {
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_meta_output_canonical', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_meta_output_canonical', true ) ) {
			return;
		}

		$canonical = gg_optimizer_get_canonical_url();
		$canonical = (string) apply_filters( 'gg_optimizer_meta_canonical_url', $canonical );

		if ( '' === trim( $canonical ) ) {
			return;
		}

		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	}
}
