---
okf_version: "0.1"
---
# Gregius Optimizer — Documentation

Plugin: Gregius Optimizer v1.2.1 — SEO, AEO, SMO, and LLMO editor extensions
(schema, meta, indexing, and social cards).

## Schema (parent subsystem)

- [schema/srs.md](schema/srs.md) — Requirements for Organization, WebSite, BreadcrumbList, and content JSON-LD schema
- [schema/architecture.md](schema/architecture.md) — Architecture views, ADRs, and design decisions for schema generation
- [schema/developer-documentation.md](schema/developer-documentation.md) — API reference for schema controllers, hooks, and output filters

### Schema sub-features

- [schema-faq/srs.md](schema-faq/srs.md) — Requirements for FAQ extraction from `core/accordion` blocks
- [schema-faq/architecture.md](schema-faq/architecture.md) — Architecture for FAQ block parsing and JSON-LD generation
- [schema-faq/developer-documentation.md](schema-faq/developer-documentation.md) — API reference for FAQ extraction hooks and filters
- [schema-logo/srs.md](schema-logo/srs.md) — Requirements for logo extraction from `core/site-logo` block
- [schema-logo/architecture.md](schema-logo/architecture.md) — Architecture for logo detection and resolution
- [schema-logo/developer-documentation.md](schema-logo/developer-documentation.md) — API reference for logo hooks and output filters
- [schema-sameas/srs.md](schema-sameas/srs.md) — Requirements for sameAs extraction from `core/social-links` block
- [schema-sameas/architecture.md](schema-sameas/architecture.md) — Architecture for sameAs link parsing and JSON-LD output
- [schema-sameas/developer-documentation.md](schema-sameas/developer-documentation.md) — API reference for sameAs extraction hooks and filters

## Standalone subsystems

- [sitemap/srs.md](sitemap/srs.md) — Requirements for XML sitemap generation, post type toggles, and exclusion controls
- [sitemap/architecture.md](sitemap/architecture.md) — Architecture for sitemap rendering, caching, and REST endpoints
- [sitemap/developer-documentation.md](sitemap/developer-documentation.md) — API reference for sitemap controllers and filters
- [robots/srs.md](robots/srs.md) — Requirements for robots.txt editing, preview, and per-page meta robots
- [robots/architecture.md](robots/architecture.md) — Architecture for robots.txt storage, rendering, and validation
- [robots/developer-documentation.md](robots/developer-documentation.md) — API reference for robots controllers and hooks
- [social-cards/srs.md](social-cards/srs.md) — Requirements for Google, OG, and Twitter social card metadata
- [social-cards/architecture.md](social-cards/architecture.md) — Architecture for social card generation and preview rendering
- [social-cards/developer-documentation.md](social-cards/developer-documentation.md) — API reference for social card controllers and filters
- [llms/srs.md](llms/srs.md) — Requirements for auto-generated llms.txt with per-post toggles
- [llms/architecture.md](llms/architecture.md) — Architecture for llms.txt generation, caching, and REST endpoints
- [llms/developer-documentation.md](llms/developer-documentation.md) — API reference for llms.txt controllers and hooks

## Convention

OKF v0.1 (Google Cloud, 2026-06-12). Each document carries YAML frontmatter with `type`,
`title`, `description`, `subsystem`, `standard`, `tags`, and `timestamp`. Type vocabulary:
`Specification` (ISO 29148 SRS), `Architecture` (ISO 42010), `DeveloperReference` (ISO 26514).

## Related

- [Plugin README](../README.md)
- [Source code](../../includes/)
