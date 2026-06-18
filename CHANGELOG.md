# Changelog

All notable changes to Gregius Optimizer will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
