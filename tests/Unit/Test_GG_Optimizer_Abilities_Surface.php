<?php
/**
 * Abilities surface tests (source inspection).
 *
 * Guards the shape of the Abilities API surface without a live WordPress
 * environment: exactly the 21 planned abilities (18 route-wrapping + 3
 * post-scoped SEO meta), no stale names, no measure, delegation (not
 * reimplementation), and reset semantics (clear, never inline).
 *
 * @package gregius-optimizer
 */

use PHPUnit\Framework\TestCase;

/**
 * Abilities surface tests.
 */
class Test_GG_Optimizer_Abilities_Surface extends TestCase {

	/**
	 * The 21 registered ability names (without the namespace prefix).
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
		'/get-post-seo',
		'/update-post-seo',
		'/reset-post-seo',
	);

	/**
	 * The 15 `_gg_optimizer_*` post-meta keys the post-seo abilities cover.
	 *
	 * @var string[]
	 */
	private $post_seo_meta_keys = array(
		'_gg_optimizer_meta_title',
		'_gg_optimizer_meta_description',
		'_gg_optimizer_meta_image',
		'_gg_optimizer_google_title',
		'_gg_optimizer_google_description',
		'_gg_optimizer_og_title',
		'_gg_optimizer_og_description',
		'_gg_optimizer_og_image',
		'_gg_optimizer_twitter_title',
		'_gg_optimizer_twitter_description',
		'_gg_optimizer_twitter_image',
		'_gg_optimizer_schema_subtype',
		'_gg_optimizer_include_in_llms',
		'_gg_optimizer_llms_description',
		'_gg_optimizer_hide_from_search',
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
	 * All 21 abilities are registered.
	 */
	public function test_all_21_abilities_registered() {
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

	/**
	 * The post-seo abilities read/write core post-meta directly (no delegation).
	 */
	public function test_post_seo_uses_core_post_meta_api() {
		$src = $this->abilities_source();

		$this->assertStringContainsString( 'execute_get_post_seo', $src );
		$this->assertStringContainsString( 'execute_update_post_seo', $src );
		$this->assertStringContainsString( 'execute_reset_post_seo', $src );

		$this->assertStringContainsString( 'get_post_meta(', $src );
		$this->assertStringContainsString( 'update_post_meta(', $src );
		$this->assertStringContainsString( 'delete_post_meta(', $src );

		// null = clear vs omitted = untouched must be distinguished by key presence.
		$this->assertStringContainsString( 'array_key_exists', $src );
	}

	/**
	 * All 15 `_gg_optimizer_*` post-meta keys are referenced.
	 */
	public function test_post_seo_meta_keys_covered() {
		$src = $this->abilities_source();

		foreach ( $this->post_seo_meta_keys as $meta_key ) {
			$this->assertStringContainsString( $meta_key, $src, "Meta key {$meta_key} is missing." );
		}
	}

	/**
	 * The post-seo abilities do not reimplement resolver/output logic.
	 */
	public function test_post_seo_does_not_reimplement_resolvers() {
		$src = $this->abilities_source();

		$this->assertStringNotContainsString( 'gg_optimizer_get_meta_title', $src );
		$this->assertStringNotContainsString( 'gg_optimizer_get_metadata_context', $src );
		$this->assertStringNotContainsString( 'gg_optimizer_rest_meta_preview', $src );
	}
}
