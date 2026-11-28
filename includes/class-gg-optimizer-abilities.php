<?php
/**
 * Abilities API registrations for Gregius Optimizer.
 *
 * Registers the deterministic read / write / preview / reset surface as
 * WordPress Abilities (WP 6.9+). Judgment (audit / diagnose / suggest) and
 * aggregate reads remain the agent harness's concern — this plugin ships only
 * deterministic capabilities (see Cordenar decision #1632).
 *
 * Reads, writes, and previews delegate to the existing `gg-optimizer/v1` REST
 * routes so validation and sanitization live in one place. The `reset-*`
 * abilities are net-new surface that clear the stored override and let the
 * feature's own fallback resolve the default — they never inline a default.
 *
 * The per-post SEO abilities (`get-post-seo`, `update-post-seo`,
 * `reset-post-seo`) have no backing route: they read and write the plugin's
 * own `_gg_optimizer_*` post-meta keys directly via the core post-meta API,
 * gated by `edit_post` on the target post.
 *
 * `set-data-retention` is intentionally NOT registered (deferred): it is a
 * one-time uninstall preference, write-only, with no agent workflow. There is
 * also no `measure` ability — no server-side measurement source exists.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Gregius Optimizer ability surface on the Abilities API.
 */
class GG_Optimizer_Abilities {

	/**
	 * Ability namespace prefix (without the trailing slash).
	 *
	 * @var string
	 */
	const NAMESPACE = 'gregius-optimizer';

	/**
	 * Ability category slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'seo';

	/**
	 * Per-post SEO meta field map: ability field name => stored post-meta key.
	 *
	 * The 15 `_gg_optimizer_*` post-meta keys (13 string + 2 boolean) that the
	 * `get-post-seo` / `update-post-seo` / `reset-post-seo` abilities operate on
	 * directly via the core post-meta API. They are registered across
	 * includes/meta-tags.php (12), includes/llms.php (2), and
	 * includes/sitemap.php (1).
	 *
	 * @var array<string,string>
	 */
	const POST_SEO_META_KEYS = array(
		'meta_title'          => '_gg_optimizer_meta_title',
		'meta_description'    => '_gg_optimizer_meta_description',
		'meta_image'          => '_gg_optimizer_meta_image',
		'google_title'        => '_gg_optimizer_google_title',
		'google_description'  => '_gg_optimizer_google_description',
		'og_title'            => '_gg_optimizer_og_title',
		'og_description'      => '_gg_optimizer_og_description',
		'og_image'            => '_gg_optimizer_og_image',
		'twitter_title'       => '_gg_optimizer_twitter_title',
		'twitter_description' => '_gg_optimizer_twitter_description',
		'twitter_image'       => '_gg_optimizer_twitter_image',
		'schema_subtype'      => '_gg_optimizer_schema_subtype',
		'include_in_llms'     => '_gg_optimizer_include_in_llms',
		'llms_description'    => '_gg_optimizer_llms_description',
		'hide_from_search'    => '_gg_optimizer_hide_from_search',
	);

	/**
	 * The post-seo fields registered as boolean meta (the rest are string).
	 *
	 * @var string[]
	 */
	const POST_SEO_BOOLEAN_FIELDS = array(
		'include_in_llms',
		'hide_from_search',
	);

