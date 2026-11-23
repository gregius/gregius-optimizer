<?php
/**
 * Data-retention uninstall tests.
 *
 * Verifies the keep-by-default behavior: uninstall only removes data when the
 * `gg_optimizer_remove_data_on_uninstall` option is enabled.
 *
 * @package gregius-optimizer
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Data-retention uninstall tests.
 */
class Test_GG_Optimizer_Retention extends PHPUnit\Framework\TestCase {

	use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Mocked $wpdb.
	 *
	 * @var \Mockery\MockInterface
	 */
	private $wpdb_mock;

	/**
	 * Options deleted during the test (recording).
	 *
	 * @var array
	 */
	private $deleted_options = array();

	/**
	 * Set up Brain\Monkey and the $wpdb mock.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		require_once __DIR__ . '/../../includes/class-gg-optimizer-db.php';

		$this->wpdb_mock           = Mockery::mock( '\stdClass' );
		$this->wpdb_mock->prefix   = 'wp_';
		$this->wpdb_mock->postmeta = 'wp_postmeta';

		global $wpdb;
		$wpdb = $this->wpdb_mock;

		$this->deleted_options = array();
	}

	/**
	 * Tear down Brain\Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * The preference option name must be stable (read by the modal and REST).
	 */
	public function test_uninstall_option_constant_is_correct() {
		$this->assertSame( 'gg_optimizer_remove_data_on_uninstall', GG_Optimizer_DB::UNINSTALL_OPTION );
	}

	/**
	 * Default (option false) preserves data: no DB query is issued.
	 */
	public function test_uninstall_keeps_data_when_option_false() {
		Functions\when( 'get_option' )->justReturn( false );

		$this->stub_deletions();

		// The core guarantee: keep-by-default never touches the DB tables/meta.
		$this->wpdb_mock->shouldReceive( 'query' )->never();

		GG_Optimizer_DB::uninstall();

		$this->assertContains( GG_Optimizer_DB::UNINSTALL_OPTION, $this->deleted_options );
		$this->assertNotContains( GG_Optimizer_DB::DB_VERSION_OPTION, $this->deleted_options );
	}

	/**
	 * Option true removes data: postmeta DELETE + table DROP are issued.
	 */
	public function test_uninstall_removes_data_when_option_true() {
		Functions\when( 'get_option' )->justReturn( true );

		$this->stub_deletions();

		$this->wpdb_mock->shouldReceive( 'prepare' )->andReturn( 'SQL' );
		$this->wpdb_mock->shouldReceive( 'esc_like' )->andReturn( '\\_gg_optimizer_' );
		$this->wpdb_mock->shouldReceive( 'query' )->twice()->andReturn( 0 );

		GG_Optimizer_DB::uninstall();

		$this->assertContains( GG_Optimizer_DB::UNINSTALL_OPTION, $this->deleted_options );
		$this->assertContains( GG_Optimizer_DB::DB_VERSION_OPTION, $this->deleted_options );
	}

	/**
	 * Record delete_option / delete_transient calls.
	 */
	private function stub_deletions() {
		Functions\when( 'delete_option' )->alias(
			function ( $option ) {
				$this->deleted_options[] = $option;
				return true;
			}
		);

		Functions\when( 'delete_transient' )->justReturn( true );
	}
}
