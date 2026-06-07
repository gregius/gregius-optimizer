<?php
/**
 * Plugin Name:       Gregius Optimizer
 * Plugin URI:        https://gregius.com/gregius-optimizer
 * Description:       SEO, AEO, SMO, and LLMO editor extensions — schema, meta, indexing, and social cards.
 * Version:           1.0.0
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

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Database setup for settings table (runs early via plugins_loaded).
require_once __DIR__ . '/includes/class-gg-optimizer-db.php';

add_action( 'plugins_loaded', array( 'GG_Optimizer_DB', 'maybe_create_tables' ) );

// Activation hook — ensure table exists.
register_activation_hook(
	__FILE__,
	function () {
		GG_Optimizer_DB::maybe_create_tables();
	}
);

// Uninstall hook — clean up the settings table.
register_uninstall_hook( __FILE__, array( 'GG_Optimizer_DB', 'drop_tables' ) );

// Custom meta field helper.
if ( ! class_exists( 'GG_Optimizer_Custom_Meta_Field' ) ) {
	require_once __DIR__ . '/includes/class-custom-meta-field.php';
}

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
