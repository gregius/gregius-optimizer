<?php
/**
 * Uninstall handler for Gregius Optimizer.
 *
 * @package gregius-optimizer
 * @license GPL-2.0-or-later
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-gg-optimizer-db.php';

GG_Optimizer_DB::uninstall();
