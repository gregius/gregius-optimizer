<?php
/**
 * llms.txt document-query bound tests (source inspection).
 *
 * Guards that the Key Documents query is bounded rather than `posts_per_page = -1`,
 * and that the toggle filter (`continue`) is preserved.
 *
 * @package gregius-optimizer
 */

use PHPUnit\Framework\TestCase;

/**
 * llms.txt document-query bound tests.
 */
class Test_GG_Optimizer_LLMS_Bound extends TestCase {

	/**
	 * Read the llms.php source.
	 *
	 * @return string
	 */
	private function llms_source() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read (plugin source), not a remote URL.
		$src = file_get_contents( dirname( __DIR__, 2 ) . '/includes/llms.php' );

		$this->assertNotFalse( $src, 'Failed to read includes/llms.php.' );

		return $src;
	}

	/**
	 * The Key Documents query must be bounded via a filterable cap, not -1.
	 */
	public function test_posts_per_page_is_bounded() {
		$src = $this->llms_source();

		$this->assertStringNotContainsString( "'posts_per_page'      => -1", $src );
		$this->assertStringContainsString(
			"apply_filters( 'gg_optimizer_llms_max_documents', 200 )",
			$src
		);
	}

	/**
	 * The toggle filter (continue guard) must remain in the loop.
	 */
	public function test_continue_guard_is_present() {
		$src = $this->llms_source();

		$this->assertStringContainsString( '$include_in_llms', $src );
		$this->assertStringContainsString( 'continue;', $src );
	}
}
