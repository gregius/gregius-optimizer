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
	 * The 18 registered ability names (namespace-prefixed).
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
	 * The `seo` category is registered and all 18 abilities are discoverable.
	 */
	public function test_all_18_abilities_registered_with_seo_category() {
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
}
