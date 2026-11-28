<?php
/**
 * Database delete integration test (Mockery $wpdb).
 *
 * Covers the `delete()` method added for the reset-to-defaults abilities.
 *
 * @package gregius-optimizer
 */

use Brain\Monkey;

/**
 * DB delete tests.
 */
class Test_GG_Optimizer_DB_Delete extends PHPUnit\Framework\TestCase {

	use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	/**
	 * Mocked $wpdb.
	 *
	 * @var \Mockery\MockInterface
	 */
	private $wpdb_mock;

	/**
	 * Set up Brain\Monkey and the $wpdb mock.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		gregius_optimizer_test_stub_common_functions();
		require_once __DIR__ . '/../../includes/class-gg-optimizer-db.php';

		$this->wpdb_mock         = Mockery::mock( '\stdClass' );
		$this->wpdb_mock->prefix = 'wp_';

		global $wpdb;
		$wpdb = $this->wpdb_mock;
	}

	/**
	 * Tear down Brain\Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * delete() removes the row from the settings table with string placeholders.
	 */
	public function test_delete_calls_wpdb_delete() {
		$this->wpdb_mock->shouldReceive( 'delete' )
			->once()
			->with(
				'wp_gg_optimizer_settings',
				array(
					'setting_key' => 'robots_txt_content',
				),
				array( '%s' )
			)
			->andReturn( 1 );

		$this->assertSame( 1, GG_Optimizer_DB::delete( 'robots_txt_content' ) );
	}
}
