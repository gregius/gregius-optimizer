<?php
/**
 * Abilities API integration tests (full WordPress).
 *
 * Exercises the registered ability surface end-to-end: discoverability,
 * annotations, delegation, and reset-to-defaults behavior.
 *
 * @package gregius-optimizer
 */

/**
 * Abilities API tests.
 */
class Test_GG_Optimizer_Abilities extends WP_UnitTestCase {

	/**
	 * The 21 registered ability names (namespace-prefixed).
	 *
	 * @var string[]
	 */
	private $expected_abilities = array(
		'gregius-optimizer/get-robots-txt',
		'gregius-optimizer/get-llms-override',
		'gregius-optimizer/get-sitemap-config',
		'gregius-optimizer/get-schema-config',
		'gregius-optimizer/get-feature-toggles',
		'gregius-optimizer/preview-llms',
		'gregius-optimizer/preview-schema',
		'gregius-optimizer/preview-meta',
		'gregius-optimizer/update-robots-txt',
		'gregius-optimizer/update-llms-override',
		'gregius-optimizer/update-sitemap-config',
		'gregius-optimizer/update-schema-config',
		'gregius-optimizer/update-feature-toggles',
		'gregius-optimizer/reset-feature-toggles',
		'gregius-optimizer/reset-robots-txt',
		'gregius-optimizer/reset-llms-override',
		'gregius-optimizer/reset-sitemap-config',
		'gregius-optimizer/reset-schema-config',
		'gregius-optimizer/get-post-seo',
		'gregius-optimizer/update-post-seo',
		'gregius-optimizer/reset-post-seo',
	);

	/**
	 * The read and preview abilities annotated readonly.
	 *
	 * @var string[]
	 */
	private $readonly_abilities = array(
		'gregius-optimizer/get-robots-txt',
		'gregius-optimizer/get-llms-override',
		'gregius-optimizer/get-sitemap-config',
		'gregius-optimizer/get-schema-config',
		'gregius-optimizer/get-feature-toggles',
		'gregius-optimizer/preview-llms',
		'gregius-optimizer/preview-schema',
		'gregius-optimizer/preview-meta',
		'gregius-optimizer/get-post-seo',
	);

	/**
	 * The write and reset abilities annotated idempotent.
	 *
	 * @var string[]
	 */
	private $idempotent_abilities = array(
		'gregius-optimizer/update-robots-txt',
		'gregius-optimizer/update-llms-override',
		'gregius-optimizer/update-sitemap-config',
		'gregius-optimizer/update-schema-config',
		'gregius-optimizer/update-feature-toggles',
		'gregius-optimizer/reset-feature-toggles',
		'gregius-optimizer/reset-robots-txt',
		'gregius-optimizer/reset-llms-override',
		'gregius-optimizer/reset-sitemap-config',
		'gregius-optimizer/reset-schema-config',
		'gregius-optimizer/update-post-seo',
		'gregius-optimizer/reset-post-seo',
	);

	/**
	 * Set up an administrator as the current user so `manage_options` gates pass.
	 */
	public function set_up() {
		parent::set_up();

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
	}

	/**
	 * The `seo` category is registered and all 21 abilities are discoverable.
	 */
	public function test_all_21_abilities_registered_with_seo_category() {
		$category = wp_get_ability_category( 'seo' );
		$this->assertNotNull( $category, 'The `seo` ability category should be registered.' );

		$abilities = wp_get_abilities();

		foreach ( $this->expected_abilities as $name ) {
			$this->assertArrayHasKey( $name, $abilities, "Ability {$name} should be registered." );
		}
	}

	/**
	 * Every ability carries category `seo`, show_in_rest, and MCP tool exposure.
	 */
	public function test_meta_and_annotations() {
		$abilities = wp_get_abilities();

		foreach ( $this->expected_abilities as $name ) {
			$ability = $abilities[ $name ];

			$this->assertSame( 'seo', $ability->get_category(), "{$name} category." );

			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'], "{$name} show_in_rest." );
			$this->assertTrue( $meta['mcp']['public'], "{$name} mcp.public." );
			$this->assertSame( 'tool', $meta['mcp']['type'], "{$name} mcp.type." );

			$annotations = $meta['annotations'];
			$this->assertFalse( $annotations['destructive'], "{$name} destructive." );

			if ( in_array( $name, $this->readonly_abilities, true ) ) {
				$this->assertTrue( $annotations['readonly'], "{$name} should be readonly." );
			} else {
				$this->assertFalse( $annotations['readonly'], "{$name} should not be readonly." );
				$this->assertTrue( $annotations['idempotent'], "{$name} should be idempotent." );
			}
		}
	}

	/**
	 * reset-robots-txt clears the override so the default is served.
	 */
	public function test_reset_robots_txt_restores_default() {
		GG_Optimizer_DB::set( 'robots_txt_content', "User-agent: *\nDisallow: /custom" );

		$reset = wp_get_ability( 'gregius-optimizer/reset-robots-txt' );
		$reset->execute();

		$read   = wp_get_ability( 'gregius-optimizer/get-robots-txt' );
		$result = $read->execute();

		$this->assertIsArray( $result );
		$this->assertFalse( $result['has_custom'] );
		$this->assertSame( gg_optimizer_get_default_robots_txt(), $result['content'] );
	}

