<?php
defined( 'ABSPATH' ) || exit;
/**
 * Social card metadata output (Open Graph and Twitter).
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

/**
 * Register social image sizes for Open Graph and Twitter Cards.
 *
 * @return void
 */
function gg_optimizer_register_image_sizes() {
	add_image_size(
		'gg_optimizer_og',
		apply_filters( 'gg_optimizer_og_image_width', 1200 ),
		apply_filters( 'gg_optimizer_og_image_height', 630 ),
		apply_filters( 'gg_optimizer_og_image_crop', true )
	);
}
add_action( 'after_setup_theme', 'gg_optimizer_register_image_sizes' );

if ( ! function_exists( 'gg_optimizer_get_og_locale' ) ) {
	/**
	 * Resolve OG locale from site locale.
	 *
	 * @return string
	 */
	function gg_optimizer_get_og_locale() {
		$locale = str_replace( '-', '_', (string) get_locale() );
		return (string) apply_filters( 'gg_optimizer_meta_og_locale', $locale );
	}
}

if ( ! function_exists( 'gg_optimizer_get_social_image_data' ) ) {
	/**
	 * Resolve social image data for OG/Twitter cards.
	 *
	 * Priority:
	 * 1) Explicit override in $overrides['meta_image_id'].
	 * 2) Saved _gg_optimizer_meta_image post meta.
	 * 3) Explicit override in $overrides['featured_media_id'].
	 * 4) Post featured image.
	 *
	 * @param int   $post_id   Post ID.
	 * @param array $overrides Optional runtime overrides.
	 * @return array{id:int,url:string,alt:string,width:int,height:int,type:string}
	 */
	function gg_optimizer_get_social_image_data( $post_id, $overrides = array() ) {
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

		$attachment_id = isset( $overrides['meta_image_id'] )
			? absint( $overrides['meta_image_id'] )
			: absint( get_post_meta( $post_id, '_gg_optimizer_meta_image', true ) );

		if ( $attachment_id <= 0 ) {
			$attachment_id = isset( $overrides['featured_media_id'] )
				? absint( $overrides['featured_media_id'] )
				: absint( get_post_thumbnail_id( $post_id ) );
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

if ( ! function_exists( 'gg_optimizer_output_og_meta' ) ) {
	/**
	 * Output Open Graph and Twitter card meta tags.
	 *
	 * @filter gg_optimizer_meta_output_og      - Set to false to disable Open Graph output. Default true.
	 * @filter gg_optimizer_meta_output_twitter - Set to false to disable Twitter card output. Default true.
	 *
	 * @return void
	 */
	function gg_optimizer_output_og_meta() {
		$post  = get_queried_object();
		$title = gg_optimizer_get_platform_title( $post instanceof WP_Post ? $post : null, 'og' );
		$desc  = gg_optimizer_get_platform_description( $post instanceof WP_Post ? $post : null, 'og' );

		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_meta_output_og', '__return_false' );
		$output_og = (bool) apply_filters( 'gg_optimizer_meta_output_og', true );

		// phpcs:ignore -- Example usage documentation.
		// Usage: add_filter( 'gg_optimizer_meta_output_twitter', '__return_false' );
		$output_twitter = (bool) apply_filters( 'gg_optimizer_meta_output_twitter', true );

		if ( ! $output_og && ! $output_twitter ) {
			return;
		}

		// Keep social description aligned with the same canonical provenance policy.
		$description       = gg_optimizer_get_platform_description( $post instanceof WP_Post ? $post : null, 'og' );
		$type              = ( is_singular() && ! is_page() ) ? 'article' : 'website';
		$url               = ( $post instanceof WP_Post ) ? get_permalink( $post ) : home_url( '/' );
		$og_image_data     = ( $post instanceof WP_Post ) ? gg_optimizer_get_platform_image( $post->ID, 'og' ) : array(
			'id'     => 0,
			'url'    => '',
			'alt'    => '',
			'width'  => 0,
			'height' => 0,
			'type'   => '',
		);
		$og_image          = $og_image_data['url'];
		$og_image_alt      = $og_image_data['alt'];
		$og_image_width    = $og_image_data['width'];
		$og_image_height   = $og_image_data['height'];
		$site              = (string) get_bloginfo( 'name' );
		$tw_card           = '' !== $og_image ? 'summary_large_image' : 'summary';
		$locale            = gg_optimizer_get_og_locale();
		$twitter_site      = trim( (string) apply_filters( 'gg_optimizer_meta_twitter_site', '' ) );
		$article_publisher = trim( (string) apply_filters( 'gg_optimizer_meta_article_publisher', '' ) );

		// Twitter-specific overrides.
		$tw_title       = gg_optimizer_get_platform_title( $post instanceof WP_Post ? $post : null, 'twitter' );
		$tw_description = gg_optimizer_get_platform_description( $post instanceof WP_Post ? $post : null, 'twitter' );
		$tw_image_data  = ( $post instanceof WP_Post ) ? gg_optimizer_get_platform_image( $post->ID, 'twitter' ) : array(
			'id'     => 0,
			'url'    => $og_image,
			'alt'    => $og_image_alt,
			'width'  => $og_image_width,
			'height' => $og_image_height,
			'type'   => '',
		);
		$tw_image       = $tw_image_data['url'];
		$tw_image_alt   = $tw_image_data['alt'];
		$tw_card        = '' !== $tw_image ? 'summary_large_image' : 'summary';

		if ( $output_og ) {
			if ( '' !== $locale ) {
				echo '<meta property="og:locale" content="' . esc_attr( $locale ) . '" />' . "\n";
			}
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
			echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
			echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( $site ) . '" />' . "\n";

			if ( '' !== $og_image ) {
				echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
				if ( $og_image_width > 0 ) {
					echo '<meta property="og:image:width" content="' . esc_attr( (string) $og_image_width ) . '" />' . "\n";
				}
				if ( $og_image_height > 0 ) {
					echo '<meta property="og:image:height" content="' . esc_attr( (string) $og_image_height ) . '" />' . "\n";
				}
				if ( '' !== $og_image_alt ) {
					echo '<meta property="og:image:alt" content="' . esc_attr( $og_image_alt ) . '" />' . "\n";
				}
			}

			if ( 'article' === $type && '' !== $article_publisher ) {
				echo '<meta property="article:publisher" content="' . esc_url( $article_publisher ) . '" />' . "\n";
			}
		}

		if ( $output_twitter ) {
			echo '<!-- Twitter -->' . "\n";
			echo '<meta name="twitter:card" content="' . esc_attr( $tw_card ) . '" />' . "\n";
			if ( '' !== $twitter_site ) {
				echo '<meta name="twitter:site" content="' . esc_attr( $twitter_site ) . '" />' . "\n";
			}
			echo '<meta name="twitter:title" content="' . esc_attr( $tw_title ) . '" />' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( $tw_description ) . '" />' . "\n";

			if ( '' !== $tw_image ) {
				echo '<meta name="twitter:image" content="' . esc_url( $tw_image ) . '" />' . "\n";
				if ( '' !== $tw_image_alt ) {
					echo '<meta name="twitter:image:alt" content="' . esc_attr( $tw_image_alt ) . '" />' . "\n";
				}
			}
		}

		if ( $output_og && 'article' === $type && $post instanceof WP_Post ) {
			echo '<meta property="article:published_time" content="' . esc_attr( get_post_time( 'c', true, $post ) ) . '" />' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_post_modified_time( 'c', true, $post ) ) . '" />' . "\n";
		}
	}
}