	/**
	 * Hook the registration callbacks.
	 *
	 * Categories must register on `wp_abilities_api_categories_init` and
	 * abilities on `wp_abilities_api_init`, in that order.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register the `seo` ability category.
	 *
	 * @return void
	 */
	public static function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'SEO', 'gregius-optimizer' ),
				'description' => __( 'Search, social, and AI (LLMs) optimization settings, previews, and resets.', 'gregius-optimizer' ),
			)
		);
	}

	/**
	 * Register the full ability surface.
	 *
	 * @return void
	 */
	public static function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Read abilities.
		self::register_read_abilities();

		// Preview abilities.
		self::register_preview_abilities();

		// Write abilities.
		self::register_write_abilities();

		// Reset abilities.
		self::register_reset_abilities();

		// Post-scoped SEO meta abilities (read / write / reset over core post-meta).
		self::register_post_seo_abilities();
	}

	/**
	 * Register the read abilities (readonly, delegated to GET routes).
	 *
	 * @return void
	 */
	private static function register_read_abilities() {
		$meta = self::meta_readonly();

		wp_register_ability(
			self::NAMESPACE . '/get-robots-txt',
			array(
				'label'               => __( 'Get robots.txt', 'gregius-optimizer' ),
				'description'         => __( 'Returns the current robots.txt content, whether it is a custom override, and its public URL.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_get_robots_txt' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/get-llms-override',
			array(
				'label'               => __( 'Get llms.txt override', 'gregius-optimizer' ),
				'description'         => __( 'Returns the stored llms.txt context override and the effective context (override or auto-generated).', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_get_llms_override' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/get-sitemap-config',
			array(
				'label'               => __( 'Get sitemap configuration', 'gregius-optimizer' ),
				'description'         => __( 'Returns the stored sitemap settings plus the available post types, taxonomies, users, and sitemap URLs.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_get_sitemap_config' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/get-schema-config',
			array(
				'label'               => __( 'Get schema configuration', 'gregius-optimizer' ),
				'description'         => __( 'Returns the schema.org post-type defaults, organization settings, and the full type map.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_get_schema_config' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/get-feature-toggles',
			array(
				'label'               => __( 'Get feature toggles', 'gregius-optimizer' ),
				'description'         => __( 'Returns the five feature toggles (sitemap, robots, schema, social_cards, llms) as booleans.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_get_feature_toggles' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'meta'                => $meta,
			)
		);
	}

	/**
	 * Register the preview abilities (readonly, non-persisting).
	 *
	 * @return void
	 */
	private static function register_preview_abilities() {
		$meta = self::meta_readonly();

		wp_register_ability(
			self::NAMESPACE . '/preview-llms',
			array(
				'label'               => __( 'Preview llms.txt', 'gregius-optimizer' ),
				'description'         => __( 'Renders the full llms.txt with an optional context override without saving any setting.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_preview_llms' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => (object) array(),
					'additionalProperties' => false,
					'properties'           => array(
						'llms_override'        => array(
							'type'        => 'string',
							'description' => 'Optional context override to render in place of the stored or auto-generated context.',
						),
						'unsaved_toggles'      => array(
							'type'                 => 'object',
							'description'          => 'Optional per-post include toggles (post_id => bool) applied to the preview without saving.',
							'additionalProperties' => true,
						),
						'unsaved_descriptions' => array(
							'type'                 => 'object',
							'description'          => 'Optional per-post descriptions (post_id => string) applied to the preview without saving.',
							'additionalProperties' => true,
						),
					),
				),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/preview-schema',
			array(
				'label'               => __( 'Preview schema JSON-LD', 'gregius-optimizer' ),
				'description'         => __( 'Returns the schema.org JSON-LD for a post without saving any setting.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_preview_schema' ),
				'permission_callback' => array( __CLASS__, 'can_edit_post' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => 'The post ID to preview.',
							'required'    => true,
						),
					),
				),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/preview-meta',
			array(
				'label'               => __( 'Preview social/meta tags', 'gregius-optimizer' ),
				'description'         => __( 'Returns the rendered social/meta preview (title, description, image, og:* and twitter:* tags) for a post without saving any setting.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_preview_meta' ),
				'permission_callback' => array( __CLASS__, 'can_edit_post' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'post_id'         => array(
							'type'        => 'integer',
							'description' => 'The post ID to preview.',
							'required'    => true,
						),
						'metaTitle'       => array(
							'type'        => 'string',
							'description' => 'Optional title override for the preview.',
						),
						'metaDescription' => array(
							'type'        => 'string',
							'description' => 'Optional meta description override for the preview.',
						),
						'metaImageId'     => array(
							'type'        => 'integer',
							'description' => 'Optional attachment ID for the meta image.',
						),
						'featuredMediaId' => array(
							'type'        => 'integer',
							'description' => 'Optional attachment ID for the featured image.',
						),
						'excerpt'         => array(
							'type'        => 'string',
							'description' => 'Optional excerpt override for the preview.',
						),
						'content'         => array(
							'type'        => 'string',
							'description' => 'Optional content override for the preview.',
						),
					),
				),
				'meta'                => $meta,
			)
		);
	}

	/**
	 * Register the write abilities (idempotent, delegated to POST routes).
	 *
	 * @return void
	 */
	private static function register_write_abilities() {
		$meta = self::meta_idempotent();

		wp_register_ability(
			self::NAMESPACE . '/update-robots-txt',
			array(
				'label'               => __( 'Update robots.txt', 'gregius-optimizer' ),
				'description'         => __( 'Sets the custom robots.txt content.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_update_robots_txt' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => (object) array(),
					'additionalProperties' => false,
					'properties'           => array(
						'content' => array(
							'type'        => 'string',
							'description' => 'The robots.txt content to store.',
						),
					),
				),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/update-llms-override',
			array(
				'label'               => __( 'Update llms.txt override', 'gregius-optimizer' ),
				'description'         => __( 'Sets the llms.txt context override (truncated at the "## Key Documents" line).', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_update_llms_override' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => (object) array(),
					'additionalProperties' => false,
					'properties'           => array(
						'llms_override' => array(
							'type'        => 'string',
							'description' => 'The llms.txt context override to store.',
						),
					),
				),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/update-sitemap-config',
			array(
				'label'               => __( 'Update sitemap configuration', 'gregius-optimizer' ),
				'description'         => __( 'Sets the sitemap settings (post types, taxonomies, excluded users, and sitemap URLs) after validation.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_update_sitemap_config' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => (object) array(),
					'additionalProperties' => false,
					'properties'           => array(
						'settings' => array(
							'type'                 => 'object',
							'description'          => 'The sitemap settings to store. Validated by the route.',
							'required'             => true,
							'additionalProperties' => true,
						),
					),
				),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/update-schema-config',
			array(
				'label'               => __( 'Update schema configuration', 'gregius-optimizer' ),
				'description'         => __( 'Sets the schema.org post-type defaults and, when provided, the organization settings.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_update_schema_config' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => (object) array(),
					'additionalProperties' => false,
					'properties'           => array(
						'post_type_defaults'  => array(
							'type'                 => 'object',
							'description'          => 'Map of post type slug => schema.org subtype. Validated by the route.',
							'required'             => true,
							'additionalProperties' => true,
						),
						'schema_org_settings' => array(
							'type'                 => 'object',
							'description'          => 'Optional organization settings (org_type). Validated by the route.',
							'additionalProperties' => true,
						),
					),
				),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/update-feature-toggles',
			array(
				'label'               => __( 'Update feature toggles', 'gregius-optimizer' ),
				'description'         => __( 'Sets the feature toggles. Unknown keys are dropped and values coerced to boolean.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_update_feature_toggles' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => (object) array(),
					'additionalProperties' => false,
					'properties'           => array(
						'toggles' => array(
							'type'                 => 'object',
							'description'          => 'Feature toggle map (sitemap, robots, schema, social_cards, llms).',
							'required'             => true,
							'additionalProperties' => true,
						),
					),
				),
				'meta'                => $meta,
			)
		);
	}

	/**
	 * Register the reset-to-defaults abilities (idempotent, net-new surface).
	 *
	 * Each reset clears the stored override so the feature's existing fallback
	 * resolves the default. None inlines a default value.
	 *
	 * @return void
	 */
	private static function register_reset_abilities() {
		$meta = self::meta_idempotent();

		wp_register_ability(
			self::NAMESPACE . '/reset-feature-toggles',
			array(
				'label'               => __( 'Reset feature toggles', 'gregius-optimizer' ),
				'description'         => __( 'Clears the stored toggles so they return to the out-of-box default (all off).', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_reset_feature_toggles' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/reset-robots-txt',
			array(
				'label'               => __( 'Reset robots.txt', 'gregius-optimizer' ),
				'description'         => __( 'Clears the custom robots.txt so the default directives are served.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_reset_robots_txt' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/reset-llms-override',
			array(
				'label'               => __( 'Reset llms.txt override', 'gregius-optimizer' ),
				'description'         => __( 'Clears the llms.txt context override so the auto-generated context is served.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_reset_llms_override' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/reset-sitemap-config',
			array(
				'label'               => __( 'Reset sitemap configuration', 'gregius-optimizer' ),
				'description'         => __( 'Clears the sitemap settings so the default sitemap state is served.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_reset_sitemap_config' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/reset-schema-config',
			array(
				'label'               => __( 'Reset schema configuration', 'gregius-optimizer' ),
				'description'         => __( 'Clears the schema post-type defaults and organization settings so the hardcoded subtype and default organization type are served.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_reset_schema_config' ),
				'permission_callback' => array( __CLASS__, 'can_manage_options' ),
				'meta'                => $meta,
			)
		);
	}

	/**
	 * Register the post-scoped SEO meta abilities (read / write / reset).
	 *
	 * Unlike the 18 route-wrapping abilities, these have no backing
	 * `gg-optimizer/v1` route: they read and write the plugin's own 15
	 * `_gg_optimizer_*` post-meta keys directly via the core post-meta API,
	 * gated by `edit_post` on the target post (see Cordenar decision #1710 F1).
	 *
	 * @return void
	 */
	private static function register_post_seo_abilities() {
		$readonly   = self::meta_readonly();
		$idempotent = self::meta_idempotent();

		wp_register_ability(
			self::NAMESPACE . '/get-post-seo',
			array(
				'label'               => __( 'Get post SEO overrides', 'gregius-optimizer' ),
				'description'         => __( 'Returns the stored (un-resolved) SEO overrides for a post across all 15 per-post meta fields.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_get_post_seo' ),
				'permission_callback' => array( __CLASS__, 'can_edit_post' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => 'The post ID whose stored SEO overrides to read.',
							'required'    => true,
						),
					),
				),
				'meta'                => $readonly,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/update-post-seo',
			array(
				'label'               => __( 'Update post SEO overrides', 'gregius-optimizer' ),
				'description'         => __( 'Writes a partial set of per-post SEO overrides. A null value clears a field; an omitted field is left untouched.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_update_post_seo' ),
				'permission_callback' => array( __CLASS__, 'can_edit_post' ),
				'input_schema'        => self::post_seo_update_schema(),
				'meta'                => $idempotent,
			)
		);

		wp_register_ability(
			self::NAMESPACE . '/reset-post-seo',
			array(
				'label'               => __( 'Reset post SEO overrides', 'gregius-optimizer' ),
				'description'         => __( 'Clears all 15 per-post SEO override fields so the resolver falls back to defaults.', 'gregius-optimizer' ),
				'category'            => self::CATEGORY,
				'execute_callback'    => array( __CLASS__, 'execute_reset_post_seo' ),
				'permission_callback' => array( __CLASS__, 'can_edit_post' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => 'The post ID whose stored SEO overrides to clear.',
							'required'    => true,
						),
					),
				),
				'meta'                => $idempotent,
			)
		);
	}

	/**
	 * Permission callback: `manage_options`.
	 *
	 * @return bool
	 */
	public static function can_manage_options() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Permission callback: `edit_posts`.
	 *
	 * @return bool
	 */
	public static function can_edit_posts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Permission callback: `edit_post` on the target post.
	 *
	 * Reads the normalized `post_id` key from the ability input.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_edit_post( $input = null ) {
		$post_id = 0;
		if ( is_array( $input ) && isset( $input['post_id'] ) ) {
			$post_id = (int) $input['post_id'];
		}

		return $post_id > 0 && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Read ability: get robots.txt.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return array|WP_Error
	 */
	public static function execute_get_robots_txt( $input = null ) {
		return self::delegate_to_route( 'GET', '/gg-optimizer/v1/robots-txt', $input );
	}

	/**
	 * Read ability: get llms.txt override.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return array|WP_Error
	 */
	public static function execute_get_llms_override( $input = null ) {
		return self::delegate_to_route( 'GET', '/gg-optimizer/v1/llms-override', $input );
	}

	/**
	 * Read ability: get sitemap configuration.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return array|WP_Error
	 */
	public static function execute_get_sitemap_config( $input = null ) {
		return self::delegate_to_route( 'GET', '/gg-optimizer/v1/sitemap-settings', $input );
	}

	/**
	 * Read ability: get schema configuration.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return array|WP_Error
	 */
	public static function execute_get_schema_config( $input = null ) {
		return self::delegate_to_route( 'GET', '/gg-optimizer/v1/schema-global-settings', $input );
	}

	/**
	 * Read ability: get feature toggles.
	 *
	 * @param mixed $input Ability input (unused).
	 * @return array|WP_Error
	 */
	public static function execute_get_feature_toggles( $input = null ) {
		return self::delegate_to_route( 'GET', '/gg-optimizer/v1/feature-toggles', $input );
	}

	/**
	 * Preview ability: llms.txt.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_preview_llms( $input = null ) {
		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/llms-preview', $input );
	}

	/**
	 * Preview ability: schema JSON-LD.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_preview_schema( $input = null ) {
		return self::delegate_to_route( 'GET', '/gg-optimizer/v1/schema-preview', $input );
	}

	/**
	 * Preview ability: social/meta tags.
	 *
	 * The backing route reads `postId`; the ability input is normalized to
	 * `post_id` (see F6), so the key is reconciled before delegation.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_preview_meta( $input = null ) {
		$input = is_array( $input ) ? $input : array();

		// F6: the /meta-preview route reads `postId`, not `post_id`.
		if ( array_key_exists( 'post_id', $input ) && ! array_key_exists( 'postId', $input ) ) {
			$input['postId'] = $input['post_id'];
			unset( $input['post_id'] );
		}

		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/meta-preview', $input );
	}

	/**
	 * Write ability: update robots.txt.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_update_robots_txt( $input = null ) {
		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/robots-txt', $input );
	}

	/**
	 * Write ability: update llms.txt override.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_update_llms_override( $input = null ) {
		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/llms-override', $input );
	}

	/**
	 * Write ability: update sitemap configuration.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_update_sitemap_config( $input = null ) {
		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/sitemap-settings', $input );
	}

	/**
	 * Write ability: update schema configuration.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_update_schema_config( $input = null ) {
		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/schema-global-settings', $input );
	}

	/**
	 * Write ability: update feature toggles.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_update_feature_toggles( $input = null ) {
		return self::delegate_to_route( 'POST', '/gg-optimizer/v1/feature-toggles', $input );
	}

	/**
	 * Reset ability: clear the stored feature toggles.
	 *
	 * @return array
	 */
	public static function execute_reset_feature_toggles() {
		GG_Optimizer_DB::delete( GG_Optimizer_Feature_Toggle::KEY );
		return array( 'success' => true );
	}

	/**
	 * Reset ability: clear the stored robots.txt override.
	 *
	 * @return array
	 */
	public static function execute_reset_robots_txt() {
		GG_Optimizer_DB::delete( 'robots_txt_content' );
		return array( 'success' => true );
	}

	/**
	 * Reset ability: clear the stored llms.txt override.
	 *
	 * @return array
	 */
	public static function execute_reset_llms_override() {
		GG_Optimizer_DB::delete( 'llms_override' );
		return array( 'success' => true );
	}

	/**
	 * Reset ability: clear the stored sitemap settings.
	 *
	 * @return array
	 */
	public static function execute_reset_sitemap_config() {
		GG_Optimizer_DB::delete( 'sitemap_settings' );
		return array( 'success' => true );
	}

	/**
	 * Reset ability: clear the stored schema defaults and organization settings.
	 *
	 * @return array
	 */
	public static function execute_reset_schema_config() {
		GG_Optimizer_DB::delete( 'schema_post_type_defaults' );
		GG_Optimizer_DB::delete( 'schema_org_settings' );
		return array( 'success' => true );
	}

	/**
	 * Read ability: get the stored (un-resolved) per-post SEO overrides.
	 *
	 * Returns all 15 fields: strings carry their stored value ('' when unset);
	 * the two boolean fields are returned as booleans (false when unset).
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_get_post_seo( $input = null ) {
		$post_id = self::extract_post_id( $input );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$result = array();

		foreach ( self::POST_SEO_META_KEYS as $field => $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );

			if ( in_array( $field, self::POST_SEO_BOOLEAN_FIELDS, true ) ) {
				$result[ $field ] = (bool) $value;
			} else {
				$result[ $field ] = (string) $value;
			}
		}

		return $result;
	}

	/**
	 * Write ability: update a partial set of per-post SEO overrides.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_update_post_seo( $input = null ) {
		$post_id = self::extract_post_id( $input );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( is_object( $input ) ) {
			$input = get_object_vars( $input );
		}

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		foreach ( self::POST_SEO_META_KEYS as $field => $meta_key ) {
			// Distinguish null (clear) from omitted (untouched) by key presence.
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}

			$value = $input[ $field ];

			if ( null === $value ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			if ( in_array( $field, self::POST_SEO_BOOLEAN_FIELDS, true ) ) {
				$value = (bool) $value;
			} else {
				$value = sanitize_text_field( $value );
			}

			update_post_meta( $post_id, $meta_key, $value );
		}

		return array( 'success' => true );
	}

	/**
	 * Reset ability: clear all 15 per-post SEO override fields.
	 *
	 * @param mixed $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_reset_post_seo( $input = null ) {
		$post_id = self::extract_post_id( $input );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		foreach ( self::POST_SEO_META_KEYS as $meta_key ) {
			delete_post_meta( $post_id, $meta_key );
		}

		return array( 'success' => true );
	}

	/**
	 * Resolve and validate the target post ID from ability input.
	 *
	 * Returns a WP_Error when `post_id` is missing, not a positive integer, or
	 * does not reference an existing post.
	 *
	 * @param mixed $input Ability input.
	 * @return int|WP_Error
	 */
	private static function extract_post_id( $input ) {
		if ( is_object( $input ) ) {
			$input = get_object_vars( $input );
		}

		if ( ! is_array( $input ) || ! isset( $input['post_id'] ) ) {
			return new WP_Error(
				'gg_optimizer_missing_post_id',
				__( 'A post_id is required.', 'gregius-optimizer' ),
				array( 'status' => 400 )
			);
		}

		$post_id = absint( $input['post_id'] );

		if ( $post_id <= 0 ) {
			return new WP_Error(
				'gg_optimizer_invalid_post_id',
				__( 'A valid post_id is required.', 'gregius-optimizer' ),
				array( 'status' => 400 )
			);
		}

		if ( ! get_post( $post_id ) ) {
			return new WP_Error(
				'gg_optimizer_post_not_found',
				__( 'Post not found.', 'gregius-optimizer' ),
				array( 'status' => 404 )
			);
		}

		return $post_id;
	}

	/**
	 * Build the input schema for the post-scoped SEO update ability.
	 *
	 * `post_id` is required; each of the 15 per-post fields is optional and
	 * nullable (null clears the stored row; an omitted field is untouched).
	 *
	 * @return array
	 */
	private static function post_seo_update_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'post_id'             => array(
					'type'        => 'integer',
					'description' => __( 'The post ID whose SEO overrides to update.', 'gregius-optimizer' ),
					'required'    => true,
				),
				'meta_title'          => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Social/search title override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'meta_description'    => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Social/search description override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'meta_image'          => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Social/search image override (attachment ID). Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'google_title'        => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Google search title override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'google_description'  => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Google search description override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'og_title'            => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Open Graph title override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'og_description'      => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Open Graph description override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'og_image'            => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Open Graph image override (attachment ID). Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'twitter_title'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Twitter title override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'twitter_description' => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Twitter description override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'twitter_image'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Twitter image override (attachment ID). Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'schema_subtype'      => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Schema.org subtype override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'include_in_llms'     => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => __( 'Whether this post is included in llms.txt. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'llms_description'    => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'llms.txt description override. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
				'hide_from_search'    => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => __( 'Whether this post is hidden from search engines. Pass null to clear the stored value.', 'gregius-optimizer' ),
				),
			),
		);
	}

	/**
	 * Delegate an ability to an existing `gg-optimizer/v1` REST route.
	 *
	 * Dispatches the route through `rest_do_request()` so the registered
	 * route's permission callback, argument validation, sanitization, and
	 * business logic all run — no duplicated logic in the ability.
	 *
	 * @param string     $method HTTP method (`GET` or `POST`).
	 * @param string     $route  Full REST route (namespace + path).
	 * @param array|null $input  Ability input; each key/value becomes a request param.
	 * @return array|WP_Error
	 */
	private static function delegate_to_route( $method, $route, $input ) {
		$request = new WP_REST_Request( $method, $route );

		if ( is_object( $input ) ) {
			$input = get_object_vars( $input );
		}

		if ( is_array( $input ) ) {
			foreach ( $input as $key => $value ) {
				$request->set_param( $key, $value );
			}
		}

		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $response instanceof WP_REST_Response && $response->is_error() ) {
			$data = $response->get_data();

			$code    = is_array( $data ) && isset( $data['code'] ) ? $data['code'] : 'rest_error';
			$message = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : '';

			$error_data = is_array( $data ) && isset( $data['data'] ) ? $data['data'] : array();
			if ( is_array( $error_data ) ) {
				$error_data['status'] = $response->get_status();
			}

			return new WP_Error( $code, $message, $error_data );
		}

		$data = $response instanceof WP_REST_Response ? $response->get_data() : $response;

		return is_array( $data ) ? $data : (array) $data;
	}

	/**
	 * Meta for readonly abilities (reads and previews).
	 *
	 * @return array
	 */
	private static function meta_readonly() {
		return array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
			'mcp'          => array(
				'public' => true,
				'type'   => 'tool',
			),
		);
	}

	/**
	 * Meta for idempotent abilities (writes and resets).
	 *
	 * @return array
	 */
	private static function meta_idempotent() {
		return array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => true,
			),
			'mcp'          => array(
				'public' => true,
				'type'   => 'tool',
			),
		);
	}
}