	/**
	 * reset-llms-override clears the override so the effective context is served.
	 */
	public function test_reset_llms_override_restores_context() {
		GG_Optimizer_DB::set( 'llms_override', 'Custom override context' );

		$reset = wp_get_ability( 'gregius-optimizer/reset-llms-override' );
		$reset->execute();

		$read   = wp_get_ability( 'gregius-optimizer/get-llms-override' );
		$result = $read->execute();

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['llms_override'] );
		$this->assertSame( gg_optimizer_get_llms_context(), $result['llms_context'] );
	}

	/**
	 * reset-feature-toggles clears the stored toggles so all five read false.
	 */
	public function test_reset_feature_toggles_all_off() {
		GG_Optimizer_Feature_Toggle::set_all(
			array(
				'sitemap'      => true,
				'robots'       => true,
				'schema'       => true,
				'social_cards' => true,
				'llms'         => true,
			)
		);

		$reset = wp_get_ability( 'gregius-optimizer/reset-feature-toggles' );
		$reset->execute();

		$read   = wp_get_ability( 'gregius-optimizer/get-feature-toggles' );
		$result = $read->execute();

		$this->assertIsArray( $result );
		foreach ( array( 'sitemap', 'robots', 'schema', 'social_cards', 'llms' ) as $feature ) {
			$this->assertFalse( $result[ $feature ], "{$feature} should reset to off." );
		}
	}

	/**
	 * get-post-seo returns all 15 fields with defaults when nothing is stored.
	 */
	public function test_get_post_seo_returns_defaults() {
		$post_id = self::factory()->post->create();

		$ability = wp_get_ability( 'gregius-optimizer/get-post-seo' );
		$result  = $ability->execute( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );

		$string_fields = array(
			'meta_title',
			'meta_description',
			'meta_image',
			'google_title',
			'google_description',
			'og_title',
			'og_description',
			'og_image',
			'twitter_title',
			'twitter_description',
			'twitter_image',
			'schema_subtype',
			'llms_description',
		);

		foreach ( $string_fields as $field ) {
			$this->assertArrayHasKey( $field, $result );
			$this->assertSame( '', $result[ $field ], "{$field} should default to ''." );
		}

		$this->assertFalse( $result['include_in_llms'] );
		$this->assertFalse( $result['hide_from_search'] );
	}

	/**
	 * update-post-seo writes a partial set; omitted fields are left untouched.
	 */
	public function test_update_post_seo_partial_write() {
		$post_id = self::factory()->post->create();

		$update = wp_get_ability( 'gregius-optimizer/update-post-seo' );
		$update->execute(
			array(
				'post_id'    => $post_id,
				'meta_title' => 'Custom title',
			)
		);

		$get    = wp_get_ability( 'gregius-optimizer/get-post-seo' );
		$result = $get->execute( array( 'post_id' => $post_id ) );

		$this->assertSame( 'Custom title', $result['meta_title'] );
		$this->assertSame( '', $result['meta_description'] );
	}

	/**
	 * update-post-seo with a null field deletes the stored row (no inline default).
	 */
	public function test_update_post_seo_null_clears() {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, '_gg_optimizer_meta_title', 'Stored' );

		$update = wp_get_ability( 'gregius-optimizer/update-post-seo' );
		$update->execute(
			array(
				'post_id'    => $post_id,
				'meta_title' => null,
			)
		);

		$this->assertSame( '', get_post_meta( $post_id, '_gg_optimizer_meta_title', true ) );
	}

	/**
	 * update-post-seo with only post_id (no fields) is a no-op.
	 */
	public function test_update_post_seo_no_fields_is_noop() {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, '_gg_optimizer_meta_title', 'Stored' );

		$update = wp_get_ability( 'gregius-optimizer/update-post-seo' );
		$result = $update->execute( array( 'post_id' => $post_id ) );

		$this->assertSame( array( 'success' => true ), $result );
		$this->assertSame( 'Stored', get_post_meta( $post_id, '_gg_optimizer_meta_title', true ) );
	}

	/**
	 * reset-post-seo clears all 15 fields.
	 */
	public function test_reset_post_seo_clears_all() {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, '_gg_optimizer_meta_title', 'Title' );
		update_post_meta( $post_id, '_gg_optimizer_google_title', 'Google' );
		update_post_meta( $post_id, '_gg_optimizer_include_in_llms', true );
		update_post_meta( $post_id, '_gg_optimizer_hide_from_search', true );

		$reset = wp_get_ability( 'gregius-optimizer/reset-post-seo' );
		$reset->execute( array( 'post_id' => $post_id ) );

		$get    = wp_get_ability( 'gregius-optimizer/get-post-seo' );
		$result = $get->execute( array( 'post_id' => $post_id ) );

		$this->assertSame( '', $result['meta_title'] );
		$this->assertSame( '', $result['google_title'] );
		$this->assertFalse( $result['include_in_llms'] );
		$this->assertFalse( $result['hide_from_search'] );
	}

	/**
	 * Twin invocation leaves stored state unchanged (idempotency).
	 */
	public function test_post_seo_idempotent() {
		$post_id = self::factory()->post->create();

		$update = wp_get_ability( 'gregius-optimizer/update-post-seo' );
		$update->execute(
			array(
				'post_id'         => $post_id,
				'meta_title'      => 'Title',
				'include_in_llms' => true,
			)
		);
		$update->execute(
			array(
				'post_id'         => $post_id,
				'meta_title'      => 'Title',
				'include_in_llms' => true,
			)
		);

		$get    = wp_get_ability( 'gregius-optimizer/get-post-seo' );
		$result = $get->execute( array( 'post_id' => $post_id ) );

		$this->assertSame( 'Title', $result['meta_title'] );
		$this->assertTrue( $result['include_in_llms'] );

		$reset = wp_get_ability( 'gregius-optimizer/reset-post-seo' );
		$reset->execute( array( 'post_id' => $post_id ) );
		$reset->execute( array( 'post_id' => $post_id ) );

		$result = $get->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( '', $result['meta_title'] );
		$this->assertFalse( $result['include_in_llms'] );
	}

	/**
	 * String fields are tag-stripped; boolean fields are stored as booleans.
	 */
	public function test_post_seo_sanitization() {
		$post_id = self::factory()->post->create();

		$update = wp_get_ability( 'gregius-optimizer/update-post-seo' );
		$update->execute(
			array(
				'post_id'         => $post_id,
				'meta_title'      => '<b>Hello</b>',
				'include_in_llms' => true,
			)
		);

		$get    = wp_get_ability( 'gregius-optimizer/get-post-seo' );
		$result = $get->execute( array( 'post_id' => $post_id ) );

		$this->assertSame( 'Hello', $result['meta_title'] );
		$this->assertTrue( $result['include_in_llms'] );
	}

	/**
	 * get/update/reset return a WP_Error for a missing or non-existent post_id.
	 */
	public function test_post_seo_error_contract() {
		// Missing post_id.
		$error = GG_Optimizer_Abilities::execute_get_post_seo( array() );
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertSame( 'gg_optimizer_missing_post_id', $error->get_error_code() );

		$this->assertInstanceOf( WP_Error::class, GG_Optimizer_Abilities::execute_update_post_seo( array() ) );
		$this->assertInstanceOf( WP_Error::class, GG_Optimizer_Abilities::execute_reset_post_seo( array() ) );

		// Non-existent post.
		$error = GG_Optimizer_Abilities::execute_get_post_seo( array( 'post_id' => PHP_INT_MAX ) );
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertSame( 'gg_optimizer_post_not_found', $error->get_error_code() );

		$this->assertInstanceOf( WP_Error::class, GG_Optimizer_Abilities::execute_update_post_seo( array( 'post_id' => PHP_INT_MAX ) ) );
		$this->assertInstanceOf( WP_Error::class, GG_Optimizer_Abilities::execute_reset_post_seo( array( 'post_id' => PHP_INT_MAX ) ) );
	}

	/**
	 * The permission gate is post-scoped `edit_post` (anon denied, owner allowed,
	 * non-owner with edit_posts denied).
	 */
	public function test_post_seo_permission_roundtrip() {
		$author_a = self::factory()->user->create( array( 'role' => 'author' ) );
		$author_b = self::factory()->user->create( array( 'role' => 'author' ) );
		$post_a   = self::factory()->post->create( array( 'post_author' => $author_a ) );

		// Anonymous.
		wp_set_current_user( 0 );
		$this->assertFalse( GG_Optimizer_Abilities::can_edit_post( array( 'post_id' => $post_a ) ) );

		// Author A owns the post (edit_posts but not manage_options).
		wp_set_current_user( $author_a );
		$this->assertTrue( GG_Optimizer_Abilities::can_edit_post( array( 'post_id' => $post_a ) ) );

		// Author B has edit_posts but not edit_post on A's post.
		wp_set_current_user( $author_b );
		$this->assertFalse( GG_Optimizer_Abilities::can_edit_post( array( 'post_id' => $post_a ) ) );
	}

	/**
	 * can_edit_post normalizes object input (stdClass) the same as array input.
	 *
	 * Regression: can_edit_post previously read only is_array($input), so a
	 * stdClass input (as some MCP transports pass) fell through to post_id = 0
	 * and denied a legitimate editor — while the execute callbacks (which
	 * normalize via get_object_vars) would have succeeded.
	 */
	public function test_can_edit_post_normalizes_object_input() {
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		$post   = self::factory()->post->create( array( 'post_author' => $author ) );

		$object_input          = new stdClass();
		$object_input->post_id = $post;

		// Owner, stdClass input — must be allowed (parity with array input).
		wp_set_current_user( $author );
		$this->assertTrue( GG_Optimizer_Abilities::can_edit_post( $object_input ) );

		// Anonymous, stdClass input — must still be denied (fails closed).
		wp_set_current_user( 0 );
		$this->assertFalse( GG_Optimizer_Abilities::can_edit_post( $object_input ) );
	}
}
