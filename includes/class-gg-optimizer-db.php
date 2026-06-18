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
	 * Drop the settings table.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query(
			$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
		delete_option( self::DB_VERSION_OPTION );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return string
	 */
	public static function get( $key, $default = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;
		$value = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT setting_value FROM %i WHERE setting_key = %s',
				$table,
				$key
			)
		);

		return ( null !== $value ) ? $value : $default;
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
	 * Get all settings with a key prefix.
	 *
	 * @param string $prefix Key prefix.
	 * @return array
	 */
	public static function get_group( $prefix ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT setting_key, setting_value FROM %i WHERE setting_key LIKE %s',
				$table,
				$wpdb->esc_like( $prefix ) . '%'
			),
			OBJECT_K
		);

		$result = array();

		foreach ( $rows as $key => $row ) {
			$result[ $key ] = $row->setting_value;
		}

		return $result;
	}

	/**
	 * Delete a single setting.
	 *
	 * @param string $key Setting key.
	 * @return int|false
	 */
	public static function delete( $key ) {
		global $wpdb;

		return $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . self::TABLE_NAME,
			array( 'setting_key' => $key ),
			array( '%s' )
		);
	}

	/**
	 * Delete all settings with a key prefix.
	 *
	 * @param string $prefix Key prefix.
	 * @return int|false
	 */
	public static function delete_group( $prefix ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;
		return $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'DELETE FROM %i WHERE setting_key LIKE %s',
				$table,
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
	}
}
