<?php
/**
 * Abilities surface tests (source inspection).
 *
 * Guards the shape of the Abilities API surface without a live WordPress
 * environment: exactly the 18 planned abilities, no stale names, no measure,
 * delegation (not reimplementation), and reset semantics (clear, never inline).
 *
 * @package gregius-optimizer
 */

use PHPUnit\Framework\TestCase;

/**
 * Abilities surface tests.
 */
class Test_GG_Optimizer_Abilities_Surface extends TestCase {

	/**
	 * The 18 registered ability names (without the namespace prefix).
	 *
	 * @var string[]
	 */
	private $expected_abilities = array(
		'/get-robots-txt',
		'/get-llms-override',
		'/get-sitemap-config',
		'/get-schema-config',
		'/get-feature-toggles',
		'/preview-llms',
		'/preview-schema',
		'/preview-meta',
		'/update-robots-txt',
		'/update-llms-override',
		'/update-sitemap-config',
		'/update-schema-config',
		'/update-feature-toggles',
		'/reset-feature-toggles',
		'/reset-robots-txt',
		'/reset-llms-override',
		'/reset-sitemap-config',
		'/reset-schema-config',
	);

	/**
	 * Read the abilities manager source.
	 *
	 * @return string
	 */
	private function abilities_source() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read (plugin source), not a remote URL.
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-gg-optimizer-abilities.php' );

		$this->assertNotFalse( $src, 'Failed to read includes/class-gg-optimizer-abilities.php.' );

		return $src;
	}

	/**
	 * All 18 abilities are registered.
	 */
	public function test_all_18_abilities_registered() {
		$src = $this->abilities_source();

		foreach ( $this->expected_abilities as $ability ) {
			$this->assertStringContainsString( $ability, $src, "Ability {$ability} is missing from the registration." );
		}
	}

	/**
	 * No measure ability and no stale llms-txt names are registered.
	 */
	public function test_no_measure_and_no_stale_names() {
		$src = $this->abilities_source();

		$this->assertStringNotContainsString( '/measure', $src );
		$this->assertStringNotContainsString( '/get-llms-txt', $src );
		$this->assertStringNotContainsString( '/update-llms-txt', $src );
	}

	/**
	 * Reads, writes, and previews delegate to the REST routes; no reimplementation.
	 */
	public function test_delegation_not_reimplementation() {
		$src = $this->abilities_source();

		$this->assertStringContainsString( 'rest_do_request', $src );
		$this->assertStringNotContainsString( 'GG_Optimizer_DB::set(', $src );
	}

	/**
	 * Resets clear the stored key (six delete calls) and inline no default value.
	 */
	public function test_resets_clear_and_do_not_inline_defaults() {
		$src = $this->abilities_source();

		// Five reset abilities: feature-toggles, robots, llms, sitemap, schema
		// (schema clears two keys, hence six delete calls in total).
		$this->assertSame( 6, substr_count( $src, 'GG_Optimizer_DB::delete(' ) );

		$this->assertStringNotContainsString( 'gg_optimizer_get_default_robots_txt', $src );
		$this->assertStringNotContainsString( 'gg_optimizer_get_llms_context', $src );
		$this->assertStringNotContainsString( 'gg_optimizer_schema_get_default_subtype', $src );
	}

	/**
	 * Annotations: reads/previews readonly; writes/resets idempotent.
	 */
	public function test_annotations_present() {
		$src = $this->abilities_source();

		$this->assertStringContainsString( "'readonly'", $src );
		$this->assertStringContainsString( "'idempotent'", $src );
		$this->assertStringContainsString( "'show_in_rest' => true", $src );
		$this->assertStringContainsString( "'public' => true", $src );
	}
}
