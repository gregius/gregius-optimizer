# Changelog

All notable changes to Gregius Optimizer will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-11-27

### Added
- WordPress Abilities API surface: 18 deterministic abilities (5 reads, 3
  previews, 5 writes, 5 resets) exposing every configurable feature to the
  agent harness. Reads, writes, and previews delegate to the existing
  `gg-optimizer/v1` REST routes; the five `reset-*` abilities clear the stored
  override so the plugin's own fallback resolves the default.
- `GG_Optimizer_DB::delete()` (re-added) to clear a stored setting on reset.

## [1.4.0] - 2026-11-22

### Added
- Opt-in data retention: a deactivation modal asks whether deleting the plugin
  should remove its data (kept by default). Deletion happens at uninstall, never
  on deactivation.
- Full GPL-2.0 license text, replacing the placeholder stub.
- GitHub Actions CI: phpcs + php -l lint, unit/integration tests, WordPress
  integration tests, and a single-site smoke test.

### Changed
- Uninstall now preserves the settings table, version option, and post meta by
  default; data is removed only when the retention preference is enabled.

### Improved
- llms.txt Key Documents query bounded to a configurable maximum (default 200)
  instead of fetching every matching post.

### Removed
- Unused internal DB methods (`drop_tables`, `get_group`, `delete`,
  `delete_group`).

## [1.3.0] - 2026-11-08

### Updated
- llms.txt and robots.txt serving aligned to one pattern: both short-circuit
  `pre_handle_404` to respond `200`, match the canonical URL exactly, and set
  `Cache-Control: no-store`.
- robots.txt on multisite subsites now mirrors core (subdir-aware `admin_url()`
  paths), and `blog_public = 0` returns `Disallow: /` consistently on single
  sites and subsites.

### Added
- llms.txt and robots.txt respect "Discourage search engines" (`blog_public = 0`).

### Removed
- Hardcoded vendor "## Core Architecture" / "## Key Specifications" sections
  from the auto-generated llms.txt context.

### Changed
- `<link rel="help">` replaced with `<link rel="describedby">` for llms.txt
  discovery (llmstxt.org v2).

### Improved
- Key Documents query narrowed with `meta_query EXISTS` to fetch only posts
  carrying the include toggle (performance).

## [1.2.1] - 2026-07-03

### Changed
- Documentation restructured as OKF v0.1 knowledge base with YAML frontmatter,
  cross-linked SRS/architecture/developer-documentation per subsystem, and
  progressive-disclosure index catalogs

### Fixed
- llms.txt markdown corrupted by HTML entity escaping — `>` blockquotes rendered as
  `&gt;`, breaking AI crawler consumption. Regression from v1.1.0 phpcs cleanup.

## [1.2.0] - 2026-06-23

### Added
- Sitemap URL selector: four independent checkboxes to control which sitemap URLs Gregius serves
- Support for `/sitemap_index.xml` (Yoast, Rank Math), `/sitemap.xml` (AIOSEO), `/sitemaps.xml` (SEOPress)
- Dynamic Sitemap lines in robots.txt and llms.txt reflecting current checkbox state
- Per-URL toggle: `/wp-sitemap.xml` can be disabled independently from other URLs

### Fixed
- Critical: feature toggle sanitizer caused 5-way mutual exclusion — saving one toggle zeroed the other four
- `supportsCustomFields` JS guard removed — per-post meta UI now renders on all public post types
- Stale Sitemap lines from saved custom robots.txt overrides now stripped before appending current URLs
- PHPCS: `parse_url()` replaced with `wp_parse_url()`; `$_SERVER['REQUEST_URI']` properly sanitized

### Changed
- Schema subtype count corrected from 176 to 174 in documentation
- Migration warning notice removed — replaced with concise helper text
- Sitemap lines moved out of robots.txt default content into dynamic output-time append

## [1.1.1] - 2026-06-18

### Fixed
- Schema validation warnings: `primaryImageOfPage` and `breadcrumb` moved to WebPage wrapper node
- Structural fix: content entity + WebPage pair connected via mainEntity/mainEntityOfPage instead of single conflated node

## [1.1.0] - 2026-06-17

### Added
- Feature toggles with on/off switch at top of each settings modal
- Robots.txt output on multisite subsites (template_redirect + wp_basename)
- Contextual HTML comments for each output section (meta, OG/Twitter, llms.txt, schema)

### Changed
- "Hide from search" toggle moved from Sitemap to Robots modal
- Google search snippet fields now used as fallbacks for meta title/description
- Feature toggle defaults: OFF on fresh install, ON for existing installs
- Modal gap standardized to 1.25rem, maxWidth to 600px

### Fixed
- Partial toggle save no longer destroys other feature states
- Schema output blocked at master dispatcher when toggle is OFF
- Google snippet title now properly overrides document title
- Fresh install defaults corrected from all-ON to all-OFF

## [1.0.0] - 2026-06-13

- Initial release
