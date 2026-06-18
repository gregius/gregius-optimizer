<?php
/**
 * Robots directives for meta robots and robots.txt.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', function () {
	if ( is_robots() ) {
		return;
	}

	$request_path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), PHP_URL_PATH );
	if ( 'robots.txt' !== wp_basename( $request_path ) ) {
		return;
	}

	$public = get_option( 'blog_public' );
	$output = "User-agent: *\n";
	if ( '0' === $public ) {
		$output .= "Disallow: /\n";
	} else {
		$site_url = wp_parse_url( site_url() );
		$path     = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';
		$output  .= "Disallow: $path/wp-admin/\n";
		$output  .= "Allow: $path/wp-admin/admin-ajax.php\n";
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally using core robots_txt filter for interoperability.
	$output = apply_filters( 'robots_txt', $output, $public );

	header( 'Content-Type: text/plain; charset=utf-8' );
	echo esc_html( $output );
	exit;
}, 0 );

add_filter( 'gg_optimizer_robots_txt_enabled', static function ( $enabled ) {
	return GG_Optimizer_Feature_Toggle::is_enabled( 'robots' ) ? $enabled : false;
}, 1 );

add_filter( 'gg_optimizer_robots_meta_enabled', static function ( $enabled ) {
	return GG_Optimizer_Feature_Toggle::is_enabled( 'robots' ) ? $enabled : false;
}, 1 );

if ( ! function_exists( 'gg_optimizer_is_hidden_from_search' ) ) {
	/**
	 * Check whether a post is flagged to hide from search engines.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	function gg_optimizer_is_hidden_from_search( $post_id ) {
		$value = get_post_meta( (int) $post_id, '_gg_optimizer_hide_from_search', true );

		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
	}
}

if ( ! function_exists( 'gg_optimizer_output_robots_meta' ) ) {
	/**
	 * Output robots meta tag in document head.
	 *
	 * Default policy:
	 * - index, follow   — all pages.
	 * - noindex, follow — search results and 404 pages.
	 *
	 * @filter gg_optimizer_robots_meta_enabled  Disable robots meta output. Default true.
	 * @filter gg_optimizer_robots_meta_content   Override the robots meta content string.
	 *
	 * @return void
	 */
	function gg_optimizer_output_robots_meta() {
		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_robots_meta_enabled', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_robots_meta_enabled', true ) ) {
			return;
		}

		$content = 'index, follow';

		if ( is_search() || is_404() ) {
			$content = 'noindex, follow';
		} elseif ( is_singular() ) {
			$post = get_queried_object();

			if ( $post instanceof WP_Post && gg_optimizer_is_hidden_from_search( $post->ID ) ) {
				$content = 'noindex, follow';
			}
		}

		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_robots_meta_content', function( $content ) { return 'noindex, nofollow'; } );
		$content = (string) apply_filters( 'gg_optimizer_robots_meta_content', $content );

		echo '<meta name="robots" content="' . esc_attr( $content ) . '" />' . "\n";
	}

}

if ( ! function_exists( 'gg_optimizer_get_default_robots_txt' ) ) {
	/**
	 * Default robots.txt directives.
	 *
	 * @return string
	 */
	function gg_optimizer_get_default_robots_txt() {
		$sitemap = home_url( '/wp-sitemap.xml' );

		$rules  = "User-agent: *\n";
		$rules .= "Disallow: /wp-admin/\n";
		$rules .= "Allow: /wp-admin/admin-ajax.php\n";
		$rules .= "\n";
		$rules .= "Sitemap: $sitemap\n";
		$rules .= "\n";
		$rules .= "# Traditional Search & Live RAG Retrieval\n";
		$rules .= "User-agent: Googlebot\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: Bingbot\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "# Conversational & Generative Answer Engines\n";
		$rules .= "User-agent: OAI-SearchBot\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: ChatGPT-User\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: PerplexityBot\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: ClaudeBot\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: Claude-Web\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "# AI Data Model Trainers\n";
		$rules .= "User-agent: Google-Extended\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: GPTBot\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: Applebot-Extended\n";
		$rules .= "Allow: /\n";
		$rules .= "\n";
		$rules .= "User-agent: Cohere-ai\n";
		$rules .= "Allow: /\n";

		return $rules;
	}
}

if ( ! function_exists( 'gg_optimizer_output_robots_txt' ) ) {
	/**
	 * Build robots.txt directives for production/public sites.
	 *
	 * Checks for a custom override in the DB; falls back to defaults.
	 *
	 * @filter gg_optimizer_robots_txt_enabled  Disable extra robots.txt directives. Default true.
	 *
	 * @param string $output    Existing robots output from WordPress.
	 * @param string $is_public Whether search indexing is enabled.
	 * @return string
	 */
	function gg_optimizer_output_robots_txt( $output, $is_public ) {
		if ( '0' === (string) $is_public ) {
			return $output;
		}

		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_robots_txt_enabled', '__return_false' );
		if ( ! apply_filters( 'gg_optimizer_robots_txt_enabled', true ) ) {
			return $output;
		}

		$custom = GG_Optimizer_DB::get( 'robots_txt_content', '' );
		if ( '' !== $custom ) {
			return $custom;
		}

		return gg_optimizer_get_default_robots_txt();
	}

	add_filter( 'robots_txt', 'gg_optimizer_output_robots_txt', 10, 2 );
}

/**
 * REST endpoint: GET / POST robots.txt content.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gg-optimizer/v1',
			'/robots-txt',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'callback'            => function () {
						$custom     = GG_Optimizer_DB::get( 'robots_txt_content', '' );
						$result_url = home_url( '/robots.txt' );

						if ( '' !== $custom ) {
							return rest_ensure_response(
								array(
									'content'        => $custom,
									'has_custom'     => true,
									'robots_txt_url' => $result_url,
								)
							);
						}

						return rest_ensure_response(
							array(
								'content'        => gg_optimizer_get_default_robots_txt(),
								'has_custom'     => false,
								'robots_txt_url' => $result_url,
							)
						);
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(
						'content' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
					'callback'            => function ( $request ) {
						$content = $request->get_param( 'content' );
						if ( ! is_string( $content ) ) {
							return new WP_Error(
								'gg_optimizer_invalid_data',
								__( 'content must be a string.', 'gregius-optimizer' ),
								array( 'status' => 400 )
							);
						}

						GG_Optimizer_DB::set( 'robots_txt_content', sanitize_textarea_field( $content ) );

						return rest_ensure_response( array( 'success' => true ) );
					},
				),
			)
		);
	}
);
