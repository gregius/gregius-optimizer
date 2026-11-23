<?php
/**
 * Data-retention REST controller integration tests.
 *
 * @package gregius-optimizer
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Data-retention REST controller tests.
 */
class Test_GG_Optimizer_Data_Retention extends PHPUnit\Framework\TestCase {

	/**
	 * Set up Brain\Monkey and load the controller + DB classes.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		require_once __DIR__ . '/../../includes/class-gg-optimizer-db.php';
		require_once __DIR__ . '/../../includes/class-gg-optimizer-data-retention.php';
	}

	/**
	 * Tear down Brain\Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * rest_update(true) persists a truthy option and echoes the flag back.
	 */
	public function test_rest_update_persists_true() {
		Functions\expect( 'update_option' )
			->once()
			->with( GG_Optimizer_DB::UNINSTALL_OPTION, true );

		Functions\expect( 'rest_ensure_response' )
			->once()
			->with( array( 'remove_data' => true ) )
			->andReturn( 'response-object' );

		$request = new WP_REST_Request( array( 'remove_data' => true ) );

		$this->assertSame( 'response-object', GG_Optimizer_Data_Retention::rest_update( $request ) );
	}

	/**
	 * rest_update(false) persists a falsy option.
	 */
	public function test_rest_update_persists_false() {
		Functions\expect( 'update_option' )
			->once()
			->with( GG_Optimizer_DB::UNINSTALL_OPTION, false );

		Functions\expect( 'rest_ensure_response' )
			->once()
			->with( array( 'remove_data' => false ) )
			->andReturn( 'response-object' );

		$request = new WP_REST_Request( array( 'remove_data' => false ) );

		$this->assertSame( 'response-object', GG_Optimizer_Data_Retention::rest_update( $request ) );
	}
}
