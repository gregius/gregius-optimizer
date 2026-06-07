<?php
/**
 * Schema.org subtype settings UI — post-type defaults + per-post override.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'gg_optimizer_schema_get_type_map' ) ) {
	/**
	 * Full schema.org type map organised by content branch.
	 *
	 * @return array<int, array{key: string, label: string, subtypes: string[]}>
	 */
	function gg_optimizer_schema_get_type_map() {
		return array(
			array(
				'key'      => 'Article',
				'label'    => 'Article',
				'subtypes' => array(
					'Article', 'AdvertiserContentArticle', 'AnalysisNewsArticle',
					'APIReference', 'BackgroundNewsArticle', 'BlogPosting',
					'DiscussionForumPosting', 'LiveBlogPosting',
					'MedicalScholarlyArticle', 'NewsArticle', 'OpinionNewsArticle',
					'Report', 'ReportageNewsArticle', 'ReviewNewsArticle',
					'SatiricalArticle', 'ScholarlyArticle', 'SocialMediaPosting',
					'TechArticle',
				),
			),
			array(
				'key'      => 'WebPage',
				'label'    => 'WebPage',
				'subtypes' => array(
					'WebPage', 'AboutPage', 'CheckoutPage', 'CollectionPage',
					'ContactPage', 'FAQPage', 'ImageGallery', 'ItemPage',
					'MedicalWebPage', 'ProfilePage', 'SearchResultsPage',
					'VideoGallery',
				),
			),
			array(
				'key'      => 'CreativeWork',
				'label'    => 'CreativeWork',
				'subtypes' => array(
					'CreativeWork', 'Book', 'Clip', 'Code', 'Collection',
					'Comment', 'Conversation', 'Course', 'CreativeWorkSeason',
					'CreativeWorkSeries', 'Dataset', 'DigitalDocument', 'Drawing',
					'Episode', 'Guide', 'HowTo', 'ImageObject', 'LearningResource',
					'Map', 'MediaObject', 'Message', 'Movie', 'MusicAlbum',
					'MusicComposition', 'MusicPlaylist', 'MusicRecording',
					'Painting', 'Photograph', 'PresentationDigitalDocument',
					'PublicationIssue', 'PublicationVolume', 'Quiz', 'Recipe',
					'Sculpture', 'Season', 'Series', 'ShortStory',
					'SoftwareApplication', 'SoftwareSourceCode', 'Song',
					'TextDigitalDocument', 'TVClip', 'TVEpisode', 'TVSeason',
					'TVSeries', 'VideoGame', 'VideoGameClip', 'VideoGameSeries',
					'VideoObject', 'VisualArtwork', 'WebApplication', 'WebContent',
					'WebPageElement', 'WebSite',
				),
			),
			array(
				'key'      => 'Event',
				'label'    => 'Event',
				'subtypes' => array(
					'Event', 'BusinessEvent', 'ChildrensEvent', 'ComedyEvent',
					'DanceEvent', 'DeliveryEvent', 'EducationEvent',
					'ExhibitionEvent', 'Festival', 'FoodEvent', 'Hackathon',
					'LiteraryEvent', 'MusicEvent', 'PublicationEvent',
					'SaleEvent', 'ScreeningEvent', 'SocialEvent', 'SportsEvent',
					'TheaterEvent', 'VisualArtsEvent',
				),
			),
			array(
				'key'      => 'Organization',
				'label'    => 'Organization',
				'subtypes' => array(
					'Organization', 'Airline', 'Corporation',
					'EducationalOrganization', 'GovernmentOrganization',
					'LocalBusiness', 'MedicalOrganization', 'NGO',
					'NewsMediaOrganization', 'PerformingGroup',
					'SportsOrganization', 'SportsTeam',
				),
			),
			array(
				'key'   => 'Person',
				'label' => 'Person',
				'subtypes' => array( 'Person' ),
			),
			array(
				'key'      => 'Place',
				'label'    => 'Place',
				'subtypes' => array(
					'Place', 'Accommodation', 'AdministrativeArea', 'Airport',
					'Apartment', 'Aquarium', 'Beach', 'BodyOfWater', 'Campground',
					'Cemetery', 'City', 'CivicStructure', 'Country', 'Crematorium',
					'EventVenue', 'FireStation', 'GovernmentBuilding', 'House',
					'Landform', 'LandmarksOrHistoricalBuildings', 'Library',
					'Mountain', 'MovieTheater', 'Museum', 'MusicVenue', 'Park',
					'ParkingFacility', 'PerformingArtsTheater', 'PlaceOfWorship',
					'Playground', 'PoliceStation', 'PostOffice', 'Residence',
					'Room', 'School', 'StadiumOrArena', 'State', 'SubwayStation',
					'Suite', 'TaxiStand', 'TouristAttraction', 'TouristDestination',
					'TrainStation', 'Volcano', 'Zoo',
				),
			),
			array(
				'key'      => 'Product',
				'label'    => 'Product',
				'subtypes' => array(
					'Product', 'IndividualProduct', 'ProductModel', 'SomeProducts',
				),
			),
			array(
				'key'      => 'Review',
				'label'    => 'Review',
				'subtypes' => array(
					'Review', 'ClaimReview', 'CriticReview', 'EmployerReview',
					'MediaReview', 'Recommendation', 'UserReview', 'VendorReview',
				),
			),
		);
	}
}

