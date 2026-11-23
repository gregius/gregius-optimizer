<?php
/**
 * Database class constant tests.
 *
 * Guards the settings-table name, schema version, and version option names
 * that the rest of the plugin (and the uninstall path) depend on.
 *
 * @package gregius-optimizer
 */

use PHPUnit\Framework\TestCase;

/**
 * DB constant tests.
 */
class Test_GG_Optimizer_DB_Constants extends TestCase {

	/**
	 * Load the DB class before each test (defines GG_Optimizer_DB only).
	 */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../../includes/class-gg-optimizer-db.php';
	}

	/**
	 * The settings table name must match the schema the rest of the plugin queries.
	 */
	public function test_table_name_constant() {
		$this->assertSame( 'gg_optimizer_settings', GG_Optimizer_DB::TABLE_NAME );
	}

	/**
	 * The schema version constant must remain a valid dotted version.
	 */
	public function test_db_version_constant() {
		$this->assertSame( '1.1.0', GG_Optimizer_DB::DB_VERSION );
	}

	/**
	 * The version option name must match the one checked/updated on install.
	 */
	public function test_db_version_option_constant() {
		$this->assertSame( 'gg_optimizer_db_version', GG_Optimizer_DB::DB_VERSION_OPTION );
	}
}
