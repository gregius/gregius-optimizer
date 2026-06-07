<?php
defined( 'ABSPATH' ) || exit;
/**
 * Sitemap optimization controls.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

// Custom_Meta_Field is loaded from gregius-optimizer.php if not already available.

add_action( 'init', 'gg_optimizer_register_hide_meta' );

function gg_optimizer_register_hide_meta() {
	if ( ! class_exists( 'GG_Optimizer_Custom_Meta_Field' ) ) {
		return;
	}

	$post_types  = get_post_types( array( 'public' => true ), 'names' );
	$meta_fields = array(
		'_gg_optimizer_hide_from_search' => array(
			'type'              => 'boolean',
			'default'           => false,
			'label'             => __( 'Hide page from search engines', 'gregius-optimizer' ),
			'description'       => __( 'If enabled, this document will use noindex and be excluded from the sitemap.', 'gregius-optimizer' ),
			'revisions_enabled' => true,
		),
	);

	$meta = new GG_Optimizer_Custom_Meta_Field( $post_types, $meta_fields );
	$meta->register_meta_fields();
}

if ( ! function_exists( 'gg_optimizer_sitemap_disabled_taxonomies' ) ) {
	/**
	 * Get disabled taxonomies for taxonomy sitemaps.
	 *
	 * Empty array means all public taxonomies are included by default.
	 *
	 * @return array
	 */
	function gg_optimizer_sitemap_disabled_taxonomies() {
		$disabled = array();

		/**
		 * Filter disabled taxonomy sitemap providers.
		 *
		 * @param array $disabled Disabled taxonomy names.
		 */
		$disabled = array_map( 'sanitize_key', (array) apply_filters( 'gg_optimizer_sitemap_disabled_taxonomies', $disabled ) );

		$saved = json_decode( GG_Optimizer_DB::get( 'sitemap_settings', '{}' ), true );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		if ( isset( $saved['taxonomies'] ) && is_array( $saved['taxonomies'] ) ) {
			foreach ( $saved['taxonomies'] as $tax => $included ) {
				$tax = sanitize_key( $tax );
				if ( $included ) {
					$disabled = array_diff( $disabled, array( $tax ) );
				} elseif ( ! in_array( $tax, $disabled, true ) ) {
					$disabled[] = $tax;
				}
			}
		}

		return array_values( array_unique( array_filter( $disabled ) ) );
	}
}

if ( ! function_exists( 'gg_optimizer_sitemap_excluded_post_types' ) ) {
	/**
	 * Get explicitly excluded post types for post sitemaps.
	 *
	 * @return array
	 */
	function gg_optimizer_sitemap_excluded_post_types() {
		$excluded = array();

		/**
		 * Filter excluded post types for sitemap posts provider.
		 *
		 * @param array $excluded Excluded post type names.
		 */
		$excluded = array_map( 'sanitize_key', (array) apply_filters( 'gg_optimizer_sitemap_excluded_post_types', $excluded ) );

		$saved = json_decode( GG_Optimizer_DB::get( 'sitemap_settings', '{}' ), true );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		if ( isset( $saved['post_types'] ) && is_array( $saved['post_types'] ) ) {
			foreach ( $saved['post_types'] as $pt => $included ) {
				$pt = sanitize_key( $pt );
				if ( $included ) {
					$excluded = array_diff( $excluded, array( $pt ) );
				} elseif ( ! in_array( $pt, $excluded, true ) ) {
					$excluded[] = $pt;
				}
			}
		}

		return array_values( array_unique( array_filter( $excluded ) ) );
	}
}

if ( ! function_exists( 'gg_optimizer_sitemap_excluded_terms' ) ) {
	/**
	 * Get explicit term IDs to exclude per taxonomy.
	 *
	 * @return array
	 */
	function gg_optimizer_sitemap_excluded_terms() {
		$excluded = array(
			// Add taxonomy term exclusion rules via filter.
		);

		/**
		 * Filter explicit term exclude lists.
		 *
		 * @param array $excluded Map: taxonomy => term_id[].
		 */
		$raw_excluded = (array) apply_filters( 'gg_optimizer_sitemap_excluded_terms', $excluded );
		$normalized   = array();

		foreach ( $raw_excluded as $taxonomy => $term_ids ) {
			$taxonomy = sanitize_key( (string) $taxonomy );

			if ( '' === $taxonomy ) {
				continue;
			}

			$ids = array_filter( array_map( 'intval', (array) $term_ids ) );

			if ( ! empty( $ids ) ) {
				$normalized[ $taxonomy ] = array_values( array_unique( $ids ) );
			}
		}

		return $normalized;
	}
}

