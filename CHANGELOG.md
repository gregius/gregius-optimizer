# Changelog

All notable changes to Gregius Optimizer will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