if ( ! function_exists( 'gg_optimizer_schema_get_all_subtypes' ) ) {
	/**
	 * All known subtypes across every category (flat array).
	 *
	 * @return string[]
	 */
	function gg_optimizer_schema_get_all_subtypes() {
		$all = array();
		foreach ( gg_optimizer_schema_get_type_map() as $group ) {
			$all = array_merge( $all, $group['subtypes'] );
		}
		return $all;
	}
}

if ( ! function_exists( 'gg_optimizer_schema_get_subtype_parent' ) ) {
	/**
	 * Determine the parent category for a given subtype.
	 *
	 * @param string $subtype The schema.org subtype.
	 * @return string Parent key or empty string.
	 */
	function gg_optimizer_schema_get_subtype_parent( $subtype ) {
		foreach ( gg_optimizer_schema_get_type_map() as $group ) {
			if ( in_array( $subtype, $group['subtypes'], true ) ) {
				return $group['key'];
			}
		}
		return '';
	}
}

if ( ! function_exists( 'gg_optimizer_schema_get_default_subtype' ) ) {
	/**
	 * Hardcoded default subtype for a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	function gg_optimizer_schema_get_default_subtype( $post_type ) {
		$defaults = array(
			'post' => 'BlogPosting',
			'page' => 'WebPage',
		);
		return isset( $defaults[ $post_type ] ) ? $defaults[ $post_type ] : 'Article';
	}
}

if ( ! function_exists( 'gg_optimizer_schema_get_resolved_subtype' ) ) {
	/**
	 * Resolve the schema.org subtype for a post using the fallback chain.
	 *
	 * Priority: per-post meta → global default → hardcoded fallback.
	 *
	 * @param WP_Post $post Post object.
	 * @return string Resolved subtype or empty string.
	 */
	function gg_optimizer_schema_get_resolved_subtype( $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			return '';
		}

		$all = gg_optimizer_schema_get_all_subtypes();

		// 1. Per-post meta override.
		$meta = get_post_meta( $post->ID, '_gg_optimizer_schema_subtype', true );
		if ( '' !== $meta && in_array( $meta, $all, true ) ) {
			return $meta;
		}

		// 2. Global post-type default.
		$global_json = GG_Optimizer_DB::get( 'schema_post_type_defaults', '{}' );
		$global      = json_decode( $global_json, true );
		if ( is_array( $global ) && isset( $global[ $post->post_type ] ) ) {
			$val = $global[ $post->post_type ];
			if ( in_array( $val, $all, true ) ) {
				return $val;
			}
		}

		// 3. Hardcoded fallback.
		return gg_optimizer_schema_get_default_subtype( $post->post_type );
	}
}

