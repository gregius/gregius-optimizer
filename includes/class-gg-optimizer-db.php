<?php
/**
 * Database setup and settings key-value CRUD for Gregius Optimizer.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Database setup and key-value CRUD for the gg_optimizer_settings table.
 */
class GG_Optimizer_DB {

	const TABLE_NAME        = 'gg_optimizer_settings';
	const DB_VERSION        = '1.1.0';
	const DB_VERSION_OPTION = 'gg_optimizer_db_version';
	const UNINSTALL_OPTION  = 'gg_optimizer_remove_data_on_uninstall';

	/**
	 * Create or upgrade the settings table if needed.
	 *
	 * @return void
	 */
	public static function maybe_create_tables() {
		$installed = get_option( self::DB_VERSION_OPTION, '0' );
		if ( version_compare( $installed, self::DB_VERSION, '>=' ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			setting_key   VARCHAR(100)    NOT NULL,
			setting_value LONGTEXT        NOT NULL,
			updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (setting_key)
		) ENGINE=InnoDB {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		if ( version_compare( $installed, '1.0.0', '>=' ) && '0' !== $installed ) {
			if ( ! self::get( 'feature_toggles' ) ) {
				self::set(
					'feature_toggles',
					wp_json_encode(
						array(
							'sitemap'      => true,
							'robots'       => true,
							'schema'       => true,
							'social_cards' => true,
							'llms'         => true,
						)
					)
				);
			}
		}
	}

	/**
	 * Remove (or preserve) plugin data on uninstall.
	 *
	 * Default keeps data. Only when the `gg_optimizer_remove_data_on_uninstall`
	 * option is enabled are the settings table, version option, and all
	 * `_gg_optimizer_*` post meta removed for the current site.
	 *
	 * @return void
	 */
	public static function uninstall() {
		$remove = (bool) get_option( self::UNINSTALL_OPTION, false );

		delete_option( self::UNINSTALL_OPTION );
		delete_transient( 'gg_optimizer_sitemap_flush' );

		if ( ! $remove ) {
			return;
		}

		global $wpdb;

		// Remove all plugin post meta for the current site (incl. revision rows).
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall-time batch cleanup; no cache invalidation needed (plugin removed).
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
				$wpdb->esc_like( '_gg_optimizer_' ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Drop the settings table.
		$table = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup.
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );

		delete_option( self::DB_VERSION_OPTION );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return string
	 */
	public static function get( $key, $fallback = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;
		$value = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT setting_value FROM %i WHERE setting_key = %s',
				$table,
				$key
			)
		);

		return ( null !== $value ) ? $value : $fallback;
	}

	/**
	 * Set (insert or replace) a single setting.
	 *
	 * @param string $key   Setting key.
	 * @param string $value Setting value.
	 * @return int|false
	 */
	public static function set( $key, $value ) {
		global $wpdb;

		return $wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . self::TABLE_NAME,
			array(
				'setting_key'   => $key,
				'setting_value' => (string) $value,
			),
			array( '%s', '%s' )
		);
	}

	/**
	 * Delete a single setting.
	 *
	 * Used by the reset-to-defaults abilities to clear a stored override so
	 * the feature's own fallback resolves the default. Deleting a key that is
	 * already absent is a no-op (returns 0, not false).
	 *
	 * @param string $key Setting key.
	 * @return int|false Number of rows deleted, or false on failure.
	 */
	public static function delete( $key ) {
		global $wpdb;

		return $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . self::TABLE_NAME,
			array(
				'setting_key' => $key,
			),
			array( '%s' )
		);
	}
}