if ( ! function_exists( 'gg_optimizer_sitemap_enable_users_provider' ) ) {
	/**
	 * Whether to enable the users sitemap provider.
	 *
	 * Disabled by default.
	 *
	 * @return bool
	 */
	function gg_optimizer_sitemap_enable_users_provider() {
		/**
		 * Filter users sitemap provider enablement.
		 *
		 * @param bool $enabled Whether users provider is enabled.
		 */
		$enabled = (bool) apply_filters( 'gg_optimizer_sitemap_enable_users_provider', false );

		$saved = json_decode( GG_Optimizer_DB::get( 'sitemap_settings', '{}' ), true );
		if ( is_array( $saved ) && isset( $saved['authors'] ) ) {
			$enabled = (bool) $saved['authors'];
		}

		return $enabled;
	}
}

if ( ! function_exists( 'gg_optimizer_sitemap_excluded_users' ) ) {
	/**
	 * Get explicit user IDs to exclude from users sitemap.
	 *
	 * @return array
	 */
	function gg_optimizer_sitemap_excluded_users() {
		$excluded = array();

		/**
		 * Filter explicit user exclude list.
		 *
		 * @param array $excluded User IDs.
		 */
		$excluded = array_filter( array_map( 'intval', (array) apply_filters( 'gg_optimizer_sitemap_excluded_users', $excluded ) ) );

		$saved = json_decode( GG_Optimizer_DB::get( 'sitemap_settings', '{}' ), true );
		if ( is_array( $saved ) && isset( $saved['excluded_users'] ) && is_array( $saved['excluded_users'] ) ) {
			foreach ( $saved['excluded_users'] as $uid ) {
				$uid = (int) $uid;
				if ( $uid > 0 && ! in_array( $uid, $excluded, true ) ) {
					$excluded[] = $uid;
				}
			}
		}

		return array_values( array_unique( $excluded ) );
	}
}

if ( ! function_exists( 'gg_optimizer_optimize_sitemap_providers' ) ) {
	/**
	 * Disable low-signal sitemap providers.
	 *
	 * @param WP_Sitemaps_Provider|false $provider Provider object.
	 * @param string                     $name     Provider name.
	 * @return WP_Sitemaps_Provider|false
	 */
	function gg_optimizer_optimize_sitemap_providers( $provider, $name ) {
		if ( 'users' === $name && ! gg_optimizer_sitemap_enable_users_provider() ) {
			return false;
		}

		return $provider;
	}
}
add_filter( 'wp_sitemaps_add_provider', 'gg_optimizer_optimize_sitemap_providers', 10, 2 );

if ( ! function_exists( 'gg_optimizer_filter_users_sitemap_query_args' ) ) {
	/**
	 * Filter user query args for users sitemap.
	 *
	 * @param array $args User query args.
	 * @return array
	 */
	function gg_optimizer_filter_users_sitemap_query_args( $args ) {
		$excluded_users = gg_optimizer_sitemap_excluded_users();

		if ( ! empty( $excluded_users ) ) {
			$args['exclude'] = $excluded_users; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		return $args;
	}
}
add_filter( 'wp_sitemaps_users_query_args', 'gg_optimizer_filter_users_sitemap_query_args' );

if ( ! function_exists( 'gg_optimizer_filter_post_type_sitemap_post_types' ) ) {
	/**
	 * Exclude post types from sitemap.
	 *
	 * @param array $post_types Post types included in sitemaps.
	 * @return array
	 */
	function gg_optimizer_filter_post_type_sitemap_post_types( $post_types ) {
		$excluded_post_types = array_filter( gg_optimizer_sitemap_excluded_post_types() );

		if ( ! empty( $excluded_post_types ) ) {
			foreach ( $excluded_post_types as $post_type ) {
				unset( $post_types[ $post_type ] );
			}
		}

		return $post_types;
	}
}
add_filter( 'wp_sitemaps_post_types', 'gg_optimizer_filter_post_type_sitemap_post_types' );

if ( ! function_exists( 'gg_optimizer_filter_posts_sitemap_query_args' ) ) {
	/**
	 * Exclude posts by post type at query level.
	 *
	 * @param array  $args      Post query args.
	 * @param string $post_type Current post type.
	 * @return array
	 */
	function gg_optimizer_filter_posts_sitemap_query_args( $args, $post_type ) {
		$excluded_post_types = array_filter( gg_optimizer_sitemap_excluded_post_types() );

		$hide_from_search_meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => '_gg_optimizer_hide_from_search',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_gg_optimizer_hide_from_search',
				'value'   => array( '1', 'true', 'yes', 'on' ),
				'compare' => 'NOT IN',
			),
		);

		if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) && ! empty( $args['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to exclude flagged posts from sitemap output.
			$args['meta_query'] = array(
				'relation' => 'AND',
				$args['meta_query'],
				$hide_from_search_meta_query,
			);
		} else {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to exclude flagged posts from sitemap output.
			$args['meta_query'] = $hide_from_search_meta_query;
		}

		if ( in_array( $post_type, $excluded_post_types, true ) ) {
			$args['post__in'] = array( 0 );
		}

		return $args;
	}
}
add_filter( 'wp_sitemaps_posts_query_args', 'gg_optimizer_filter_posts_sitemap_query_args', 10, 2 );