/**
 * Override the schema.org @type using the resolved subtype chain.
 */
add_filter(
	'gg_optimizer_schema_article_type',
	function ( $type, $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			return $type;
		}
		$resolved = gg_optimizer_schema_get_resolved_subtype( $post );
		return '' !== $resolved ? $resolved : $type;
	},
	10,
	2
);

/**
 * REST endpoint: GET / POST schema global defaults.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gg-optimizer/v1',
			'/schema-global-settings',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'callback'            => function () {
						$global_json = GG_Optimizer_DB::get( 'schema_post_type_defaults', '{}' );
						$org_json    = GG_Optimizer_DB::get( 'schema_org_settings', '{}' );
						return rest_ensure_response(
							array(
								'post_type_defaults'  => json_decode( $global_json, true ),
								'schema_org_settings' => json_decode( $org_json, true ),
								'type_map'            => gg_optimizer_schema_get_type_map(),
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
						$defaults = $request->get_param( 'post_type_defaults' );
						if ( ! is_array( $defaults ) ) {
							return new WP_Error(
								'gg_optimizer_invalid_data',
								__( 'post_type_defaults must be an object.', 'gregius-optimizer' ),
								array( 'status' => 400 )
							);
						}

						$all = gg_optimizer_schema_get_all_subtypes();
						foreach ( $defaults as $post_type => $subtype ) {
							if ( ! in_array( $subtype, $all, true ) ) {
								return new WP_Error(
									'gg_optimizer_invalid_subtype',
									sprintf(
										/* translators: %s: invalid schema subtype */
										__( 'Invalid subtype: %s.', 'gregius-optimizer' ),
										esc_html( $subtype )
									),
									array( 'status' => 400 )
								);
							}
						}

						GG_Optimizer_DB::set( 'schema_post_type_defaults', wp_json_encode( $defaults ) );

						$org_settings = $request->get_param( 'schema_org_settings' );
						if ( is_array( $org_settings ) ) {
							if ( ! empty( $org_settings['org_type'] ) ) {
								$valid_org_types = array();
								foreach ( gg_optimizer_schema_get_type_map() as $group ) {
									if ( 'Organization' === $group['key'] ) {
										$valid_org_types = $group['subtypes'];
										break;
									}
								}
								if ( ! in_array( $org_settings['org_type'], $valid_org_types, true ) ) {
									return new WP_Error(
										'gg_optimizer_invalid_org_type',
										__( 'Invalid organization type.', 'gregius-optimizer' ),
										array( 'status' => 400 )
									);
								}
							}
							GG_Optimizer_DB::set( 'schema_org_settings', wp_json_encode( $org_settings ) );
						}

						return rest_ensure_response( array( 'success' => true ) );
					},
				),
			)
		);
	}
);

add_filter(
	'gg_optimizer_schema_organization_type',
	function ( $type ) {
		$settings_json = GG_Optimizer_DB::get( 'schema_org_settings', '{}' );
		$settings      = json_decode( $settings_json, true );
		if ( ! is_array( $settings ) || empty( $settings['org_type'] ) ) {
			return $type;
		}

		foreach ( gg_optimizer_schema_get_type_map() as $group ) {
			if ( 'Organization' === $group['key'] ) {
				if ( in_array( $settings['org_type'], $group['subtypes'], true ) ) {
					return $settings['org_type'];
				}
				break;
			}
		}

		return $type;
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'gg-optimizer/v1',
			'/schema-preview',
			array(
				'methods'             => 'GET',
				'permission_callback' => function ( $request ) {
					$post_id = (int) $request->get_param( 'post_id' );
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				'callback'            => function ( $request ) {
					$post_id = (int) $request->get_param( 'post_id' );
					$post    = get_post( $post_id );
					if ( ! $post ) {
						return new WP_Error(
							'gg_optimizer_post_not_found',
							__( 'Post not found.', 'gregius-optimizer' ),
							array( 'status' => 404 )
						);
					}
					return rest_ensure_response(
						gg_optimizer_schema_build_json_ld( $post )
					);
				},
			)
		);
	}
);
