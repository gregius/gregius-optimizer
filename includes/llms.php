<?php
/**
 * LLMs context file and meta registration.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'gg_optimizer_llms_enabled', static function ( $enabled ) {
	return GG_Optimizer_Feature_Toggle::is_enabled( 'llms' ) ? $enabled : false;
}, 1 );

// 1. Register meta fields for all public post types.
add_action( 'init', 'gg_optimizer_register_llms_meta_fields' );

/**
 * Register LLMs meta fields for all public post types.
 *
 * @return void
 */
function gg_optimizer_register_llms_meta_fields() {
	if ( ! class_exists( 'GG_Optimizer_Custom_Meta_Field' ) ) {
		return;
	}

	$post_types  = get_post_types( array( 'public' => true ), 'names' );
	$meta_fields = array(
		'_gg_optimizer_include_in_llms'  => array(
			'type'        => 'boolean',
			'default'     => false,
			'label'       => 'Include in llms.txt',
			'description' => 'If enabled, this document will be included in the LLMs context file.',
		),
		'_gg_optimizer_llms_description' => array(
			'type'        => 'string',
			'default'     => '',
			'label'       => 'LLMs Description',
			'description' => 'Custom description for this document when included in llms.txt. Leave empty to auto-generate from excerpt or content.',
		),
	);

	$meta = new GG_Optimizer_Custom_Meta_Field( $post_types, $meta_fields );
	$meta->register_meta_fields();
}

// 2. Serve llms.txt at the root.
add_action(
	'template_redirect',
	function () {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request_uri = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

		if ( ! apply_filters( 'gg_optimizer_llms_enabled', true ) ) {
			return;
		}

		if ( 'llms.txt' === wp_basename( $request_uri ) ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			gg_optimizer_output_llms_txt();
			exit;
		}
	}
);

if ( ! function_exists( 'gg_optimizer_llms_normalize_text' ) ) {
	/**
	 * Normalize text content for LLMs context output.
	 *
	 * Strips shortcodes, HTML tags, and decodes HTML entities.
	 *
	 * @param string $text Raw text content.
	 * @return string Normalized plain text.
	 */
	function gg_optimizer_llms_normalize_text( $text ) {
		$text = (string) $text;
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text, true );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = (string) preg_replace( '/\s+/', ' ', $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'gg_optimizer_get_llms_context' ) ) {
	/**
	 * Generate the auto‑generated context portion of llms.txt.
	 *
	 * Used as a fallback when no stored override exists, and for Reset.
	 *
	 * @return string
	 */
	function gg_optimizer_get_llms_context() {
		$lines = array();

		$site_title = gg_optimizer_llms_normalize_text( get_bloginfo( 'name', 'raw' ) );
		$site_desc  = gg_optimizer_llms_normalize_text( get_bloginfo( 'description', 'raw' ) );
		$site_url   = home_url( '/' );

		$lines[] = '# ' . $site_title;
		$lines[] = '';

		if ( '' !== $site_desc ) {
			$lines[] = '> ' . $site_desc;
			$lines[] = '';
		}

		$home = get_post( get_option( 'page_on_front' ) );

		if ( $home ) {
			$summary = get_the_excerpt( $home );

			if ( '' === trim( $summary ) ) {
				$summary = wp_trim_words( $home->post_content, 40 );
			}

			$summary = gg_optimizer_llms_normalize_text( $summary );
			$lines[] = sprintf( '- [Home](%1$s): %2$s', esc_url( $site_url ), $summary );
			$lines[] = '';
		}

		$defaults = array(
			'## Core Architecture',
			'- Gives developers full control of AI pipelines natively inside WordPress.',
			'- Agnostic database and language model registry integrations.',
			'- Full data telemetry control from top to bottom.',
			'',
			'## Key Specifications',
			'- Designed to support the enterprise shift to agentic web applications.',
			'- Bridges core WordPress data structures with modern AI engineering protocols.',
			'- Features a decoupled dual-layer storage design using PostgreSQL for vector operations alongside native WordPress content infrastructures.',
		);

		$lines = array_merge( $lines, $defaults );

		return implode( "\n", $lines );
	}
}

if ( ! function_exists( 'gg_optimizer_get_llms_key_documents' ) ) {
	/**
	 * Generate the Key Documents section for llms.txt.
	 *
	 * Description priority: unsaved_descriptions > stored _gg_optimizer_llms_description > excerpt > trim_words(content, 20)
	 *
	 * @param array $unsaved_toggles      Associative array of post_id => bool toggle overrides.
	 * @param array $unsaved_descriptions Associative array of post_id => string description overrides.
	 * @return string
	 */
	function gg_optimizer_get_llms_key_documents( $unsaved_toggles = array(), $unsaved_descriptions = array() ) {
		$output = "## Key Documents\n";

		$args  = array(
			'post_type'           => get_post_types( array( 'public' => true ), 'names' ),
			'posts_per_page'      => -1,
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = (int) get_the_ID();

				if ( isset( $unsaved_toggles[ $post_id ] ) ) {
					$include_in_llms = ! empty( $unsaved_toggles[ $post_id ] );
				} else {
					$include_in_llms = get_post_meta( $post_id, '_gg_optimizer_include_in_llms', true );
				}

				if ( empty( $include_in_llms ) ) {
					continue;
				}

				$title = gg_optimizer_llms_normalize_text( get_the_title() );
				$url   = esc_url( get_permalink() );

				// Description: unsaved > stored custom > auto-gen from excerpt > auto-gen from content.
				if ( isset( $unsaved_descriptions[ $post_id ] ) && '' !== $unsaved_descriptions[ $post_id ] ) {
					$excerpt = gg_optimizer_llms_normalize_text( $unsaved_descriptions[ $post_id ] );
				} else {
					$custom_desc = get_post_meta( $post_id, '_gg_optimizer_llms_description', true );

					if ( '' !== trim( $custom_desc ) ) {
						$excerpt = gg_optimizer_llms_normalize_text( $custom_desc );
					} else {
						$excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 20 );
						$excerpt = gg_optimizer_llms_normalize_text( $excerpt );
					}
				}

				$output .= sprintf( "- [%s](%s): %s\n", $title, $url, $excerpt );
			}

			wp_reset_postdata();
		}

		return $output;
	}
}

