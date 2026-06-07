<?php
defined( 'ABSPATH' ) || exit;
/**
 * Asset enqueue logic for Gregius Optimizer.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

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
