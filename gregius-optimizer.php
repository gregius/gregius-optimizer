<?php
/**
 * Plugin Name:       Gregius Optimizer
 * Plugin URI:        https://gregius.com/gregius-optimizer
 * Description:       SEO, AEO, SMO, and LLMO editor extensions — schema, meta, indexing, and social cards.
 * Version:           1.4.0
 * Requires at least: 6.9
 * Requires PHP:      8.2
 * Author:            Hector Jarquin, Gregius
 * Author URI:        https://gregius.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gregius-optimizer
 * Domain Path:       /languages
 *
 * @package           gregius-optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Database setup for settings table (runs early via plugins_loaded).
require_once __DIR__ . '/includes/class-gg-optimizer-db.php';

add_action( 'plugins_loaded', array( 'GG_Optimizer_DB', 'maybe_create_tables' ) );

// Activation hook — ensure table exists.
register_activation_hook(
	__FILE__,
	function () {
		GG_Optimizer_DB::maybe_create_tables();
		flush_rewrite_rules( false );
	}
);

// Deactivation hook — flush rewrites (data is never removed on deactivation).
register_deactivation_hook(
	__FILE__,
	static function ( $network_wide ) {
		delete_transient( 'gg_optimizer_sitemap_flush' );

		if ( is_multisite() && $network_wide ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				update_option( 'rewrite_rules', '' );
				restore_current_blog();
			}
			return;
		}

		update_option( 'rewrite_rules', '' );
	}
);

// Custom meta field helper.
if ( ! class_exists( 'GG_Optimizer_Custom_Meta_Field' ) ) {
	require_once __DIR__ . '/includes/class-gg-optimizer-custom-meta-field.php';
}

// Feature toggle CRUD.
require_once __DIR__ . '/includes/class-gg-optimizer-feature-toggle.php';

// Data-retention preference (deactivation modal + uninstall handler).
require_once __DIR__ . '/includes/class-gg-optimizer-data-retention.php';

// Virtual root files (llms.txt, robots.txt).
require_once __DIR__ . '/includes/virtual-files.php';

// Search-facing metadata.
require_once __DIR__ . '/includes/search.php';

// Meta tags.
require_once __DIR__ . '/includes/meta-tags.php';

// Social card metadata.
require_once __DIR__ . '/includes/social-cards.php';

// Robots.
require_once __DIR__ . '/includes/robots.php';

// Sitemap.
require_once __DIR__ . '/includes/sitemap.php';

// Schema.
require_once __DIR__ . '/includes/schema.php';

// Schema settings (subtype mapping UI).
require_once __DIR__ . '/includes/schema-settings.php';

// LLMs context.
require_once __DIR__ . '/includes/llms.php';

// Assets.
require_once __DIR__ . '/assets/assets.php';

add_action( 'rest_api_init', array( 'GG_Optimizer_Feature_Toggle', 'register_routes' ) );

add_action( 'rest_api_init', array( 'GG_Optimizer_Data_Retention', 'register_routes' ) );
