<?php
/**
 * Asset enqueue logic for Gregius Optimizer.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'enqueue_block_editor_assets',
	function () {
		$asset_path = plugin_dir_path( __FILE__ ) . 'build/editor.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset_file = require $asset_path;

		if ( ! is_array( $asset_file ) || ! isset( $asset_file['dependencies'], $asset_file['version'] ) ) {
			return;
		}

		wp_enqueue_script(
			'gg-optimizer-editor',
			plugin_dir_url( __FILE__ ) . 'build/editor.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			false
		);
	}
);

// Deactivation modal — enqueue only on the Plugins screen for users who can deactivate.
add_action(
	'admin_enqueue_scripts',
	static function ( $hook_suffix ) {
		if ( 'plugins.php' !== $hook_suffix || ! current_user_can( 'deactivate_plugins' ) ) {
			return;
		}

		$asset_path = plugin_dir_path( __FILE__ ) . 'build/deactivation-modal.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset_file = require $asset_path;

		if ( ! is_array( $asset_file ) || ! isset( $asset_file['dependencies'], $asset_file['version'] ) ) {
			return;
		}

		wp_enqueue_script(
			'gg-optimizer-deactivation',
			plugin_dir_url( __FILE__ ) . 'build/deactivation-modal.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_localize_script(
			'gg-optimizer-deactivation',
			'ggOptimizerDeactivation',
			array(
				'deactivateId' => 'deactivate-' . plugin_basename( dirname( __DIR__ ) . '/gregius-optimizer.php' ),
				'removeData'   => (bool) get_option( GG_Optimizer_DB::UNINSTALL_OPTION, false ),
			)
		);
	}
);