if ( ! function_exists( 'gg_optimizer_filter_taxonomy_sitemap_taxonomies' ) ) {
	/**
	 * Exclude taxonomies from sitemap.
	 *
	 * @param array $taxonomies Taxonomies included in sitemap.
	 * @return array
	 */
	function gg_optimizer_filter_taxonomy_sitemap_taxonomies( $taxonomies ) {
		$disabled = array_filter( array_map( 'sanitize_key', (array) gg_optimizer_sitemap_disabled_taxonomies() ) );

		if ( ! empty( $disabled ) ) {
			foreach ( $disabled as $taxonomy_name ) {
				if ( isset( $taxonomies[ $taxonomy_name ] ) ) {
					unset( $taxonomies[ $taxonomy_name ] );
					continue;
				}

				foreach ( (array) $taxonomies as $key => $taxonomy ) {
					$current_name = '';

					if ( is_string( $taxonomy ) ) {
						$current_name = $taxonomy;
					} elseif ( is_object( $taxonomy ) && isset( $taxonomy->name ) && is_string( $taxonomy->name ) ) {
						$current_name = $taxonomy->name;
					}

					if ( $taxonomy_name === $current_name ) {
						unset( $taxonomies[ $key ] );
					}
				}
			}
		}

		return $taxonomies;
	}
}
add_filter( 'wp_sitemaps_taxonomies', 'gg_optimizer_filter_taxonomy_sitemap_taxonomies' );

