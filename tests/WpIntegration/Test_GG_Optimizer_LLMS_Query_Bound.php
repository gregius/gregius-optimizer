<?php
/**
 * llms.txt document-query bound tests (full WordPress).
 *
 * Exercises gg_optimizer_get_llms_key_documents() against a real WP_Query:
 * the bounded cap is respected and the continue guard filters toggled-off posts.
 *
 * @package gregius-optimizer
 */

/**
 * llms.txt document-query bound tests.
 */
class Test_GG_Optimizer_LLMS_Query_Bound extends WP_UnitTestCase {

	/**
	 * The Key Documents query is bounded by the gg_optimizer_llms_max_documents filter.
	 */
	public function test_key_documents_query_is_bounded() {
		for ( $i = 1; $i <= 5; $i++ ) {
			$post_id = self::factory()->post->create( array( 'post_title' => "Doc $i" ) );
			update_post_meta( $post_id, '_gg_optimizer_include_in_llms', '1' );
		}

		add_filter(
			'gg_optimizer_llms_max_documents',
			function () {
				return 2;
			}
		);

		$output = gg_optimizer_get_llms_key_documents();

		$this->assertStringContainsString( '## Key Documents', $output );

		$entries = preg_match_all( '/^- \[/m', $output, $matches );
		$this->assertSame( 2, $entries, 'Key Documents should be capped to 2 entries.' );
	}

	/**
	 * The continue guard excludes posts whose toggle is off despite the EXISTS query.
	 */
	public function test_continue_guard_excludes_toggled_off_posts() {
		$on = self::factory()->post->create( array( 'post_title' => 'Toggle On' ) );
		update_post_meta( $on, '_gg_optimizer_include_in_llms', '1' );

		$off = self::factory()->post->create( array( 'post_title' => 'Toggle Off' ) );
		update_post_meta( $off, '_gg_optimizer_include_in_llms', '0' );

		$output = gg_optimizer_get_llms_key_documents();

		$this->assertStringContainsString( 'Toggle On', $output );
		$this->assertStringNotContainsString( 'Toggle Off', $output );
	}
}
