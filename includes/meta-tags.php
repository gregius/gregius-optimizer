<?php
/**
 * Meta tags for search and social cards.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
// Custom_Meta_Field is loaded from gregius-optimizer.php if not already available.

add_action( 'init', 'gg_optimizer_register_smo_meta_fields' );

/**
 * Register social meta override fields for all public post types.
 *
 * @return void
 */
function gg_optimizer_register_smo_meta_fields() {
	if ( ! class_exists( 'GG_Optimizer_Custom_Meta_Field' ) ) {
		return;
	}

	$post_types  = get_post_types( array( 'public' => true ), 'names' );
	$meta_fields = array(
		'_gg_optimizer_meta_title'       => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Social Meta Title', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for social and search preview title.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_meta_description' => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Social Meta Description', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for social and search preview description.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_meta_image'       => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Social Meta Image', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for social and search preview image (attachment ID).', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_google_title'        => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Google Search Title', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for Google search result title.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_google_description'  => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Google Search Description', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for Google search result description.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_og_title'            => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Open Graph Title', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for og:title.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_og_description'      => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Open Graph Description', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for og:description.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_og_image'            => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Open Graph Image', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for og:image (attachment ID).', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_twitter_title'       => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Twitter Title', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for twitter:title.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_twitter_description' => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Twitter Description', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for twitter:description.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_twitter_image'       => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Twitter Image', 'gregius-optimizer' ),
			'description'       => __( 'Optional override for twitter:image (attachment ID).', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
		'_gg_optimizer_schema_subtype'      => array(
			'type'              => 'string',
			'default'           => '',
			'label'             => __( 'Schema Subtype', 'gregius-optimizer' ),
			'description'       => __( 'Override schema.org type for this post.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
	);

	$meta = new GG_Optimizer_Custom_Meta_Field( $post_types, $meta_fields );
	$meta->register_meta_fields();
}

if ( ! function_exists( 'gg_optimizer_meta_normalize_text' ) ) {
	/**
	 * Normalize meta text for social and search tags.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	function gg_optimizer_meta_normalize_text( $text ) {
		$text = (string) $text;
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text, true );
		$text = (string) preg_replace( '/\s+/', ' ', $text );
		return trim( $text );
	}
}

if ( ! function_exists( 'gg_optimizer_get_meta_title' ) ) {
	/**
	 * Resolve canonical meta title.
	 *
	 * Priority:
	 * 1) Explicit override passed in $overrides['meta_title'].
	 * 2) Saved _gg_optimizer_meta_title post meta.
	 * 3) wp_get_document_title().
	 *
	 * @param WP_Post|null $post      Post object.
	 * @param array        $overrides Optional runtime overrides.
	 * @return string
	 */
	function gg_optimizer_get_meta_title( $post = null, $overrides = array() ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		$override_title = '';

		if ( isset( $overrides['meta_title'] ) ) {
			$override_title = gg_optimizer_meta_normalize_text( $overrides['meta_title'] );
		}

		if ( '' === $override_title && $post instanceof WP_Post ) {
			$override_title = gg_optimizer_meta_normalize_text( get_post_meta( $post->ID, '_gg_optimizer_meta_title', true ) );
		}

		if ( '' === $override_title && $post instanceof WP_Post ) {
			$override_title = gg_optimizer_meta_normalize_text( get_post_meta( $post->ID, '_gg_optimizer_google_title', true ) );
		}

		if ( '' !== $override_title ) {
			return $override_title;
		}

		$title = wp_get_document_title();
		return gg_optimizer_meta_normalize_text( $title );
	}
}

if ( ! function_exists( 'gg_optimizer_get_meta_description' ) ) {
	/**
	 * Build a meta description using a deterministic provenance policy.
	 *
	 * Provenance priority:
	 * 1) Post excerpt.
	 * 2) Post content when excerpt is empty.
	 * 3) Site tagline when no post context exists.
	 *
	 * Normalization policy:
	 * - Strip shortcodes and HTML tags.
	 * - Collapse whitespace and trim.
	 * - Truncate to 155 characters with ellipsis.
	 *
	 * @param WP_Post|null $post      Post object.
	 * @param array        $overrides Optional runtime overrides.
	 * @return string
	 */
	function gg_optimizer_get_meta_description( $post = null, $overrides = array() ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		$override_description = '';
		if ( isset( $overrides['meta_description'] ) ) {
			$override_description = gg_optimizer_meta_normalize_text( $overrides['meta_description'] );
		}

		if ( '' === $override_description && $post instanceof WP_Post ) {
			$override_description = gg_optimizer_meta_normalize_text( get_post_meta( $post->ID, '_gg_optimizer_meta_description', true ) );
		}

		if ( '' === $override_description && $post instanceof WP_Post ) {
			$override_description = gg_optimizer_meta_normalize_text( get_post_meta( $post->ID, '_gg_optimizer_google_description', true ) );
		}

		// Respect explicit user-entered override value as-is (normalized, no default truncation).
		if ( '' !== $override_description ) {
			return $override_description;
		}

		$description = '';

		if ( $post instanceof WP_Post ) {
			if ( isset( $overrides['excerpt'] ) && '' !== trim( (string) $overrides['excerpt'] ) ) {
				$description = (string) $overrides['excerpt'];
			} else {
				$description = (string) get_the_excerpt( $post );
			}

			if ( '' === trim( $description ) ) {
				if ( isset( $overrides['content'] ) && '' !== trim( (string) $overrides['content'] ) ) {
					$description = (string) $overrides['content'];
				} else {
					$description = (string) $post->post_content;
				}
			}
		} else {
			$description = (string) get_bloginfo( 'description' );
		}

		$description = gg_optimizer_meta_normalize_text( $description );

		if ( '' === $description ) {
			return '';
		}

		return wp_html_excerpt( $description, 155, '...' );
	}
}

if ( ! function_exists( 'gg_optimizer_get_platform_title' ) ) {
	/**
	 * Resolve title for a specific platform with fallback chain.
	 *
	 * Priority:
	 * 1) Platform-specific post meta (_gg_optimizer_{platform}_title).
	 * 2) Common _gg_optimizer_meta_title.
	 * 3) wp_get_document_title().
	 *
	 * @param WP_Post|null $post     Post object.
	 * @param string       $platform 'google', 'og', or 'twitter'.
	 * @param array        $overrides Optional runtime overrides keyed by platform (e.g. google_title, og_title).
	 * @return string
	 */
	function gg_optimizer_get_platform_title( $post = null, $platform = '', $overrides = array() ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		if ( ! $post instanceof WP_Post ) {
			return gg_optimizer_get_meta_title( $post, $overrides );
		}

		$override_key = $platform . '_title';
		if ( isset( $overrides[ $override_key ] ) && '' !== $overrides[ $override_key ] ) {
			return gg_optimizer_meta_normalize_text( $overrides[ $override_key ] );
		}

		$platform_value = get_post_meta( $post->ID, "_gg_optimizer_{$platform}_title", true );
		if ( '' !== $platform_value ) {
			return gg_optimizer_meta_normalize_text( $platform_value );
		}

		return gg_optimizer_get_meta_title( $post, $overrides );
	}
}

if ( ! function_exists( 'gg_optimizer_get_platform_description' ) ) {
	/**
	 * Resolve description for a specific platform with fallback chain.
	 *
	 * Priority:
	 * 1) Platform-specific post meta (_gg_optimizer_{platform}_description).
	 * 2) Common _gg_optimizer_meta_description.
	 * 3) Excerpt → content → site tagline.
	 *
	 * @param WP_Post|null $post     Post object.
	 * @param string       $platform 'google', 'og', or 'twitter'.
	 * @param array        $overrides Optional runtime overrides.
	 * @return string
	 */
	function gg_optimizer_get_platform_description( $post = null, $platform = '', $overrides = array() ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		if ( ! $post instanceof WP_Post ) {
			return gg_optimizer_get_meta_description( $post, $overrides );
		}

		$override_key = $platform . '_description';
		if ( isset( $overrides[ $override_key ] ) && '' !== $overrides[ $override_key ] ) {
			return gg_optimizer_meta_normalize_text( $overrides[ $override_key ] );
		}

		$platform_value = get_post_meta( $post->ID, "_gg_optimizer_{$platform}_description", true );
		if ( '' !== $platform_value ) {
			return gg_optimizer_meta_normalize_text( $platform_value );
		}

		return gg_optimizer_get_meta_description( $post, $overrides );
	}
}

if ( ! function_exists( 'gg_optimizer_get_platform_image' ) ) {
	/**
	 * Resolve social image for a specific platform with fallback chain.
	 *
	 * Priority:
	 * 1) Platform-specific post meta (_gg_optimizer_{platform}_image).
	 * 2) Downward platform fallback (twitter → og → common).
	 * 3) _gg_optimizer_meta_image.
	 * 4) Featured image.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $platform 'og' or 'twitter'.
	 * @param array  $overrides Optional runtime overrides.
	 * @return array{id:int,url:string,alt:string,width:int,height:int,type:string}
	 */
	function gg_optimizer_get_platform_image( $post_id, $platform = '', $overrides = array() ) {
		$image_data = array(
			'id'     => 0,
			'url'    => '',
			'alt'    => '',
			'width'  => 0,
			'height' => 0,
			'type'   => '',
		);

		if ( $post_id <= 0 ) {
			return $image_data;
		}

		$attachment_id = 0;

		if ( isset( $overrides[ $platform . '_image_id' ] ) ) {
			$attachment_id = absint( $overrides[ $platform . '_image_id' ] );
		}

		if ( $attachment_id <= 0 ) {
			$attachment_id = absint( get_post_meta( $post_id, "_gg_optimizer_{$platform}_image", true ) );
		}

		// Fall back to featured image.
		if ( $attachment_id <= 0 ) {
			$attachment_id = isset( $overrides['featured_media_id'] )
				? absint( $overrides['featured_media_id'] )
				: absint( get_post_thumbnail_id( $post_id ) );
		}

		// Fall back to common meta image (Global Image).
		if ( $attachment_id <= 0 ) {
			if ( isset( $overrides['meta_image_id'] ) ) {
				$attachment_id = absint( $overrides['meta_image_id'] );
			}
			if ( $attachment_id <= 0 ) {
				$attachment_id = absint( get_post_meta( $post_id, '_gg_optimizer_meta_image', true ) );
			}
		}

		if ( $attachment_id <= 0 ) {
			return $image_data;
		}

		$image_url = wp_get_attachment_image_url( $attachment_id, 'gg_optimizer_og' );
		if ( ! $image_url ) {
			$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		}

		if ( ! $image_url ) {
			return $image_data;
		}

		$image_data['id']  = $attachment_id;
		$image_data['url'] = $image_url;
		$image_data['alt'] = gg_optimizer_meta_normalize_text( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

		$image_meta = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $image_meta ) ) {
			$image_data['width']  = isset( $image_meta['width'] ) ? absint( $image_meta['width'] ) : 0;
			$image_data['height'] = isset( $image_meta['height'] ) ? absint( $image_meta['height'] ) : 0;
		}

		$image_data['type'] = (string) get_post_mime_type( $attachment_id );

		return $image_data;
	}
}

// Apply Google-specific title override to the document <title> tag.
if ( ! function_exists( 'gg_optimizer_filter_document_title' ) ) {
	/**
	 * Filter document title to use Google-specific override when set.
	 *
	 * @param string $title The document title.
	 * @return string
	 */
	function gg_optimizer_filter_document_title( $title ) {
		if ( is_admin() || ! is_singular() ) {
			return $title;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return $title;
		}

		$google_title = get_post_meta( $post->ID, '_gg_optimizer_google_title', true );
		if ( '' !== $google_title ) {
			return gg_optimizer_meta_normalize_text( $google_title );
		}

		return $title;
	}
}
add_filter( 'pre_get_document_title', 'gg_optimizer_filter_document_title', 10, 1 );

if ( ! function_exists( 'gg_optimizer_get_metadata_context' ) ) {
	/**
	 * Resolve and return a unified metadata context for a given post.
	 *
	 * All emitters — search tags, social cards, schema JSON-LD, and REST preview —
	 * MUST draw their shared content fields from this single resolver to prevent
	 * parity drift across channels.
	 *
	 * Intentional divergences that remain outside this context:
	 * - Schema 'headline' uses raw post title without the site name suffix.
	 * - Schema '@type' (WebPage vs BlogPosting) is schema-internal, not derived from og_type.
	 *
	 * @param WP_Post|null $post      Post object. Falls back to queried object when null.
	 * @param array        $overrides Optional runtime overrides (meta_title, meta_description,
	 *                                meta_image_id, featured_media_id, excerpt, content).
	 * @return array{
	 *     title: string,
	 *     description: string,
	 *     canonical: string,
	 *     og_url: string,
	 *     og_type: string,
	 *     site_name: string,
	 *     locale: string,
	 *     image: array{id: int, url: string, alt: string, width: int, height: int, type: string}
	 * }
	 */
	function gg_optimizer_get_metadata_context( $post = null, $overrides = array() ) {
		if ( null === $post ) {
			$post = get_queried_object();
		}

		$post_obj    = $post instanceof WP_Post ? $post : null;
		$title       = gg_optimizer_get_meta_title( $post_obj, $overrides );
		$description = gg_optimizer_get_meta_description( $post_obj, $overrides );
		$canonical   = function_exists( 'gg_optimizer_get_canonical_url' )
			? gg_optimizer_get_canonical_url( $post_obj )
			: ( $post_obj ? (string) get_permalink( $post_obj ) : home_url( '/' ) );
		$og_url      = $post_obj ? (string) get_permalink( $post_obj ) : home_url( '/' );
		$og_type     = $post_obj
			? ( 'page' !== get_post_type( $post_obj ) ? 'article' : 'website' )
			: ( ( is_singular() && ! is_page() ) ? 'article' : 'website' );
		$site_name   = (string) get_bloginfo( 'name' );
		$locale      = function_exists( 'gg_optimizer_get_og_locale' )
			? gg_optimizer_get_og_locale()
			: str_replace( '-', '_', (string) get_locale() );
		$image       = ( $post_obj && function_exists( 'gg_optimizer_get_social_image_data' ) )
			? gg_optimizer_get_social_image_data( $post_obj->ID, $overrides )
			: array( 'id' => 0, 'url' => '', 'alt' => '', 'width' => 0, 'height' => 0, 'type' => '' );

		return array(
			'title'       => $title,
			'description' => $description,
			'canonical'   => $canonical,
			'og_url'      => $og_url,
			'og_type'     => $og_type,
			'site_name'   => $site_name,
			'locale'      => $locale,
			'image'       => $image,
		);
	}
}

if ( ! function_exists( 'gg_optimizer_output_head_meta' ) ) {
	/**
	 * Output all custom metadata tags in a single organized head block.
	 *
	 * @return void
	 */
	function gg_optimizer_output_head_meta() {
		if ( is_admin() ) {
			return;
		}

		if ( ! GG_Optimizer_Feature_Toggle::is_enabled( 'social_cards' )
			&& ! GG_Optimizer_Feature_Toggle::is_enabled( 'robots' )
			&& ! GG_Optimizer_Feature_Toggle::is_enabled( 'schema' )
			&& ! GG_Optimizer_Feature_Toggle::is_enabled( 'llms' )
		) {
			return;
		}

		if ( GG_Optimizer_Feature_Toggle::is_enabled( 'social_cards' )
			|| GG_Optimizer_Feature_Toggle::is_enabled( 'robots' )
		) {
			echo '<!-- Meta description, Canonical URL & Robots directives -->' . "\n";
		}

		if ( function_exists( 'gg_optimizer_output_meta_description' ) ) {
			gg_optimizer_output_meta_description();
		}

		if ( function_exists( 'gg_optimizer_output_canonical_link' ) ) {
			gg_optimizer_output_canonical_link();
		}

		if ( function_exists( 'gg_optimizer_output_robots_meta' ) ) {
			gg_optimizer_output_robots_meta();
		}

		if ( GG_Optimizer_Feature_Toggle::is_enabled( 'social_cards' ) ) {
			echo '<!-- Open Graph & Twitter Cards meta -->' . "\n";
		}

		if ( function_exists( 'gg_optimizer_output_og_meta' ) ) {
			gg_optimizer_output_og_meta();
		}

		if ( GG_Optimizer_Feature_Toggle::is_enabled( 'llms' ) ) {
			echo '<!-- llms.txt AI agent context -->' . "\n";
		}

		if ( function_exists( 'gg_optimizer_output_llms_head_link' ) ) {
			gg_optimizer_output_llms_head_link();
		}

		if ( GG_Optimizer_Feature_Toggle::is_enabled( 'schema' ) ) {
			echo '<!-- Schema.org JSON-LD Graph -->' . "\n";
		}

		if ( function_exists( 'gg_optimizer_schema_output_json_ld' ) ) {
			gg_optimizer_schema_output_json_ld();
		}
	}

	add_action( 'wp_head', 'gg_optimizer_output_head_meta', 1 );
}

// Add permission callback for REST API.
if ( ! function_exists( 'gg_optimizer_meta_preview_permission' ) ) {
	/**
	 * Check permission for meta preview endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	function gg_optimizer_meta_preview_permission( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'postId' ) );
		return $post_id > 0 && current_user_can( 'edit_post', $post_id );
	}
}

// Add REST API callback for meta preview.
if ( ! function_exists( 'gg_optimizer_rest_meta_preview' ) ) {
	/**
	 * Build social meta preview payload from canonical resolver helpers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	function gg_optimizer_rest_meta_preview( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'postId' ) );
		$post    = get_post( $post_id );

		if ( ! ( $post instanceof WP_Post ) ) {
			return new WP_Error( 'gg_optimizer_meta_preview_not_found', __( 'Post not found.', 'gregius-optimizer' ), array( 'status' => 404 ) );
		}

		$overrides = array(
			'meta_title'        => sanitize_text_field( (string) $request->get_param( 'metaTitle' ) ),
			'meta_description'  => sanitize_textarea_field( (string) $request->get_param( 'metaDescription' ) ),
			'meta_image_id'     => absint( $request->get_param( 'metaImageId' ) ),
			'featured_media_id' => absint( $request->get_param( 'featuredMediaId' ) ),
			'excerpt'           => sanitize_textarea_field( (string) $request->get_param( 'excerpt' ) ),
			'content'           => sanitize_textarea_field( (string) $request->get_param( 'content' ) ),
		);

		$context      = gg_optimizer_get_metadata_context( $post, $overrides );
		$title        = $context['title'];
		$description  = $context['description'];
		$url          = $context['og_url'];
		$canonical    = $context['canonical'];
		$site_name    = $context['site_name'];
		$type         = $context['og_type'];
		$locale       = $context['locale'];
		$image_data   = $context['image'];
		$image        = $image_data['url'];
		$image_alt    = $image_data['alt'];
		$image_width  = $image_data['width'];
		$image_height = $image_data['height'];
		$tw_card           = '' !== $image ? 'summary_large_image' : 'summary';
		$twitter_site      = trim( (string) apply_filters( 'gg_optimizer_meta_twitter_site', '' ) );
		$article_publisher = trim( (string) apply_filters( 'gg_optimizer_meta_article_publisher', '' ) );

		$response = array(
			'title'       => $title,
			'description' => $description,
			'url'         => $url,
			'image'       => $image,
			'imageAlt'    => $image_alt,
			'ogType'      => $type,
			'twitterCard' => $tw_card,
			'siteName'    => $site_name,
			'tags'        => array(
				'description'         => $description,
				'canonical'           => $canonical,
				'og:locale'           => $locale,
				'og:title'            => $title,
				'og:description'      => $description,
				'og:type'             => $type,
				'og:url'              => $url,
				'og:site_name'        => $site_name,
				'og:image'            => $image,
				'og:image:width'      => $image_width,
				'og:image:height'     => $image_height,
				'twitter:card'        => $tw_card,
				'twitter:site'        => $twitter_site,
				'twitter:title'       => $title,
				'twitter:description' => $description,
				'twitter:image'       => $image,
			),
		);

		if ( 'article' === $type && '' !== $article_publisher ) {
			$response['tags']['article:publisher'] = $article_publisher;
		}

		if ( '' !== $image_alt ) {
			$response['tags']['og:image:alt']      = $image_alt;
			$response['tags']['twitter:image:alt'] = $image_alt;
		}

		return rest_ensure_response( $response );
	}
}

// Register REST API route.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gg-optimizer/v1',
			'/meta-preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'gg_optimizer_rest_meta_preview',
				'permission_callback' => 'gg_optimizer_meta_preview_permission',
				'args'                => array(
					'postId'          => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'metaTitle'       => array(
						'required' => false,
						'type'     => 'string',
					),
					'metaDescription' => array(
						'required' => false,
						'type'     => 'string',
					),
					'metaImageId'     => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'featuredMediaId' => array(
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'excerpt'         => array(
						'required' => false,
						'type'     => 'string',
					),
					'content'         => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);
	}
);