if ( ! function_exists( 'gg_optimizer_filter_taxonomy_sitemap_query_args' ) ) {
	/**
	 * Filter term query args for each taxonomy sitemap.
	 *
	 * @param array  $args     Term query args.
	 * @param string $taxonomy Current taxonomy.
	 * @return array
	 */
	function gg_optimizer_filter_taxonomy_sitemap_query_args( $args, $taxonomy ) {
		$taxonomy = sanitize_key( (string) $taxonomy );

		$args['hide_empty'] = true;
		$args['orderby']    = 'name';
		$args['order']      = 'ASC';

		$excluded_terms_map = gg_optimizer_sitemap_excluded_terms();

		if ( isset( $excluded_terms_map[ $taxonomy ] ) && ! empty( $excluded_terms_map[ $taxonomy ] ) ) {
			$args['exclude'] = array_map( 'intval', (array) $excluded_terms_map[ $taxonomy ] ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		return $args;
	}
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'gg_optimizer_filter_taxonomy_sitemap_query_args', 10, 2 );

if ( ! function_exists( 'gg_optimizer_sitemap_enabled_gate' ) ) {
	/**
	 * Master gate for entire sitemap feature.
	 *
	 * phpcs:ignore -- Example usage documentation.
	 * Usage: add_filter( 'wp_sitemaps_enabled', '__return_false' );
	 *
	 * @param bool $enabled Whether sitemaps are enabled.
	 * @return bool
	 */
	function gg_optimizer_sitemap_enabled_gate( $enabled ) {
		/**
		 * Filter sitemap feature enablement.
		 *
		 * @param bool $enabled Whether sitemap is enabled.
		 */
		return (bool) apply_filters( 'gg_optimizer_sitemap_enabled', (bool) $enabled );
	}
}
add_filter( 'wp_sitemaps_enabled', 'gg_optimizer_sitemap_enabled_gate' );

/**
 * Site-specific configuration: Exclude all taxonomies from sitemap.
 *
 * Remove or modify this hook when site has sufficient taxonomy content.
 */
add_filter(
	'gg_optimizer_sitemap_disabled_taxonomies',
	function () {
		return array(
			'category',
			'post_tag',
		);
	}
);

/**
 * REST endpoint: GET / POST sitemap settings.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gg-optimizer/v1',
			'/sitemap-settings',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'callback'            => function () {
						$saved = json_decode( GG_Optimizer_DB::get( 'sitemap_settings', '{}' ), true );
						if ( ! is_array( $saved ) ) {
							$saved = array();
						}

						$post_types = array();
						foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
							if ( in_array( $pt->name, array( 'attachment', 'customize_changeset', 'nav_menu_item' ), true ) ) {
								continue;
							}
							$post_types[] = array(
								'slug'  => $pt->name,
								'label' => $pt->label ? $pt->label : $pt->name,
							);
						}

						$taxonomies = array();
						foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $tax ) {
							if ( 'post_format' === $tax->name ) {
								continue;
							}
							$taxonomies[] = array(
								'slug'  => $tax->name,
								'label' => $tax->label ? $tax->label : $tax->name,
							);
						}

						$users = array();
						$all_users = get_users(
							array(
								'capability__in' => array( 'publish_posts' ),
								'orderby'        => 'display_name',
							)
						);
						foreach ( $all_users as $user ) {
							$users[] = array(
								'id'           => $user->ID,
								'display_name' => $user->display_name,
							);
						}

						return rest_ensure_response(
							array(
								'settings'    => $saved,
								'post_types'  => $post_types,
								'taxonomies'  => $taxonomies,
								'users'       => $users,
								'sitemap_url' => home_url( '/wp-sitemap.xml' ),
							)
						);
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'callback'            => function ( $request ) {
						$settings = $request->get_param( 'settings' );
						if ( ! is_array( $settings ) ) {
							return new WP_Error(
								'gg_optimizer_invalid_data',
								__( 'settings must be an object.', 'gregius-optimizer' ),
								array( 'status' => 400 )
							);
						}

						$valid_post_types = get_post_types( array( 'public' => true ), 'names' );
						if ( isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ) {
							foreach ( $settings['post_types'] as $pt => $val ) {
								$pt = sanitize_key( (string) $pt );
								if ( ! in_array( $pt, $valid_post_types, true ) ) {
									return new WP_Error(
										'gg_optimizer_invalid_post_type',
										sprintf(
											/* translators: %s: post type slug */
											__( 'Invalid post type: %s.', 'gregius-optimizer' ),
											esc_html( $pt )
										),
										array( 'status' => 400 )
									);
								}
							}
						}

						$valid_taxonomies = get_taxonomies( array( 'public' => true ), 'names' );
						if ( isset( $settings['taxonomies'] ) && is_array( $settings['taxonomies'] ) ) {
							foreach ( $settings['taxonomies'] as $tax => $val ) {
								$tax = sanitize_key( (string) $tax );
								if ( ! in_array( $tax, $valid_taxonomies, true ) ) {
									return new WP_Error(
										'gg_optimizer_invalid_taxonomy',
										sprintf(
											/* translators: %s: taxonomy slug */
											__( 'Invalid taxonomy: %s.', 'gregius-optimizer' ),
											esc_html( $tax )
										),
										array( 'status' => 400 )
									);
								}
							}
						}

						if ( isset( $settings['excluded_users'] ) && is_array( $settings['excluded_users'] ) ) {
							foreach ( $settings['excluded_users'] as $uid ) {
								$uid = (int) $uid;
								if ( $uid <= 0 || ! get_user_by( 'ID', $uid ) ) {
									return new WP_Error(
										'gg_optimizer_invalid_user',
										sprintf(
											/* translators: %d: user ID */
											__( 'Invalid user ID: %d.', 'gregius-optimizer' ),
											$uid
										),
										array( 'status' => 400 )
									);
								}
							}
						}

						GG_Optimizer_DB::set( 'sitemap_settings', wp_json_encode( $settings ) );

						return rest_ensure_response( array( 'success' => true ) );
					},
				),
			)
		);
	}
);
