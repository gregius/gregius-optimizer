<?php
/**
 * Database CRUD integration tests (Brain\Monkey + Mockery $wpdb).
 *
 * Exercises the key-value CRUD surface against a mocked $wpdb, so the
 * settings layer is covered without a live database.
 *
 * @package gregius-optimizer
 */

use Brain\Monkey;

/**
 * DB CRUD tests.
 */
class Test_GG_Optimizer_DB_CRUD extends PHPUnit\Framework\TestCase {

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
	 * get() returns the stored value from the DB.
	 */
	public function test_get_returns_stored_value() {
		$this->wpdb_mock->shouldReceive( 'prepare' )->andReturn( 'SELECT 1' );
		$this->wpdb_mock->shouldReceive( 'get_var' )->andReturn( 'stored' );

		$this->assertSame( 'stored', GG_Optimizer_DB::get( 'some_key', '' ) );
	}

	/**
	 * get() falls back to the default when the DB returns null.
	 */
	public function test_get_returns_default_when_null() {
		$this->wpdb_mock->shouldReceive( 'prepare' )->andReturn( 'SELECT 1' );
		$this->wpdb_mock->shouldReceive( 'get_var' )->andReturn( null );

		$this->assertSame( 'fallback', GG_Optimizer_DB::get( 'missing', 'fallback' ) );
	}

	/**
	 * set() replaces the row in the settings table with string placeholders.
	 */
	public function test_set_calls_replace() {
		$this->wpdb_mock->shouldReceive( 'replace' )
			->once()
			->with(
				'wp_gg_optimizer_settings',
				array(
					'setting_key'   => 'k',
					'setting_value' => 'v',
				),
				array( '%s', '%s' )
			)
			->andReturn( 1 );

		$this->assertSame( 1, GG_Optimizer_DB::set( 'k', 'v' ) );
	}
}
