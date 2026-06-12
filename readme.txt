=== Gregius Optimizer ===
Contributors: hectorjarquin, gregiusteam
Tags: seo, aeo, smo, llmo, optimization
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

SEO, AEO, SMO, and LLMO editor extensions — schema, meta, indexing, social cards, and llms.txt — all from Gutenberg sidebar panels.

== Description ==

Gregius Optimizer gives content editors and site administrators a unified control panel for search, social, and AI metadata — without leaving the block editor.

= Panels =

* **Sitemap** — Toggle post types, taxonomies, and authors in your XML sitemap. Exclude individual posts from search engines with a single click.
* **Robots.txt** — Edit your robots.txt file from a modal textarea with dynamic row sizing. Reset to WordPress defaults at any time.
* **Schema** — Full schema.org type map with 176 subtypes across 9 categories (Article, WebPage, CreativeWork, Event, Organization, Person, Place, Product, Review). Assign global defaults per post type, override per post, and preview JSON-LD with clipboard copy. Organization JSON-LD includes sameAs and logo.
* **Social Cards** — Per-platform title, description, and image overrides for Google Search Snippets, Open Graph (Facebook, LinkedIn), and Twitter/X Cards. Inline live previews with character counters (Google 60/160, OG 55/65, Twitter 70/200). Global fallback image with kebab menu.
* **LLMs** — Auto-generate `/llms.txt` from site content for AI agent discoverability. Custom context editing with live preview. Per-post include toggle with custom descriptions.

All panels are accessible from `PluginDocumentSettingPanel` in the Gutenberg sidebar, listed in order: Sitemap → Robots → Schema → Social Cards → LLMs.

= Key Features =

* Meta description and canonical URL output via `wp_head`
* `pre_get_document_title` filter for Google-specific title override
* Open Graph (`og:*`) and Twitter Card meta tags with per-platform fallback chains
* `gg_optimizer_og` image size (1200×630, cropped) registered on `after_setup_theme`
* JSON-LD structured data: Organization (configurable subtype), WebSite, BreadcrumbList, article/page (176 subtypes)
* `@graph` wrapper for multiple JSON-LD nodes
* Custom DB table (`gg_optimizer_settings`) shared across all features
* REST endpoints for all settings: sitemap, robots, schema, social cards preview, llms override and preview
* Filter-based architecture — every output group can be disabled via filters
* All meta fields support revisions

== Installation ==

1. Upload the `gregius-optimizer` folder to `/wp-content/plugins/`
2. Activate through the Plugins screen in WordPress
3. Open any post or page in the block editor — the optimizer panels appear in the right sidebar

== Frequently Asked Questions ==

= Does this plugin replace Yoast SEO or Rank Math? =

Gregius Optimizer focuses on the Gutenberg-native editing experience for metadata that those plugins often bury in separate metaboxes. It can complement or replace them depending on your workflow.

= Which post types are supported? =

All public post types that support `custom-fields`. Meta fields, schema subtype, and LLMs toggle register on every public post type automatically.

= Can I disable specific output groups? =

Yes. Each output group (meta description, canonical, Open Graph, Twitter Cards, LLMs) has a corresponding `gg_optimizer_meta_output_*` or `gg_optimizer_llms_enabled` filter.

= Does the plugin add a database table? =

Yes. Settings for sitemap, robots.txt, schema defaults, and LLMs context are stored in `{$prefix}gg_optimizer_settings`. The table is created on activation and removed on uninstall.

= Is there a translation file? =

The text domain is `gregius-optimizer`. A `.pot` file is not bundled; translations are handled via the standard WordPress translation system.

== Contributors & Developers ==

Gregius Optimizer is open source software. The following people have contributed to this plugin.

* **Hector Jarquin** — Lead developer and maintainer
* **Gregius** — Product owner and sponsor

Visit the contributor profiles on WordPress.org:
* https://profiles.wordpress.org/hectorjarquin/
* https://profiles.wordpress.org/gregiusteam/

== Changelog ==

= 1.0.0 =
* Initial release
* Sitemap panel with per-post-type/taxonomy/author toggles and noindex per-post
* Robots.txt modal editor with reset to defaults
* Schema panel with 176 subtypes across 9 categories, Organization type, per-post override, JSON-LD preview
* Social Cards panel with Google Search Snippet, Open Graph, Twitter/X Cards, Global Image, character counters
* LLMs panel with auto-generated llms.txt, custom context, per-post include toggle, live preview
* Meta description and canonical URL output
* Open Graph and Twitter Card meta tags with per-platform fallback chains
* JSON-LD structured data: Organization, WebSite, BreadcrumbList, article/page
* 11 custom meta fields with revision support
 * REST API endpoints for all settings and previews

== Repository ==

Source code and build instructions:
https://github.com/gregius/gregius-optimizer

This plugin uses npm and @wordpress/scripts for asset compilation. Source JavaScript lives in `assets/src/` and compiles to `assets/build/`. All PHP source is human-readable.

== External Services ==

Gregius Optimizer does not send data to any external service. The following URLs are referenced as documentation or standards only:

* **Google Robots** — https://developers.google.com/search/docs/crawling-indexing/robots/intro — robots.txt and meta robots documentation
* **Schema.org** — https://schema.org/ — structured data vocabulary reference
* **Google Structured Data Gallery** — https://developers.google.com/search/docs/appearance/structured-data/search-gallery — structured data feature reference
* **Open Graph Protocol** — https://ogp.me/ — Open Graph meta tag specification
* **Twitter/X Cards** — https://docs.x.com/overview — Twitter Card meta tag documentation
* **llms.txt** — https://llmstxt.org/ — llms.txt proposal specification