if ( ! function_exists( 'gg_optimizer_output_llms_txt' ) ) {
	/**
	 * Output full llms.txt content.
	 *
	 * @param string|null $override_context   Optional runtime context override. Null = use DB.
	 * @param array       $unsaved_toggles    Associative array of post_id => bool toggle overrides.
	 * @param array       $unsaved_descriptions Associative array of post_id => string description overrides.
	 * @return void
	 */
	function gg_optimizer_output_llms_txt( $override_context = null, $unsaved_toggles = array(), $unsaved_descriptions = array() ) {
		if ( null === $override_context ) {
			$context = GG_Optimizer_DB::get( 'llms_override', '' );
		} else {
			$context = $override_context;
		}

		if ( '' !== $context ) {
			echo $context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/plain response
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/plain response
			echo gg_optimizer_get_llms_context();
		}

		echo "\n\n";

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text/plain response
		echo gg_optimizer_get_llms_key_documents( $unsaved_toggles, $unsaved_descriptions );
		$settings     = json_decode( GG_Optimizer_DB::get( 'sitemap_settings', '{}' ), true );
		$sitemap_urls = isset( $settings['sitemap_urls'] ) && is_array( $settings['sitemap_urls'] )
			? $settings['sitemap_urls']
			: array( '/wp-sitemap.xml' );
		foreach ( $sitemap_urls as $path ) {
			echo "\nSitemap: " . esc_url( home_url( $path ) ) . "\n";
		}
	}
}

if ( ! function_exists( 'gg_optimizer_output_llms_head_link' ) ) {
	/**
	 * Output a <link> element in the document head pointing to /llms.txt.
	 *
	 * @return void
	 */
	function gg_optimizer_output_llms_head_link() {
		if ( ! apply_filters( 'gg_optimizer_llms_enabled', true ) ) {
			return;
		}

		echo '<link rel="help" type="text/plain" href="' . esc_url( home_url( '/llms.txt' ) ) . '" title="LLMs Context Summary">' . "\n";
	}
}

// 3. REST endpoints for the llms override and preview.
add_action(
	'rest_api_init',
	function () {
		// GET + POST /llms-override — read and save the context override.
		register_rest_route(
			'gg-optimizer/v1',
			'/llms-override',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'callback'            => function () {
						$override = GG_Optimizer_DB::get( 'llms_override', '' );
						$context  = '' !== $override ? $override : gg_optimizer_get_llms_context();

						return rest_ensure_response(
							array(
								'llms_override' => $override,
								'llms_context'  => $context,
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
						'llms_override' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
					'callback'            => function ( $request ) {
						$text = $request->get_param( 'llms_override' );

						if ( null === $text ) {
							$text = '';
						}

						// Strip everything from ## Key Documents onward as a safety measure.
						if ( preg_match( '/^## Key Documents$/m', $text, $matches, PREG_OFFSET_CAPTURE ) && isset( $matches[0][1] ) ) {
							$text = rtrim( substr( $text, 0, (int) $matches[0][1] ) );
						}

						GG_Optimizer_DB::set( 'llms_override', $text );

						return rest_ensure_response( array( 'success' => true ) );
					},
				),
			)
		);

		// POST /gg-optimizer/v1/llms-preview — render full llms.txt with given context and return as text.
		register_rest_route(
			'gg-optimizer/v1',
			'/llms-preview',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'llms_override'        => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'unsaved_toggles'      => array(
						'required' => false,
						'type'     => 'object',
					),
					'unsaved_descriptions' => array(
						'required' => false,
						'type'     => 'object',
					),
				),
				'callback'            => function ( $request ) {
					$context = $request->get_param( 'llms_override' );

					if ( null === $context ) {
						$context = '';
					}

					$unsaved_toggles = $request->get_param( 'unsaved_toggles' );

					if ( ! is_array( $unsaved_toggles ) ) {
						$unsaved_toggles = array();
					}

					$unsaved_descriptions = $request->get_param( 'unsaved_descriptions' );

					if ( ! is_array( $unsaved_descriptions ) ) {
						$unsaved_descriptions = array();
					}

					ob_start();
					gg_optimizer_output_llms_txt( $context, $unsaved_toggles, $unsaved_descriptions );
					$llms_txt = ob_get_clean();

					return rest_ensure_response( array( 'llms_txt' => $llms_txt ) );
				},
			)
		);
	}
);
